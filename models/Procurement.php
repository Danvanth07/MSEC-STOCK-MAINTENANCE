<?php
/**
 * MSEC SMART STOCK - Procurement & Vendor Management Model
 * Locked Scope: Section 6.1 (Stock Inward), 16 (Procurement Integration), 17 (Vendor Management)
 */

require_once dirname(__DIR__) . '/core/Database.php';

class Procurement {

    public static function getVendors(): array {
        $pdo = Database::getInstance();
        return $pdo->query("SELECT * FROM vendors ORDER BY performance_rating DESC, company_name ASC")->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getPurchaseOrders(): array {
        $pdo = Database::getInstance();
        $stmt = $pdo->query("SELECT po.*, v.company_name, v.contact_person, v.mobile_number, u.full_name as created_by_name, sr.request_code
                             FROM purchase_orders po
                             JOIN vendors v ON po.vendor_id = v.id
                             LEFT JOIN users u ON po.created_by = u.id
                             LEFT JOIN stock_requests sr ON po.indent_id = sr.id
                             ORDER BY po.id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getPOById(int $id): ?array {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT po.*, v.company_name, v.gst_number, v.pan_number, v.contact_person, v.mobile_number, v.email as vendor_email, v.address as vendor_address, u.full_name as created_by_name, sr.request_code, sr.purpose, d.name as dept_name
                              FROM purchase_orders po
                              JOIN vendors v ON po.vendor_id = v.id
                              LEFT JOIN users u ON po.created_by = u.id
                              LEFT JOIN stock_requests sr ON po.indent_id = sr.id
                              LEFT JOIN departments d ON sr.dept_id = d.id
                              WHERE po.id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function getQuotations(?int $requestId = null): array {
        $pdo = Database::getInstance();
        $sql = "SELECT q.*, v.company_name, v.gst_number, v.mobile_number, v.performance_rating, sr.request_code, sr.purpose
                FROM quotations q
                JOIN vendors v ON q.vendor_id = v.id
                LEFT JOIN stock_requests sr ON q.request_id = sr.id";
        if ($requestId !== null) {
            $sql .= " WHERE q.request_id = " . (int)$requestId;
        }
        $sql .= " ORDER BY q.is_selected DESC, q.total_amount ASC";
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function createPO(array $data, int $userId): ?int {
        $pdo = Database::getInstance();
        try {
            $year = date('Y');
            $count = (int)$pdo->query("SELECT COUNT(*) FROM purchase_orders")->fetchColumn() + 1;
            $poNum = "PO-MSEC-{$year}-" . str_pad((string)$count, 4, '0', STR_PAD_LEFT);

            $stmt = $pdo->prepare("
                INSERT INTO purchase_orders 
                (po_number, indent_id, vendor_id, total_amount, tax_amount, terms, status, created_by)
                VALUES (?, ?, ?, ?, ?, ?, 'Issued', ?)
            ");
            $stmt->execute([
                $poNum,
                !empty($data['indent_id']) ? (int)$data['indent_id'] : null,
                (int)$data['vendor_id'],
                (float)$data['total_amount'],
                (float)($data['tax_amount'] ?? 0),
                $data['terms'] ?? '1. Delivery within 10 days of PO receipt.\n2. 100% payment upon physical verification and GRN generation.\n3. Standard 1-year manufacturer on-site replacement warranty.',
                $userId
            ]);
            return (int)$pdo->lastInsertId();
        } catch (Exception $e) {
            return null;
        }
    }

    public static function addQuotation(array $data): ?int {
        $pdo = Database::getInstance();
        try {
            $stmt = $pdo->prepare("
                INSERT INTO quotations 
                (request_id, vendor_id, quote_reference, quote_date, total_amount, tax_amount, delivery_period, warranty_terms, is_selected, remarks)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                !empty($data['request_id']) ? (int)$data['request_id'] : null,
                (int)$data['vendor_id'],
                $data['quote_reference'],
                $data['quote_date'] ?? date('Y-m-d'),
                (float)$data['total_amount'],
                (float)($data['tax_amount'] ?? 0),
                $data['delivery_period'] ?? '7 Days',
                $data['warranty_terms'] ?? '1 Year Standard',
                !empty($data['is_selected']) ? 1 : 0,
                $data['remarks'] ?? ''
            ]);
            return (int)$pdo->lastInsertId();
        } catch (Exception $e) {
            return null;
        }
    }

    public static function createVendor(array $data): ?int {
        $pdo = Database::getInstance();
        try {
            $count = (int)$pdo->query("SELECT COUNT(*) FROM vendors")->fetchColumn() + 1;
            $vendorCode = "VND-" . str_pad((string)$count, 3, '0', STR_PAD_LEFT);

            $stmt = $pdo->prepare("
                INSERT INTO vendors 
                (vendor_code, company_name, gst_number, pan_number, contact_person, mobile_number, email, address, product_categories, performance_rating, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active')
            ");
            $stmt->execute([
                $vendorCode,
                $data['company_name'],
                $data['gst_number'] ?? '',
                $data['pan_number'] ?? '',
                $data['contact_person'] ?? '',
                $data['mobile_number'] ?? '',
                $data['email'] ?? '',
                $data['address'] ?? '',
                $data['product_categories'] ?? 'General Supplies',
                (float)($data['performance_rating'] ?? 4.5)
            ]);
            return (int)$pdo->lastInsertId();
        } catch (Exception $e) {
            return null;
        }
    }

    public static function getGoodsReceipts(): array {
        $pdo = Database::getInstance();
        $stmt = $pdo->query("SELECT grn.*, v.company_name, st.name as store_name, u.full_name as verified_by_name
                             FROM goods_receipts grn
                             JOIN vendors v ON grn.vendor_id = v.id
                             JOIN stores st ON grn.store_id = st.id
                             JOIN users u ON grn.verified_by = u.id
                             ORDER BY grn.id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function createGRN(array $data, array $items): ?int {
        $pdo = Database::getInstance();
        $pdo->beginTransaction();

        try {
            $year = date('Y');
            $countStmt = $pdo->query("SELECT COUNT(*) FROM goods_receipts");
            $nextNum = str_pad((int)$countStmt->fetchColumn() + 1, 5, '0', STR_PAD_LEFT);
            $grnNumber = "GRN-{$year}-{$nextNum}";

            $stmt = $pdo->prepare("INSERT INTO goods_receipts (grn_number, po_id, vendor_id, store_id, received_date, verified_by, invoice_no, invoice_date, remarks) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $grnNumber,
                $data['po_id'] ?? null,
                $data['vendor_id'],
                $data['store_id'],
                $data['received_date'] ?? date('Y-m-d'),
                $data['verified_by'],
                $data['invoice_no'] ?? null,
                $data['invoice_date'] ?? null,
                $data['remarks'] ?? 'Stock Received & Quality Verified'
            ]);
            $grnId = (int)$pdo->lastInsertId();

            $itemStmt = $pdo->prepare("INSERT INTO goods_receipt_items (grn_id, item_id, ordered_qty, received_qty, accepted_qty, rejected_qty, unit_price) 
                                       VALUES (?, ?, ?, ?, ?, ?, ?)");

            foreach ($items as $item) {
                $acceptedQty = (float)$item['accepted_qty'];
                $itemId = (int)$item['item_id'];
                $unitPrice = (float)$item['unit_price'];

                $itemStmt->execute([
                    $grnId,
                    $itemId,
                    $item['ordered_qty'] ?? $acceptedQty,
                    $item['received_qty'] ?? $acceptedQty,
                    $acceptedQty,
                    $item['rejected_qty'] ?? 0,
                    $unitPrice
                ]);

                // Update Central Store Inventory
                $chk = $pdo->prepare("SELECT id FROM stock WHERE store_id = ? AND item_id = ?");
                $chk->execute([$data['store_id'], $itemId]);
                if ($chk->fetch()) {
                    $pdo->prepare("UPDATE stock SET available_quantity = available_quantity + ?, last_movement_date = date('now') WHERE store_id = ? AND item_id = ?")
                        ->execute([$acceptedQty, $data['store_id'], $itemId]);
                } else {
                    $pdo->prepare("INSERT INTO stock (store_id, item_id, available_quantity, store_location, last_movement_date) VALUES (?, ?, ?, 'Central Receiving', date('now'))")
                        ->execute([$data['store_id'], $itemId, $acceptedQty]);
                }

                // Ledger Entry
                $pdo->prepare("INSERT INTO stock_transactions (transaction_type, item_id, store_id, quantity, unit_cost, reference_id, reference_type, notes, created_by) 
                               VALUES ('INWARD', ?, ?, ?, ?, ?, 'GRN Receipt', 'Purchased stock inward', ?)")
                    ->execute([$itemId, $data['store_id'], $acceptedQty, $unitPrice, $grnId, $data['verified_by']]);
            }

            // Update associated PO status if exists
            if (!empty($data['po_id'])) {
                $pdo->prepare("UPDATE purchase_orders SET status = 'Fulfilled' WHERE id = ?")->execute([$data['po_id']]);
            }

            $pdo->commit();
            return $grnId;
        } catch (Exception $e) {
            $pdo->rollBack();
            return null;
        }
    }
}

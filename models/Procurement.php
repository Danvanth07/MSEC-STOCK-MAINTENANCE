<?php
/**
 * MSEC SMART STOCK - Procurement & Vendor Management Model
 * Locked Scope: Section 6.1, 16 & 17
 */

require_once dirname(__DIR__) . '/core/Database.php';

class Procurement {

    public static function getVendors(): array {
        $pdo = Database::getInstance();
        return $pdo->query("SELECT * FROM vendors ORDER BY company_name ASC")->fetchAll();
    }

    public static function getPurchaseOrders(): array {
        $pdo = Database::getInstance();
        $stmt = $pdo->query("SELECT po.*, v.company_name, v.contact_person, v.mobile_number
                             FROM purchase_orders po
                             JOIN vendors v ON po.vendor_id = v.id
                             ORDER BY po.id DESC");
        return $stmt->fetchAll();
    }

    public static function getGoodsReceipts(): array {
        $pdo = Database::getInstance();
        $stmt = $pdo->query("SELECT grn.*, v.company_name, st.name as store_name, u.full_name as verified_by_name
                             FROM goods_receipts grn
                             JOIN vendors v ON grn.vendor_id = v.id
                             JOIN stores st ON grn.store_id = st.id
                             JOIN users u ON grn.verified_by = u.id
                             ORDER BY grn.id DESC");
        return $stmt->fetchAll();
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

            $pdo->commit();
            return $grnId;
        } catch (Exception $e) {
            $pdo->rollBack();
            return null;
        }
    }
}

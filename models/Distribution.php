<?php
/**
 * MSEC SMART STOCK - Stock Distribution & Voucher Model
 */

require_once dirname(__DIR__) . '/core/Database.php';

class Distribution {

    public static function createIssue(int $sourceStoreId, int $targetDeptId, ?int $targetLabId, int $issuedBy, string $voucherType, array $items, ?int $requestId = null): ?int {
        $pdo = Database::getInstance();
        $pdo->beginTransaction();

        try {
            $year = date('Y');
            $countStmt = $pdo->query("SELECT COUNT(*) FROM stock_issues");
            $nextNum = str_pad((int)$countStmt->fetchColumn() + 1, 5, '0', STR_PAD_LEFT);
            $issueCode = "VCH-{$year}-{$nextNum}";

            $stmt = $pdo->prepare("INSERT INTO stock_issues (issue_code, request_id, source_store_id, target_dept_id, target_lab_id, issued_by, voucher_type, status, remarks) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, 'Issued', 'Material issued from Central Store')");
            $stmt->execute([$issueCode, $requestId, $sourceStoreId, $targetDeptId, $targetLabId, $issuedBy, $voucherType]);
            $issueId = (int)$pdo->lastInsertId();

            $itemStmt = $pdo->prepare("INSERT INTO stock_issue_items (issue_id, item_id, quantity, notes) VALUES (?, ?, ?, ?)");

            // Target store lookup
            $targetStoreStmt = $pdo->prepare("SELECT id FROM stores WHERE dept_id = ? AND type = 'DEPARTMENT' LIMIT 1");
            $targetStoreStmt->execute([$targetDeptId]);
            $targetStoreId = $targetStoreStmt->fetchColumn();

            foreach ($items as $item) {
                $qty = (float)$item['quantity'];
                $itemId = (int)$item['item_id'];
                $itemStmt->execute([$issueId, $itemId, $qty, $item['notes'] ?? '']);

                // 1. Deduct from Source Store
                $deduct = $pdo->prepare("UPDATE stock SET available_quantity = available_quantity - ?, last_movement_date = date('now') WHERE store_id = ? AND item_id = ?");
                $deduct->execute([$qty, $sourceStoreId, $itemId]);

                // 2. Add to Department Store if exists
                if ($targetStoreId) {
                    $chk = $pdo->prepare("SELECT id FROM stock WHERE store_id = ? AND item_id = ?");
                    $chk->execute([$targetStoreId, $itemId]);
                    if ($chk->fetch()) {
                        $add = $pdo->prepare("UPDATE stock SET available_quantity = available_quantity + ?, last_movement_date = date('now') WHERE store_id = ? AND item_id = ?");
                        $add->execute([$qty, $targetStoreId, $itemId]);
                    } else {
                        $ins = $pdo->prepare("INSERT INTO stock (store_id, item_id, available_quantity, store_location, last_movement_date) VALUES (?, ?, ?, 'Dept Rack', date('now'))");
                        $ins->execute([$targetStoreId, $itemId, $qty]);
                    }
                }

                // 3. Immutable Transaction Ledger
                $itemCostStmt = $pdo->prepare("SELECT unit_cost FROM items WHERE id = ?");
                $itemCostStmt->execute([$itemId]);
                $unitCost = (float)$itemCostStmt->fetchColumn();

                $ledger = $pdo->prepare("INSERT INTO stock_transactions (transaction_type, item_id, store_id, quantity, unit_cost, reference_id, reference_type, notes, created_by) 
                                         VALUES ('ISSUE', ?, ?, ?, ?, ?, 'Stock Distribution Voucher', 'Issued to Department', ?)");
                $ledger->execute([$itemId, $sourceStoreId, -$qty, $unitCost, $issueId, $issuedBy]);
            }

            // If linked to request, update request status to Fulfilled
            if ($requestId) {
                $pdo->prepare("UPDATE stock_requests SET status = 'Fulfilled' WHERE id = ?")->execute([$requestId]);
            }

            $pdo->commit();
            return $issueId;
        } catch (Exception $e) {
            $pdo->rollBack();
            return null;
        }
    }

    public static function getAll(): array {
        $pdo = Database::getInstance();
        $stmt = $pdo->query("SELECT si.*, s.name as source_store_name, d.name as target_dept_name, d.code as target_dept_code,
                             l.name as target_lab_name, u.full_name as issued_by_name,
                             dr.id as receipt_id, dr.acknowledgement_date
                             FROM stock_issues si
                             JOIN stores s ON si.source_store_id = s.id
                             JOIN departments d ON si.target_dept_id = d.id
                             LEFT JOIN laboratories l ON si.target_lab_id = l.id
                             JOIN users u ON si.issued_by = u.id
                             LEFT JOIN department_receipts dr ON si.id = dr.issue_id
                             ORDER BY si.id DESC");
        return $stmt->fetchAll();
    }

    public static function getById(int $id): ?array {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT si.*, s.name as source_store_name, d.name as target_dept_name, d.code as target_dept_code,
                               l.name as target_lab_name, u.full_name as issued_by_name, u.role_key as issued_by_role,
                               dr.id as receipt_id, dr.receipt_code, dr.acknowledgement_date, ack_u.full_name as acknowledged_by_name
                               FROM stock_issues si
                               JOIN stores s ON si.source_store_id = s.id
                               JOIN departments d ON si.target_dept_id = d.id
                               LEFT JOIN laboratories l ON si.target_lab_id = l.id
                               JOIN users u ON si.issued_by = u.id
                               LEFT JOIN department_receipts dr ON si.id = dr.issue_id
                               LEFT JOIN users ack_u ON dr.acknowledged_by = ack_u.id
                               WHERE si.id = ?");
        $stmt->execute([$id]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    public static function getItems(int $issueId): array {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT sii.*, i.item_code, i.item_name, i.unit_cost, u.code as unit_code,
                               (sii.quantity * i.unit_cost) as total_value
                               FROM stock_issue_items sii
                               JOIN items i ON sii.item_id = i.id
                               JOIN units u ON i.unit_id = u.id
                               WHERE sii.issue_id = ?");
        $stmt->execute([$issueId]);
        return $stmt->fetchAll();
    }

    public static function acknowledge(int $issueId, int $deptId, int $userId, ?string $remarks = null): bool {
        $pdo = Database::getInstance();
        $pdo->beginTransaction();

        try {
            $year = date('Y');
            $countStmt = $pdo->query("SELECT COUNT(*) FROM department_receipts");
            $nextNum = str_pad((int)$countStmt->fetchColumn() + 1, 5, '0', STR_PAD_LEFT);
            $receiptCode = "REC-{$year}-{$nextNum}";

            $ins = $pdo->prepare("INSERT INTO department_receipts (receipt_code, issue_id, dept_id, acknowledged_by, digital_acknowledgement, remarks) 
                                  VALUES (?, ?, ?, ?, 1, ?)");
            $ins->execute([$receiptCode, $issueId, $deptId, $userId, $remarks]);

            $upd = $pdo->prepare("UPDATE stock_issues SET status = 'Acknowledged' WHERE id = ?");
            $upd->execute([$issueId]);

            $pdo->commit();
            return true;
        } catch (Exception $e) {
            $pdo->rollBack();
            return false;
        }
    }
}

<?php
/**
 * MSEC SMART STOCK - Stock Movement & Inter-Department Transfer Model
 * Locked Workflows: Section 13 & 14
 */

require_once dirname(__DIR__) . '/core/Database.php';

class Movement {

    public static function createTransfer(string $movementType, ?int $sourceDeptId, ?int $targetDeptId, ?int $sourceLabId, ?int $targetLabId, int $initiatedBy, string $notes, array $items): ?int {
        $pdo = Database::getInstance();
        $pdo->beginTransaction();

        try {
            $year = date('Y');
            $countStmt = $pdo->query("SELECT COUNT(*) FROM stock_movements");
            $nextNum = str_pad((int)$countStmt->fetchColumn() + 1, 5, '0', STR_PAD_LEFT);
            $movCode = "MOV-{$year}-{$nextNum}";

            $stmt = $pdo->prepare("INSERT INTO stock_movements (movement_code, movement_type, source_dept_id, target_dept_id, source_lab_id, target_lab_id, initiated_by, status, notes) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, 'Submitted', ?)");
            $stmt->execute([$movCode, $movementType, $sourceDeptId, $targetDeptId, $sourceLabId, $targetLabId, $initiatedBy, $notes]);
            $movementId = (int)$pdo->lastInsertId();

            $itemStmt = $pdo->prepare("INSERT INTO stock_movement_items (movement_id, item_id, quantity, remarks) VALUES (?, ?, ?, ?)");
            foreach ($items as $item) {
                $itemStmt->execute([$movementId, $item['item_id'], $item['quantity'], $item['remarks'] ?? '']);
            }

            $pdo->commit();
            return $movementId;
        } catch (Exception $e) {
            $pdo->rollBack();
            return null;
        }
    }

    public static function getAll(): array {
        $pdo = Database::getInstance();
        $stmt = $pdo->query("SELECT m.*, sd.code as source_dept_code, sd.name as source_dept_name,
                             td.code as target_dept_code, td.name as target_dept_name,
                             u.full_name as initiated_by_name, u.role_key as initiated_by_role
                             FROM stock_movements m
                             LEFT JOIN departments sd ON m.source_dept_id = sd.id
                             LEFT JOIN departments td ON m.target_dept_id = td.id
                             JOIN users u ON m.initiated_by = u.id
                             ORDER BY m.id DESC");
        return $stmt->fetchAll();
    }

    public static function getById(int $id): ?array {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT m.*, sd.code as source_dept_code, sd.name as source_dept_name,
                               td.code as target_dept_code, td.name as target_dept_name,
                               u.full_name as initiated_by_name, u.role_key as initiated_by_role
                               FROM stock_movements m
                               LEFT JOIN departments sd ON m.source_dept_id = sd.id
                               LEFT JOIN departments td ON m.target_dept_id = td.id
                               JOIN users u ON m.initiated_by = u.id
                               WHERE m.id = ?");
        $stmt->execute([$id]);
        $mov = $stmt->fetch();
        return $mov ?: null;
    }

    public static function getItems(int $movementId): array {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT mi.*, i.item_code, i.item_name, u.code as unit_code
                               FROM stock_movement_items mi
                               JOIN items i ON mi.item_id = i.id
                               JOIN units u ON i.unit_id = u.id
                               WHERE mi.movement_id = ?");
        $stmt->execute([$movementId]);
        return $stmt->fetchAll();
    }

    public static function advanceStatus(int $movementId, string $nextStatus, int $userId): bool {
        $pdo = Database::getInstance();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare("SELECT * FROM stock_movements WHERE id = ?");
            $stmt->execute([$movementId]);
            $mov = $stmt->fetch();
            if (!$mov) return false;

            $items = self::getItems($movementId);

            // If advancing to Closed / Acknowledged, update department balances
            if (($nextStatus === 'Acknowledged' || $nextStatus === 'Closed') && $mov['status'] !== 'Closed') {
                if ($mov['movement_type'] === 'DEPT_TO_DEPT' && $mov['source_dept_id'] && $mov['target_dept_id']) {
                    // Fetch source and target stores
                    $srcStoreStmt = $pdo->prepare("SELECT id FROM stores WHERE dept_id = ? LIMIT 1");
                    $srcStoreStmt->execute([$mov['source_dept_id']]);
                    $srcStoreId = $srcStoreStmt->fetchColumn();

                    $tgtStoreStmt = $pdo->prepare("SELECT id FROM stores WHERE dept_id = ? LIMIT 1");
                    $tgtStoreStmt->execute([$mov['target_dept_id']]);
                    $tgtStoreId = $tgtStoreStmt->fetchColumn();

                    if ($srcStoreId && $tgtStoreId) {
                        foreach ($items as $item) {
                            $qty = (float)$item['quantity'];
                            $itemId = (int)$item['item_id'];

                            // Deduct from source store
                            $pdo->prepare("UPDATE stock SET available_quantity = available_quantity - ?, last_movement_date = date('now') WHERE store_id = ? AND item_id = ?")
                                ->execute([$qty, $srcStoreId, $itemId]);

                            // Add to target store
                            $chk = $pdo->prepare("SELECT id FROM stock WHERE store_id = ? AND item_id = ?");
                            $chk->execute([$tgtStoreId, $itemId]);
                            if ($chk->fetch()) {
                                $pdo->prepare("UPDATE stock SET available_quantity = available_quantity + ?, last_movement_date = date('now') WHERE store_id = ? AND item_id = ?")
                                    ->execute([$qty, $tgtStoreId, $itemId]);
                            } else {
                                $pdo->prepare("INSERT INTO stock (store_id, item_id, available_quantity, store_location, last_movement_date) VALUES (?, ?, ?, 'Dept Rack', date('now'))")
                                    ->execute([$tgtStoreId, $itemId, $qty]);
                            }

                            // Record ledger entry
                            $pdo->prepare("INSERT INTO stock_transactions (transaction_type, item_id, store_id, quantity, unit_cost, reference_id, reference_type, notes, created_by) 
                                           VALUES ('TRANSFER_IN', ?, ?, ?, 0, ?, 'Inter-Dept Transfer', 'Transfer Completed', ?)")
                                ->execute([$itemId, $tgtStoreId, $qty, $movementId, $userId]);
                        }
                    }
                }
            }

            $upd = $pdo->prepare("UPDATE stock_movements SET status = ? WHERE id = ?");
            $upd->execute([$nextStatus, $movementId]);

            $pdo->commit();
            return true;
        } catch (Exception $e) {
            $pdo->rollBack();
            return false;
        }
    }
}

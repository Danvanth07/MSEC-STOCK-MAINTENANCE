<?php
/**
 * MSEC SMART STOCK - Inventory Model
 */

require_once dirname(__DIR__) . '/core/Database.php';

class Inventory {

    public static function getAllItems(): array {
        $pdo = Database::getInstance();
        $stmt = $pdo->query("SELECT i.*, c.name as category_name, c.type as category_type, u.code as unit_code,
                             COALESCE(SUM(s.available_quantity), 0) as total_available,
                             COALESCE(SUM(s.reserved_quantity), 0) as total_reserved
                             FROM items i
                             LEFT JOIN item_categories c ON i.category_id = c.id
                             LEFT JOIN units u ON i.unit_id = u.id
                             LEFT JOIN stock s ON i.id = s.item_id
                             GROUP BY i.id
                             ORDER BY i.item_name ASC");
        return $stmt->fetchAll();
    }

    public static function getStockByStore(int $storeId): array {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT s.*, i.item_code, i.item_name, i.unit_cost, i.min_stock, i.max_stock, i.is_consumable,
                               c.name as category_name, u.code as unit_code,
                               (s.available_quantity * i.unit_cost) as total_value
                               FROM stock s
                               JOIN items i ON s.item_id = i.id
                               JOIN item_categories c ON i.category_id = c.id
                               JOIN units u ON i.unit_id = u.id
                               WHERE s.store_id = ?
                               ORDER BY i.item_name ASC");
        $stmt->execute([$storeId]);
        return $stmt->fetchAll();
    }

    public static function getLowStockItems(): array {
        $pdo = Database::getInstance();
        $stmt = $pdo->query("SELECT s.*, i.item_code, i.item_name, i.min_stock, u.code as unit_code, st.name as store_name
                             FROM stock s
                             JOIN items i ON s.item_id = i.id
                             JOIN units u ON i.unit_id = u.id
                             JOIN stores st ON s.store_id = st.id
                             WHERE s.available_quantity <= i.min_stock
                             ORDER BY s.available_quantity ASC");
        return $stmt->fetchAll();
    }

    public static function getDeadStockItems(): array {
        $pdo = Database::getInstance();
        // Identify items with no movement for 1, 2, or 3 years (Section 21)
        $stmt = $pdo->query("SELECT s.*, i.item_code, i.item_name, i.unit_cost, (s.available_quantity * i.unit_cost) as total_value,
                             st.name as store_name, u.code as unit_code,
                             s.last_movement_date
                             FROM stock s
                             JOIN items i ON s.item_id = i.id
                             JOIN units u ON i.unit_id = u.id
                             JOIN stores st ON s.store_id = st.id
                             WHERE s.available_quantity > 0 
                             AND (s.last_movement_date IS NULL OR s.last_movement_date <= date('now', '-1 year'))
                             ORDER BY s.last_movement_date ASC");
        return $stmt->fetchAll();
    }

    public static function adjustStock(int $storeId, int $itemId, float $newQuantity, string $reason, ?string $supportingDoc, int $approvedBy): bool {
        $pdo = Database::getInstance();
        $pdo->beginTransaction();

        try {
            // Get current stock
            $stmt = $pdo->prepare("SELECT available_quantity FROM stock WHERE store_id = ? AND item_id = ?");
            $stmt->execute([$storeId, $itemId]);
            $currentStock = $stmt->fetchColumn();
            $oldQty = $currentStock !== false ? (float)$currentStock : 0.0;
            $diff = $newQuantity - $oldQty;

            // Update or insert stock
            if ($currentStock !== false) {
                $upd = $pdo->prepare("UPDATE stock SET available_quantity = ?, last_movement_date = date('now') WHERE store_id = ? AND item_id = ?");
                $upd->execute([$newQuantity, $storeId, $itemId]);
            } else {
                $ins = $pdo->prepare("INSERT INTO stock (store_id, item_id, available_quantity, store_location, last_movement_date) VALUES (?, ?, ?, 'General Shelf', date('now'))");
                $ins->execute([$storeId, $itemId, $newQuantity]);
            }

            // Create stock adjustment record
            $adjCode = 'ADJ-' . date('Ymd') . '-' . rand(1000, 9999);
            $adjStmt = $pdo->prepare("INSERT INTO stock_adjustments (adjustment_code, store_id, item_id, old_quantity, new_quantity, difference, reason, supporting_document, approved_by) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $adjStmt->execute([$adjCode, $storeId, $itemId, $oldQty, $newQuantity, $diff, $reason, $supportingDoc, $approvedBy]);

            // Create ledger entry
            $itemCostStmt = $pdo->prepare("SELECT unit_cost FROM items WHERE id = ?");
            $itemCostStmt->execute([$itemId]);
            $unitCost = (float)$itemCostStmt->fetchColumn();

            $ledgerStmt = $pdo->prepare("INSERT INTO stock_transactions (transaction_type, item_id, store_id, quantity, unit_cost, reference_type, notes, created_by) 
                                         VALUES ('ADJUSTMENT', ?, ?, ?, ?, 'Stock Adjustment', ?, ?)");
            $ledgerStmt->execute([$itemId, $storeId, $diff, $unitCost, $reason, $approvedBy]);

            $pdo->commit();
            return true;
        } catch (Exception $e) {
            $pdo->rollBack();
            return false;
        }
    }
}

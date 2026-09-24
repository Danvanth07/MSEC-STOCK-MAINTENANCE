<?php
/**
 * MSEC SMART STOCK - Institutional Budget & Financial Model
 * Tracks Department Budget Heads, Capital vs Operational Allocations, Commitments & Utilization.
 */

require_once dirname(__DIR__) . '/core/Database.php';

class Budget {

    public static function getAll(): array {
        $pdo = Database::getInstance();
        $stmt = $pdo->query("
            SELECT b.*, d.name as dept_name, d.code as dept_code,
                   (b.allocated_amount - b.utilized_amount - b.committed_amount) as available_balance
            FROM budgets b
            JOIN departments d ON b.dept_id = d.id
            ORDER BY d.code ASC, b.category ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getByDept(int $deptId): array {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("
            SELECT b.*, d.name as dept_name, d.code as dept_code,
                   (b.allocated_amount - b.utilized_amount - b.committed_amount) as available_balance
            FROM budgets b
            JOIN departments d ON b.dept_id = d.id
            WHERE b.dept_id = ?
            ORDER BY b.category ASC
        ");
        $stmt->execute([$deptId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getSummary(): array {
        $pdo = Database::getInstance();
        $stmt = $pdo->query("
            SELECT 
                COALESCE(SUM(allocated_amount), 0) as total_allocated,
                COALESCE(SUM(utilized_amount), 0) as total_utilized,
                COALESCE(SUM(committed_amount), 0) as total_committed,
                COALESCE(SUM(allocated_amount - utilized_amount - committed_amount), 0) as total_available
            FROM budgets
        ");
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [
            'total_allocated' => 0, 'total_utilized' => 0, 'total_committed' => 0, 'total_available' => 0
        ];
    }
}

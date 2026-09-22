<?php
/**
 * MSEC SMART STOCK - Stock Request & Indent Model
 */

require_once dirname(__DIR__) . '/core/Database.php';
require_once dirname(__DIR__) . '/core/ApprovalEngine.php';

class StockRequest {

    public static function create(int $deptId, ?int $labId, int $requestedBy, string $purpose, string $requiredDate, array $items): ?int {
        $pdo = Database::getInstance();
        $pdo->beginTransaction();

        try {
            // Generate Request Code, e.g., CSE-REQ-2026-00126
            $deptStmt = $pdo->prepare("SELECT code FROM departments WHERE id = ?");
            $deptStmt->execute([$deptId]);
            $deptCode = $deptStmt->fetchColumn() ?: 'MSEC';

            $year = date('Y');
            $countStmt = $pdo->query("SELECT COUNT(*) FROM stock_requests");
            $nextNum = str_pad((int)$countStmt->fetchColumn() + 1, 5, '0', STR_PAD_LEFT);
            $requestCode = "{$deptCode}-REQ-{$year}-{$nextNum}";

            // Calculate total estimated value
            $totalValue = 0.0;
            foreach ($items as $item) {
                $costStmt = $pdo->prepare("SELECT unit_cost FROM items WHERE id = ?");
                $costStmt->execute([$item['item_id']]);
                $unitCost = (float)$costStmt->fetchColumn();
                $totalValue += ($unitCost * (float)$item['quantity']);
            }

            // Insert Request
            $reqStmt = $pdo->prepare("INSERT INTO stock_requests (request_code, dept_id, lab_id, requested_by, purpose, required_date, total_estimated_value, current_level, status) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?, 1, 'Pending Approval')");
            $reqStmt->execute([$requestCode, $deptId, $labId, $requestedBy, $purpose, $requiredDate, $totalValue]);
            $requestId = (int)$pdo->lastInsertId();

            // Insert Items
            $itemStmt = $pdo->prepare("INSERT INTO stock_request_items (request_id, item_id, requested_quantity, approved_quantity, unit_cost, notes) 
                                       VALUES (?, ?, ?, ?, ?, ?)");
            foreach ($items as $item) {
                $costStmt = $pdo->prepare("SELECT unit_cost FROM items WHERE id = ?");
                $costStmt->execute([$item['item_id']]);
                $unitCost = (float)$costStmt->fetchColumn();
                $itemStmt->execute([$requestId, $item['item_id'], $item['quantity'], $item['quantity'], $unitCost, $item['notes'] ?? '']);
            }

            // Initialize Dynamic Approval Workflow
            ApprovalEngine::initializeWorkflow($requestId, $totalValue);

            $pdo->commit();
            return $requestId;
        } catch (Exception $e) {
            $pdo->rollBack();
            return null;
        }
    }

    public static function getAll(?int $deptId = null, ?string $status = null): array {
        $pdo = Database::getInstance();
        $sql = "SELECT r.*, d.name as dept_name, d.code as dept_code, l.name as lab_name, u.full_name as requester_name, u.role_key as requester_role
                FROM stock_requests r
                JOIN departments d ON r.dept_id = d.id
                LEFT JOIN laboratories l ON r.lab_id = l.id
                JOIN users u ON r.requested_by = u.id
                WHERE 1=1";
        $params = [];

        if ($deptId !== null) {
            $sql .= " AND r.dept_id = ?";
            $params[] = $deptId;
        }
        if ($status !== null) {
            $sql .= " AND r.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY r.id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function getById(int $id): ?array {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT r.*, d.name as dept_name, d.code as dept_code, l.name as lab_name, u.full_name as requester_name, u.role_key as requester_role, u.email as requester_email
                               FROM stock_requests r
                               JOIN departments d ON r.dept_id = d.id
                               LEFT JOIN laboratories l ON r.lab_id = l.id
                               JOIN users u ON r.requested_by = u.id
                               WHERE r.id = ?");
        $stmt->execute([$id]);
        $req = $stmt->fetch();
        return $req ?: null;
    }

    public static function getItems(int $requestId): array {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT ri.*, i.item_code, i.item_name, u.code as unit_code
                               FROM stock_request_items ri
                               JOIN items i ON ri.item_id = i.id
                               JOIN units u ON i.unit_id = u.id
                               WHERE ri.request_id = ?");
        $stmt->execute([$requestId]);
        return $stmt->fetchAll();
    }

    public static function getApprovals(int $requestId): array {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT a.*, u.full_name as approver_name
                               FROM request_approvals a
                               LEFT JOIN users u ON a.approver_user_id = u.id
                               WHERE a.request_id = ?
                               ORDER BY a.level_number ASC");
        $stmt->execute([$requestId]);
        return $stmt->fetchAll();
    }

    public static function getPendingForRole(string $roleKey): array {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT r.*, d.name as dept_name, d.code as dept_code, u.full_name as requester_name, a.level_title, a.id as approval_id
                               FROM stock_requests r
                               JOIN request_approvals a ON r.id = a.request_id
                               JOIN departments d ON r.dept_id = d.id
                               JOIN users u ON r.requested_by = u.id
                               WHERE a.status = 'Pending' AND a.approver_role = ?
                               ORDER BY r.id DESC");
        $stmt->execute([$roleKey]);
        return $stmt->fetchAll();
    }
}

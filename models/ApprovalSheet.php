<?php
/**
 * MSEC SMART STOCK - Official Approval Sheet Model
 * Manages the Store Keeper's official digital verification sheet, vendor quotations,
 * and multi-signature authorization records.
 */

require_once dirname(__DIR__) . '/core/Database.php';

class ApprovalSheet {

    public static function getByRequestId(int $requestId): ?array {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("
            SELECT s.*, u.full_name as store_keeper_name, v.company_name as vendor_name, v.gst_number as vendor_gst,
                   r.request_code, r.purpose, r.required_date, d.name as dept_name, d.code as dept_code,
                   req_user.full_name as requester_name, req_user.designation as requester_designation
            FROM approval_sheets s
            JOIN users u ON s.store_keeper_id = u.id
            JOIN stock_requests r ON s.request_id = r.id
            JOIN departments d ON r.dept_id = d.id
            JOIN users req_user ON r.requested_by = req_user.id
            LEFT JOIN vendors v ON s.vendor_id = v.id
            WHERE s.request_id = ?
            ORDER BY s.id DESC LIMIT 1
        ");
        $stmt->execute([$requestId]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    public static function getById(int $id): ?array {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("
            SELECT s.*, u.full_name as store_keeper_name, v.company_name as vendor_name, v.gst_number as vendor_gst,
                   r.request_code, r.purpose, r.required_date, d.name as dept_name, d.code as dept_code,
                   req_user.full_name as requester_name, req_user.designation as requester_designation
            FROM approval_sheets s
            JOIN users u ON s.store_keeper_id = u.id
            JOIN stock_requests r ON s.request_id = r.id
            JOIN departments d ON r.dept_id = d.id
            JOIN users req_user ON r.requested_by = req_user.id
            LEFT JOIN vendors v ON s.vendor_id = v.id
            WHERE s.id = ?
        ");
        $stmt->execute([$id]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    public static function getAll(): array {
        $pdo = Database::getInstance();
        $stmt = $pdo->query("
            SELECT s.*, u.full_name as store_keeper_name, v.company_name as vendor_name,
                   r.request_code, r.purpose, d.code as dept_code
            FROM approval_sheets s
            JOIN users u ON s.store_keeper_id = u.id
            JOIN stock_requests r ON s.request_id = r.id
            JOIN departments d ON r.dept_id = d.id
            LEFT JOIN vendors v ON s.vendor_id = v.id
            ORDER BY s.id DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

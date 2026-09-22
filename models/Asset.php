<?php
/**
 * MSEC SMART STOCK - Asset Management & IT Tracking Model
 * Locked Scope: Section 8, 18 & 20
 */

require_once dirname(__DIR__) . '/core/Database.php';

class Asset {

    public static function getAll(?int $deptId = null, ?string $status = null): array {
        $pdo = Database::getInstance();
        $sql = "SELECT a.*, d.name as dept_name, d.code as dept_code, l.name as lab_name, c.name as category_name,
                       u.full_name as custodian_name
                FROM assets a
                JOIN departments d ON a.dept_id = d.id
                LEFT JOIN laboratories l ON a.lab_id = l.id
                JOIN item_categories c ON a.category_id = c.id
                LEFT JOIN users u ON a.custodian_id = u.id
                WHERE 1=1";
        $params = [];

        if ($deptId !== null) {
            $sql .= " AND a.dept_id = ?";
            $params[] = $deptId;
        }
        if ($status !== null) {
            $sql .= " AND a.asset_status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY a.id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function getById(int $id): ?array {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT a.*, d.name as dept_name, d.code as dept_code, l.name as lab_name, c.name as category_name,
                               u.full_name as custodian_name
                               FROM assets a
                               JOIN departments d ON a.dept_id = d.id
                               LEFT JOIN laboratories l ON a.lab_id = l.id
                               JOIN item_categories c ON a.category_id = c.id
                               LEFT JOIN users u ON a.custodian_id = u.id
                               WHERE a.id = ?");
        $stmt->execute([$id]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    public static function create(array $data): ?int {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("INSERT INTO assets (asset_id, asset_name, category_id, brand, model, serial_number, mac_address, ip_address, warranty_expiry, amc_details, configuration, purchase_date, purchase_cost, dept_id, lab_id, location, custodian_id, asset_status) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['asset_id'],
            $data['asset_name'],
            $data['category_id'],
            $data['brand'] ?? '',
            $data['model'] ?? '',
            $data['serial_number'] ?? '',
            $data['mac_address'] ?? null,
            $data['ip_address'] ?? null,
            $data['warranty_expiry'] ?? null,
            $data['amc_details'] ?? null,
            $data['configuration'] ?? null,
            $data['purchase_date'] ?? date('Y-m-d'),
            $data['purchase_cost'] ?? 0,
            $data['dept_id'],
            $data['lab_id'] ?? null,
            $data['location'] ?? 'Lab Desk',
            $data['custodian_id'] ?? null,
            $data['asset_status'] ?? 'Available'
        ]);
        return (int)$pdo->lastInsertId();
    }

    public static function updateStatus(int $id, string $status): bool {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("UPDATE assets SET asset_status = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }

    public static function getMaintenanceRecords(int $assetId): array {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT m.*, u.full_name as reported_by_name
                               FROM asset_maintenance m
                               JOIN users u ON m.reported_by = u.id
                               WHERE m.asset_id = ?
                               ORDER BY m.id DESC");
        $stmt->execute([$assetId]);
        return $stmt->fetchAll();
    }

    public static function addMaintenance(int $assetId, string $issue, int $reportedBy, string $status = 'Under Repair'): int {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("INSERT INTO asset_maintenance (asset_id, issue_reported, reported_by, status) VALUES (?, ?, ?, ?)");
        $stmt->execute([$assetId, $issue, $reportedBy, $status]);

        // Update asset status
        self::updateStatus($assetId, 'Under Repair');
        return (int)$pdo->lastInsertId();
    }
}

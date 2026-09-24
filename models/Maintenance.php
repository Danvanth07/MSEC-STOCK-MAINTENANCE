<?php
/**
 * MSEC SMART STOCK - Asset Maintenance & Service Ticket Model
 * Handles equipment repair bays, service tickets, technician diagnosis, and completion logs.
 */

require_once dirname(__DIR__) . '/core/Database.php';
require_once dirname(__DIR__) . '/core/AuditLogger.php';

class Maintenance {

    public static function getAll(?string $status = null): array {
        $pdo = Database::getInstance();
        $sql = "
            SELECT m.*, a.asset_id as asset_tag, a.asset_name, a.brand, a.model, a.location as asset_location,
                   u_rep.full_name as reporter_name, u_rep.role_key as reporter_role,
                   u_tech.full_name as technician_name,
                   d.code as dept_code, d.name as dept_name
            FROM asset_maintenance m
            JOIN assets a ON m.asset_id = a.id
            JOIN users u_rep ON m.reported_by = u_rep.id
            LEFT JOIN users u_tech ON m.technician_id = u_tech.id
            JOIN departments d ON a.dept_id = d.id
            WHERE 1=1
        ";
        $params = [];
        if ($status) {
            $sql .= " AND m.status = ?";
            $params[] = $status;
        }
        $sql .= " ORDER BY m.id DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getById(int $id): ?array {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("
            SELECT m.*, a.asset_id as asset_tag, a.asset_name, a.brand, a.model, a.serial_number, a.location as asset_location,
                   u_rep.full_name as reporter_name, u_tech.full_name as technician_name,
                   d.code as dept_code, d.name as dept_name
            FROM asset_maintenance m
            JOIN assets a ON m.asset_id = a.id
            JOIN users u_rep ON m.reported_by = u_rep.id
            LEFT JOIN users u_tech ON m.technician_id = u_tech.id
            JOIN departments d ON a.dept_id = d.id
            WHERE m.id = ?
        ");
        $stmt->execute([$id]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    public static function createTicket(array $data, int $reporterId): ?int {
        $pdo = Database::getInstance();
        $year = date('Y');
        $count = (int)$pdo->query("SELECT COUNT(*) FROM asset_maintenance")->fetchColumn() + 1;
        $ticketNo = "TCK-{$year}-" . str_pad($count, 4, '0', STR_PAD_LEFT);

        $stmt = $pdo->prepare("
            INSERT INTO asset_maintenance 
            (ticket_number, asset_id, reported_by, issue_reported, status, created_at)
            VALUES (?, ?, ?, ?, 'Open', datetime('now', 'localtime'))
        ");
        $stmt->execute([
            $ticketNo,
            (int)$data['asset_id'],
            $reporterId,
            $data['issue_reported']
        ]);

        $ticketId = (int)$pdo->lastInsertId();

        // Update asset status to 'Under Repair'
        $pdo->prepare("UPDATE assets SET asset_status = 'Under Repair' WHERE id = ?")
            ->execute([(int)$data['asset_id']]);

        AuditLogger::log(
            'MAINTENANCE_TICKET_OPENED',
            'Maintenance',
            "Opened service ticket {$ticketNo} for Asset #{$data['asset_id']}: {$data['issue_reported']}",
            'AssetMaintenance',
            $ticketId,
            null,
            ['ticket_number' => $ticketNo, 'status' => 'Open'],
            $reporterId
        );

        return $ticketId;
    }

    public static function updateTicket(int $ticketId, array $data, int $technicianId): bool {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("
            UPDATE asset_maintenance 
            SET technician_id = ?, diagnosis = ?, status = ?, repair_cost = ?, resolution_notes = ?,
                completed_date = CASE WHEN ? = 'Repaired' THEN datetime('now', 'localtime') ELSE completed_date END
            WHERE id = ?
        ");
        $status = $data['status'] ?? 'In Progress';
        $repairCost = (float)($data['repair_cost'] ?? 0);
        $res = $stmt->execute([
            $technicianId,
            $data['diagnosis'] ?? '',
            $status,
            $repairCost,
            $data['resolution_notes'] ?? '',
            $status,
            $ticketId
        ]);

        if ($status === 'Repaired') {
            $ticket = self::getById($ticketId);
            if ($ticket) {
                $pdo->prepare("UPDATE assets SET asset_status = 'Available' WHERE id = ?")->execute([$ticket['asset_id']]);
            }
        }

        AuditLogger::log(
            'MAINTENANCE_TICKET_UPDATED',
            'Maintenance',
            "Technician updated Ticket #{$ticketId} to status '{$status}'. Notes: " . ($data['resolution_notes'] ?? ''),
            'AssetMaintenance',
            $ticketId,
            null,
            ['status' => $status, 'repair_cost' => $repairCost],
            $technicianId,
            'technician'
        );

        return $res;
    }

    public static function getKPIs(): array {
        $pdo = Database::getInstance();
        $totalOpen = (int)$pdo->query("SELECT COUNT(*) FROM asset_maintenance WHERE status IN ('Open', 'Assigned', 'In Progress', 'Under Repair')")->fetchColumn();
        $completed = (int)$pdo->query("SELECT COUNT(*) FROM asset_maintenance WHERE status = 'Repaired'")->fetchColumn();
        $underRepairAssets = (int)$pdo->query("SELECT COUNT(*) FROM assets WHERE asset_status = 'Under Repair'")->fetchColumn();
        $totalCost = (float)$pdo->query("SELECT COALESCE(SUM(repair_cost), 0) FROM asset_maintenance")->fetchColumn();

        return [
            'open_tickets' => $totalOpen,
            'completed_tickets' => $completed,
            'assets_under_repair' => $underRepairAssets,
            'total_repair_cost' => $totalCost
        ];
    }
}

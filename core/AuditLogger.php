<?php
/**
 * MSEC SMART STOCK - Centralized Immutable Audit Logger
 * Records every administrative, security, workflow, and stock transaction permanently.
 */

require_once __DIR__ . '/Database.php';

class AuditLogger {

    public static function log(
        string $action,
        string $module,
        ?string $remarks = null,
        ?string $entityType = null,
        ?int $entityId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $userId = null,
        ?string $role = null
    ): bool {
        try {
            $pdo = Database::getInstance();

            if ($userId === null && class_exists('Auth')) {
                $user = Auth::user();
                $userId = $user['id'] ?? null;
                $role = $role ?? ($user['role_key'] ?? 'guest');
            }

            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $oldJson = $oldValues ? json_encode($oldValues, JSON_UNESCAPED_UNICODE) : null;
            $newJson = $newValues ? json_encode($newValues, JSON_UNESCAPED_UNICODE) : null;

            $stmt = $pdo->prepare("
                INSERT INTO audit_logs 
                (user_id, role, action, module, entity_type, entity_id, old_values, new_values, remarks, ip_address, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now', 'localtime'))
            ");

            return $stmt->execute([
                $userId,
                $role,
                $action,
                $module,
                $entityType,
                $entityId,
                $oldJson,
                $newJson,
                $remarks,
                $ip
            ]);
        } catch (Exception $e) {
            // Failsafe fallback: write to PHP error log if DB write fails
            error_log("AuditLogger Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retrieve recent audit logs with user details
     */
    public static function getLogs(int $limit = 100, ?string $module = null, ?string $action = null): array {
        try {
            $pdo = Database::getInstance();
            $sql = "
                SELECT a.*, u.full_name, u.username
                FROM audit_logs a
                LEFT JOIN users u ON a.user_id = u.id
                WHERE 1=1
            ";
            $params = [];

            if ($module) {
                $sql .= " AND a.module = ?";
                $params[] = $module;
            }
            if ($action) {
                $sql .= " AND a.action = ?";
                $params[] = $action;
            }

            $sql .= " ORDER BY a.id DESC LIMIT ?";
            $params[] = $limit;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Retrieve security specific events
     */
    public static function getSecurityEvents(int $limit = 50): array {
        try {
            $pdo = Database::getInstance();
            $stmt = $pdo->prepare("
                SELECT a.*, u.full_name, u.username
                FROM audit_logs a
                LEFT JOIN users u ON a.user_id = u.id
                WHERE a.module IN ('Security', 'Authentication', 'User Management')
                ORDER BY a.id DESC LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
}

<?php
/**
 * MSEC SMART STOCK - Authentication & RBAC Engine
 */

require_once __DIR__ . '/Database.php';

class Auth {
    public static function init(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function check(): bool {
        self::init();
        return isset($_SESSION['msec_user']);
    }

    public static function user(): ?array {
        self::init();
        return $_SESSION['msec_user'] ?? null;
    }

    public static function id(): ?int {
        return self::user()['id'] ?? null;
    }

    public static function role(): ?string {
        return self::user()['role_key'] ?? null;
    }

    public static function deptId(): ?int {
        return self::user()['dept_id'] ?? null;
    }

    public static function login(string $username, string $password): bool {
        self::init();
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT u.*, d.name as dept_name, d.code as dept_code 
                               FROM users u 
                               LEFT JOIN departments d ON u.dept_id = d.id 
                               WHERE u.username = ? AND u.status = 'Active'");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            unset($user['password_hash']);
            $_SESSION['msec_user'] = $user;
            self::logActivity('User Login', 'Authentication', "User {$user['username']} logged in successfully");
            return true;
        }
        return false;
    }

    public static function loginAs(string $roleKey): bool {
        self::init();
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT u.*, d.name as dept_name, d.code as dept_code 
                               FROM users u 
                               LEFT JOIN departments d ON u.dept_id = d.id 
                               WHERE u.role_key = ? AND u.status = 'Active' 
                               LIMIT 1");
        $stmt->execute([$roleKey]);
        $user = $stmt->fetch();

        if ($user) {
            unset($user['password_hash']);
            $_SESSION['msec_user'] = $user;
            self::logActivity('Role Switched (Demo)', 'Authentication', "Switched persona to {$user['username']} ({$roleKey})");
            return true;
        }
        return false;
    }

    public static function logout(): void {
        self::init();
        if (isset($_SESSION['msec_user'])) {
            self::logActivity('User Logout', 'Authentication', "User {$_SESSION['msec_user']['username']} logged out");
        }
        unset($_SESSION['msec_user']);
        session_destroy();
    }

    public static function hasRole($roles): bool {
        $user = self::user();
        if (!$user) return false;
        if (is_string($roles)) return $user['role_key'] === $roles;
        if (is_array($roles)) return in_array($user['role_key'], $roles);
        return false;
    }

    public static function requireAuth(): void {
        if (!self::check()) {
            header("Location: index.php?route=login");
            exit;
        }
    }

    public static function requireRole($roles): void {
        self::requireAuth();
        if (!self::hasRole($roles)) {
            header("Location: index.php?route=unauthorized");
            exit;
        }
    }

    public static function canAccessRoute(string $route): bool {
        $role = self::role();
        if (!$role) return false;
        if ($role === 'super_admin' || $role === 'admin') return true;

        $permissions = [
            'dashboard'     => ['*'],
            'inventory'     => ['director', 'principal', 'vice_principal', 'store_keeper', 'accounts_officer', 'hod', 'dept_coordinator', 'lab_incharge', 'auditor'],
            'requests'      => ['*'],
            'new_request'   => ['store_keeper', 'hod', 'dept_coordinator', 'lab_incharge', 'faculty', 'technician'],
            'view_request'  => ['*'],
            'distribution'  => ['director', 'principal', 'vice_principal', 'store_keeper', 'hod', 'dept_coordinator', 'lab_incharge', 'accounts_officer', 'auditor'],
            'issue_stock'   => ['store_keeper'],
            'view_voucher'  => ['director', 'principal', 'vice_principal', 'store_keeper', 'hod', 'dept_coordinator', 'lab_incharge', 'accounts_officer', 'auditor'],
            'movement'      => ['director', 'principal', 'vice_principal', 'store_keeper', 'hod', 'dept_coordinator', 'lab_incharge', 'auditor'],
            'assets'        => ['*'],
            'procurement'   => ['director', 'principal', 'purchase_officer', 'store_keeper', 'accounts_officer', 'auditor'],
            'verification'  => ['director', 'principal', 'vice_principal', 'store_keeper', 'auditor', 'accounts_officer'],
            'reports'       => ['director', 'principal', 'vice_principal', 'accounts_officer', 'auditor', 'store_keeper', 'purchase_officer', 'hod', 'management_viewer'],
            'admin_masters' => ['super_admin', 'admin'],
            'admin_users'   => ['super_admin', 'admin'],
            'admin_audit'   => ['super_admin', 'admin', 'auditor', 'director', 'principal'],
            'unauthorized'  => ['*']
        ];

        if (!isset($permissions[$route])) return false;
        if (in_array('*', $permissions[$route], true)) return true;
        return in_array($role, $permissions[$route], true);
    }

    public static function can(string $action): bool {
        $role = self::role();
        if (!$role) return false;
        if ($role === 'super_admin' || $role === 'admin') return true;

        // Read-only roles are strictly prevented from mutation actions
        if (in_array($role, ['auditor', 'management_viewer'], true)) {
            return false;
        }

        switch ($action) {
            case 'adjust_stock':
            case 'create_stock_entry':
                return $role === 'store_keeper';

            case 'create_request':
            case 'raise_indent':
                return in_array($role, ['store_keeper', 'hod', 'dept_coordinator', 'lab_incharge', 'faculty', 'technician'], true);

            case 'approve_request':
                return in_array($role, ['director', 'principal', 'vice_principal', 'hod', 'store_keeper'], true);

            case 'issue_stock':
            case 'create_voucher':
                return $role === 'store_keeper';

            case 'ack_receipt':
                return in_array($role, ['hod', 'dept_coordinator', 'lab_incharge'], true);

            case 'create_transfer':
            case 'advance_transfer':
                return in_array($role, ['store_keeper', 'dept_coordinator', 'hod', 'lab_incharge'], true);

            case 'create_asset':
                return in_array($role, ['store_keeper', 'dept_coordinator'], true);

            case 'report_damage':
                return in_array($role, ['faculty', 'technician', 'lab_incharge', 'dept_coordinator', 'hod', 'store_keeper'], true);

            case 'create_po':
                return $role === 'purchase_officer';

            case 'create_grn':
                return in_array($role, ['purchase_officer', 'store_keeper'], true);

            case 'conduct_audit':
                return in_array($role, ['store_keeper', 'auditor'], true);

            case 'view_reports':
                return in_array($role, ['director', 'principal', 'vice_principal', 'accounts_officer', 'auditor', 'store_keeper', 'purchase_officer', 'hod', 'management_viewer'], true);

            case 'manage_users':
            case 'manage_masters':
                return false;

            default:
                return false;
        }
    }

    public static function getRoleLevel(): string {
        $role = self::role() ?? 'super_admin';
        return ROLES[$role]['level'] ?? 'User';
    }

    public static function logActivity(string $activity, string $module, ?string $details = null): void {
        try {
            $pdo = Database::getInstance();
            $userId = self::id();
            $role = self::role() ?? 'guest';
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

            $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, role, activity, module, action_details, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $role, $activity, $module, $details, $ip]);
        } catch (Exception $e) {
            // fail-safe logging
        }
    }
}

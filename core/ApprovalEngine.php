<?php
/**
 * MSEC SMART STOCK - Dynamic Approval Workflow Engine
 * Locked Matrix Implementation (Section 11 & 30)
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';

class ApprovalEngine {

    /**
     * Determine and initialize the approval chain for a stock request
     */
    public static function initializeWorkflow(int $requestId, float $totalValue): void {
        $pdo = Database::getInstance();

        // Standard sequence:
        // Level 1: Department Stock Coordinator
        // Level 2: Head of Department (HOD)
        // Level 3+: Depends on monetary value thresholds
        $levels = [
            [
                'level' => 1,
                'title' => 'Department Stock Coordinator Verification',
                'role'  => 'dept_coordinator'
            ],
            [
                'level' => 2,
                'title' => 'Head of Department (HOD) Approval',
                'role'  => 'hod'
            ]
        ];

        if ($totalValue <= 5000) {
            // Below ₹5,000: HOD + Store Keeper
            $levels[] = [
                'level' => 3,
                'title' => 'Central Store Keeper Fulfillment',
                'role'  => 'store_keeper'
            ];
        } elseif ($totalValue <= 25000) {
            // ₹5,001 - ₹25,000: HOD + Vice Principal
            $levels[] = [
                'level' => 3,
                'title' => 'Vice Principal Administrative Approval',
                'role'  => 'vice_principal'
            ];
            $levels[] = [
                'level' => 4,
                'title' => 'Central Store Keeper Issue',
                'role'  => 'store_keeper'
            ];
        } elseif ($totalValue <= 100000) {
            // ₹25,001 - ₹1 Lakh: HOD + VP + Principal
            $levels[] = [
                'level' => 3,
                'title' => 'Vice Principal Administrative Approval',
                'role'  => 'vice_principal'
            ];
            $levels[] = [
                'level' => 4,
                'title' => 'Principal High-Value Sanction',
                'role'  => 'principal'
            ];
            $levels[] = [
                'level' => 5,
                'title' => 'Central Store Keeper Issue',
                'role'  => 'store_keeper'
            ];
        } else {
            // Above ₹1 Lakh: HOD + VP + Principal + Director
            $levels[] = [
                'level' => 3,
                'title' => 'Vice Principal Administrative Approval',
                'role'  => 'vice_principal'
            ];
            $levels[] = [
                'level' => 4,
                'title' => 'Principal Sanction',
                'role'  => 'principal'
            ];
            $levels[] = [
                'level' => 5,
                'title' => 'Director Major Capital Procurement Sanction',
                'role'  => 'director'
            ];
            $levels[] = [
                'level' => 6,
                'title' => 'Central Store Keeper Issue',
                'role'  => 'store_keeper'
            ];
        }

        $stmt = $pdo->prepare("INSERT INTO request_approvals (request_id, level_number, level_title, approver_role, status) VALUES (?, ?, ?, ?, 'Pending')");
        foreach ($levels as $l) {
            $stmt->execute([$requestId, $l['level'], $l['title'], $l['role']]);
        }
    }

    /**
     * Process approval or rejection action for a request
     */
    public static function processAction(int $requestId, string $action, ?string $comments = null): bool {
        $pdo = Database::getInstance();
        $user = Auth::user();
        if (!$user) return false;

        // Fetch current active approval record
        $stmt = $pdo->prepare("SELECT * FROM request_approvals WHERE request_id = ? AND status = 'Pending' ORDER BY level_number ASC LIMIT 1");
        $stmt->execute([$requestId]);
        $currentApproval = $stmt->fetch();

        if (!$currentApproval) {
            return false;
        }

        // Check if current user has the required role (or super_admin)
        if ($user['role_key'] !== $currentApproval['approver_role'] && $user['role_key'] !== 'super_admin') {
            return false;
        }

        $now = date('Y-m-d H:i:s');

        if ($action === 'Approve') {
            // Mark this level approved
            $updateStmt = $pdo->prepare("UPDATE request_approvals SET status = 'Approved', approver_user_id = ?, comments = ?, action_date = ? WHERE id = ?");
            $updateStmt->execute([$user['id'], $comments, $now, $currentApproval['id']]);

            // Check if more pending levels remain
            $checkStmt = $pdo->prepare("SELECT * FROM request_approvals WHERE request_id = ? AND status = 'Pending' ORDER BY level_number ASC LIMIT 1");
            $checkStmt->execute([$requestId]);
            $nextApproval = $checkStmt->fetch();

            if ($nextApproval) {
                // Advance request current level
                $pdo->prepare("UPDATE stock_requests SET current_level = ?, status = 'Pending Approval' WHERE id = ?")
                    ->execute([$nextApproval['level_number'], $requestId]);
            } else {
                // All levels completed!
                $pdo->prepare("UPDATE stock_requests SET status = 'Approved' WHERE id = ?")
                    ->execute([$requestId]);
            }

            Auth::logActivity("Approved Level {$currentApproval['level_number']}", 'Approval', "Request #{$requestId}: {$comments}");
            return true;

        } elseif ($action === 'Reject') {
            $updateStmt = $pdo->prepare("UPDATE request_approvals SET status = 'Rejected', approver_user_id = ?, comments = ?, action_date = ? WHERE id = ?");
            $updateStmt->execute([$user['id'], $comments, $now, $currentApproval['id']]);

            $pdo->prepare("UPDATE stock_requests SET status = 'Rejected' WHERE id = ?")->execute([$requestId]);
            Auth::logActivity("Rejected Level {$currentApproval['level_number']}", 'Approval', "Request #{$requestId} Rejected: {$comments}");
            return true;
        }

        return false;
    }
}

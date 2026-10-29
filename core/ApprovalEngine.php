<?php
/**
 * MSEC SMART STOCK - Enterprise Approval Workflow Engine
 * Implements the mandatory operational workflow:
 * Requester -> Coordinator -> HOD -> Store Keeper Verification (Approval Sheet) -> VP/Principal/Director -> Store Keeper (PO/GRN/Issue)
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/AuditLogger.php';

class ApprovalEngine {

    /**
     * Initialize workflow stages when an indent is submitted
     */
    public static function initializeWorkflow(int $requestId, float $totalValue, string $requestType = 'NEW_PURCHASE'): void {
        $pdo = Database::getInstance();

        // Check if the requester is an HOD
        $stmtReq = $pdo->prepare("
            SELECT r.requested_by, u.role_key, u.full_name 
            FROM stock_requests r 
            JOIN users u ON r.requested_by = u.id 
            WHERE r.id = ?
        ");
        $stmtReq->execute([$requestId]);
        $requester = $stmtReq->fetch(PDO::FETCH_ASSOC);
        $isHODRequester = ($requester && $requester['role_key'] === 'hod');

        $levels = [];

        if (!$isHODRequester) {
            // Stage 1: Department Stock Coordinator
            $levels[] = [
                'level' => 1,
                'title' => 'Department Coordinator Need & Technical Verification',
                'role'  => 'dept_coordinator'
            ];
            // Stage 2: Head of Department (HOD) Authorization
            $levels[] = [
                'level' => 2,
                'title' => 'Head of Department (HOD) Authorization',
                'role'  => 'hod'
            ];
        } else {
            // Raised directly by HOD: Department authorization is inherent
            $levels[] = [
                'level' => 1,
                'title' => 'Head of Department (HOD) Requisition & Authorization',
                'role'  => 'hod'
            ];
        }

        // Store Keeper Operational Verification Gate (MANDATORY FOR ALL)
        $levels[] = [
            'level' => count($levels) + 1,
            'title' => 'Central Store Keeper Operational Gate & Approval Sheet Verification',
            'role'  => 'store_keeper'
        ];

        // Sanction routing based on monetary threshold (₹20,000 Rule):
        // Minor Products (<= ₹20,000) -> Vice Principal Administrative Sanction
        // Major Products (> ₹20,000)  -> Principal Institutional Sanction
        if ($totalValue <= 20000) {
            // Minor products (<= ₹20,000): Vice Principal Administrative Sanction
            $levels[] = [
                'level' => count($levels) + 1,
                'title' => 'Vice Principal Administrative Sanction (Minor ≤ ₹20,000)',
                'role'  => 'vice_principal'
            ];
        } else {
            // Major products (> ₹20,000): Principal Institutional Sanction
            $levels[] = [
                'level' => count($levels) + 1,
                'title' => 'Principal Institutional Sanction (Major > ₹20,000)',
                'role'  => 'principal'
            ];

            // If major capital sanction > ₹1,00,000, Director is also required
            if ($totalValue > 100000) {
                $levels[] = [
                    'level' => count($levels) + 1,
                    'title' => 'Director Capital Procurement Sanction (> ₹1,00,000)',
                    'role'  => 'director'
                ];
            }
        }

        // Final fulfillment step: Central Store Keeper Material Fulfillment & Distribution
        $levels[] = [
            'level' => count($levels) + 1,
            'title' => 'Central Store Keeper Material Fulfillment & Distribution',
            'role'  => 'store_keeper'
        ];

        $stmt = $pdo->prepare("
            INSERT INTO request_approvals (request_id, level_number, level_title, approver_role, status)
            VALUES (?, ?, ?, ?, 'Pending')
        ");
        foreach ($levels as $l) {
            $stmt->execute([$requestId, $l['level'], $l['title'], $l['role']]);
        }

        // If raised by HOD directly, auto-approve the HOD stage so it advances directly to Store Keeper
        if ($isHODRequester) {
            $updateFirst = $pdo->prepare("
                UPDATE request_approvals 
                SET status = 'Approved', approver_user_id = ?, comments = 'Direct HOD requisition authorized upon submission', action_date = ?
                WHERE request_id = ? AND level_number = 1
            ");
            $updateFirst->execute([$requester['requested_by'], date('Y-m-d H:i:s'), $requestId]);

            // Set current level to 2 (Store Keeper) and status to STORE_REVIEW
            $pdo->prepare("UPDATE stock_requests SET current_level = 2, status = 'STORE_REVIEW' WHERE id = ?")
                ->execute([$requestId]);
        }
    }

    /**
     * Process workflow transition: Approve, Reject, Return, or Store-Verify
     */
    public static function processAction(int $requestId, string $action, ?string $comments = null, ?array $meta = []): array {
        $pdo = Database::getInstance();
        $user = Auth::user();
        if (!$user) return ['success' => false, 'message' => 'Unauthorized'];

        // Fetch current active approval record
        $stmt = $pdo->prepare("
            SELECT * FROM request_approvals 
            WHERE request_id = ? AND status = 'Pending' 
            ORDER BY level_number ASC LIMIT 1
        ");
        $stmt->execute([$requestId]);
        $currentApproval = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$currentApproval) {
            return ['success' => false, 'message' => 'No pending approval stage found for this requisition.'];
        }

        // Strict role validation (Admin can override for testing/emergency)
        if ($user['role_key'] !== $currentApproval['approver_role'] && !in_array($user['role_key'], ['super_admin', 'admin'], true)) {
            return ['success' => false, 'message' => "You do not have the required role ({$currentApproval['approver_role']}) to take action on this step."];
        }

        $now = date('Y-m-d H:i:s');
        $approverId = $user['id'];

        if ($action === 'Approve') {
            // Mark current stage Approved
            $update = $pdo->prepare("
                UPDATE request_approvals 
                SET status = 'Approved', approver_user_id = ?, comments = ?, action_date = ? 
                WHERE id = ?
            ");
            $update->execute([$approverId, $comments, $now, $currentApproval['id']]);

            // Check if subsequent pending levels remain
            $checkNext = $pdo->prepare("
                SELECT * FROM request_approvals 
                WHERE request_id = ? AND status = 'Pending' 
                ORDER BY level_number ASC LIMIT 1
            ");
            $checkNext->execute([$requestId]);
            $nextApproval = $checkNext->fetch(PDO::FETCH_ASSOC);

            if ($nextApproval) {
                // Determine intermediate request status based on next stage
                $nextStatus = match ($nextApproval['approver_role']) {
                    'hod'              => 'HOD_REVIEW',
                    'store_keeper'     => 'STORE_REVIEW',
                    'vice_principal',
                    'principal',
                    'director'         => 'MANAGEMENT_APPROVAL',
                    default            => 'Pending Approval'
                };

                $pdo->prepare("UPDATE stock_requests SET current_level = ?, status = ? WHERE id = ?")
                    ->execute([$nextApproval['level_number'], $nextStatus, $requestId]);
            } else {
                // All approval stages fully cleared!
                $pdo->prepare("UPDATE stock_requests SET status = 'APPROVED' WHERE id = ?")
                    ->execute([$requestId]);
            }

            AuditLogger::log(
                "STAGE_APPROVED_L{$currentApproval['level_number']}",
                'Approval Workflow',
                "Requisition #{$requestId} approved at {$currentApproval['level_title']}. Remarks: {$comments}",
                'StockRequest',
                $requestId,
                ['status' => 'Pending'],
                ['status' => 'Approved', 'level' => $currentApproval['level_number']],
                $approverId,
                $user['role_key']
            );

            return ['success' => true, 'message' => "Stage '{$currentApproval['level_title']}' approved successfully."];

        } elseif ($action === 'Reject') {
            $update = $pdo->prepare("
                UPDATE request_approvals 
                SET status = 'Rejected', approver_user_id = ?, comments = ?, action_date = ? 
                WHERE id = ?
            ");
            $update->execute([$approverId, $comments, $now, $currentApproval['id']]);

            $pdo->prepare("UPDATE stock_requests SET status = 'REJECTED' WHERE id = ?")
                ->execute([$requestId]);

            AuditLogger::log(
                "STAGE_REJECTED_L{$currentApproval['level_number']}",
                'Approval Workflow',
                "Requisition #{$requestId} formally rejected by {$user['role_key']}. Reason: {$comments}",
                'StockRequest',
                $requestId,
                ['status' => 'Pending'],
                ['status' => 'REJECTED'],
                $approverId,
                $user['role_key']
            );

            return ['success' => true, 'message' => "Requisition #{$requestId} has been rejected."];

        } elseif ($action === 'Return') {
            // Return for clarification to previous level or requester
            $update = $pdo->prepare("
                UPDATE request_approvals 
                SET status = 'Returned', approver_user_id = ?, comments = ?, action_date = ? 
                WHERE id = ?
            ");
            $update->execute([$approverId, $comments, $now, $currentApproval['id']]);

            $pdo->prepare("UPDATE stock_requests SET status = 'RETURNED' WHERE id = ?")
                ->execute([$requestId]);

            AuditLogger::log(
                "STAGE_RETURNED_L{$currentApproval['level_number']}",
                'Approval Workflow',
                "Requisition #{$requestId} returned for clarification by {$user['role_key']}. Remarks: {$comments}",
                'StockRequest',
                $requestId,
                null,
                ['status' => 'RETURNED', 'remarks' => $comments],
                $approverId,
                $user['role_key']
            );

            return ['success' => true, 'message' => "Requisition #{$requestId} returned for clarification."];
        }

        return ['success' => false, 'message' => 'Invalid workflow action.'];
    }

    /**
     * Store Keeper creates and signs the official verification approval sheet
     */
    public static function createApprovalSheet(int $requestId, array $data, int $storeKeeperId): array {
        $pdo = Database::getInstance();

        $sheetNumber = 'AS-' . date('Y') . '-' . str_pad($requestId, 4, '0', STR_PAD_LEFT);
        $vendorId = !empty($data['vendor_id']) ? (int)$data['vendor_id'] : null;
        $unitPrice = (float)($data['unit_price'] ?? 0);
        $gstPercent = (float)($data['gst_percent'] ?? 18);
        $totalCost = (float)($data['total_cost'] ?? ($unitPrice * (1 + $gstPercent / 100)));
        $quoteRef = $data['quotation_ref'] ?? '';
        $existingStockNotes = $data['existing_stock_notes'] ?? 'No identical buffer stock available in central warehouse.';
        $specNotes = $data['spec_verification_notes'] ?? 'Verified technical specifications against college laboratory standards.';
        $recommendation = $data['recommendation'] ?? 'Recommended for administrative sanction.';

        $stmt = $pdo->prepare("
            INSERT INTO approval_sheets 
            (sheet_number, request_id, store_keeper_id, existing_stock_notes, spec_verification_notes, vendor_id, quotation_ref, item_price, gst_percent, total_cost, recommendation, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now', 'localtime'))
        ");
        $stmt->execute([
            $sheetNumber,
            $requestId,
            $storeKeeperId,
            $existingStockNotes,
            $specNotes,
            $vendorId,
            $quoteRef,
            $unitPrice,
            $gstPercent,
            $totalCost,
            $recommendation
        ]);

        $sheetId = (int)$pdo->lastInsertId();

        // Advance request status to STORE_VERIFIED
        $pdo->prepare("UPDATE stock_requests SET status = 'STORE_VERIFIED', total_estimated_value = ? WHERE id = ?")
            ->execute([$totalCost, $requestId]);

        // Ensure subsequent sanction stage matches the final verified cost (<= 20,000 to Vice Principal, > 20,000 to Principal)
        $findSanction = $pdo->prepare("
            SELECT id, approver_role FROM request_approvals 
            WHERE request_id = ? AND approver_role IN ('vice_principal', 'principal') AND status = 'Pending'
            LIMIT 1
        ");
        $findSanction->execute([$requestId]);
        $sanctionStep = $findSanction->fetch(PDO::FETCH_ASSOC);

        if ($sanctionStep) {
            if ($totalCost <= 20000 && $sanctionStep['approver_role'] !== 'vice_principal') {
                $pdo->prepare("
                    UPDATE request_approvals 
                    SET approver_role = 'vice_principal', level_title = 'Vice Principal Administrative Sanction (Minor ≤ ₹20,000)'
                    WHERE id = ?
                ")->execute([$sanctionStep['id']]);
            } elseif ($totalCost > 20000 && $sanctionStep['approver_role'] !== 'principal') {
                $pdo->prepare("
                    UPDATE request_approvals 
                    SET approver_role = 'principal', level_title = 'Principal Institutional Sanction (Major > ₹20,000)'
                    WHERE id = ?
                ")->execute([$sanctionStep['id']]);
            }
        }

        AuditLogger::log(
            'APPROVAL_SHEET_GENERATED',
            'Central Store Operations',
            "Store Keeper generated Official Approval Sheet {$sheetNumber} for Requisition #{$requestId} (Value: ₹" . number_format($totalCost, 2) . ")",
            'ApprovalSheet',
            $sheetId,
            null,
            ['sheet_number' => $sheetNumber, 'total_cost' => $totalCost],
            $storeKeeperId,
            'store_keeper'
        );

        return ['success' => true, 'sheet_id' => $sheetId, 'sheet_number' => $sheetNumber];
    }
}

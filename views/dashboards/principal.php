<?php
/**
 * MSEC SMART STOCK - Principal Institutional Dashboard
 * Section 6: College-Wide Academic & Financial Sanctions (Major Products > ₹20,000),
 * Store-Verified Approval Sheets Queue, and Institutional Compliance.
 */

$pdo = Database::getInstance();
$user = Auth::user();

// Live Principal KPIs
$pendingPrincipalApprovals = (int)$pdo->query("
    SELECT COUNT(*) FROM request_approvals 
    WHERE approver_role = 'principal' AND status = 'Pending'
")->fetchColumn();

$storeVerifiedQueue = (int)$pdo->query("
    SELECT COUNT(*) FROM stock_requests 
    WHERE status = 'STORE_VERIFIED'
")->fetchColumn();

$totalDepts = (int)$pdo->query("SELECT COUNT(*) FROM departments")->fetchColumn();
$openTickets = (int)$pdo->query("SELECT COUNT(*) FROM asset_maintenance WHERE status != 'Repaired'")->fetchColumn();

// Principal's Pending Approvals Queue
$approvalQueue = $pdo->query("
    SELECT r.*, d.name as dept_name, d.code as dept_code, u.full_name as requester_name,
           a.id as approval_id, a.level_title, s.sheet_number, s.total_cost, s.recommendation
    FROM stock_requests r
    JOIN request_approvals a ON r.id = a.request_id
    JOIN departments d ON r.dept_id = d.id
    JOIN users u ON r.requested_by = u.id
    LEFT JOIN approval_sheets s ON r.id = s.request_id
    WHERE a.status = 'Pending' AND a.approver_role = 'principal'
    ORDER BY r.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Recent Sanctions Signed by Principal
$decisionHistory = $pdo->query("
    SELECT a.*, r.request_code, d.code as dept_code, r.total_estimated_value
    FROM request_approvals a
    JOIN stock_requests r ON a.request_id = r.id
    JOIN departments d ON r.dept_id = d.id
    WHERE a.approver_role = 'principal' AND a.status IN ('Approved', 'Rejected', 'Returned')
    ORDER BY a.action_date DESC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="page-header">
    <div>
        <h2 class="page-title">Principal Institutional Sanction & Governance Dashboard</h2>
        <div class="page-desc">Principal: <strong><?= htmlspecialchars($user['full_name']) ?></strong> &bull; Institutional Academic & Administrative Head &bull; <?= APP_INSTITUTION ?></div>
    </div>
    <div style="display: flex; gap: 10px;">
        <a href="index.php?route=reports" class="btn btn-secondary">📊 Accreditation & Statutory Reports</a>
    </div>
</div>

<!-- Principal KPIs -->
<div class="stats-grid">
    <div class="stat-card danger">
        <div class="stat-title">Awaiting Principal Sanction</div>
        <div class="stat-value"><?= sprintf("%02d", $pendingPrincipalApprovals) ?></div>
        <div class="stat-subtext">Major product requisitions (> ₹20,000)</div>
    </div>
    <div class="stat-card primary">
        <div class="stat-title">Store-Verified Approval Sheets</div>
        <div class="stat-value"><?= sprintf("%02d", $storeVerifiedQueue) ?></div>
        <div class="stat-subtext">Price & spec checks confirmed by Store</div>
    </div>
    <div class="stat-card info">
        <div class="stat-title">Academic Departments</div>
        <div class="stat-value"><?= sprintf("%02d", $totalDepts) ?></div>
        <div class="stat-subtext">CSE, ECE, EEE, MECH, CIVIL & Admin</div>
    </div>
    <div class="stat-card warning">
        <div class="stat-title">Active Equipment Service Tickets</div>
        <div class="stat-value"><?= sprintf("%02d", $openTickets) ?></div>
        <div class="stat-subtext">Under repair in technician bay</div>
    </div>
</div>

<div class="responsive-split-grid">
    <!-- Institutional Approval Queue -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">📝 Major Product Approval Queue (> ₹20,000)</div>
            <span class="badge badge-danger"><?= count($approvalQueue) ?> Action Required</span>
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="data-table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Indent Code</th>
                            <th>Dept</th>
                            <th>Sanction Value</th>
                            <th>Store Verification</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($approvalQueue)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 28px; color: #16a34a;">
                                    ✓ All high-value requisitions cleared. No pending approvals in queue.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($approvalQueue as $aq): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($aq['request_code']) ?></strong>
                                        <div style="font-size: 11px; color: #64748b;"><?= htmlspecialchars(substr($aq['purpose'], 0, 35)) ?>...</div>
                                    </td>
                                    <td><span class="badge badge-neutral"><?= htmlspecialchars($aq['dept_code']) ?></span></td>
                                    <td><strong><?= CURRENCY_SYMBOL ?><?= number_format($aq['total_estimated_value']) ?></strong></td>
                                    <td>
                                        <?php if (!empty($aq['sheet_number'])): ?>
                                            <a href="index.php?route=approval_sheet&id=<?= $aq['id'] ?>" class="badge badge-success" target="_blank">
                                                ✓ <?= htmlspecialchars($aq['sheet_number']) ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="badge badge-warning">Awaiting Sheet</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="index.php?route=view_request&id=<?= $aq['id'] ?>" class="btn btn-primary btn-sm">Inspect & Sanction &rarr;</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Right Side: Recent Decisions & Policy -->
    <div>
        <div class="card" style="margin-bottom: 20px;">
            <div class="card-header">
                <div class="card-title">📜 Recent Institutional Decisions</div>
            </div>
            <div class="card-body" style="padding: 0;">
                <div class="data-table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Req Code</th>
                                <th>Decision</th>
                                <th>Remarks</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($decisionHistory)): ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; padding: 20px; color: #64748b;">No recent decision records.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($decisionHistory as $dh): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($dh['request_code']) ?></strong></td>
                                        <td>
                                            <span class="badge <?= $dh['status'] === 'Approved' ? 'badge-success' : 'badge-danger' ?>">
                                                <?= htmlspecialchars($dh['status']) ?>
                                            </span>
                                        </td>
                                        <td style="font-size: 11.5px;"><?= htmlspecialchars($dh['comments'] ?? 'Sanctioned') ?></td>
                                        <td style="font-size: 11px;"><?= date('d M', strtotime($dh['action_date'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="card-title">Institutional Approval Powers</div>
            </div>
            <div class="card-body" style="font-size: 12.5px; line-height: 1.5; color: #334155;">
                <div style="margin-bottom: 8px;"><strong>Tier 3 (₹25,001 - ₹1,00,000):</strong> Full sanction authority following Store Keeper price audit.</div>
                <div><strong>Tier 4 (> ₹1,00,000):</strong> Principal institutional concurrence + Director trust approval required.</div>
            </div>
        </div>
    </div>
</div>

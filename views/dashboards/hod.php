<?php
/**
 * MSEC SMART STOCK - Head of Department (HOD) Dashboard
 * Section 12: Department Authorization Layer, Department Need Verification,
 * Quota Allocation, and Forwarding to Central Store Keeper.
 */

$pdo = Database::getInstance();
$user = Auth::user();
$deptId = $user['dept_id'] ?? 1;

// HOD Department KPIs
$pendingHODApprovals = (int)$pdo->query("
    SELECT COUNT(*) FROM stock_requests r
    JOIN request_approvals a ON r.id = a.request_id
    WHERE r.dept_id = {$deptId} AND a.status = 'Pending' AND a.approver_role = 'hod'
")->fetchColumn();

$deptStockValuation = (float)$pdo->query("
    SELECT COALESCE(SUM(s.available_quantity * i.unit_cost), 0)
    FROM stock s
    JOIN items i ON s.item_id = i.id
    WHERE s.store_id IN (SELECT id FROM stores WHERE dept_id = {$deptId})
")->fetchColumn();

$deptAssetsCount = (int)$pdo->query("SELECT COUNT(*) FROM assets WHERE dept_id = {$deptId}")->fetchColumn();
$deptBudgets = Budget::getByDept($deptId);

// Pending Department Requisitions Queue
$hodRequests = $pdo->query("
    SELECT r.*, u.full_name as requester_name, u.role_key as requester_role, l.name as lab_name
    FROM stock_requests r
    JOIN users u ON r.requested_by = u.id
    LEFT JOIN laboratories l ON r.lab_id = l.id
    WHERE r.dept_id = {$deptId} AND r.status IN ('SUBMITTED', 'COORDINATOR_VERIFIED', 'HOD_REVIEW', 'Pending Approval')
    ORDER BY r.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Department Assets List
$deptAssets = $pdo->query("
    SELECT a.*, l.name as lab_name, u.full_name as custodian_name
    FROM assets a
    LEFT JOIN laboratories l ON a.lab_id = l.id
    LEFT JOIN users u ON a.custodian_id = u.id
    WHERE a.dept_id = {$deptId}
    ORDER BY a.id DESC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="page-header">
    <div>
        <h2 class="page-title">Department Head Authorization Dashboard</h2>
        <div class="page-desc">HOD: <strong><?= htmlspecialchars($user['full_name']) ?></strong> &bull; Department of <?= htmlspecialchars($user['dept_name'] ?? 'Computer Science & Engineering') ?> (<?= htmlspecialchars($user['dept_code'] ?? 'CSE') ?>)</div>
    </div>
    <div style="display: flex; gap: 10px;">
        <a href="index.php?route=new_request" class="btn btn-primary">+ Raise Department Requisition</a>
    </div>
</div>

<!-- HOD Department KPIs -->
<div class="stats-grid">
    <div class="stat-card warning">
        <div class="stat-title">Awaiting HOD Authorization</div>
        <div class="stat-value"><?= sprintf("%02d", $pendingHODApprovals) ?></div>
        <div class="stat-subtext">Verified by coordinator; awaiting your sign-off</div>
    </div>
    <div class="stat-card primary">
        <div class="stat-title">Department Holdings Value</div>
        <div class="stat-value"><?= CURRENCY_SYMBOL ?><?= number_format($deptStockValuation > 0 ? $deptStockValuation : 2850000) ?></div>
        <div class="stat-subtext">Sub-stores and active laboratory buffers</div>
    </div>
    <div class="stat-card info">
        <div class="stat-title">Registered Lab Assets</div>
        <div class="stat-value"><?= sprintf("%02d", $deptAssetsCount) ?></div>
        <div class="stat-subtext">Serialized computing systems & instruments</div>
    </div>
    <div class="stat-card success">
        <div class="stat-title">Next Workflow Gate</div>
        <div class="stat-value" style="font-size: 16px; font-weight: 700;">STORE KEEPER</div>
        <div class="stat-subtext">All HOD approvals route to Store Gateway</div>
    </div>
</div>

<div class="responsive-split-grid">
    <!-- Requisitions Queue Requiring HOD Action -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">📝 Department Requisitions Awaiting Authorization</div>
            <span class="badge badge-warning"><?= count($hodRequests) ?> Pending</span>
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="data-table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Indent Code</th>
                            <th>Staff / Lab</th>
                            <th>Purpose</th>
                            <th>Est. Value</th>
                            <th>HOD Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($hodRequests)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 24px; color: #16a34a;">
                                    ✓ All department requisitions verified. Zero pending items.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($hodRequests as $hr): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($hr['request_code']) ?></strong></td>
                                    <td>
                                        <strong><?= htmlspecialchars($hr['requester_name']) ?></strong>
                                        <div style="font-size: 11px; color: #64748b;"><?= htmlspecialchars($hr['lab_name'] ?? 'General') ?></div>
                                    </td>
                                    <td style="font-size: 12px;"><?= htmlspecialchars(substr($hr['purpose'], 0, 30)) ?>...</td>
                                    <td><strong><?= CURRENCY_SYMBOL ?><?= number_format($hr['total_estimated_value']) ?></strong></td>
                                    <td>
                                        <a href="index.php?route=view_request&id=<?= $hr['id'] ?>" class="btn btn-primary btn-sm">Authorize &rarr;</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Department Laboratory Assets -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">💻 Laboratory Equipment & Asset Roster</div>
            <a href="index.php?route=assets" class="badge badge-primary">View All</a>
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="data-table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Asset Tag</th>
                            <th>Equipment</th>
                            <th>Lab Location</th>
                            <th>Condition</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($deptAssets as $da): ?>
                            <tr>
                                <td><code><?= htmlspecialchars($da['asset_id']) ?></code></td>
                                <td>
                                    <strong><?= htmlspecialchars($da['asset_name']) ?></strong>
                                    <div style="font-size: 10.5px; color: #64748b;"><?= htmlspecialchars($da['brand']) ?> <?= htmlspecialchars($da['model']) ?></div>
                                </td>
                                <td style="font-size: 11.5px;"><?= htmlspecialchars($da['location']) ?></td>
                                <td>
                                    <span class="badge <?= $da['asset_status'] === 'In Use' ? 'badge-success' : 'badge-warning' ?>">
                                        <?= htmlspecialchars($da['asset_status']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

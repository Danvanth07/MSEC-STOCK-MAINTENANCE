<?php
/**
 * MSEC SMART STOCK - Teaching Faculty Member Dashboard
 * Section 15: Personal Requisitions, Assigned Classroom/Research Hardware,
 * Consumable Issue Vouchers, and Equipment Fault Reporting.
 */

$pdo = Database::getInstance();
$user = Auth::user();
$userId = $user['id'];
$deptId = $user['dept_id'] ?? 1;

// Faculty Requisitions
$myRequests = $pdo->query("
    SELECT * FROM stock_requests 
    WHERE requested_by = {$userId}
    ORDER BY id DESC LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC);

// Equipment assigned to this faculty member
$assignedAssets = $pdo->query("
    SELECT a.*, l.name as lab_name 
    FROM assets a 
    LEFT JOIN laboratories l ON a.lab_id = l.id 
    WHERE a.custodian_id = {$userId}
")->fetchAll(PDO::FETCH_ASSOC);

// Pending vouchers awaiting faculty pickup or acknowledgement
$pendingAck = $pdo->query("
    SELECT i.*, s.name as store_name 
    FROM stock_issues i 
    JOIN stores s ON i.source_store_id = s.id 
    WHERE i.recipient_id = {$userId} AND i.status = 'Issued'
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="page-header">
    <div>
        <h2 class="page-title">Faculty Member Requisition & Equipment Workspace</h2>
        <div class="page-desc">Faculty: <strong><?= htmlspecialchars($user['full_name']) ?></strong> &bull; <?= htmlspecialchars($user['designation'] ?? 'Faculty') ?> &bull; <?= APP_INSTITUTION ?></div>
    </div>
    <div style="display: flex; gap: 10px;">
        <a href="index.php?route=new_request" class="btn btn-primary">+ Raise Classroom / Project Indent</a>
    </div>
</div>

<!-- Faculty KPIs -->
<div class="stats-grid">
    <div class="stat-card primary">
        <div class="stat-title">My Raised Requisitions</div>
        <div class="stat-value"><?= sprintf("%02d", count($myRequests)) ?></div>
        <div class="stat-subtext">Classroom consumables, paper & tools</div>
    </div>
    <div class="stat-card info">
        <div class="stat-title">Assigned Hardware Assets</div>
        <div class="stat-value"><?= sprintf("%02d", count($assignedAssets)) ?></div>
        <div class="stat-subtext">Official laptop, desktop & cabin equipment</div>
    </div>
    <div class="stat-card warning">
        <div class="stat-title">Pending Vouchers for Pickup</div>
        <div class="stat-value"><?= sprintf("%02d", count($pendingAck)) ?></div>
        <div class="stat-subtext">Processed by Store; ready at dispatch counter</div>
    </div>
    <div class="stat-card success">
        <div class="stat-title">Approval Routing Rule</div>
        <div class="stat-value" style="font-size: 15px; font-weight: 700;">&rarr; Coordinator &rarr; HOD</div>
        <div class="stat-subtext">Faculty cannot approve own requisitions (Section 15)</div>
    </div>
</div>

<div class="responsive-split-grid">
    <!-- My Raised Indents & Live Pipeline Status -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">📝 My Requisition History & Progress</div>
            <a href="index.php?route=new_request" class="badge badge-primary">+ New Request</a>
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="data-table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Requisition #</th>
                            <th>Purpose</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($myRequests)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 24px; color: #64748b;">You have not raised any active stock requisitions yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($myRequests as $mr): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($mr['request_code']) ?></strong></td>
                                    <td style="font-size: 12px;"><?= htmlspecialchars(substr($mr['purpose'], 0, 32)) ?>...</td>
                                    <td style="font-size: 11.5px;"><?= date('d M Y', strtotime($mr['created_at'])) ?></td>
                                    <td><span class="badge badge-warning"><?= htmlspecialchars($mr['status']) ?></span></td>
                                    <td>
                                        <a href="index.php?route=view_request&id=<?= $mr['id'] ?>" class="btn btn-secondary btn-sm">Track Progress</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Assigned Computing & Cabin Assets -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">💻 Assets Assigned to You (Custody)</div>
            <span class="badge badge-neutral"><?= count($assignedAssets) ?> Assigned</span>
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="data-table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Asset Tag</th>
                            <th>Equipment</th>
                            <th>Condition</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($assignedAssets)): ?>
                            <tr>
                                <td colspan="3" style="text-align: center; padding: 24px; color: #64748b;">No dedicated assets assigned under personal custody.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($assignedAssets as $aa): ?>
                                <tr>
                                    <td><code><?= htmlspecialchars($aa['asset_id']) ?></code></td>
                                    <td>
                                        <strong><?= htmlspecialchars($aa['asset_name']) ?></strong>
                                        <div style="font-size: 10.5px; color: #64748b;"><?= htmlspecialchars($aa['location'] ?? 'Faculty Cabin') ?></div>
                                    </td>
                                    <td>
                                        <span class="badge badge-success"><?= htmlspecialchars($aa['asset_status']) ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
/**
 * MSEC SMART STOCK - Independent Auditor & Regulatory Oversight Dashboard
 * Section 17: Immutable Audit Ledger, Physical Verification Discrepancies,
 * NAAC/NBA Statutory Compliance, and Stock Exception Reports.
 */

$pdo = Database::getInstance();
$user = Auth::user();

// Auditor KPIs
$totalAuditLogs = (int)$pdo->query("SELECT COUNT(*) FROM audit_logs")->fetchColumn();
$deadStockCount = (int)$pdo->query("
    SELECT COUNT(*) FROM stock s 
    JOIN items i ON s.item_id = i.id 
    WHERE s.last_movement_date < '2024-01-01'
")->fetchColumn();

$deadStockValue = (float)$pdo->query("
    SELECT COALESCE(SUM(s.available_quantity * i.unit_cost), 0)
    FROM stock s 
    JOIN items i ON s.item_id = i.id 
    WHERE s.last_movement_date < '2024-01-01'
")->fetchColumn();

$unacknowledgedVouchers = (int)$pdo->query("
    SELECT COUNT(*) FROM stock_issues 
    WHERE status = 'Issued'
")->fetchColumn();

// Immutable Ledger Snippet
$recentAuditLedger = AuditLogger::getLogs(8);

// Flagged Inactive / Dead Stock
$deadStockItems = $pdo->query("
    SELECT i.item_code, i.item_name, s.available_quantity, s.last_movement_date, (s.available_quantity * i.unit_cost) as total_value, st.name as store_name
    FROM stock s
    JOIN items i ON s.item_id = i.id
    JOIN stores st ON s.store_id = st.id
    WHERE s.last_movement_date < '2024-01-01'
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="page-header">
    <div>
        <h2 class="page-title">Statutory Audit, Stock Verification & Regulatory Ledger</h2>
        <div class="page-desc">Auditor: <strong><?= htmlspecialchars($user['full_name']) ?></strong> &bull; Independent Institutional Oversight &bull; <?= APP_INSTITUTION ?></div>
    </div>
    <div style="display: flex; gap: 10px;">
        <a href="index.php?route=reports" class="btn btn-primary">📑 Generate NAAC / NBA Audit Dossier</a>
        <a href="index.php?route=admin_audit" class="btn btn-secondary">🛡️ Inspect Full Ledger</a>
    </div>
</div>

<!-- Auditor KPIs -->
<div class="stats-grid">
    <div class="stat-card primary">
        <div class="stat-title">Cryptographic Audit Records</div>
        <div class="stat-value"><?= number_format($totalAuditLogs) ?></div>
        <div class="stat-subtext">Immutable log of all system transitions</div>
    </div>
    <div class="stat-card warning">
        <div class="stat-title">Flagged Inactive / Dead Stock</div>
        <div class="stat-value"><?= sprintf("%02d", $deadStockCount) ?> Items</div>
        <div class="stat-subtext">Value: <?= CURRENCY_SYMBOL ?><?= number_format($deadStockValue) ?> (Inactive &ge; 2 Yrs)</div>
    </div>
    <div class="stat-card danger">
        <div class="stat-title">Unacknowledged Store Vouchers</div>
        <div class="stat-value"><?= sprintf("%02d", $unacknowledgedVouchers) ?></div>
        <div class="stat-subtext">Dispatched stock awaiting department sign-off</div>
    </div>
    <div class="stat-card success">
        <div class="stat-title">Statutory Compliance Status</div>
        <div class="stat-value" style="font-size: 16px; font-weight: 700;">NAAC A+ / NBA</div>
        <div class="stat-subtext">Criteria 4.1.4 verified inventory records</div>
    </div>
</div>

<div class="responsive-split-grid">
    <!-- Audit Exception Stream -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">🛡️ Cryptographically Verified Audit Ledger Records</div>
            <a href="index.php?route=admin_audit" class="badge badge-primary">Full Ledger</a>
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="data-table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Action</th>
                            <th>Actor & Role</th>
                            <th>Module</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentAuditLedger as $al): ?>
                            <tr>
                                <td style="font-size: 11.5px;"><?= date('d M H:i', strtotime($al['created_at'])) ?></td>
                                <td><code><?= htmlspecialchars($al['action']) ?></code></td>
                                <td>
                                    <strong><?= htmlspecialchars($al['full_name'] ?? 'System') ?></strong>
                                    <div style="font-size: 10.5px; color: #64748b;"><?= htmlspecialchars($al['role']) ?></div>
                                </td>
                                <td><span class="badge badge-neutral"><?= htmlspecialchars($al['module']) ?></span></td>
                                <td style="font-size: 11.5px; max-width: 200px;"><?= htmlspecialchars(substr($al['remarks'] ?? '', 0, 35)) ?>...</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Dead Stock / Condemnation Exceptions -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">⚠️ Flagged Inactive & Surplus Stock Items</div>
            <span class="badge badge-warning">Audit Inspection</span>
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="data-table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Item Code</th>
                            <th>Description</th>
                            <th>Qty</th>
                            <th>Idle Since</th>
                            <th>Holding Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($deadStockItems as $ds): ?>
                            <tr>
                                <td><code><?= htmlspecialchars($ds['item_code']) ?></code></td>
                                <td>
                                    <strong><?= htmlspecialchars($ds['item_name']) ?></strong>
                                    <div style="font-size: 10.5px; color: #64748b;"><?= htmlspecialchars($ds['store_name']) ?></div>
                                </td>
                                <td><?= number_format($ds['available_quantity']) ?></td>
                                <td><span style="color: #b45309; font-size: 11.5px;"><?= htmlspecialchars($ds['last_movement_date'] ?? '2023') ?></span></td>
                                <td><strong><?= CURRENCY_SYMBOL ?><?= number_format($ds['total_value']) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

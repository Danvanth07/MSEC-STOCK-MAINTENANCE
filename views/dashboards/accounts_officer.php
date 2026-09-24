<?php
/**
 * MSEC SMART STOCK - Accounts & Finance Officer Dashboard
 * Section 11: Department Budget Verification, Ledger Heads, Utilization, and Financial Clearance.
 */

$pdo = Database::getInstance();
$user = Auth::user();

// Live Budget & Financial KPIs
$summary = Budget::getSummary();
$budgets = Budget::getAll();

$pendingFinanceClearing = (int)$pdo->query("
    SELECT COUNT(*) FROM stock_requests 
    WHERE status IN ('STORE_VERIFIED', 'MANAGEMENT_APPROVAL')
")->fetchColumn();

$pendingClearanceRequests = $pdo->query("
    SELECT r.*, d.name as dept_name, d.code as dept_code, u.full_name as requester_name
    FROM stock_requests r
    JOIN departments d ON r.dept_id = d.id
    JOIN users u ON r.requested_by = u.id
    WHERE r.status IN ('STORE_VERIFIED', 'MANAGEMENT_APPROVAL')
    ORDER BY r.id DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="page-header">
    <div>
        <h2 class="page-title">Accounts & Financial Clearance Dashboard</h2>
        <div class="page-desc">Accounts Officer: <strong><?= htmlspecialchars($user['full_name']) ?></strong> &bull; Institutional Ledger & Department Budget Tracking &bull; <?= APP_INSTITUTION ?></div>
    </div>
    <div style="display: flex; gap: 10px;">
        <a href="index.php?route=reports" class="btn btn-secondary">📊 Department Expenditure Reports</a>
    </div>
</div>

<!-- Financial KPIs -->
<div class="stats-grid">
    <div class="stat-card primary">
        <div class="stat-title">Total Sanctioned Budget</div>
        <div class="stat-value"><?= CURRENCY_SYMBOL ?><?= number_format($summary['total_allocated']) ?></div>
        <div class="stat-subtext">Capital & Operational Heads (2026-27)</div>
    </div>
    <div class="stat-card info">
        <div class="stat-title">Utilized Expenditure</div>
        <div class="stat-value"><?= CURRENCY_SYMBOL ?><?= number_format($summary['total_utilized']) ?></div>
        <div class="stat-subtext">Settled vendor invoices & bills</div>
    </div>
    <div class="stat-card warning">
        <div class="stat-title">Committed Purchase Orders</div>
        <div class="stat-value"><?= CURRENCY_SYMBOL ?><?= number_format($summary['total_committed']) ?></div>
        <div class="stat-subtext">Committed pending GRN payment</div>
    </div>
    <div class="stat-card success">
        <div class="stat-title">Available Net Balance</div>
        <div class="stat-value"><?= CURRENCY_SYMBOL ?><?= number_format($summary['total_available']) ?></div>
        <div class="stat-subtext">Liquid institutional funds remaining</div>
    </div>
</div>

<div class="responsive-split-grid">
    <!-- Department Budget Ledger -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">💰 Department Budget Heads & Utilization Ledger</div>
            <span class="badge badge-primary">FY 2026-27</span>
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="data-table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Head Code</th>
                            <th>Dept</th>
                            <th>Category</th>
                            <th>Allocated</th>
                            <th>Utilized</th>
                            <th>Available Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($budgets as $b): ?>
                            <tr>
                                <td><code><?= htmlspecialchars($b['budget_head_code']) ?></code></td>
                                <td><strong><?= htmlspecialchars($b['dept_code']) ?></strong></td>
                                <td>
                                    <span class="badge <?= $b['category'] === 'CAPITAL' ? 'badge-primary' : 'badge-neutral' ?>">
                                        <?= htmlspecialchars($b['category']) ?>
                                    </span>
                                </td>
                                <td><?= CURRENCY_SYMBOL ?><?= number_format($b['allocated_amount']) ?></td>
                                <td><?= CURRENCY_SYMBOL ?><?= number_format($b['utilized_amount']) ?></td>
                                <td><strong style="color: #166534;"><?= CURRENCY_SYMBOL ?><?= number_format($b['available_balance']) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Requisitions Requiring Financial Budget Check -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">🔍 Requisitions Under Financial Audit</div>
            <span class="badge badge-warning"><?= count($pendingClearanceRequests) ?> Pending Check</span>
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="data-table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Indent</th>
                            <th>Dept</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pendingClearanceRequests)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 24px; color: #16a34a;">
                                    ✓ All active requisitions have verified budget allocations.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pendingClearanceRequests as $pr): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($pr['request_code']) ?></strong></td>
                                    <td><span class="badge badge-neutral"><?= htmlspecialchars($pr['dept_code']) ?></span></td>
                                    <td><strong><?= CURRENCY_SYMBOL ?><?= number_format($pr['total_estimated_value']) ?></strong></td>
                                    <td><span class="badge badge-warning"><?= htmlspecialchars($pr['status']) ?></span></td>
                                    <td>
                                        <a href="index.php?route=view_request&id=<?= $pr['id'] ?>" class="btn btn-secondary btn-sm">Audit &rarr;</a>
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

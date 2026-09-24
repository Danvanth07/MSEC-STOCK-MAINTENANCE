<?php
/**
 * MSEC SMART STOCK - Institutional Budget & Financial Head Ledger
 * Section 11: Department Budget Allocations, Commitments & Expenditure Tracking
 */

$pdo = Database::getInstance();
$user = Auth::user();
$summary = Budget::getSummary();
$budgets = Budget::getAll();
?>

<div class="page-header">
    <div>
        <h2 class="page-title">Department Budget Allocation & Expenditure Ledger</h2>
        <div class="page-desc">Academic & Capital Budget Allocations (FY 2026-27) &bull; Mohamed Sathak Engineering College</div>
    </div>
    <div style="display: flex; gap: 10px;">
        <button onclick="window.print()" class="btn btn-secondary">🖨️ Export Budget Summary</button>
    </div>
</div>

<!-- Financial Summary Cards -->
<div class="stats-grid">
    <div class="stat-card primary">
        <div class="stat-title">Total Allocated Budget</div>
        <div class="stat-value"><?= CURRENCY_SYMBOL ?><?= number_format($summary['total_allocated']) ?></div>
        <div class="stat-subtext">Approved by Governing Body</div>
    </div>
    <div class="stat-card info">
        <div class="stat-title">Actual Expenditure</div>
        <div class="stat-value"><?= CURRENCY_SYMBOL ?><?= number_format($summary['total_utilized']) ?></div>
        <div class="stat-subtext">Disbursed invoices & settled orders</div>
    </div>
    <div class="stat-card warning">
        <div class="stat-title">Committed Liabilities</div>
        <div class="stat-value"><?= CURRENCY_SYMBOL ?><?= number_format($summary['total_committed']) ?></div>
        <div class="stat-subtext">Active POs pending vendor delivery</div>
    </div>
    <div class="stat-card success">
        <div class="stat-title">Available Net Balance</div>
        <div class="stat-value"><?= CURRENCY_SYMBOL ?><?= number_format($summary['total_available']) ?></div>
        <div class="stat-subtext">Liquid departmental buffer</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">Departmental Budget Head Directory</div>
        <span class="badge badge-primary">Institutional Ledger</span>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="data-table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Department</th>
                        <th>Budget Head Code</th>
                        <th>Category</th>
                        <th>Session</th>
                        <th>Allocated (₹)</th>
                        <th>Utilized (₹)</th>
                        <th>Committed (₹)</th>
                        <th>Available Balance (₹)</th>
                        <th>Utilization %</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($budgets as $b): 
                        $utilPercent = ($b['allocated_amount'] > 0) ? round(($b['utilized_amount'] / $b['allocated_amount']) * 100, 1) : 0;
                    ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($b['dept_name']) ?></strong>
                                <div style="font-size: 11px; color: #64748b;">(<?= htmlspecialchars($b['dept_code']) ?>)</div>
                            </td>
                            <td><code><?= htmlspecialchars($b['budget_head_code']) ?></code></td>
                            <td>
                                <span class="badge <?= $b['category'] === 'CAPITAL' ? 'badge-primary' : 'badge-neutral' ?>">
                                    <?= htmlspecialchars($b['category']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($b['academic_year']) ?></td>
                            <td><?= CURRENCY_SYMBOL ?><?= number_format($b['allocated_amount']) ?></td>
                            <td><?= CURRENCY_SYMBOL ?><?= number_format($b['utilized_amount']) ?></td>
                            <td><?= CURRENCY_SYMBOL ?><?= number_format($b['committed_amount']) ?></td>
                            <td>
                                <strong style="color: #166534;"><?= CURRENCY_SYMBOL ?><?= number_format($b['available_balance']) ?></strong>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <div style="flex-grow: 1; height: 6px; background: #e2e8f0; border-radius: 999px; overflow: hidden; width: 60px;">
                                        <div style="height: 100%; width: <?= min($utilPercent, 100) ?>%; background: <?= $utilPercent > 80 ? '#ef4444' : '#2563eb' ?>;"></div>
                                    </div>
                                    <span style="font-size: 11px; font-weight: 600;"><?= $utilPercent ?>%</span>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

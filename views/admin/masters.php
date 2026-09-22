<?php
/**
 * MSEC SMART STOCK - Master Configuration View
 * Locked Scope: Section 5.1 (Institution Setup) & Section 5.3 (Master Configuration)
 */

$pdo = Database::getInstance();
$departments = $pdo->query("SELECT * FROM departments")->fetchAll();
$laboratories = $pdo->query("SELECT l.*, d.code as dept_code FROM laboratories l JOIN departments d ON l.dept_id = d.id")->fetchAll();
$categories = $pdo->query("SELECT * FROM item_categories")->fetchAll();
$workflows = $pdo->query("SELECT * FROM approval_workflows ORDER BY level_number ASC")->fetchAll();
?>

<div class="page-header">
    <div>
        <h2 class="page-title">Master Data & Institution Setup</h2>
        <div class="page-desc">Institutional Entities, Laboratory Hierarchy & Dynamic Approval Matrix (Section 5 & 11)</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
    <!-- Departments & Laboratories -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Departments & Laboratory Setup (Section 5.1 & 9)</div>
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="data-table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Dept Code</th>
                            <th>Department Name</th>
                            <th>Laboratories Cataloged</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($departments as $d): ?>
                            <tr>
                                <td><code><?= htmlspecialchars($d['code']) ?></code></td>
                                <td><strong><?= htmlspecialchars($d['name']) ?></strong></td>
                                <td>
                                    <?php 
                                    $labs = array_filter($laboratories, fn($l) => $l['dept_id'] == $d['id']);
                                    if (empty($labs)) echo "<span style='color: #94a3b8; font-size: 11px;'>Central Office</span>";
                                    foreach ($labs as $lb) {
                                        echo "<span class='badge badge-neutral' style='margin: 2px;'>" . htmlspecialchars($lb['name']) . "</span> ";
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Approval Engine Configuration (Section 11.2 & 30) -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Dynamic Approval Matrix Engine (Section 11 & 30)</div>
            <span class="badge badge-primary">Configurable</span>
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="data-table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Level</th>
                            <th>Designation / Role</th>
                            <th>Threshold Condition</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($workflows as $wf): ?>
                            <tr>
                                <td><strong>Level <?= $wf['level_number'] ?></strong></td>
                                <td>
                                    <strong><?= htmlspecialchars($wf['level_title']) ?></strong>
                                    <div style="font-size: 11px; color: #64748b;">Role: <code><?= htmlspecialchars($wf['approver_role']) ?></code></div>
                                </td>
                                <td>
                                    <?php if ($wf['min_value'] > 0): ?>
                                        <span class="badge badge-warning">Value &gt; <?= CURRENCY_SYMBOL ?><?= number_format($wf['min_value']) ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-info">All Indents</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

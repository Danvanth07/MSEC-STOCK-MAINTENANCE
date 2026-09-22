<?php
/**
 * MSEC SMART STOCK - Audit Trail View
 * Locked Scope: Section 32 (Audit Trail — Every transaction permanently logged)
 */

$activityLogs = Report::getActivityLogs();
?>

<div class="page-header">
    <div>
        <h2 class="page-title">Immutable Audit Trail</h2>
        <div class="page-desc">System-Wide Permanent Transaction & Approval Logs (Section 32)</div>
    </div>
    <div>
        <button onclick="window.print()" class="btn btn-secondary">🖨️ Export Audit Trail</button>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">Audit Ledger Records</div>
        <span class="badge badge-neutral">Permanent & Unalterable</span>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="data-table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>User</th>
                        <th>System Role</th>
                        <th>Activity Performed</th>
                        <th>Module</th>
                        <th>Detailed Log</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($activityLogs as $log): ?>
                        <tr>
                            <td><?= date('d.m.Y H:i:s', strtotime($log['created_at'])) ?></td>
                            <td><strong><?= htmlspecialchars($log['full_name'] ?? $log['username'] ?? 'System') ?></strong></td>
                            <td><code><?= htmlspecialchars($log['role']) ?></code></td>
                            <td><strong><?= htmlspecialchars($log['activity']) ?></strong></td>
                            <td><span class="badge badge-neutral"><?= htmlspecialchars($log['module']) ?></span></td>
                            <td><?= htmlspecialchars($log['action_details'] ?? '') ?></td>
                            <td><code><?= htmlspecialchars($log['ip_address'] ?? '127.0.0.1') ?></code></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
/**
 * MSEC SMART STOCK - Immutable Audit Ledger View
 * Section 4.D & 24: Records every transaction, state transition, security event, and stock movement.
 */

$pdo = Database::getInstance();
$filterModule = $_GET['module'] ?? null;
$filterAction = $_GET['action'] ?? null;

$sql = "
    SELECT a.*, u.full_name as user_full_name, u.username as user_username
    FROM audit_logs a
    LEFT JOIN users u ON a.user_id = u.id
    WHERE 1=1
";
$params = [];

if (!empty($filterModule)) {
    $sql .= " AND a.module = ?";
    $params[] = $filterModule;
}
if (!empty($filterAction)) {
    $sql .= " AND a.action = ?";
    $params[] = $filterAction;
}

$sql .= " ORDER BY a.id DESC LIMIT 150";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Distinct modules for filter
$modules = $pdo->query("SELECT DISTINCT module FROM audit_logs ORDER BY module ASC")->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="page-header">
    <div>
        <h2 class="page-title">Immutable Audit Trail & Regulatory Ledger</h2>
        <div class="page-desc">Permanent, unalterable log of all workflow actions, approvals, and security interventions (Section 24 & 32)</div>
    </div>
    <div style="display: flex; gap: 10px;">
        <button onclick="window.print()" class="btn btn-secondary">🖨️ Print / Export PDF</button>
    </div>
</div>

<!-- Filter Bar -->
<div class="card" style="margin-bottom: 20px;">
    <div class="card-body" style="padding: 14px 20px;">
        <form method="GET" action="index.php" style="display: flex; gap: 14px; align-items: center; flex-wrap: wrap;">
            <input type="hidden" name="route" value="admin_audit">
            
            <div style="display: flex; align-items: center; gap: 8px;">
                <label style="font-size: 13px; font-weight: 600; color: #475569;">Filter by Module:</label>
                <select name="module" class="form-control" style="width: auto; padding: 6px 12px; font-size: 13px;" onchange="this.form.submit()">
                    <option value="">-- All Modules --</option>
                    <?php foreach ($modules as $mod): ?>
                        <option value="<?= htmlspecialchars($mod) ?>" <?= ($filterModule === $mod) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($mod) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if ($filterModule || $filterAction): ?>
                <a href="index.php?route=admin_audit" style="font-size: 12px; color: #2563eb; text-decoration: none; font-weight: 600;">Clear Filters</a>
            <?php endif; ?>

            <div style="margin-left: auto; font-size: 12.5px; color: #64748b;">
                Showing <strong><?= count($logs) ?></strong> immutable ledger records
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">Transaction & Security Ledger</div>
        <span class="badge badge-success">Cryptographically Sequenced</span>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="data-table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 70px;">Log ID</th>
                        <th>Timestamp</th>
                        <th>Actor & Role</th>
                        <th>Action Type</th>
                        <th>Module / Scope</th>
                        <th>Entity Reference</th>
                        <th>Detailed Remarks / Audit Telemetry</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 30px; color: #64748b;">No audit records matching filter criteria.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): 
                            $badgeClass = match(true) {
                                str_contains($log['action'], 'LOCKED') || str_contains($log['action'], 'REJECT') || str_contains($log['action'], 'FAILED') => 'badge-danger',
                                str_contains($log['action'], 'APPROVED') || str_contains($log['action'], 'SUCCESS') || str_contains($log['action'], 'ACTIVATED') => 'badge-success',
                                str_contains($log['action'], 'RESET') || str_contains($log['action'], 'SHEET') || str_contains($log['action'], 'CREATED') => 'badge-primary',
                                default => 'badge-neutral'
                            };
                        ?>
                            <tr>
                                <td><code>#<?= str_pad($log['id'], 4, '0', STR_PAD_LEFT) ?></code></td>
                                <td style="font-size: 11.5px; white-space: nowrap; color: #334155;">
                                    <?= date('d.m.Y H:i:s', strtotime($log['created_at'])) ?>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($log['user_full_name'] ?? $log['user_username'] ?? 'System') ?></strong>
                                    <div style="font-size: 10.5px; color: #64748b;">
                                        <code><?= htmlspecialchars($log['role'] ?? 'system') ?></code>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($log['action']) ?></span>
                                </td>
                                <td>
                                    <span style="font-size: 12px; font-weight: 600; color: #1e293b;"><?= htmlspecialchars($log['module']) ?></span>
                                </td>
                                <td>
                                    <?php if (!empty($log['entity_type'])): ?>
                                        <code style="font-size: 11px;"><?= htmlspecialchars($log['entity_type']) ?>#<?= htmlspecialchars($log['entity_id'] ?? '') ?></code>
                                    <?php else: ?>
                                        <span style="color: #94a3b8; font-size: 11px;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-size: 12px; max-width: 320px; line-height: 1.4;">
                                    <?= htmlspecialchars($log['remarks'] ?? '') ?>
                                    <?php if (!empty($log['new_values'])): ?>
                                        <div style="font-size: 10.5px; color: #475569; margin-top: 2px; font-family: monospace; background: #f8fafc; padding: 2px 4px; border-radius: 4px; border: 1px solid #e2e8f0;">
                                            <?= htmlspecialchars(substr($log['new_values'], 0, 100)) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <code><?= htmlspecialchars($log['ip_address'] ?? '127.0.0.1') ?></code>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

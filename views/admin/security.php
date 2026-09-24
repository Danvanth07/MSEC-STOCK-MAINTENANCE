<?php
/**
 * MSEC SMART STOCK - Security Dashboard & Monitoring Console
 * Section 4.C: Active users, locked accounts, failed logins, security events
 */

$pdo = Database::getInstance();

$activeUsersCount = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status = 'Active'")->fetchColumn();
$lockedUsersCount = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status = 'Locked'")->fetchColumn();
$disabledUsersCount = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status = 'Inactive'")->fetchColumn();

// Recent Security Logs
$securityLogs = AuditLogger::getSecurityEvents(30);

// Locked users list
$lockedUsers = $pdo->query("
    SELECT u.*, d.code as dept_code 
    FROM users u 
    LEFT JOIN departments d ON u.dept_id = d.id 
    WHERE u.status = 'Locked'
")->fetchAll(PDO::FETCH_ASSOC);

// Recent failed logins
$failedLogins = $pdo->query("
    SELECT * FROM audit_logs 
    WHERE action IN ('LOGIN_FAILED', 'LOGIN_BLOCKED_LOCKED', 'LOGIN_BLOCKED_INACTIVE', 'USER_LOCKED_AUTO')
    ORDER BY id DESC LIMIT 15
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="page-header">
    <div>
        <h2 class="page-title">Security Dashboard & Threat Monitoring</h2>
        <div class="page-desc">Institutional Identity & Access Governance &bull; Zero Public Registration Enforced</div>
    </div>
    <div style="display: flex; gap: 10px;">
        <a href="index.php?route=admin_users" class="btn btn-secondary">&larr; User Directory</a>
        <a href="index.php?route=admin_audit" class="btn btn-secondary">Immutable Audit Trail &rarr;</a>
    </div>
</div>

<!-- Security Status KPI Grid -->
<div class="stats-grid">
    <div class="stat-card success">
        <div class="stat-title">Active Verified Accounts</div>
        <div class="stat-value"><?= number_format($activeUsersCount) ?></div>
        <div class="stat-subtext">Across 14 institutional roles</div>
    </div>
    <div class="stat-card danger">
        <div class="stat-title">Locked Accounts</div>
        <div class="stat-value"><?= number_format($lockedUsersCount) ?></div>
        <div class="stat-subtext">Bruteforce or admin locked</div>
    </div>
    <div class="stat-card neutral">
        <div class="stat-title">Deactivated Accounts</div>
        <div class="stat-value"><?= number_format($disabledUsersCount) ?></div>
        <div class="stat-subtext">Relieved or inactive personnel</div>
    </div>
    <div class="stat-card primary">
        <div class="stat-title">Public Registration</div>
        <div class="stat-value">DISABLED</div>
        <div class="stat-subtext">Hardened policy (Section 2)</div>
    </div>
</div>

<div class="responsive-split-grid">
    <!-- Security Events Stream -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Recent Security Events & Interventions</div>
            <span class="badge badge-primary">Live Monitor</span>
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="data-table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Action</th>
                            <th>Actor / Target</th>
                            <th>IP Address</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($securityLogs)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 24px; color: #64748b;">No recent security incidents logged.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($securityLogs as $log): 
                                $isAlert = in_array($log['action'], ['USER_LOCKED', 'USER_LOCKED_AUTO', 'LOGIN_FAILED', 'LOGIN_BLOCKED_LOCKED']);
                            ?>
                                <tr>
                                    <td style="font-size: 11.5px;"><?= date('d M H:i:s', strtotime($log['created_at'])) ?></td>
                                    <td>
                                        <span class="badge <?= $isAlert ? 'badge-danger' : 'badge-neutral' ?>">
                                            <?= htmlspecialchars($log['action']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($log['full_name'] ?? $log['username'] ?? 'System') ?></strong>
                                        <div style="font-size: 10.5px; color: #64748b;"><code><?= htmlspecialchars($log['role']) ?></code></div>
                                    </td>
                                    <td><code><?= htmlspecialchars($log['ip_address'] ?? '127.0.0.1') ?></code></td>
                                    <td style="font-size: 12px;"><?= htmlspecialchars($log['remarks'] ?? '') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Right Side: Locked Accounts & Policies -->
    <div>
        <div class="card" style="margin-bottom: 20px;">
            <div class="card-header">
                <div class="card-title">🔒 Locked Accounts Requiring Attention</div>
                <span class="badge badge-danger"><?= count($lockedUsers) ?></span>
            </div>
            <div class="card-body">
                <?php if (empty($lockedUsers)): ?>
                    <div style="text-align: center; padding: 20px; color: #16a34a; font-size: 13px;">
                        ✓ Zero locked accounts. All active credentials operating normally.
                    </div>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <?php foreach ($lockedUsers as $lu): ?>
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 12px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px;">
                                <div>
                                    <strong style="color: #991b1b;"><?= htmlspecialchars($lu['full_name']) ?></strong>
                                    <div style="font-size: 11px; color: #b91c1c;"><code>@<?= htmlspecialchars($lu['username']) ?></code> &bull; <?= htmlspecialchars($lu['dept_code'] ?? 'Central') ?></div>
                                </div>
                                <form method="POST" action="index.php?route=admin_toggle_lock">
                                    <input type="hidden" name="user_id" value="<?= $lu['id'] ?>">
                                    <input type="hidden" name="lock" value="0">
                                    <input type="hidden" name="reason" value="System Admin verified employee in person and unlocked">
                                    <button type="submit" class="btn btn-warning btn-sm">Unlock</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="card-title">Institutional Protection Policies</div>
            </div>
            <div class="card-body" style="font-size: 13px; line-height: 1.6; color: #334155;">
                <div style="margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid #e2e8f0;">
                    <strong>Bruteforce Lockout:</strong><br>
                    Accounts lock automatically upon <strong>5 consecutive failed login attempts</strong>.
                </div>
                <div style="margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid #e2e8f0;">
                    <strong>Password Reset Protocol:</strong><br>
                    No email OTP reset allowed. Staff must contact System Admin in person/intercom.
                </div>
                <div>
                    <strong>Audit Integrity:</strong><br>
                    Every authentication failure and role change is written to the immutable ledger with IP address and timestamps.
                </div>
            </div>
        </div>
    </div>
</div>

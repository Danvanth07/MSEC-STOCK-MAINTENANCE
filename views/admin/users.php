<?php
/**
 * MSEC SMART STOCK - User Management & RBAC View
 * Locked Scope: Section 3 (User Roles) & Section 5.2 (User Management)
 */

$pdo = Database::getInstance();
$users = $pdo->query("SELECT u.*, d.name as dept_name, d.code as dept_code, l.name as lab_name
                      FROM users u
                      LEFT JOIN departments d ON u.dept_id = d.id
                      LEFT JOIN laboratories l ON u.lab_id = l.id
                      ORDER BY u.id ASC")->fetchAll();
?>

<div class="page-header">
    <div>
        <h2 class="page-title">User Management & Role Permissions</h2>
        <div class="page-desc">The 14 Institutional Roles Across 5 Levels (Section 3 & 5.2)</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">Authorized System Users</div>
        <span class="badge badge-neutral"><?= count($users) ?> Total Accounts</span>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="data-table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Name & Username</th>
                        <th>Role Assigned</th>
                        <th>Hierarchy Level</th>
                        <th>Department / Laboratory</th>
                        <th>Email & Phone</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): 
                        $roleInfo = ROLES[$u['role_key']] ?? ['name' => $u['role_key'], 'level' => 'General'];
                    ?>
                        <tr>
                            <td><code>#<?= str_pad($u['id'], 3, '0', STR_PAD_LEFT) ?></code></td>
                            <td>
                                <strong><?= htmlspecialchars($u['full_name']) ?></strong>
                                <div style="font-size: 11px; color: #64748b;"><code>@<?= htmlspecialchars($u['username']) ?></code></div>
                            </td>
                            <td><span class="badge badge-primary"><?= htmlspecialchars($roleInfo['name']) ?></span></td>
                            <td><span class="badge badge-neutral"><?= htmlspecialchars($roleInfo['level']) ?></span></td>
                            <td>
                                <strong><?= htmlspecialchars($u['dept_code'] ?? 'Central') ?></strong>
                                <?php if (!empty($u['lab_name'])): ?>
                                    <div style="font-size: 11px; color: #64748b;"><?= htmlspecialchars($u['lab_name']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="font-size: 12px;"><?= htmlspecialchars($u['email']) ?></div>
                                <div style="font-size: 11px; color: #64748b;"><?= htmlspecialchars($u['phone'] ?? '') ?></div>
                            </td>
                            <td><span class="badge badge-success">Active</span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

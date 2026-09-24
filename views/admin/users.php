<?php
/**
 * MSEC SMART STOCK - System Administrator User & Account Management
 * Enforces Institutional Account Security: Admin-only account creation, password resets,
 * activation, deactivation, locking, and role assignment.
 */

$pdo = Database::getInstance();
$users = $pdo->query("
    SELECT u.*, d.name as dept_name, d.code as dept_code, l.name as lab_name
    FROM users u
    LEFT JOIN departments d ON u.dept_id = d.id
    LEFT JOIN laboratories l ON u.lab_id = l.id
    ORDER BY u.id ASC
")->fetchAll(PDO::FETCH_ASSOC);

$departments = $pdo->query("SELECT id, code, name FROM departments ORDER BY code ASC")->fetchAll(PDO::FETCH_ASSOC);
$msg = $_GET['msg'] ?? null;
$error = $_GET['error'] ?? null;
?>

<div class="page-header">
    <div>
        <h2 class="page-title">Institutional User & Account Directory</h2>
        <div class="page-desc">System Admin Account Governance &bull; Total Accounts: <strong><?= count($users) ?></strong></div>
    </div>
    <div style="display: flex; gap: 10px;">
        <button type="button" class="btn btn-primary" onclick="openCreateUserModal()">+ Create User Account</button>
        <a href="index.php?route=admin_security" class="btn btn-secondary">🛡️ Security Console</a>
    </div>
</div>

<?php if ($msg): ?>
    <div style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 13.5px;">
        ✓ <?= htmlspecialchars($msg) ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div style="background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 13.5px;">
        ⚠️ <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<!-- Institutional Account Policy Banner -->
<div style="background: #f8fafc; border-left: 4px solid #102a43; padding: 14px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; color: #334155; line-height: 1.5;">
    <strong>🔒 Institutional Policy Reminder (Section 2 & 4):</strong>
    Self-registration and self-service password resets are permanently disabled. As System Administrator, verify employee identity before executing a password reset or unlocking an account. Every administrative change is permanently sealed in the audit log.
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">Authorized Staff & Officer Accounts</div>
        <span class="badge badge-primary"><?= count($users) ?> Enrolled</span>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="data-table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Emp ID</th>
                        <th>User & Credentials</th>
                        <th>Assigned Role</th>
                        <th>Department</th>
                        <th>Designation</th>
                        <th>Account Status</th>
                        <th>Last Login</th>
                        <th style="text-align: right;">Security Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): 
                        $roleInfo = ROLES[$u['role_key']] ?? ['name' => $u['role_key'], 'level' => 'General'];
                        $isLocked = ($u['status'] === 'Locked');
                        $isActive = ($u['status'] === 'Active');
                    ?>
                        <tr>
                            <td><code><?= htmlspecialchars($u['employee_id'] ?? ('EMP-' . str_pad($u['id'], 3, '0', STR_PAD_LEFT))) ?></code></td>
                            <td>
                                <strong><?= htmlspecialchars($u['full_name']) ?></strong>
                                <div style="font-size: 11px; color: #64748b;">
                                    <code>@<?= htmlspecialchars($u['username']) ?></code> &bull; <?= htmlspecialchars($u['email']) ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-primary"><?= htmlspecialchars($roleInfo['name']) ?></span>
                                <div style="font-size: 10.5px; color: #64748b; margin-top: 2px;"><?= htmlspecialchars($roleInfo['level']) ?></div>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($u['dept_code'] ?? 'Central') ?></strong>
                                <?php if (!empty($u['lab_name'])): ?>
                                    <div style="font-size: 11px; color: #64748b;"><?= htmlspecialchars($u['lab_name']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span style="font-size: 12.5px;"><?= htmlspecialchars($u['designation'] ?? 'Staff') ?></span>
                            </td>
                            <td>
                                <?php if ($isLocked): ?>
                                    <span class="badge badge-danger">🔒 Locked</span>
                                <?php elseif ($isActive): ?>
                                    <span class="badge badge-success">✓ Active</span>
                                <?php else: ?>
                                    <span class="badge badge-neutral">✕ Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td style="font-size: 11.5px; color: #64748b;">
                                <?= $u['last_login_at'] ? date('d M Y H:i', strtotime($u['last_login_at'])) : 'Never' ?>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 6px;">
                                    <!-- Reset Password Button -->
                                    <button type="button" class="btn btn-secondary btn-sm" onclick="openResetPasswordModal(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['full_name'])) ?>', '<?= htmlspecialchars(addslashes($u['username'])) ?>')" title="Admin Password Reset">
                                        🔑 Reset Pass
                                    </button>

                                    <!-- Lock / Unlock Toggle -->
                                    <?php if ($isLocked): ?>
                                        <form method="POST" action="index.php?route=admin_toggle_lock" style="display:inline;" onsubmit="return confirm('Unlock account for <?= htmlspecialchars($u['username']) ?>?');">
                                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                            <input type="hidden" name="lock" value="0">
                                            <input type="hidden" name="reason" value="System Admin verified identity and unlocked account">
                                            <button type="submit" class="btn btn-warning btn-sm">🔓 Unlock</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" action="index.php?route=admin_toggle_lock" style="display:inline;" onsubmit="return confirm('Lock account for <?= htmlspecialchars($u['username']) ?>?');">
                                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                            <input type="hidden" name="lock" value="1">
                                            <input type="hidden" name="reason" value="System Admin security intervention">
                                            <button type="submit" class="btn btn-secondary btn-sm" style="color: #b91c1c;">🔒 Lock</button>
                                        </form>
                                    <?php endif; ?>

                                    <!-- Activate / Deactivate Toggle -->
                                    <?php if ($isActive): ?>
                                        <form method="POST" action="index.php?route=admin_toggle_status" style="display:inline;" onsubmit="return confirm('Deactivate account for <?= htmlspecialchars($u['username']) ?>?');">
                                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                            <input type="hidden" name="status" value="Inactive">
                                            <button type="submit" class="btn btn-secondary btn-sm" title="Deactivate">Deactivate</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" action="index.php?route=admin_toggle_status" style="display:inline;" onsubmit="return confirm('Activate account for <?= htmlspecialchars($u['username']) ?>?');">
                                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                            <input type="hidden" name="status" value="Active">
                                            <button type="submit" class="btn btn-success btn-sm" title="Activate">Activate</button>
                                        </form>
                                    <?php endif; ?>

                                    <!-- Role / Dept Edit Button -->
                                    <button type="button" class="btn btn-secondary btn-sm" onclick="openEditRoleModal(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['full_name'])) ?>', '<?= $u['role_key'] ?>', '<?= $u['dept_id'] ?>', '<?= htmlspecialchars(addslashes($u['designation'] ?? '')) ?>')">
                                        ✏️ Role
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: CREATE USER ACCOUNT (ADMIN ONLY) -->
<!-- ========================================== -->
<div id="createUserModal" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.65); z-index:9999; align-items:center; justify-content:center; padding:20px;">
    <div style="background:#ffffff; width:640px; max-width:100%; border-radius:16px; box-shadow:0 25px 50px -12px rgba(0,0,0,0.4); overflow:hidden;">
        <div style="background:#102a43; color:#ffffff; padding:20px 24px; display:flex; justify-content:space-between; align-items:center;">
            <h3 style="margin:0; font-size:18px; font-weight:700;">+ Create Authorized User Account</h3>
            <button type="button" onclick="closeCreateUserModal()" style="background:none; border:none; color:#ffffff; font-size:22px; cursor:pointer;">&times;</button>
        </div>
        <form method="POST" action="index.php?route=admin_create_user" style="padding:24px;">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="full_name" class="form-control" placeholder="e.g. Dr. K. Anand" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Username *</label>
                    <input type="text" name="username" class="form-control" placeholder="e.g. anand_cse" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Official Email ID *</label>
                    <input type="email" name="email" class="form-control" placeholder="anand@msec.edu.in" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Mobile Number</label>
                    <input type="text" name="phone" class="form-control" placeholder="+91 98765 43210">
                </div>
                <div class="form-group">
                    <label class="form-label">Employee ID</label>
                    <input type="text" name="employee_id" class="form-control" placeholder="EMP-CSE-045">
                </div>
                <div class="form-group">
                    <label class="form-label">Designation</label>
                    <input type="text" name="designation" class="form-control" placeholder="Assistant Professor (Sr.G)">
                </div>
                <div class="form-group">
                    <label class="form-label">Institutional Role *</label>
                    <select name="role_key" class="form-control" required>
                        <?php foreach (ROLES as $key => $r): ?>
                            <option value="<?= $key ?>"><?= htmlspecialchars($r['name']) ?> (<?= htmlspecialchars($r['level']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Department *</label>
                    <select name="dept_id" class="form-control" required>
                        <?php foreach ($departments as $d): ?>
                            <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?> (<?= htmlspecialchars($d['code']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group" style="margin-top:10px;">
                <label class="form-label">Initial Account Password *</label>
                <input type="password" name="password" class="form-control" value="MsecStock@2026" required>
                <div style="font-size:11.5px; color:#64748b; margin-top:4px;">Default initial password: <code>MsecStock@2026</code>. Will be hashed using bcrypt.</div>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:24px; padding-top:16px; border-top:1px solid #e2e8f0;">
                <button type="button" class="btn btn-secondary" onclick="closeCreateUserModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Create User Account</button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: ADMIN PASSWORD RESET (SECTION 2 & 4) -->
<!-- ========================================== -->
<div id="resetPasswordModal" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.65); z-index:9999; align-items:center; justify-content:center; padding:20px;">
    <div style="background:#ffffff; width:500px; max-width:100%; border-radius:16px; box-shadow:0 25px 50px -12px rgba(0,0,0,0.4); overflow:hidden;">
        <div style="background:#102a43; color:#ffffff; padding:18px 24px; display:flex; justify-content:space-between; align-items:center;">
            <h3 style="margin:0; font-size:17px; font-weight:700;">🔑 Admin Password Reset</h3>
            <button type="button" onclick="closeResetPasswordModal()" style="background:none; border:none; color:#ffffff; font-size:22px; cursor:pointer;">&times;</button>
        </div>
        <form method="POST" action="index.php?route=admin_reset_password" style="padding:24px;">
            <input type="hidden" name="user_id" id="reset_user_id">
            
            <div style="background:#eff6ff; border-left:4px solid #3b82f6; padding:12px 14px; border-radius:6px; font-size:12.5px; margin-bottom:18px; color:#1e3a8a;">
                Target User: <strong id="reset_user_name"></strong> (<code id="reset_user_uname"></code>)<br>
                Identity must be verified prior to issuing this reset. This action is permanently recorded in the audit log.
            </div>

            <div class="form-group">
                <label class="form-label">New Secure Password *</label>
                <input type="text" name="new_password" id="new_password_input" class="form-control" value="MsecReset@2026" required>
            </div>

            <div style="font-size:12px; color:#64748b; margin-bottom:18px;">
                <button type="button" onclick="generateRandomPassword()" style="background:none; border:1px dashed #94a3b8; border-radius:4px; padding:4px 8px; font-size:11.5px; cursor:pointer; color:#1e40af;">
                    🎲 Generate Random Password
                </button>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; border-top:1px solid #e2e8f0; padding-top:16px;">
                <button type="button" class="btn btn-secondary" onclick="closeResetPasswordModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Apply Password Reset & Log</button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: EDIT ROLE & DEPARTMENT -->
<!-- ========================================== -->
<div id="editRoleModal" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.65); z-index:9999; align-items:center; justify-content:center; padding:20px;">
    <div style="background:#ffffff; width:520px; max-width:100%; border-radius:16px; box-shadow:0 25px 50px -12px rgba(0,0,0,0.4); overflow:hidden;">
        <div style="background:#102a43; color:#ffffff; padding:18px 24px; display:flex; justify-content:space-between; align-items:center;">
            <h3 style="margin:0; font-size:17px; font-weight:700;">✏️ Reassign Role & Department</h3>
            <button type="button" onclick="closeEditRoleModal()" style="background:none; border:none; color:#ffffff; font-size:22px; cursor:pointer;">&times;</button>
        </div>
        <form method="POST" action="index.php?route=admin_update_role" style="padding:24px;">
            <input type="hidden" name="user_id" id="edit_user_id">
            
            <div style="margin-bottom:16px; font-size:13px;">
                Modifying profile for: <strong id="edit_user_name"></strong>
            </div>

            <div class="form-group">
                <label class="form-label">System Role *</label>
                <select name="role_key" id="edit_role_key" class="form-control" required>
                    <?php foreach (ROLES as $key => $r): ?>
                        <option value="<?= $key ?>"><?= htmlspecialchars($r['name']) ?> (<?= htmlspecialchars($r['level']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Department *</label>
                <select name="dept_id" id="edit_dept_id" class="form-control" required>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?> (<?= htmlspecialchars($d['code']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Designation</label>
                <input type="text" name="designation" id="edit_designation" class="form-control">
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; border-top:1px solid #e2e8f0; padding-top:16px;">
                <button type="button" class="btn btn-secondary" onclick="closeEditRoleModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes & Audit</button>
            </div>
        </form>
    </div>
</div>

<script>
function openCreateUserModal() {
    document.getElementById('createUserModal').style.display = 'flex';
}
function closeCreateUserModal() {
    document.getElementById('createUserModal').style.display = 'none';
}

function openResetPasswordModal(id, name, uname) {
    document.getElementById('reset_user_id').value = id;
    document.getElementById('reset_user_name').textContent = name;
    document.getElementById('reset_user_uname').textContent = uname;
    document.getElementById('resetPasswordModal').style.display = 'flex';
}
function closeResetPasswordModal() {
    document.getElementById('resetPasswordModal').style.display = 'none';
}

function openEditRoleModal(id, name, role, deptId, designation) {
    document.getElementById('edit_user_id').value = id;
    document.getElementById('edit_user_name').textContent = name;
    document.getElementById('edit_role_key').value = role;
    document.getElementById('edit_dept_id').value = deptId;
    document.getElementById('edit_designation').value = designation;
    document.getElementById('editRoleModal').style.display = 'flex';
}
function closeEditRoleModal() {
    document.getElementById('editRoleModal').style.display = 'none';
}

function generateRandomPassword() {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%';
    let pass = 'Msec@';
    for (let i = 0; i < 8; i++) {
        pass += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    document.getElementById('new_password_input').value = pass;
}
</script>

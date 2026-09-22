<?php
/**
 * MSEC SMART STOCK - Dynamic Role-Filtered Sidebar Navigation
 * Enforces strict RBAC (Section 3 & 27) & Mobile Off-Canvas Drawer
 */

$currentRoute = $_GET['route'] ?? 'dashboard';
$user = Auth::user();
$currentRole = $user['role_key'] ?? 'super_admin';
$roleName = ROLES[$currentRole]['name'] ?? $currentRole;
$roleLevel = ROLES[$currentRole]['level'] ?? 'User';

// Pre-calculate section visibilities based on permissions
$canProcure = Auth::canAccessRoute('procurement');
$canVerify  = Auth::canAccessRoute('verification');
$canReport  = Auth::canAccessRoute('reports');
$showProcurementSection = $canProcure || $canVerify || $canReport;

$canMasters = Auth::canAccessRoute('admin_masters');
$canUsers   = Auth::canAccessRoute('admin_users');
$canAudit   = Auth::canAccessRoute('admin_audit');
$showAdminSection = $canMasters || $canUsers || $canAudit;
?>
<aside class="app-sidebar" id="appSidebar">
    <div class="sidebar-header">
        <div style="display: flex; align-items: center; gap: 12px; flex-grow: 1; min-width: 0;">
            <img src="images/msec_logo.png" alt="MSEC Emblem" class="sidebar-logo">
            <div style="min-width: 0;">
                <div class="brand-title">MSEC STOCK</div>
                <div class="brand-subtitle">Mohamed Sathak Engg. College</div>
            </div>
        </div>
        <!-- Mobile Drawer Close Button -->
        <button type="button" class="sidebar-close-btn" onclick="closeSidebar()" aria-label="Close menu">&times;</button>
    </div>

    <!-- Active Persona Quick-Info Badge -->
    <div class="sidebar-role-card">
        <div class="role-pill-level"><?= htmlspecialchars($roleLevel) ?></div>
        <div class="role-pill-title"><?= htmlspecialchars($roleName) ?></div>
        <div class="role-pill-dept"><?= htmlspecialchars($user['dept_name'] ?? 'Meenakshi Sathak Campus') ?></div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-label">Core Operations</div>
        
        <?php if (Auth::canAccessRoute('dashboard')): ?>
            <a href="index.php?route=dashboard" class="nav-link <?= $currentRoute === 'dashboard' ? 'active' : '' ?>">
                <span class="nav-icon">📊</span>
                <span>Dashboard</span>
            </a>
        <?php endif; ?>

        <?php if (Auth::canAccessRoute('inventory')): ?>
            <a href="index.php?route=inventory" class="nav-link <?= $currentRoute === 'inventory' ? 'active' : '' ?>">
                <span class="nav-icon">📦</span>
                <span>Inventory Master</span>
            </a>
        <?php endif; ?>

        <?php if (Auth::canAccessRoute('requests')): ?>
            <a href="index.php?route=requests" class="nav-link <?= in_array($currentRoute, ['requests', 'new_request', 'view_request']) ? 'active' : '' ?>">
                <span class="nav-icon">📝</span>
                <span>Requests & Indent</span>
            </a>
        <?php endif; ?>

        <?php if (Auth::canAccessRoute('distribution')): ?>
            <a href="index.php?route=distribution" class="nav-link <?= in_array($currentRoute, ['distribution', 'issue_stock', 'view_voucher']) ? 'active' : '' ?>">
                <span class="nav-icon">🚚</span>
                <span>Distribution & Vouchers</span>
            </a>
        <?php endif; ?>

        <?php if (Auth::canAccessRoute('movement')): ?>
            <a href="index.php?route=movement" class="nav-link <?= $currentRoute === 'movement' ? 'active' : '' ?>">
                <span class="nav-icon">🔄</span>
                <span>Stock Movement</span>
            </a>
        <?php endif; ?>

        <?php if (Auth::canAccessRoute('assets')): ?>
            <a href="index.php?route=assets" class="nav-link <?= $currentRoute === 'assets' ? 'active' : '' ?>">
                <span class="nav-icon">💻</span>
                <span>Asset Register</span>
            </a>
        <?php endif; ?>

        <?php if ($showProcurementSection): ?>
            <div class="nav-label">Procurement & Governance</div>

            <?php if ($canProcure): ?>
                <a href="index.php?route=procurement" class="nav-link <?= $currentRoute === 'procurement' ? 'active' : '' ?>">
                    <span class="nav-icon">🛒</span>
                    <span>Procurement & GRN</span>
                </a>
            <?php endif; ?>

            <?php if ($canVerify): ?>
                <a href="index.php?route=verification" class="nav-link <?= $currentRoute === 'verification' ? 'active' : '' ?>">
                    <span class="nav-icon">📋</span>
                    <span>Stock Verification</span>
                </a>
            <?php endif; ?>

            <?php if ($canReport): ?>
                <a href="index.php?route=reports" class="nav-link <?= $currentRoute === 'reports' ? 'active' : '' ?>">
                    <span class="nav-icon">📈</span>
                    <span>Reports & Analytics</span>
                </a>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($showAdminSection): ?>
            <div class="nav-label">System Administration</div>

            <?php if ($canMasters): ?>
                <a href="index.php?route=admin_masters" class="nav-link <?= $currentRoute === 'admin_masters' ? 'active' : '' ?>">
                    <span class="nav-icon">🏛️</span>
                    <span>Master Data</span>
                </a>
            <?php endif; ?>

            <?php if ($canUsers): ?>
                <a href="index.php?route=admin_users" class="nav-link <?= $currentRoute === 'admin_users' ? 'active' : '' ?>">
                    <span class="nav-icon">👥</span>
                    <span>User Management</span>
                </a>
            <?php endif; ?>

            <?php if ($canAudit): ?>
                <a href="index.php?route=admin_audit" class="nav-link <?= $currentRoute === 'admin_audit' ? 'active' : '' ?>">
                    <span class="nav-icon">🛡️</span>
                    <span>Audit Logs</span>
                </a>
            <?php endif; ?>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
        <a href="index.php?route=logout" class="sidebar-logout-link" title="Logout">
            <span class="nav-icon">🚪</span>
            <span>Sign Out</span>
        </a>
    </div>
</aside>

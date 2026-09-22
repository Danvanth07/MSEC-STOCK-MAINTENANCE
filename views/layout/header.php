<?php
/**
 * MSEC SMART STOCK - Main Header Layout
 * Responsive Mobile & PC Navigation with Role-Aware Quick Persona Switcher
 */

$user = Auth::user();
$currentRole = $user['role_key'] ?? 'super_admin';
$roleName = ROLES[$currentRole]['name'] ?? $currentRole;
$roleLevel = ROLES[$currentRole]['level'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= APP_NAME ?> — <?= APP_INSTITUTION_FULL ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
</head>
<body>
<!-- Backdrop Overlay for Mobile Sidebar Drawer -->
<div class="sidebar-backdrop" id="sidebarBackdrop" onclick="closeSidebar()"></div>

<div class="app-container">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <div class="app-main">
        <header class="app-header">
            <div class="header-left">
                <!-- Mobile Hamburger Button (Visible only on mobile/tablet) -->
                <button type="button" class="mobile-menu-btn" id="mobileMenuBtn" onclick="toggleSidebar()" aria-label="Toggle menu">
                    <span class="hamburger-bar"></span>
                    <span class="hamburger-bar"></span>
                    <span class="hamburger-bar"></span>
                </button>

                <div class="brand-header-info">
                    <img src="images/msec_logo.png" alt="MSEC Logo" class="header-brand-logo">
                    <div class="header-title-wrap">
                        <div class="header-top-row">
                            <h1 class="portal-title"><?= APP_NAME ?></h1>
                            <span class="header-tagline-badge"><?= APP_INSTITUTION_TAGLINE ?></span>
                        </div>
                        <div class="header-institution-sub">
                            <?= APP_INSTITUTION_FULL ?> &bull; <span class="hide-mobile"><?= APP_INSTITUTION_LOCATION ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="header-right">
                <!-- Persona Quick-Switcher: Grouped by Institutional Level -->
                <form method="GET" action="index.php" class="persona-switcher" id="personaForm">
                    <input type="hidden" name="route" value="<?= htmlspecialchars($_GET['route'] ?? 'dashboard') ?>">
                    <span class="persona-icon">🎭</span>
                    <label for="switch_role" class="hide-mobile">Demo Role:</label>
                    <select name="switch_role" id="switch_role" class="persona-select" onchange="document.getElementById('personaForm').submit();" title="Switch demo persona to test role permissions">
                        <optgroup label="🏛️ Central & Executive">
                            <option value="super_admin" <?= $currentRole === 'super_admin' ? 'selected' : '' ?>>Super Admin (Full Access)</option>
                            <option value="director" <?= $currentRole === 'director' ? 'selected' : '' ?>>Director (Executive Approval > 1 Lakh)</option>
                            <option value="principal" <?= $currentRole === 'principal' ? 'selected' : '' ?>>Principal (Institutional Approval > 25k)</option>
                            <option value="vice_principal" <?= $currentRole === 'vice_principal' ? 'selected' : '' ?>>Vice Principal (Admin Approval > 5k)</option>
                            <option value="admin" <?= $currentRole === 'admin' ? 'selected' : '' ?>>Central Admin (System Admin)</option>
                        </optgroup>
                        <optgroup label="📦 Stores & Procurement">
                            <option value="store_keeper" <?= $currentRole === 'store_keeper' ? 'selected' : '' ?>>Central Store Keeper (Issue Vouchers & Stock)</option>
                            <option value="purchase_officer" <?= $currentRole === 'purchase_officer' ? 'selected' : '' ?>>Purchase Officer (Procurement & GRN)</option>
                            <option value="accounts_officer" <?= $currentRole === 'accounts_officer' ? 'selected' : '' ?>>Accounts Officer (Valuation & Finance)</option>
                        </optgroup>
                        <optgroup label="🏢 Department & Labs">
                            <option value="hod" <?= $currentRole === 'hod' ? 'selected' : '' ?>>HOD CSE (Dept Approval < 5k)</option>
                            <option value="dept_coordinator" <?= $currentRole === 'dept_coordinator' ? 'selected' : '' ?>>Dept Stock Coordinator (Transfers & Stock)</option>
                            <option value="lab_incharge" <?= $currentRole === 'lab_incharge' ? 'selected' : '' ?>>Lab Incharge (Lab Requisitions & Assets)</option>
                        </optgroup>
                        <optgroup label="👤 Faculty & Technicians (Restricted)">
                            <option value="faculty" <?= $currentRole === 'faculty' ? 'selected' : '' ?>>Teaching Faculty (Raise Indents Only)</option>
                            <option value="technician" <?= $currentRole === 'technician' ? 'selected' : '' ?>>Lab Technician (Raise Indents & Damage)</option>
                        </optgroup>
                        <optgroup label="🛡️ Audit & Oversight (Read-Only)">
                            <option value="auditor" <?= $currentRole === 'auditor' ? 'selected' : '' ?>>Independent Auditor (Read-Only Logs)</option>
                            <option value="management_viewer" <?= $currentRole === 'management_viewer' ? 'selected' : '' ?>>Management Viewer (Read-Only KPI)</option>
                        </optgroup>
                    </select>
                </form>

                <!-- Current User Profile Badge -->
                <div class="user-profile-badge">
                    <div class="user-avatar" title="<?= htmlspecialchars($user['full_name'] ?? 'User') ?>">
                        <?= strtoupper(substr($user['full_name'] ?? 'U', 0, 1)) ?>
                    </div>
                    <div class="user-details hide-mobile">
                        <div class="user-name"><?= htmlspecialchars($user['full_name'] ?? 'Guest') ?></div>
                        <div class="user-role-badge">
                            <span class="role-name"><?= htmlspecialchars($roleName) ?></span>
                            <span class="dept-dot">&bull;</span>
                            <span class="dept-name"><?= htmlspecialchars($user['dept_code'] ?? 'Central') ?></span>
                        </div>
                    </div>
                </div>

                <a href="index.php?route=logout" class="btn btn-secondary btn-sm hide-mobile" title="Logout">Sign Out</a>
            </div>
        </header>

        <main class="page-content">

<?php
/**
 * MSEC SMART STOCK - Enterprise Institutional Login Portal
 * Mohamed Sathak Engineering College (Autonomous), Kilakarai
 * Enforces Institutional Security: Admin-only account provisioning & password resets.
 */

$error = $_GET['error'] ?? null;
$msg = $_GET['msg'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
    <meta name="theme-color" content="#070d18">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="format-detection" content="telephone=no">
    <title>Sign In — <?= APP_NAME ?> | <?= APP_INSTITUTION_FULL ?></title>
    <link rel="stylesheet" href="css/style.css?v=<?= filemtime(dirname(__DIR__, 2) . '/public/css/style.css') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --brand-primary: #0f2444;
            --brand-navy-dark: #071326;
            --brand-accent: #2563eb;
            --brand-amber: #d97706;
            --brand-gold: #f59e0b;
        }

        body {
            background-color: #070d18;
            background-image: 
                radial-gradient(circle at 15% 15%, rgba(37, 99, 235, 0.22) 0%, transparent 45%),
                radial-gradient(circle at 85% 85%, rgba(217, 119, 6, 0.18) 0%, transparent 45%),
                radial-gradient(circle at 50% 50%, rgba(15, 36, 68, 0.6) 0%, transparent 70%);
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 16px;
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            color: #0f172a;
        }

        .login-wrapper {
            width: 1100px;
            max-width: 100%;
            background: #ffffff;
            border-radius: 24px;
            box-shadow: 
                0 32px 64px -12px rgba(0, 0, 0, 0.55),
                0 0 0 1px rgba(255, 255, 255, 0.15),
                0 0 80px -20px rgba(37, 99, 235, 0.25);
            display: grid;
            grid-template-columns: 480px 1fr;
            overflow: hidden;
            animation: wrapperFadeUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes wrapperFadeUp {
            from { opacity: 0; transform: translateY(16px) scale(0.98); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        .login-branding {
            background: linear-gradient(155deg, #091322 0%, #0f2444 50%, #1e3a8a 100%);
            padding: 48px 42px;
            color: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }

        .login-branding::after {
            content: "";
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at bottom left, rgba(37, 99, 235, 0.35), transparent 70%);
            pointer-events: none;
        }

        .brand-seal-wrap {
            display: flex;
            align-items: center;
            gap: 18px;
            margin-bottom: 26px;
            position: relative;
            z-index: 2;
        }

        .college-emblem {
            width: 72px;
            height: 72px;
            background: #ffffff;
            border-radius: 50%;
            padding: 6px;
            object-fit: contain;
            box-shadow: 0 8px 20px rgba(0,0,0,0.35), 0 0 0 2px rgba(255, 255, 255, 0.3);
            transition: transform 0.25s ease;
        }

        .college-emblem:hover {
            transform: scale(1.05) rotate(2deg);
        }

        .portal-title-lg {
            font-family: 'Outfit', sans-serif;
            font-size: 27px;
            font-weight: 800;
            letter-spacing: -0.5px;
            line-height: 1.15;
            color: #ffffff;
            background: linear-gradient(135deg, #ffffff 40%, #93c5fd 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .portal-sub {
            font-size: 13.5px;
            color: #93c5fd;
            margin-top: 5px;
            font-weight: 600;
            letter-spacing: 0.2px;
        }

        .institution-name {
            font-size: 15.5px;
            font-weight: 800;
            color: #fef08a;
            margin-top: 20px;
            line-height: 1.35;
            letter-spacing: -0.2px;
            position: relative;
            z-index: 2;
        }

        .institution-meta {
            font-size: 12px;
            color: #cbd5e1;
            margin-top: 6px;
            line-height: 1.55;
            position: relative;
            z-index: 2;
        }

        .accreditation-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.22);
            padding: 7px 14px;
            border-radius: 999px;
            font-size: 11.5px;
            font-weight: 700;
            color: #ffffff;
            margin-top: 18px;
            position: relative;
            z-index: 2;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .security-policy-box {
            background: rgba(7, 19, 38, 0.55);
            border: 1px solid rgba(255, 255, 255, 0.14);
            border-radius: 14px;
            padding: 18px;
            font-size: 12px;
            line-height: 1.55;
            color: #e2e8f0;
            margin-top: 24px;
            position: relative;
            z-index: 2;
            backdrop-filter: blur(8px);
        }

        .security-policy-box strong {
            color: #fde68a;
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 5px;
            font-size: 12.5px;
            font-weight: 700;
        }

        .login-content {
            padding: 44px 44px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: #ffffff;
        }

        .form-header h2 {
            font-family: 'Outfit', sans-serif;
            font-size: 26px;
            font-weight: 800;
            color: #0f172a;
            margin: 0 0 6px 0;
            letter-spacing: -0.4px;
        }

        .form-header p {
            font-size: 13.5px;
            color: #64748b;
            margin: 0 0 24px 0;
            line-height: 1.45;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            color: #334155;
            margin-bottom: 6px;
        }

        .input-group {
            position: relative;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 1.5px solid #cbd5e1;
            border-radius: 12px;
            font-size: 14px;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            box-sizing: border-box;
            font-family: inherit;
            background: #ffffff;
            color: #0f172a;
        }

        .form-control:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.15);
        }

        .toggle-password-btn {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #64748b;
            font-size: 12.5px;
            cursor: pointer;
            font-weight: 700;
            padding: 4px 6px;
            border-radius: 6px;
            transition: color 0.15s, background 0.15s;
        }

        .toggle-password-btn:hover {
            color: #1e3a8a;
            background: #f1f5f9;
        }

        .btn-submit {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, #0f2444 0%, #1e3a8a 50%, #2563eb 100%);
            color: #ffffff;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            margin-top: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35);
            border-top: 1px solid rgba(255, 255, 255, 0.25);
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.45);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .contact-admin-notice {
            margin-top: 20px;
            padding: 13px 16px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-left: 4px solid #3b82f6;
            border-radius: 10px;
            font-size: 12px;
            color: #475569;
            line-height: 1.5;
        }

        .contact-admin-notice strong {
            color: #0f172a;
            display: block;
            margin-bottom: 2px;
        }

        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
        }

        .alert-success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 20px;
            font-weight: 600;
        }

        /* Demo Accounts Quick-Select Roster */
        .demo-roster-box {
            margin-top: 20px;
            border-top: 1px solid #e2e8f0;
            padding-top: 16px;
        }

        .demo-roster-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            font-size: 11.5px;
            font-weight: 800;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            user-select: none;
            padding: 4px 0;
            transition: color 0.15s;
        }

        .demo-roster-header:hover {
            color: #2563eb;
        }

        .demo-roster-content {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
            margin-top: 12px;
            max-height: 210px;
            overflow-y: auto;
            padding-right: 4px;
        }

        .roster-item {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 8px 12px;
            font-size: 12px;
            cursor: pointer;
            text-align: left;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
        }

        .roster-item:hover {
            background: #eff6ff;
            border-color: #93c5fd;
            transform: translateY(-1.5px);
            box-shadow: 0 3px 8px rgba(37, 99, 235, 0.12);
        }

        .roster-item strong {
            display: block;
            color: #0f172a;
            font-size: 12px;
            font-weight: 700;
        }

        .roster-item span {
            color: #64748b;
            font-size: 11px;
            display: block;
            margin-top: 1px;
        }

        @media (max-width: 960px) {
            .login-wrapper {
                grid-template-columns: 1fr;
            }
            .login-branding {
                padding: 34px 28px;
            }
            .login-content {
                padding: 34px 28px;
            }
            .demo-roster-content {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<div class="login-wrapper">
    <!-- Left Branding & Security Policy Column -->
    <div class="login-branding">
        <div>
            <div class="brand-seal-wrap">
                <img src="images/msec_logo.png" alt="MSEC Emblem" class="college-emblem">
                <div>
                    <h1 class="portal-title-lg"><?= APP_NAME ?></h1>
                    <div class="portal-sub">Smart Inventory, Asset & Distribution Portal</div>
                </div>
            </div>

            <div class="institution-name"><?= APP_INSTITUTION_FULL ?></div>
            <div class="institution-meta">
                <?= APP_INSTITUTION_TAGLINE ?> &bull; <?= APP_INSTITUTION_LOCATION ?><br>
                <?= APP_INSTITUTION_APPROVALS ?>
            </div>

            <div class="accreditation-pill">
                ⭐ <?= APP_INSTITUTION_ACCREDITATIONS ?>
            </div>
        </div>

        <div>
            <div class="security-policy-box">
                <strong>🛡️ Institutional Account Security Policy</strong>
                Public self-registration and automated self-service password resets are permanently disabled to prevent unauthorized credential tampering. All accounts and privileges are provisioned and verified solely by the <strong>System Administrator</strong>.
            </div>
            <div style="font-size: 11px; color: #94a3b8; margin-top: 14px; letter-spacing: 0.2px;">
                🔒 Secured Enterprise Session &bull; ISO 27001 & AICTE Compliant Architecture
            </div>
        </div>
    </div>

    <!-- Right Login Form Column -->
    <div class="login-content">
        <div class="form-header">
            <h2>Authorized Sign In</h2>
            <p>Enter your institutional username or employee ID to access your role dashboard</p>
        </div>

        <?php if ($error): ?>
            <div class="alert-error">
                <span>⚠️</span>
                <div><?= htmlspecialchars($error) ?></div>
            </div>
        <?php endif; ?>

        <?php if ($msg): ?>
            <div class="alert-success">
                <span>✓</span>
                <div><?= htmlspecialchars($msg) ?></div>
            </div>
        <?php endif; ?>

        <form method="POST" action="index.php?route=do_login" id="loginForm">
            <div class="form-group">
                <label class="form-label" for="username">Username / Employee ID</label>
                <div class="input-group">
                    <input type="text" id="username" name="username" class="form-control" placeholder="e.g. storekeeper / admin / hod_cse" required autofocus value="admin">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Account Password</label>
                <div class="input-group">
                    <input type="password" id="password" name="password" class="form-control" placeholder="Enter secure password" required value="password123">
                    <button type="button" class="toggle-password-btn" onclick="togglePasswordVisibility()" id="togglePassBtn">Show</button>
                </div>
            </div>

            <button type="submit" class="btn-submit">
                <span>Sign In to Dashboard</span>
                <span>&rarr;</span>
            </button>
        </form>

        <!-- Mandatory Security Statement (Section 2 & 29) -->
        <div class="contact-admin-notice">
            <strong>Forgot your password?</strong>
            Please contact the <strong>System Administrator</strong> in the Central IT Office. The Administrator will verify your employee identity and issue a secure password reset.
        </div>

        <!-- Authorized Role Accounts Roster (For Project Evaluation & Inspection) -->
        <div class="demo-roster-box">
            <div class="demo-roster-header" onclick="toggleDemoRoster()">
                <span>⚡ Quick Fill Demo Accounts (14 Roles):</span>
                <span id="rosterToggleIcon">▼</span>
            </div>
            <div class="demo-roster-content" id="demoRoster">
                <button type="button" class="roster-item" onclick="fillCreds('admin', 'password123')">
                    <strong>Super Administrator</strong>
                    <span>Security, Users, Audit Logs</span>
                </button>
                <button type="button" class="roster-item" onclick="fillCreds('store_keeper', 'password123')">
                    <strong>Central Store Keeper</strong>
                    <span>Operational Gateway & Vouchers</span>
                </button>
                <button type="button" class="roster-item" onclick="fillCreds('hod_cse', 'password123')">
                    <strong>HOD (CSE Dept)</strong>
                    <span>Dept Quotas & Authorizations</span>
                </button>
                <button type="button" class="roster-item" onclick="fillCreds('principal', 'password123')">
                    <strong>Principal</strong>
                    <span>Institutional Sanctions > 25k</span>
                </button>
                <button type="button" class="roster-item" onclick="fillCreds('director', 'password123')">
                    <strong>Director</strong>
                    <span>Capital Sanctions > 1 Lakh</span>
                </button>
                <button type="button" class="roster-item" onclick="fillCreds('purchase_officer', 'password123')">
                    <strong>Purchase Officer</strong>
                    <span>POs, Quotations, Vendors</span>
                </button>
                <button type="button" class="roster-item" onclick="fillCreds('coord_cse', 'password123')">
                    <strong>Dept Coordinator</strong>
                    <span>Spec Verification & Transfers</span>
                </button>
                <button type="button" class="roster-item" onclick="fillCreds('lab_ai', 'password123')">
                    <strong>Lab Incharge (AI Lab)</strong>
                    <span>Lab Assets & Damage Reports</span>
                </button>
                <button type="button" class="roster-item" onclick="fillCreds('faculty_cse', 'password123')">
                    <strong>Faculty (CSE)</strong>
                    <span>Indent Raising & Custody</span>
                </button>
                <button type="button" class="roster-item" onclick="fillCreds('vice_principal', 'password123')">
                    <strong>Vice Principal</strong>
                    <span>Admin Sanctions > 5k</span>
                </button>
                <button type="button" class="roster-item" onclick="fillCreds('accounts_officer', 'password123')">
                    <strong>Accounts Officer</strong>
                    <span>Budget Heads & Expenditure</span>
                </button>
                <button type="button" class="roster-item" onclick="fillCreds('tech_hardware', 'password123')">
                    <strong>Technician</strong>
                    <span>Maintenance & Repair Bay</span>
                </button>
                <button type="button" class="roster-item" onclick="fillCreds('auditor_ext', 'password123')">
                    <strong>Internal Auditor</strong>
                    <span>Stock Verification & Ledger</span>
                </button>
                <button type="button" class="roster-item" onclick="fillCreds('viewer_mgmt', 'password123')">
                    <strong>Management Viewer</strong>
                    <span>Read-Only Executive KPIs</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function togglePasswordVisibility() {
    const passInput = document.getElementById('password');
    const toggleBtn = document.getElementById('togglePassBtn');
    if (passInput.type === 'password') {
        passInput.type = 'text';
        toggleBtn.textContent = 'Hide';
    } else {
        passInput.type = 'password';
        toggleBtn.textContent = 'Show';
    }
}

function fillCreds(username, password) {
    document.getElementById('username').value = username;
    document.getElementById('password').value = password;
}

function toggleDemoRoster() {
    const roster = document.getElementById('demoRoster');
    const icon = document.getElementById('rosterToggleIcon');
    if (roster.style.display === 'none') {
        roster.style.display = 'grid';
        icon.textContent = '▲';
    } else {
        roster.style.display = 'none';
        icon.textContent = '▼';
    }
}
</script>

</body>
</html>

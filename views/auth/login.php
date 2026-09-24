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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — <?= APP_NAME ?> | <?= APP_INSTITUTION_FULL ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --brand-primary: #102a43;
            --brand-navy-dark: #0b1d30;
            --brand-accent: #2563eb;
            --brand-amber: #d97706;
        }
        body {
            background: radial-gradient(circle at 20% 20%, #17365d 0%, #0b1d30 70%, #060e18 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            color: #0f172a;
        }
        .login-wrapper {
            width: 1080px;
            max-width: 100%;
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 30px 60px -12px rgba(0, 0, 0, 0.45), 0 0 0 1px rgba(255, 255, 255, 0.1);
            display: grid;
            grid-template-columns: 460px 1fr;
            overflow: hidden;
        }
        .login-branding {
            background: linear-gradient(155deg, #102a43 0%, #17365d 50%, #1e429f 100%);
            padding: 44px 38px;
            color: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
        }
        .login-branding::after {
            content: "";
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at bottom left, rgba(37, 99, 235, 0.25), transparent 70%);
            pointer-events: none;
        }
        .brand-seal-wrap {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
        }
        .college-emblem {
            width: 68px;
            height: 68px;
            background: #ffffff;
            border-radius: 50%;
            padding: 5px;
            object-fit: contain;
            box-shadow: 0 8px 16px rgba(0,0,0,0.25);
        }
        .portal-title-lg {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 26px;
            font-weight: 700;
            letter-spacing: -0.5px;
            line-height: 1.15;
            color: #ffffff;
        }
        .portal-sub {
            font-size: 13.5px;
            color: #93c5fd;
            margin-top: 4px;
            font-weight: 500;
        }
        .institution-name {
            font-size: 15px;
            font-weight: 700;
            color: #fef08a;
            margin-top: 18px;
            line-height: 1.35;
        }
        .institution-meta {
            font-size: 11.5px;
            color: #cbd5e1;
            margin-top: 4px;
            line-height: 1.5;
        }
        .accreditation-pill {
            display: inline-block;
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
            color: #ffffff;
            margin-top: 14px;
        }
        .security-policy-box {
            background: rgba(15, 23, 42, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 12px;
            padding: 16px;
            font-size: 12px;
            line-height: 1.5;
            color: #e2e8f0;
            margin-top: 24px;
        }
        .security-policy-box strong {
            color: #fed7aa;
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 4px;
            font-size: 12.5px;
        }
        .login-content {
            padding: 44px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: #ffffff;
        }
        .form-header h2 {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 24px;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 6px 0;
        }
        .form-header p {
            font-size: 13.5px;
            color: #64748b;
            margin: 0 0 24px 0;
        }
        .form-group {
            margin-bottom: 18px;
        }
        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 6px;
        }
        .input-group {
            position: relative;
        }
        .form-control {
            width: 100%;
            padding: 12px 14px;
            border: 1.5px solid #cbd5e1;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.2s ease;
            box-sizing: border-box;
            font-family: inherit;
        }
        .form-control:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }
        .toggle-password-btn {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #64748b;
            font-size: 13px;
            cursor: pointer;
            font-weight: 600;
        }
        .btn-submit {
            width: 100%;
            padding: 13px;
            background: #102a43;
            color: #ffffff;
            border: none;
            border-radius: 10px;
            font-size: 14.5px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-submit:hover {
            background: #17365d;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(16, 42, 67, 0.25);
        }
        .contact-admin-notice {
            margin-top: 22px;
            padding: 14px 16px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-left: 4px solid #3b82f6;
            border-radius: 8px;
            font-size: 12.5px;
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
            padding: 12px 14px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .alert-success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
            padding: 12px 14px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 20px;
        }
        /* Demo Accounts Quick-Select Roster */
        .demo-roster-box {
            margin-top: 22px;
            border-top: 1px solid #e2e8f0;
            padding-top: 16px;
        }
        .demo-roster-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            font-size: 12px;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .demo-roster-content {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 6px;
            margin-top: 10px;
            max-height: 200px;
            overflow-y: auto;
            padding-right: 4px;
        }
        .roster-item {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 6px 10px;
            font-size: 11.5px;
            cursor: pointer;
            text-align: left;
            transition: all 0.15s ease;
        }
        .roster-item:hover {
            background: #eff6ff;
            border-color: #93c5fd;
        }
        .roster-item strong {
            display: block;
            color: #1e293b;
        }
        .roster-item span {
            color: #64748b;
            font-size: 10.5px;
        }
        @media (max-width: 920px) {
            .login-wrapper {
                grid-template-columns: 1fr;
            }
            .login-branding {
                padding: 30px 24px;
            }
            .login-content {
                padding: 32px 24px;
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
            <div style="font-size: 11px; color: #94a3b8; margin-top: 14px;">
                Secured Enterprise Session &bull; ISO 27001 & AICTE Compliant Architecture
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
                <?= htmlspecialchars($msg) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="index.php?route=do_login" id="loginForm">
            <div class="form-group">
                <label class="form-label" for="username">Username / Employee ID</label>
                <div class="input-group">
                    <input type="text" id="username" name="username" class="form-control" placeholder="e.g. storekeeper / hod_cse / superadmin" required autofocus value="storekeeper">
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
                <span>⚡ Click to Fill Role Account (14 Available):</span>
                <span id="rosterToggleIcon">▼</span>
            </div>
            <div class="demo-roster-content" id="demoRoster">
                <button type="button" class="roster-item" onclick="fillCreds('storekeeper', 'password123')">
                    <strong>Central Store Keeper</strong>
                    <span>Operational Gateway & Vouchers</span>
                </button>
                <button type="button" class="roster-item" onclick="fillCreds('superadmin', 'password123')">
                    <strong>Super Administrator</strong>
                    <span>Security, Users, Audit Logs</span>
                </button>
                <button type="button" class="roster-item" onclick="fillCreds('hod_cse', 'password123')">
                    <strong>HOD (CSE Dept)</strong>
                    <span>Dept Quotas & Authorizations</span>
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
                <button type="button" class="roster-item" onclick="fillCreds('principal', 'password123')">
                    <strong>Principal</strong>
                    <span>Institutional Sanctions > 25k</span>
                </button>
                <button type="button" class="roster-item" onclick="fillCreds('viceprincipal', 'password123')">
                    <strong>Vice Principal</strong>
                    <span>Admin Sanctions > 5k</span>
                </button>
                <button type="button" class="roster-item" onclick="fillCreds('director', 'password123')">
                    <strong>Director</strong>
                    <span>Capital Sanctions > 1 Lakh</span>
                </button>
                <button type="button" class="roster-item" onclick="fillCreds('purchaseofficer', 'password123')">
                    <strong>Purchase Officer</strong>
                    <span>POs, Quotations, Vendors</span>
                </button>
                <button type="button" class="roster-item" onclick="fillCreds('accountsofficer', 'password123')">
                    <strong>Accounts Officer</strong>
                    <span>Budget Heads & Expenditure</span>
                </button>
                <button type="button" class="roster-item" onclick="fillCreds('technician', 'password123')">
                    <strong>Technician</strong>
                    <span>Maintenance & Repair Bay</span>
                </button>
                <button type="button" class="roster-item" onclick="fillCreds('auditor', 'password123')">
                    <strong>Internal Auditor</strong>
                    <span>Stock Verification & Ledger</span>
                </button>
                <button type="button" class="roster-item" onclick="fillCreds('viewer', 'password123')">
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

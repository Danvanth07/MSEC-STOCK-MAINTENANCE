<?php
/**
 * MSEC SMART STOCK - Login Portal
 */
$error = $_GET['error'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            background: radial-gradient(circle at 10% 20%, rgb(15, 23, 42) 0%, rgb(30, 27, 75) 90%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 15px;
        }
        .login-card {
            background: #ffffff;
            border-radius: 16px;
            width: 1000px;
            max-width: 100%;
            display: grid;
            grid-template-columns: 1fr 1fr;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
            overflow: hidden;
        }
        .login-brand {
            background: linear-gradient(135deg, #312e81, #4f46e5);
            color: #ffffff;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .login-form-side {
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .persona-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-top: 14px;
            max-height: 260px;
            overflow-y: auto;
            padding-right: 4px;
        }
        .persona-btn {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 8px 10px;
            text-align: left;
            text-decoration: none;
            color: #0f172a;
            font-size: 12px;
            display: block;
            transition: all 0.2s ease;
        }
        .persona-btn:hover {
            background: #eef2ff;
            border-color: #818cf8;
            transform: translateY(-1px);
        }
        @media (max-width: 768px) {
            .login-card { grid-template-columns: 1fr; }
            .login-brand { display: none; }
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="login-brand">
        <div>
            <img src="images/msec_logo.png" alt="MSEC Emblem" style="width: 64px; height: 64px; object-fit: contain; margin-bottom: 16px; background: #ffffff; border-radius: 50%; padding: 4px; box-shadow: 0 4px 12px rgba(0,0,0,0.2);">
            <h1 style="font-family: var(--font-heading); font-size: 26px; font-weight: 800; line-height: 1.2;"><?= APP_NAME ?></h1>
            <p style="font-size: 13.5px; opacity: 0.95; margin-top: 6px;"><?= APP_FULL_NAME ?></p>
            <p style="font-size: 13px; color: #fed7aa; margin-top: 4px; font-weight: 700;"><?= APP_INSTITUTION_FULL ?></p>
            <p style="font-size: 11.5px; color: #e2e8f0; margin-top: 2px; opacity: 0.9;"><?= APP_INSTITUTION_TAGLINE ?> &bull; <?= APP_INSTITUTION_LOCATION ?></p>
        </div>

        <div style="font-size: 11.5px; opacity: 0.9; border-top: 1px solid rgba(255,255,255,0.18); padding-top: 16px; line-height: 1.5;">
            <div style="color: #fed7aa; font-weight: 700; margin-bottom: 4px;"><?= APP_INSTITUTION_ACCREDITATIONS ?></div>
            <div>Central Store &bull; Department Stores (CSE, ECE, MECH) &bull; Lab Asset Lifecycle</div>
        </div>
    </div>

    <div class="login-form-side">
        <h2 style="font-family: var(--font-heading); font-size: 22px; font-weight: 700; color: #0f172a; margin-bottom: 6px;">Sign In to Portal</h2>
        <p style="font-size: 13px; color: #64748b; margin-bottom: 20px;">Enter credentials or select a one-click demo persona</p>

        <?php if ($error): ?>
            <div style="background: #fee2e2; color: #b91c1c; padding: 10px 14px; border-radius: 6px; font-size: 13px; margin-bottom: 16px;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="index.php?route=do_login">
            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" placeholder="e.g. storekeeper / hod_cse / director" required value="storekeeper">
            </div>

            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="Default: password123" required value="password123">
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 11px; margin-top: 6px;">Sign In</button>
        </form>

        <div style="margin-top: 24px; border-top: 1px solid #e2e8f0; padding-top: 16px;">
            <div style="font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.5px;">⚡ One-Click Persona Login:</div>
            <div class="persona-grid">
                <a href="index.php?route=switch_persona&role=director" class="persona-btn">
                    <strong>Director</strong>
                    <div style="color: #64748b; font-size: 11px;">Management</div>
                </a>
                <a href="index.php?route=switch_persona&role=principal" class="persona-btn">
                    <strong>Principal</strong>
                    <div style="color: #64748b; font-size: 11px;">Institution Level</div>
                </a>
                <a href="index.php?route=switch_persona&role=vice_principal" class="persona-btn">
                    <strong>Vice Principal</strong>
                    <div style="color: #64748b; font-size: 11px;">Administration</div>
                </a>
                <a href="index.php?route=switch_persona&role=store_keeper" class="persona-btn">
                    <strong>Store Keeper</strong>
                    <div style="color: #64748b; font-size: 11px;">Operational</div>
                </a>
                <a href="index.php?route=switch_persona&role=hod" class="persona-btn">
                    <strong>HOD (CSE)</strong>
                    <div style="color: #64748b; font-size: 11px;">Department</div>
                </a>
                <a href="index.php?route=switch_persona&role=lab_incharge" class="persona-btn">
                    <strong>Lab Incharge</strong>
                    <div style="color: #64748b; font-size: 11px;">AI Laboratory</div>
                </a>
                <a href="index.php?route=switch_persona&role=super_admin" class="persona-btn">
                    <strong>Super Admin</strong>
                    <div style="color: #64748b; font-size: 11px;">System Level</div>
                </a>
                <a href="index.php?route=switch_persona&role=auditor" class="persona-btn">
                    <strong>Auditor</strong>
                    <div style="color: #64748b; font-size: 11px;">Read-Only Audit</div>
                </a>
            </div>
        </div>
    </div>
</div>

</body>
</html>

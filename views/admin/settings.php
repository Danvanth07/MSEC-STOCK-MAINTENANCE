<?php
/**
 * MSEC SMART STOCK - Institutional System Settings
 * Section 41: Institution details, academic/financial years, numbering formats, approval thresholds
 */

$pdo = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    if (!Auth::can('manage_settings')) {
        header("Location: index.php?route=unauthorized");
        exit;
    }

    $settingsToSave = $_POST['settings'] ?? [];
    $updateStmt = $pdo->prepare("INSERT OR REPLACE INTO system_settings (setting_key, setting_value, category, description) VALUES (?, ?, ?, ?)");
    foreach ($settingsToSave as $k => $v) {
        $category = $_POST['categories'][$k] ?? 'General';
        $desc = $_POST['descriptions'][$k] ?? '';
        $updateStmt->execute([$k, $v, $category, $desc]);
    }

    AuditLogger::log('SETTINGS_UPDATED', 'System Administration', 'Institutional settings updated by System Administrator', 'SystemSettings', null);
    header("Location: index.php?route=admin_settings&msg=Settings+saved+successfully");
    exit;
}

$settingsRows = $pdo->query("SELECT * FROM system_settings ORDER BY category ASC, setting_key ASC")->fetchAll(PDO::FETCH_ASSOC);
$byCategory = [];
foreach ($settingsRows as $row) {
    $byCategory[$row['category']][] = $row;
}

$msg = $_GET['msg'] ?? null;
?>

<div class="page-header">
    <div>
        <h2 class="page-title">Institutional Parameters & System Settings</h2>
        <div class="page-desc">Section 41: College Metadata, Academic Years, Financial Head Rules, and Workflow Thresholds</div>
    </div>
</div>

<?php if ($msg): ?>
    <div style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 13.5px;">
        ✓ <?= htmlspecialchars($msg) ?>
    </div>
<?php endif; ?>

<form method="POST" action="index.php?route=admin_settings">
    <input type="hidden" name="save_settings" value="1">

    <div style="display: flex; flex-direction: column; gap: 24px;">
        <?php foreach ($byCategory as $category => $items): ?>
            <div class="card">
                <div class="card-header">
                    <div class="card-title"><?= htmlspecialchars($category) ?> Settings</div>
                    <span class="badge badge-primary"><?= count($items) ?> Parameters</span>
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 18px;">
                        <?php foreach ($items as $item): ?>
                            <div class="form-group" style="margin: 0;">
                                <label class="form-label" style="font-family: monospace; font-size: 12px; color: #1e293b;">
                                    <?= htmlspecialchars($item['setting_key']) ?>
                                </label>
                                <input type="text" name="settings[<?= htmlspecialchars($item['setting_key']) ?>]" class="form-control" value="<?= htmlspecialchars($item['setting_value']) ?>">
                                <input type="hidden" name="categories[<?= htmlspecialchars($item['setting_key']) ?>]" value="<?= htmlspecialchars($item['category']) ?>">
                                <input type="hidden" name="descriptions[<?= htmlspecialchars($item['setting_key']) ?>]" value="<?= htmlspecialchars($item['description'] ?? '') ?>">
                                <div style="font-size: 11px; color: #64748b; margin-top: 4px;">
                                    <?= htmlspecialchars($item['description'] ?? '') ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <div style="display: flex; justify-content: flex-end; gap: 12px;">
            <button type="submit" class="btn btn-primary" style="padding: 12px 28px; font-size: 14.5px;">Save Institutional Settings</button>
        </div>
    </div>
</form>

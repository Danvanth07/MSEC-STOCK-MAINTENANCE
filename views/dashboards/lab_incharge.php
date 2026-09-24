<?php
/**
 * MSEC SMART STOCK - Laboratory Incharge Dashboard
 * Section 14: Laboratory Inventory, Serialized Computing Assets, Consumable Indents,
 * Service Due Equipment, and Equipment Damage Reporting.
 */

$pdo = Database::getInstance();
$user = Auth::user();
$labId = $user['lab_id'] ?? 2;

// Lab Information
$lab = $pdo->query("SELECT * FROM laboratories WHERE id = {$labId}")->fetch(PDO::FETCH_ASSOC) ?: [
    'name' => 'AI & Deep Learning Laboratory', 'location' => 'Main Block - Floor 3 - Lab 304'
];

// Lab Assets
$labAssets = $pdo->query("SELECT * FROM assets WHERE lab_id = {$labId} ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
$totalLabAssets = count($labAssets);
$activeWorking = 0;
$underRepair = 0;
foreach ($labAssets as $a) {
    if ($a['asset_status'] === 'In Use') $activeWorking++;
    if ($a['asset_status'] === 'Under Repair' || $a['asset_status'] === 'Under Maintenance') $underRepair++;
}

// Lab Requisitions
$myRequests = $pdo->query("
    SELECT * FROM stock_requests 
    WHERE lab_id = {$labId} OR requested_by = {$user['id']}
    ORDER BY id DESC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="page-header">
    <div>
        <h2 class="page-title">Laboratory Operations & Asset Management Hub</h2>
        <div class="page-desc">Incharge: <strong><?= htmlspecialchars($user['full_name']) ?></strong> &bull; <?= htmlspecialchars($lab['name']) ?> (<?= htmlspecialchars($lab['location']) ?>)</div>
    </div>
    <div style="display: flex; gap: 10px;">
        <button type="button" class="btn btn-secondary" onclick="openDamageReportModal()">⚠️ Report Damaged Equipment</button>
        <a href="index.php?route=new_request" class="btn btn-primary">+ Raise Lab Consumables Indent</a>
    </div>
</div>

<!-- Lab KPIs -->
<div class="stats-grid">
    <div class="stat-card primary">
        <div class="stat-title">Registered Lab Systems</div>
        <div class="stat-value"><?= sprintf("%02d", $totalLabAssets) ?></div>
        <div class="stat-subtext">Workstations, Switches & Displays</div>
    </div>
    <div class="stat-card success">
        <div class="stat-title">Fully Operational (100% Uptime)</div>
        <div class="stat-value"><?= sprintf("%02d", $activeWorking) ?></div>
        <div class="stat-subtext">Verified ready for student practicals</div>
    </div>
    <div class="stat-card danger">
        <div class="stat-title">Equipment In Service Bay</div>
        <div class="stat-value"><?= sprintf("%02d", $underRepair) ?></div>
        <div class="stat-subtext">Fault reported; awaiting technician repair</div>
    </div>
    <div class="stat-card info">
        <div class="stat-title">Lab Indents Raised</div>
        <div class="stat-value"><?= sprintf("%02d", count($myRequests)) ?></div>
        <div class="stat-subtext">Cables, toners & examination sheets</div>
    </div>
</div>

<div class="responsive-split-grid">
    <!-- Lab Assets Table -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">🖥️ Laboratory Terminals & Equipment Roster</div>
            <span class="badge badge-primary"><?= $totalLabAssets ?> Assigned</span>
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="data-table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Asset Tag</th>
                            <th>Description</th>
                            <th>IP Address</th>
                            <th>Condition</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($labAssets as $la): ?>
                            <tr>
                                <td><code><?= htmlspecialchars($la['asset_id']) ?></code></td>
                                <td>
                                    <strong><?= htmlspecialchars($la['asset_name']) ?></strong>
                                    <div style="font-size: 11px; color: #64748b;"><?= htmlspecialchars($la['configuration'] ?? '') ?></div>
                                </td>
                                <td><code><?= htmlspecialchars($la['ip_address'] ?? 'DHCP') ?></code></td>
                                <td>
                                    <span class="badge <?= $la['asset_status'] === 'In Use' ? 'badge-success' : 'badge-danger' ?>">
                                        <?= htmlspecialchars($la['asset_status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($la['asset_status'] === 'In Use'): ?>
                                        <button type="button" class="btn btn-secondary btn-sm" style="color: #b91c1c;" onclick="reportThisAsset(<?= $la['id'] ?>, '<?= htmlspecialchars(addslashes($la['asset_id'])) ?>')">
                                            Log Fault
                                        </button>
                                    <?php else: ?>
                                        <span style="font-size: 11.5px; color: #64748b;">In Bay</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Lab Requisitions Tracker -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">📝 Active Laboratory Indents & Vouchers</div>
            <a href="index.php?route=requests" class="badge badge-secondary">All Requisitions</a>
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="data-table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Indent</th>
                            <th>Purpose</th>
                            <th>Status</th>
                            <th>Track</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($myRequests as $mr): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($mr['request_code']) ?></strong></td>
                                <td style="font-size: 11.5px;"><?= htmlspecialchars(substr($mr['purpose'], 0, 30)) ?>...</td>
                                <td><span class="badge badge-warning"><?= htmlspecialchars($mr['status']) ?></span></td>
                                <td>
                                    <a href="index.php?route=view_request&id=<?= $mr['id'] ?>" class="btn btn-secondary btn-sm">Inspect</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Report Damaged Asset -->
<div id="damageReportModal" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.65); z-index:9999; align-items:center; justify-content:center; padding:20px;">
    <div style="background:#ffffff; width:520px; max-width:100%; border-radius:16px; box-shadow:0 25px 50px -12px rgba(0,0,0,0.4); overflow:hidden;">
        <div style="background:#102a43; color:#ffffff; padding:18px 24px; display:flex; justify-content:space-between; align-items:center;">
            <h3 style="margin:0; font-size:17px; font-weight:700;">⚠️ Report Equipment Fault / Damage</h3>
            <button type="button" onclick="closeDamageReportModal()" style="background:none; border:none; color:#ffffff; font-size:22px; cursor:pointer;">&times;</button>
        </div>
        <form method="POST" action="index.php?route=report_damage" style="padding:24px;">
            <div class="form-group">
                <label class="form-label">Select Laboratory Equipment *</label>
                <select name="asset_id" id="modal_asset_select" class="form-control" required>
                    <?php foreach ($labAssets as $la): ?>
                        <option value="<?= $la['id'] ?>"><?= htmlspecialchars($la['asset_id']) ?> — <?= htmlspecialchars($la['asset_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Nature of Fault / Problem Observed *</label>
                <textarea name="issue" class="form-control" rows="3" placeholder="Describe symptoms: e.g. SMPS will not power on, display artifacts, network port loose..." required></textarea>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; border-top:1px solid #e2e8f0; padding-top:16px;">
                <button type="button" class="btn btn-secondary" onclick="closeDamageReportModal()">Cancel</button>
                <button type="submit" class="btn btn-danger">Log Fault to Repair Bay</button>
            </div>
        </form>
    </div>
</div>

<script>
function openDamageReportModal() {
    document.getElementById('damageReportModal').style.display = 'flex';
}
function closeDamageReportModal() {
    document.getElementById('damageReportModal').style.display = 'none';
}
function reportThisAsset(assetId, assetTag) {
    document.getElementById('modal_asset_select').value = assetId;
    openDamageReportModal();
}
</script>

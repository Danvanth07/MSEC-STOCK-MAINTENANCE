<?php
/**
 * MSEC SMART STOCK - Laboratory Maintenance & Equipment Service Ledger
 * Section 16: Hardware Service Tickets, Diagnostic Updates, Technician Actions
 */

$pdo = Database::getInstance();
$user = Auth::user();
$kpis = Maintenance::getKPIs();
$tickets = Maintenance::getAll();
$assets = $pdo->query("SELECT id, asset_id, asset_name, location FROM assets ORDER BY asset_id ASC")->fetchAll(PDO::FETCH_ASSOC);

$msg = $_GET['msg'] ?? null;
?>

<div class="page-header">
    <div>
        <h2 class="page-title">Equipment Maintenance & Service Ticket Bay</h2>
        <div class="page-desc">Laboratory Hardware Repair Log &bull; Calibration & Diagnostic Status &bull; <?= APP_INSTITUTION ?></div>
    </div>
    <div style="display: flex; gap: 10px;">
        <button type="button" class="btn btn-primary" onclick="openNewTicketModal()">+ Open Service Ticket</button>
    </div>
</div>

<?php if ($msg): ?>
    <div style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 13.5px;">
        ✓ <?= htmlspecialchars($msg) ?>
    </div>
<?php endif; ?>

<!-- Maintenance KPIs -->
<div class="stats-grid">
    <div class="stat-card danger">
        <div class="stat-title">Open Service Tickets</div>
        <div class="stat-value"><?= sprintf("%02d", $kpis['open_tickets']) ?></div>
        <div class="stat-subtext">Awaiting diagnostic & spares</div>
    </div>
    <div class="stat-card warning">
        <div class="stat-title">Assets Under Repair</div>
        <div class="stat-value"><?= sprintf("%02d", $kpis['assets_under_repair']) ?></div>
        <div class="stat-subtext">Disassembled in technician repair bay</div>
    </div>
    <div class="stat-card success">
        <div class="stat-title">Completed Repairs</div>
        <div class="stat-value"><?= sprintf("%02d", $kpis['completed_tickets']) ?></div>
        <div class="stat-subtext">Restored to lab working inventory</div>
    </div>
    <div class="stat-card primary">
        <div class="stat-title">Total Spares Cost</div>
        <div class="stat-value"><?= CURRENCY_SYMBOL ?><?= number_format($kpis['total_repair_cost']) ?></div>
        <div class="stat-subtext">Components and replacement units</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">Laboratory Equipment Service Tickets</div>
        <span class="badge badge-neutral"><?= count($tickets) ?> Total Tickets</span>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="data-table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Ticket #</th>
                        <th>Asset Tag & Name</th>
                        <th>Department</th>
                        <th>Reported Issue</th>
                        <th>Diagnosis</th>
                        <th>Technician</th>
                        <th>Status</th>
                        <th>Repair Cost</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tickets)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 24px; color: #64748b;">No maintenance service tickets on record.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($tickets as $t): ?>
                            <tr>
                                <td><code><?= htmlspecialchars($t['ticket_number']) ?></code></td>
                                <td>
                                    <strong><?= htmlspecialchars($t['asset_name']) ?></strong>
                                    <div style="font-size: 11px; color: #64748b;"><code><?= htmlspecialchars($t['asset_tag']) ?></code> &bull; <?= htmlspecialchars($t['asset_location']) ?></div>
                                </td>
                                <td><span class="badge badge-neutral"><?= htmlspecialchars($t['dept_code']) ?></span></td>
                                <td style="font-size: 12px; max-width: 220px;"><?= htmlspecialchars($t['issue_reported']) ?></td>
                                <td style="font-size: 11.5px; color: #475569; max-width: 200px;"><?= htmlspecialchars($t['diagnosis'] ?? 'Pending diagnosis') ?></td>
                                <td>
                                    <span style="font-size: 12px; font-weight: 600;"><?= htmlspecialchars($t['technician_name'] ?? 'Unassigned') ?></span>
                                </td>
                                <td>
                                    <span class="badge <?= $t['status'] === 'Repaired' ? 'badge-success' : ($t['status'] === 'Under Repair' ? 'badge-danger' : 'badge-warning') ?>">
                                        <?= htmlspecialchars($t['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <strong><?= CURRENCY_SYMBOL ?><?= number_format($t['repair_cost']) ?></strong>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Open New Service Ticket -->
<div id="newTicketModal" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.65); z-index:9999; align-items:center; justify-content:center; padding:20px;">
    <div style="background:#ffffff; width:520px; max-width:100%; border-radius:16px; box-shadow:0 25px 50px -12px rgba(0,0,0,0.4); overflow:hidden;">
        <div style="background:#102a43; color:#ffffff; padding:18px 24px; display:flex; justify-content:space-between; align-items:center;">
            <h3 style="margin:0; font-size:17px; font-weight:700;">+ Open Equipment Service Ticket</h3>
            <button type="button" onclick="closeNewTicketModal()" style="background:none; border:none; color:#ffffff; font-size:22px; cursor:pointer;">&times;</button>
        </div>
        <form method="POST" action="index.php?route=create_maintenance_ticket" style="padding:24px;">
            <div class="form-group">
                <label class="form-label">Select Equipment / Asset *</label>
                <select name="asset_id" class="form-control" required>
                    <?php foreach ($assets as $a): ?>
                        <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['asset_id']) ?> — <?= htmlspecialchars($a['asset_name']) ?> (<?= htmlspecialchars($a['location']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Observed Fault / Issue Description *</label>
                <textarea name="issue_reported" class="form-control" rows="3" placeholder="Describe symptoms in detail..." required></textarea>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; border-top:1px solid #e2e8f0; padding-top:16px;">
                <button type="button" class="btn btn-secondary" onclick="closeNewTicketModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Submit to Repair Bay</button>
            </div>
        </form>
    </div>
</div>

<script>
function openNewTicketModal() {
    document.getElementById('newTicketModal').style.display = 'flex';
}
function closeNewTicketModal() {
    document.getElementById('newTicketModal').style.display = 'none';
}
</script>

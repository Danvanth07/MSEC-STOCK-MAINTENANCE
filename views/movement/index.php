<?php
/**
 * MSEC SMART STOCK - Stock Movement & Inter-Department Transfers View
 * Locked Scope: Section 13 (Inter-Department Transfer) & Section 14 (Stock Movement Lifecycle)
 */

$user = Auth::user();
$movements = Movement::getAll();
$items = Inventory::getAllItems();
?>

<div class="page-header">
    <div>
        <h2 class="page-title">Stock Movement & Department Transfers</h2>
        <div class="page-desc">Inter-Department Reallocation, Location Relocation & Transit Chain (Section 13 & 14)</div>
    </div>
    <div>
        <?php if (Auth::can('create_transfer')): ?>
            <button onclick="openModal('newTransferModal')" class="btn btn-primary">+ Initiate Inter-Dept Transfer</button>
        <?php endif; ?>
    </div>
</div>

<?php
$totalMovements = count($movements);
$inTransit = count(array_filter($movements, fn($m) => in_array($m['status'], ['Dispatched', 'In Transit'])));
$pendingAppr = count(array_filter($movements, fn($m) => in_array($m['status'], ['Submitted', 'Pending'])));
$completed = count(array_filter($movements, fn($m) => in_array($m['status'], ['Closed', 'Acknowledged'])));
?>

<!-- Movement KPI Overview Grid -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon" style="background: #e0e7ff; color: #4338ca;">📦</div>
        <div class="stat-content">
            <div class="stat-value"><?= $totalMovements ?> Orders</div>
            <div class="stat-label">Total Movement Cycles</div>
        </div>
    </div>
    <div class="stat-card info">
        <div class="stat-icon" style="background: #e0f2fe; color: #0284c7;">🚚</div>
        <div class="stat-content">
            <div class="stat-value"><?= $inTransit ?> Transfers</div>
            <div class="stat-label">In-Transit Consignments</div>
        </div>
    </div>
    <div class="stat-card warning">
        <div class="stat-icon" style="background: #fef3c7; color: #b45309;">⏳</div>
        <div class="stat-content">
            <div class="stat-value"><?= $pendingAppr ?> Pending</div>
            <div class="stat-label">Awaiting HoD Authorization</div>
        </div>
    </div>
    <div class="stat-card success">
        <div class="stat-icon" style="background: #dcfce7; color: #15803d;">✅</div>
        <div class="stat-content">
            <div class="stat-value"><?= $completed ?> Closed</div>
            <div class="stat-label">Delivered & Acknowledged</div>
        </div>
    </div>
</div>

<!-- Highlighted Workflow Box: Section 13 Keyboard Transfer Example -->
<div style="background: linear-gradient(135deg, #eef2ff, #e0e7ff); border: 1px solid #c7d2fe; border-radius: 12px; padding: 18px 24px; margin-bottom: 24px;">
    <div style="display: flex; align-items: center; justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <span style="font-size: 24px;">🔄</span>
            <div>
                <h4 style="font-size: 15px; font-weight: 700; color: #312e81;">Locked Workflow Example: CSE &rarr; ECE Transfer (Section 13)</h4>
                <p style="font-size: 13px; color: #4338ca; margin-top: 2px;">
                    "CSE has 10 extra keyboards and ECE needs 5." Workflow: ECE Request &rarr; CSE HOD Approval &rarr; Central Store Verification &rarr; Dispatch &rarr; ECE Ack &rarr; Stock balances updated.
                </p>
            </div>
        </div>
        <span class="badge badge-primary">Active Flow</span>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">Movement & Inter-Department Transfer Register</div>
        <div>
            <input type="text" class="form-control table-search-input" data-table="movementTable" placeholder="Search movements..." style="width: 240px; padding: 6px 12px; font-size: 13px;">
        </div>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="data-table-container">
            <table class="data-table" id="movementTable">
                <thead>
                    <tr>
                        <th>Movement Code</th>
                        <th>Type</th>
                        <th>Source</th>
                        <th>Destination</th>
                        <th>Initiated By</th>
                        <th>Current Status</th>
                        <th>Notes / Justification</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($movements as $m): 
                        $statusClass = match($m['status']) {
                            'Closed', 'Acknowledged' => 'badge-success',
                            'In Transit', 'Dispatched' => 'badge-info',
                            'Approved', 'Ready for Dispatch' => 'badge-primary',
                            'Rejected' => 'badge-danger',
                            default => 'badge-warning'
                        };
                    ?>
                        <tr>
                            <td><code><?= htmlspecialchars($m['movement_code']) ?></code></td>
                            <td><span class="badge badge-neutral"><?= htmlspecialchars(str_replace('_', ' ', $m['movement_type'])) ?></span></td>
                            <td><strong><?= htmlspecialchars($m['source_dept_code'] ?? 'Central') ?></strong></td>
                            <td><strong><?= htmlspecialchars($m['target_dept_code'] ?? 'Store') ?></strong></td>
                            <td><?= htmlspecialchars($m['initiated_by_name']) ?></td>
                            <td><span class="badge <?= $statusClass ?>"><?= htmlspecialchars($m['status']) ?></span></td>
                            <td><?= htmlspecialchars($m['notes']) ?></td>
                            <td>
                                <div style="display: flex; gap: 6px;">
                                    <?php if ($m['status'] === 'Submitted'): ?>
                                        <?php if (Auth::can('advance_transfer')): ?>
                                            <a href="index.php?route=advance_movement&id=<?= $m['id'] ?>&status=Approved" class="btn btn-primary btn-sm">Approve</a>
                                        <?php else: ?>
                                            <span class="badge badge-warning">Awaiting Approval</span>
                                        <?php endif; ?>
                                    <?php elseif ($m['status'] === 'Approved'): ?>
                                        <?php if (Auth::can('advance_transfer')): ?>
                                            <a href="index.php?route=advance_movement&id=<?= $m['id'] ?>&status=Dispatched" class="btn btn-info btn-sm">Dispatch</a>
                                        <?php else: ?>
                                            <span class="badge badge-info">Approved / Queue</span>
                                        <?php endif; ?>
                                    <?php elseif ($m['status'] === 'Dispatched' || $m['status'] === 'In Transit'): ?>
                                        <?php if (Auth::can('advance_transfer')): ?>
                                            <a href="index.php?route=advance_movement&id=<?= $m['id'] ?>&status=Acknowledged" class="btn btn-success btn-sm">Acknowledge & Close</a>
                                        <?php else: ?>
                                            <span class="badge badge-primary">In Transit</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="font-size: 12px; color: #16a34a; font-weight: 600;">✓ Completed</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if (Auth::can('create_transfer')): ?>
<!-- Modal: New Inter-Department Transfer (Section 13) -->
<div id="newTransferModal" style="display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.6); z-index: 999; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div style="background: white; border-radius: 12px; width: 550px; max-width: 95%; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.2);">
        <div style="padding: 18px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
            <h3 style="font-size: 16px; font-weight: 700;">Initiate Stock Transfer (Section 13 & 14)</h3>
            <button onclick="closeModal('newTransferModal')" style="border: none; background: none; font-size: 20px; cursor: pointer;">&times;</button>
        </div>
        <form method="POST" action="index.php?route=create_movement" style="padding: 24px;">
            <div class="form-group">
                <label class="form-label">Transfer Movement Type</label>
                <select name="movement_type" class="form-control" required>
                    <option value="DEPT_TO_DEPT">Department &rarr; Department Transfer</option>
                    <option value="CENTRAL_TO_DEPT">Central Store &rarr; Department</option>
                    <option value="DEPT_TO_LAB">Department &rarr; Lab Allocation</option>
                    <option value="LAB_TO_FACULTY">Lab &rarr; Faculty Temporary Issue</option>
                    <option value="DEPT_TO_CENTRAL">Department &rarr; Central Store (Return)</option>
                    <option value="STORE_TO_REPAIR">Store &rarr; Repair Bay</option>
                    <option value="DEPT_TO_DISPOSAL">Department &rarr; Condemnation Disposal</option>
                </select>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                <div class="form-group">
                    <label class="form-label">Source Department</label>
                    <select name="source_dept_id" class="form-control">
                        <option value="1">Computer Science (CSE)</option>
                        <option value="2">Electronics (ECE)</option>
                        <option value="3">Electrical (EEE)</option>
                        <option value="4">Mechanical (MECH)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Destination Department</label>
                    <select name="target_dept_id" class="form-control">
                        <option value="2">Electronics (ECE)</option>
                        <option value="1">Computer Science (CSE)</option>
                        <option value="3">Electrical (EEE)</option>
                        <option value="4">Mechanical (MECH)</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Item to Transfer & Quantity</label>
                <div style="display: grid; grid-template-columns: 3fr 1fr; gap: 10px;">
                    <select name="items[0][item_id]" class="form-control" required>
                        <?php foreach ($items as $it): ?>
                            <option value="<?= $it['id'] ?>" <?= $it['item_name'] === 'Logitech USB Keyboard (Standard)' ? 'selected' : '' ?>>
                                <?= htmlspecialchars($it['item_name']) ?> (<?= htmlspecialchars($it['item_code']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="number" name="items[0][quantity]" class="form-control" value="5" required placeholder="Qty">
                </div>
            </div>

            <div class="form-group" style="margin-top: 14px;">
                <label class="form-label">Transfer Justification</label>
                <textarea name="notes" rows="2" class="form-control" required>Inter-department transfer: CSE has 10 extra keyboards and ECE requested 5 units for communication lab</textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button type="button" onclick="closeModal('newTransferModal')" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Submit Transfer Request</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

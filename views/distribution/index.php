<?php
/**
 * MSEC SMART STOCK - Stock Distribution & Vouchers View
 * Locked Scope: Section 12 (Stock Distribution & Automatically Generated Documents)
 */

$user = Auth::user();
$issues = Distribution::getAll();
$items = Inventory::getAllItems();
?>

<div class="page-header">
    <div>
        <h2 class="page-title">Stock Distribution & Vouchers</h2>
        <div class="page-desc">Material Dispatch, Issue Vouchers, Delivery Challans & Department Acknowledgements</div>
    </div>
    <div>
        <?php if (Auth::can('issue_stock')): ?>
            <button onclick="openModal('newIssueModal')" class="btn btn-primary">+ Generate Stock Issue Voucher</button>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">Distribution Orders & Issue Vouchers Log</div>
        <div>
            <input type="text" class="form-control table-search-input" data-table="distributionTable" placeholder="Search vouchers..." style="width: 240px; padding: 6px 12px; font-size: 13px;">
        </div>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="data-table-container">
            <table class="data-table" id="distributionTable">
                <thead>
                    <tr>
                        <th>Voucher Code</th>
                        <th>Document Type</th>
                        <th>Source Store</th>
                        <th>Target Department</th>
                        <th>Issued By</th>
                        <th>Issue Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($issues)): ?>
                        <tr><td colspan="8" style="text-align: center; color: #64748b; padding: 30px;">No distribution vouchers generated yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($issues as $iss): ?>
                            <tr>
                                <td><code><?= htmlspecialchars($iss['issue_code']) ?></code></td>
                                <td><span class="badge badge-primary"><?= htmlspecialchars($iss['voucher_type']) ?></span></td>
                                <td><?= htmlspecialchars($iss['source_store_name']) ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($iss['target_dept_code']) ?></strong>
                                    <?php if (!empty($iss['target_lab_name'])): ?>
                                        <div style="font-size: 11px; color: #64748b;"><?= htmlspecialchars($iss['target_lab_name']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($iss['issued_by_name']) ?></td>
                                <td><?= date('d M Y', strtotime($iss['issue_date'])) ?></td>
                                <td>
                                    <?php if ($iss['status'] === 'Acknowledged'): ?>
                                        <span class="badge badge-success">✓ Acknowledged</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">Pending Ack</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 6px;">
                                        <a href="index.php?route=view_voucher&id=<?= $iss['id'] ?>" class="btn btn-secondary btn-sm" target="_blank">🖨️ Print Voucher</a>
                                        <?php if ($iss['status'] !== 'Acknowledged' && Auth::can('ack_receipt')): ?>
                                            <button onclick="openAckModal(<?= $iss['id'] ?>, '<?= htmlspecialchars($iss['issue_code']) ?>', <?= $iss['target_dept_id'] ?>)" class="btn btn-success btn-sm">Acknowledge</button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Generate Stock Issue Voucher -->
<div id="newIssueModal" style="display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.6); z-index: 999; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div style="background: white; border-radius: 12px; width: 600px; max-width: 95%; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.2);">
        <div style="padding: 18px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
            <h3 style="font-size: 16px; font-weight: 700;">Issue Stock & Generate Voucher (Section 12)</h3>
            <button onclick="closeModal('newIssueModal')" style="border: none; background: none; font-size: 20px; cursor: pointer;">&times;</button>
        </div>
        <form method="POST" action="index.php?route=create_issue" style="padding: 24px;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                <div class="form-group">
                    <label class="form-label">Source Store</label>
                    <select name="source_store_id" class="form-control" required>
                        <option value="1">MSEC Central Store</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Voucher Type</label>
                    <select name="voucher_type" class="form-control" required>
                        <option value="Issue Voucher">Issue Voucher</option>
                        <option value="Delivery Challan">Delivery Challan</option>
                        <option value="Stock Transfer Note">Stock Transfer Note</option>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                <div class="form-group">
                    <label class="form-label">Destination Department</label>
                    <select name="target_dept_id" class="form-control" required>
                        <option value="1">CSE - Computer Science & Engineering</option>
                        <option value="2">ECE - Electronics & Communication</option>
                        <option value="3">EEE - Electrical & Electronics</option>
                        <option value="4">MECH - Mechanical Engineering</option>
                        <option value="5">CIVIL - Civil Engineering</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Target Laboratory</label>
                    <select name="target_lab_id" class="form-control">
                        <option value="">Department Tech Store</option>
                        <option value="1">Programming Lab</option>
                        <option value="2" selected>AI Lab</option>
                        <option value="3">Network Lab</option>
                    </select>
                </div>
            </div>

            <div style="border-top: 1px solid #e2e8f0; padding-top: 14px; margin-top: 10px;">
                <h4 style="font-size: 13.5px; font-weight: 700; margin-bottom: 8px;">Line Items to Dispatch:</h4>
                <div style="display: grid; grid-template-columns: 3fr 1fr; gap: 10px; margin-bottom: 10px;">
                    <select name="items[0][item_id]" class="form-control" required>
                        <?php foreach ($items as $it): ?>
                            <option value="<?= $it['id'] ?>" <?= $it['item_code'] === 'IT-KEY-002' ? 'selected' : '' ?>>
                                <?= htmlspecialchars($it['item_name']) ?> (Avail: <?= $it['total_available'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="number" name="items[0][quantity]" class="form-control" value="5" required placeholder="Qty">
                </div>
                <div style="display: grid; grid-template-columns: 3fr 1fr; gap: 10px;">
                    <select name="items[1][item_id]" class="form-control">
                        <option value="">-- Optional Second Item --</option>
                        <?php foreach ($items as $it): ?>
                            <option value="<?= $it['id'] ?>" <?= $it['item_code'] === 'IT-CBL-001' ? 'selected' : '' ?>>
                                <?= htmlspecialchars($it['item_name']) ?> (Avail: <?= $it['total_available'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="number" name="items[1][quantity]" class="form-control" value="10" placeholder="Qty">
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button type="button" onclick="closeModal('newIssueModal')" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Authorize Issue & Dispatch</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Digital Acknowledgement (Section 12) -->
<div id="ackModal" style="display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.6); z-index: 999; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div style="background: white; border-radius: 12px; width: 450px; max-width: 90%; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.2); overflow: hidden;">
        <div style="padding: 18px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
            <h3 style="font-size: 16px; font-weight: 700;">Department Acknowledgement</h3>
            <button onclick="closeModal('ackModal')" style="border: none; background: none; font-size: 20px; cursor: pointer;">&times;</button>
        </div>
        <form method="POST" action="index.php?route=ack_issue" style="padding: 24px;">
            <input type="hidden" name="issue_id" id="ack_issue_id">
            <input type="hidden" name="dept_id" id="ack_dept_id">
            <p style="font-size: 13.5px; margin-bottom: 14px;">
                Confirm receipt of items dispatched under voucher <strong id="ack_code_label"></strong> into department inventory?
            </p>
            <div class="form-group">
                <label class="form-label">Acknowledgement Remarks / Verification</label>
                <textarea name="remarks" class="form-control" rows="2" placeholder="e.g. Received in sound condition, physically verified and added to lab register">Physically verified in intact condition and updated to Department Inventory</textarea>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="closeModal('ackModal')" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-success">✓ Submit Digital Acknowledgement</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAckModal(id, code, deptId) {
    document.getElementById('ack_issue_id').value = id;
    document.getElementById('ack_dept_id').value = deptId;
    document.getElementById('ack_code_label').innerText = code;
    openModal('ackModal');
}
</script>

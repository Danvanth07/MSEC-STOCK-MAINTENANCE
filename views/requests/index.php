<?php
/**
 * MSEC SMART STOCK - Stock Requests & Indents View
 * Locked Scope: Section 10 (Stock Request / Indent) & Section 11 (Approval Workflow)
 */

$user = Auth::user();
$requests = StockRequest::getAll();
$items = Inventory::getAllItems();
?>

<div class="page-header">
    <div>
        <h2 class="page-title">Stock Requests & Indents</h2>
        <div class="page-desc">Requisitions, Multilevel Approval Hierarchy & Indent Tracking</div>
    </div>
    <div>
        <?php if (Auth::can('raise_indent')): ?>
            <button onclick="openModal('newRequestModal')" class="btn btn-primary">+ Raise New Stock Request</button>
        <?php endif; ?>
    </div>
</div>

<!-- Requests Table -->
<div class="card">
    <div class="card-header">
        <div class="card-title">Stock Requisitions Log</div>
        <div>
            <input type="text" class="form-control table-search-input" data-table="requestsTable" placeholder="Search requests..." style="width: 240px; padding: 6px 12px; font-size: 13px;">
        </div>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="data-table-container">
            <table class="data-table" id="requestsTable">
                <thead>
                    <tr>
                        <th>Request ID</th>
                        <th>Department</th>
                        <th>Requested By</th>
                        <th>Purpose</th>
                        <th>Required Date</th>
                        <th>Estimated Value</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $req): 
                        $statusClass = match($req['status']) {
                            'Approved' => 'badge-success',
                            'Pending Approval' => 'badge-warning',
                            'Fulfilled' => 'badge-primary',
                            'Rejected' => 'badge-danger',
                            default => 'badge-neutral'
                        };
                    ?>
                        <tr>
                            <td><code><?= htmlspecialchars($req['request_code']) ?></code></td>
                            <td>
                                <strong><?= htmlspecialchars($req['dept_code']) ?></strong>
                                <?php if (!empty($req['lab_name'])): ?>
                                    <div style="font-size: 11px; color: #64748b;"><?= htmlspecialchars($req['lab_name']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div><?= htmlspecialchars($req['requester_name']) ?></div>
                                <span class="badge badge-neutral" style="font-size: 10px;"><?= htmlspecialchars($req['requester_role']) ?></span>
                            </td>
                            <td><?= htmlspecialchars($req['purpose']) ?></td>
                            <td><?= htmlspecialchars($req['required_date']) ?></td>
                            <td><strong><?= CURRENCY_SYMBOL ?><?= number_format($req['total_estimated_value'], 2) ?></strong></td>
                            <td><span class="badge <?= $statusClass ?>"><?= htmlspecialchars($req['status']) ?></span></td>
                            <td>
                                <a href="index.php?route=view_request&id=<?= $req['id'] ?>" class="btn btn-secondary btn-sm">Inspect & Track</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- New Request Modal (Section 10) -->
<div id="newRequestModal" style="display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.6); z-index: 999; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div style="background: white; border-radius: 12px; width: 650px; max-width: 95%; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.2);">
        <div style="padding: 18px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
            <h3 style="font-size: 16px; font-weight: 700;">Raise Stock Indent / Requisition (Section 10)</h3>
            <button onclick="closeModal('newRequestModal')" style="border: none; background: none; font-size: 20px; cursor: pointer;">&times;</button>
        </div>
        <form method="POST" action="index.php?route=create_request" style="padding: 24px;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                <div class="form-group">
                    <label class="form-label">Department</label>
                    <select name="dept_id" class="form-control" required>
                        <option value="1">CSE - Computer Science & Engineering</option>
                        <option value="2">ECE - Electronics & Communication</option>
                        <option value="3">EEE - Electrical & Electronics</option>
                        <option value="4">MECH - Mechanical Engineering</option>
                        <option value="5">CIVIL - Civil Engineering</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Laboratory / Unit</label>
                    <select name="lab_id" class="form-control">
                        <option value="">General Department Requirement</option>
                        <option value="1">Programming Lab</option>
                        <option value="2" selected>AI Lab</option>
                        <option value="3">Network Lab</option>
                        <option value="4">Department Office</option>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 14px;">
                <div class="form-group">
                    <label class="form-label">Purpose / Justification</label>
                    <input type="text" name="purpose" class="form-control" required placeholder="e.g. AI Laboratory Maintenance & Lab Exam Preparation" value="AI Laboratory Maintenance & Semester Lab Exam Preparation">
                </div>
                <div class="form-group">
                    <label class="form-label">Required Date</label>
                    <input type="date" name="required_date" class="form-control" required value="<?= date('Y-m-d', strtotime('+7 days')) ?>">
                </div>
            </div>

            <div style="border-top: 1px solid #e2e8f0; padding-top: 16px; margin-top: 8px;">
                <h4 style="font-size: 14px; font-weight: 700; margin-bottom: 10px;">Select Requisition Items:</h4>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <div style="display: grid; grid-template-columns: 3fr 1fr 2fr; gap: 10px; align-items: center;">
                        <select name="items[0][item_id]" class="form-control" required>
                            <?php foreach ($items as $it): ?>
                                <option value="<?= $it['id'] ?>" <?= $it['item_code'] === 'IT-CBL-001' ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($it['item_name']) ?> (₹<?= $it['unit_cost'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="number" name="items[0][quantity]" class="form-control" placeholder="Qty" value="10" required>
                        <input type="text" name="items[0][notes]" class="form-control" placeholder="Notes" value="10 Nos for lab monitor displays">
                    </div>

                    <div style="display: grid; grid-template-columns: 3fr 1fr 2fr; gap: 10px; align-items: center;">
                        <select name="items[1][item_id]" class="form-control" required>
                            <?php foreach ($items as $it): ?>
                                <option value="<?= $it['id'] ?>" <?= $it['item_code'] === 'IT-KEY-002' ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($it['item_name']) ?> (₹<?= $it['unit_cost'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="number" name="items[1][quantity]" class="form-control" placeholder="Qty" value="5" required>
                        <input type="text" name="items[1][notes]" class="form-control" placeholder="Notes" value="5 Nos replacement for damaged terminals">
                    </div>

                    <div style="display: grid; grid-template-columns: 3fr 1fr 2fr; gap: 10px; align-items: center;">
                        <select name="items[2][item_id]" class="form-control" required>
                            <?php foreach ($items as $it): ?>
                                <option value="<?= $it['id'] ?>" <?= $it['item_code'] === 'STN-PPR-001' ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($it['item_name']) ?> (₹<?= $it['unit_cost'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="number" name="items[2][quantity]" class="form-control" placeholder="Qty" value="20" required>
                        <input type="text" name="items[2][notes]" class="form-control" placeholder="Notes" value="20 Reams for student records">
                    </div>
                </div>
            </div>

            <div style="background: #f1f5f9; padding: 12px; border-radius: 8px; margin-top: 18px; font-size: 12px; color: #475569;">
                ℹ️ <strong>Approval Matrix Rule:</strong> Calculated value dynamically routes through Coordinator &rarr; HOD &rarr; Store Keeper (&le;₹5K) / Vice Principal (&gt;₹5K) / Principal (&gt;₹25K) / Director (&gt;₹1L).
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 18px;">
                <button type="button" onclick="closeModal('newRequestModal')" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Submit Requisition</button>
            </div>
        </form>
    </div>
</div>

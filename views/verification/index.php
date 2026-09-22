<?php
/**
 * MSEC SMART STOCK - Stock Verification & Dead Stock Audits View
 * Locked Scope: Section 21 (Dead Stock) & Section 23 (Stock Verification Process)
 */

$user = Auth::user();
$items = Inventory::getAllItems();
$deadStock = Inventory::getDeadStockItems();
?>

<div class="page-header">
    <div>
        <h2 class="page-title">Stock Verification & Audit Governance</h2>
        <div class="page-desc">Physical Count vs System Variance &bull; Dead Stock Analysis (Section 21 & 23)</div>
    </div>
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <?php if (Auth::can('conduct_audit')): ?>
            <button onclick="openModal('auditModal')" class="btn btn-primary">+ Conduct Physical Stock Audit</button>
        <?php endif; ?>
    </div>
</div>

<!-- Section 21: Dead Stock Watchlist -->
<div class="card" style="margin-bottom: 24px;">
    <div class="card-header">
        <div class="card-title">⚠️ Dead Stock Watchlist (Section 21 Locked Examples)</div>
        <span class="badge badge-danger">Inactive &ge; 1 to 3 Years</span>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="data-table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Item Description</th>
                        <th>Store Location</th>
                        <th>Last Movement</th>
                        <th>Inactive Duration</th>
                        <th>Quantity</th>
                        <th>Unit Rate</th>
                        <th>Tied Capital Value</th>
                        <th>Recommended Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <strong>Old Keyboards (Legacy Batch)</strong>
                            <div style="font-size: 11px; color: #64748b;">Code: <code>IT-KEY-OLD</code> &bull; Non-Consumable</div>
                        </td>
                        <td>Central Scrap Shelf C</td>
                        <td><strong>2023</strong></td>
                        <td><span class="badge badge-warning">3 Years</span></td>
                        <td><strong>15</strong> Nos</td>
                        <td><?= CURRENCY_SYMBOL ?>500.00</td>
                        <td><strong style="color: #b91c1c;"><?= CURRENCY_SYMBOL ?>7,500.00</strong></td>
                        <td><span class="badge badge-neutral">Condemnation & E-Waste Disposal</span></td>
                    </tr>
                    <tr>
                        <td>
                            <strong>Cisco Catalyst 24-Port Gigabit Switch</strong>
                            <div style="font-size: 11px; color: #64748b;">Code: <code>IT-SWT-001</code> &bull; Network Hardware</div>
                        </td>
                        <td>Central Network Rack</td>
                        <td><strong>2022</strong></td>
                        <td><span class="badge badge-danger">4 Years</span></td>
                        <td><strong>3</strong> Nos</td>
                        <td><?= CURRENCY_SYMBOL ?>10,000.00</td>
                        <td><strong style="color: #b91c1c;"><?= CURRENCY_SYMBOL ?>30,000.00</strong></td>
                        <td><span class="badge badge-info">Reallocate to ECE / AI Laboratory</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Section 23: Verification & Variance Analysis -->
<div class="card">
    <div class="card-header">
        <div class="card-title">Physical Audit & Variance Records (Section 23)</div>
        <span class="badge badge-primary">Cycle: Quarterly & Annual</span>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="data-table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Item Name</th>
                        <th>Store</th>
                        <th>System Stock</th>
                        <th>Physical Count</th>
                        <th>Variance</th>
                        <th>Audit Status</th>
                        <th>Corrective Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>A4 Copier Sheets (75 GSM)</strong></td>
                        <td>MSEC Central Store</td>
                        <td>12 Reams</td>
                        <td>10 Reams</td>
                        <td><strong style="color: #dc2626;">-2 Reams</strong></td>
                        <td><span class="badge badge-warning">Variance Noted</span></td>
                        <td>Stock Adjustment applied via supporting memo</td>
                    </tr>
                    <tr>
                        <td><strong>Dell USB Keyboards</strong></td>
                        <td>CSE Department Store</td>
                        <td>20 Nos</td>
                        <td>20 Nos</td>
                        <td><strong style="color: #16a34a;">0 (Matched)</strong></td>
                        <td><span class="badge badge-success">Verified Accurate</span></td>
                        <td>Record locked & certified</td>
                    </tr>
                    <tr>
                        <td><strong>HDMI Cable 2.0 (3M)</strong></td>
                        <td>MSEC Central Store</td>
                        <td>65 Nos</td>
                        <td>65 Nos</td>
                        <td><strong style="color: #16a34a;">0 (Matched)</strong></td>
                        <td><span class="badge badge-success">Verified Accurate</span></td>
                        <td>Record locked & certified</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Conduct Physical Audit (Section 23) -->
<div id="auditModal" style="display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.6); z-index: 999; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div style="background: white; border-radius: 12px; width: 550px; max-width: 95%; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.2); overflow: hidden;">
        <div style="padding: 18px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
            <h3 style="font-size: 16px; font-weight: 700;">Initiate Stock Audit & Physical Count</h3>
            <button onclick="closeModal('auditModal')" style="border: none; background: none; font-size: 20px; cursor: pointer;">&times;</button>
        </div>
        <form method="POST" action="index.php?route=record_audit" style="padding: 24px;">
            <div class="form-group">
                <label class="form-label">Audit Cycle</label>
                <select name="cycle" class="form-control">
                    <option value="Monthly">Monthly Inspection</option>
                    <option value="Quarterly" selected>Quarterly Formal Audit</option>
                    <option value="Half-yearly">Half-yearly Audit</option>
                    <option value="Annual">Annual Institutional Verification</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Target Store</label>
                <select name="store_id" class="form-control">
                    <option value="1">Central Store</option>
                    <option value="2">CSE Department Store</option>
                    <option value="3">ECE Department Store</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Audit Notes & Team Observers</label>
                <textarea name="remarks" rows="2" class="form-control" placeholder="Names of auditing faculty and store representatives">Conducted by Internal Audit Cell with Department Coordinators present.</textarea>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="closeModal('auditModal')" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Generate Audit Report & Certificate</button>
            </div>
        </form>
    </div>
</div>

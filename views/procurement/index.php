<?php
/**
 * MSEC SMART STOCK - Procurement & Vendor Management View
 * Locked Scope: Section 6.1 (Stock Inward), 16 (Procurement Integration), 17 (Vendor Management)
 */

$user = Auth::user();
$vendors = Procurement::getVendors();
$grns = Procurement::getGoodsReceipts();
$items = Inventory::getAllItems();
?>

<div class="page-header">
    <div>
        <h2 class="page-title">Procurement & Vendor Management</h2>
        <div class="page-desc">Material Receipt, Quality Inspection & Goods Receipt Note (GRN) Inward (Section 16 & 17)</div>
    </div>
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <?php if (Auth::can('create_grn')): ?>
            <button onclick="openModal('newGRNModal')" class="btn btn-primary">+ Generate GRN & Stock Inward</button>
        <?php endif; ?>
    </div>
</div>

<!-- Workflow banner from Section 6.1 -->
<div style="background: white; border: 1px solid var(--border-color); border-radius: 12px; padding: 18px 24px; margin-bottom: 24px;">
    <div style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 10px;">Section 6.1 & 16 Locked Inward Pipeline:</div>
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; font-size: 13px; font-weight: 600;">
        <div style="padding: 6px 14px; background: #f1f5f9; border-radius: 6px;">1. Purchase Order</div>
        <span>&rarr;</span>
        <div style="padding: 6px 14px; background: #f1f5f9; border-radius: 6px;">2. Material Received</div>
        <span>&rarr;</span>
        <div style="padding: 6px 14px; background: #f1f5f9; border-radius: 6px;">3. Quality Verification</div>
        <span>&rarr;</span>
        <div style="padding: 6px 14px; background: #dbeafe; color: #1e40af; border-radius: 6px;">4. Goods Receipt Note (GRN)</div>
        <span>&rarr;</span>
        <div style="padding: 6px 14px; background: #f1f5f9; border-radius: 6px;">5. Stock Entry</div>
        <span>&rarr;</span>
        <div style="padding: 6px 14px; background: #dcfce7; color: #15803d; border-radius: 6px;">6. Central Inventory Updated ✓</div>
    </div>
</div>

<div class="responsive-split-grid">
    <!-- Left: Goods Receipt Notes (GRN) -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Goods Receipt Notes (GRN Inward Log)</div>
            <span class="badge badge-success">Live Inward</span>
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="data-table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>GRN Number</th>
                            <th>Vendor</th>
                            <th>Target Store</th>
                            <th>Invoice Ref</th>
                            <th>Verified By</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($grns)): ?>
                            <tr><td colspan="7" style="text-align: center; color: #64748b; padding: 25px;">No GRN receipts logged.</td></tr>
                        <?php else: ?>
                            <?php foreach ($grns as $g): ?>
                                <tr>
                                    <td><code><?= htmlspecialchars($g['grn_number']) ?></code></td>
                                    <td><strong><?= htmlspecialchars($g['company_name']) ?></strong></td>
                                    <td><?= htmlspecialchars($g['store_name']) ?></td>
                                    <td><?= htmlspecialchars($g['invoice_no'] ?: 'INV-PO-981') ?></td>
                                    <td><?= htmlspecialchars($g['verified_by_name']) ?></td>
                                    <td><?= htmlspecialchars($g['received_date']) ?></td>
                                    <td><span class="badge badge-success">Inventory Updated</span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Right: Approved Vendors (Section 17) -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Institutional Vendors</div>
            <span class="badge badge-neutral"><?= count($vendors) ?> Active</span>
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="data-table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Vendor</th>
                            <th>Rating</th>
                            <th>Categories</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vendors as $v): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($v['company_name']) ?></strong>
                                    <div style="font-size: 11px; color: #64748b;">GST: <?= htmlspecialchars($v['gst_number']) ?> &bull; <?= htmlspecialchars($v['mobile_number']) ?></div>
                                </td>
                                <td><span class="badge badge-warning">★ <?= number_format($v['performance_rating'], 1) ?></span></td>
                                <td style="font-size: 12px;"><?= htmlspecialchars($v['product_categories']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal: New GRN Entry (Section 6.1) -->
<div id="newGRNModal" style="display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.6); z-index: 999; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div style="background: white; border-radius: 12px; width: 620px; max-width: 95%; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.2);">
        <div style="padding: 18px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
            <h3 style="font-size: 16px; font-weight: 700;">Record Goods Receipt Note (GRN) & Stock Entry</h3>
            <button onclick="closeModal('newGRNModal')" style="border: none; background: none; font-size: 20px; cursor: pointer;">&times;</button>
        </div>
        <form method="POST" action="index.php?route=create_grn" style="padding: 24px;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                <div class="form-group">
                    <label class="form-label">Vendor (Supplier)</label>
                    <select name="vendor_id" class="form-control" required>
                        <?php foreach ($vendors as $v): ?>
                            <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['company_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Receiving Store</label>
                    <select name="store_id" class="form-control" required>
                        <option value="1">MSEC Central Store</option>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                <div class="form-group">
                    <label class="form-label">Vendor Invoice Number</label>
                    <input type="text" name="invoice_no" class="form-control" required placeholder="e.g. INV-2026-9810" value="INV-2026-<?= rand(1000, 9999) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Receipt Date</label>
                    <input type="date" name="received_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                </div>
            </div>

            <div style="border-top: 1px solid #e2e8f0; padding-top: 14px; margin-top: 10px;">
                <h4 style="font-size: 13.5px; font-weight: 700; margin-bottom: 8px;">Received Items (Quality Inspection Verified):</h4>
                <div style="display: grid; grid-template-columns: 3fr 1fr 1.5fr; gap: 10px;">
                    <select name="items[0][item_id]" class="form-control" required>
                        <?php foreach ($items as $it): ?>
                            <option value="<?= $it['id'] ?>" <?= $it['item_code'] === 'STN-PPR-001' ? 'selected' : '' ?>>
                                <?= htmlspecialchars($it['item_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="number" name="items[0][accepted_qty]" class="form-control" value="50" required placeholder="Qty">
                    <input type="number" name="items[0][unit_price]" class="form-control" value="280" required placeholder="Unit Cost (₹)">
                </div>
            </div>

            <div class="form-group" style="margin-top: 14px;">
                <label class="form-label">Quality Verification Remarks</label>
                <input type="text" name="remarks" class="form-control" value="All boxes inspected, factory sealed, accepted in 100% sound condition">
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button type="button" onclick="closeModal('newGRNModal')" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Save GRN & Update Central Inventory</button>
            </div>
        </form>
    </div>
</div>

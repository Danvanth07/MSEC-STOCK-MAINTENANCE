<?php
/**
 * MSEC SMART STOCK - Procurement & Vendor Management View
 * Locked Scope: Section 6.1 (Stock Inward), 16 (Procurement & POs), 17 (Vendor Management & Quotation Matrix)
 */

$user = Auth::user();
$vendors = Procurement::getVendors();
$purchaseOrders = Procurement::getPurchaseOrders();
$quotations = Procurement::getQuotations();
$grns = Procurement::getGoodsReceipts();
$items = Inventory::getAllItems();

$activeTab = $_GET['tab'] ?? 'pos';
?>

<div class="page-header">
    <div>
        <h2 class="page-title">Procurement & Vendor Management</h2>
        <div class="page-desc">Quotations &bull; Purchase Orders (PO) &bull; GRN Inward &bull; Vendor Registry (Section 6.1, 16 & 17)</div>
    </div>
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <?php if (Auth::can('create_po')): ?>
            <button onclick="openModal('newPOModal')" class="btn btn-primary">+ Generate Purchase Order (PO)</button>
            <button onclick="openModal('newQuoteModal')" class="btn btn-secondary">+ Add Vendor Quotation</button>
        <?php endif; ?>
        <?php if (Auth::can('create_grn')): ?>
            <button onclick="openModal('newGRNModal')" class="btn btn-success">+ Record GRN Inward</button>
        <?php endif; ?>
    </div>
</div>

<!-- Section 6.1 & 16 Inward Pipeline Workflow Bar -->
<div style="background: white; border: 1px solid var(--border-color); border-radius: 12px; padding: 18px 24px; margin-bottom: 24px;">
    <div style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 10px;">Section 6.1 & 16 Locked Procurement & Inward Pipeline:</div>
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; font-size: 13px; font-weight: 600;">
        <div style="padding: 6px 14px; background: #e0e7ff; color: #3730a3; border-radius: 6px;">1. Indent Sanctioned</div>
        <span>&rarr;</span>
        <div style="padding: 6px 14px; background: #e0e7ff; color: #3730a3; border-radius: 6px;">2. Comparative Quotations</div>
        <span>&rarr;</span>
        <div style="padding: 6px 14px; background: #dbeafe; color: #1e40af; border-radius: 6px;">3. Purchase Order (PO)</div>
        <span>&rarr;</span>
        <div style="padding: 6px 14px; background: #f1f5f9; border-radius: 6px;">4. Physical Delivery</div>
        <span>&rarr;</span>
        <div style="padding: 6px 14px; background: #fef3c7; color: #92400e; border-radius: 6px;">5. GRN & Quality Check</div>
        <span>&rarr;</span>
        <div style="padding: 6px 14px; background: #dcfce7; color: #15803d; border-radius: 6px;">6. Central Stock Credited ✓</div>
    </div>
</div>

<!-- Navigation Tabs -->
<div class="tabs-nav-scroll">
    <a href="index.php?route=procurement&tab=pos" class="btn <?= $activeTab === 'pos' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">1. Purchase Orders (<?= count($purchaseOrders) ?>)</a>
    <a href="index.php?route=procurement&tab=quotes" class="btn <?= $activeTab === 'quotes' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">2. Quotation Comparison Matrix (<?= count($quotations) ?>)</a>
    <a href="index.php?route=procurement&tab=grn" class="btn <?= $activeTab === 'grn' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">3. Goods Receipt Notes (GRN) (<?= count($grns) ?>)</a>
    <a href="index.php?route=procurement&tab=vendors" class="btn <?= $activeTab === 'vendors' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">4. Approved Vendors (<?= count($vendors) ?>)</a>
</div>

<?php if ($activeTab === 'pos'): ?>
    <!-- TAB 1: PURCHASE ORDERS -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Official Purchase Orders Register (Section 16)</div>
            <span class="badge badge-primary">Active Procurement</span>
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="data-table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>PO Number</th>
                            <th>Vendor (Supplier)</th>
                            <th>Linked Requisition</th>
                            <th>Total Amount</th>
                            <th>Tax Amount</th>
                            <th>PO Status</th>
                            <th>Issued By</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($purchaseOrders)): ?>
                            <tr><td colspan="9" style="text-align: center; color: #64748b; padding: 25px;">No purchase orders generated yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($purchaseOrders as $po): ?>
                                <tr>
                                    <td><code><?= htmlspecialchars($po['po_number']) ?></code></td>
                                    <td>
                                        <strong><?= htmlspecialchars($po['company_name']) ?></strong>
                                        <div style="font-size: 11px; color: #64748b;">Contact: <?= htmlspecialchars($po['mobile_number']) ?></div>
                                    </td>
                                    <td><code><?= htmlspecialchars($po['request_code'] ?? 'CSE-REQ-2026-00125') ?></code></td>
                                    <td><strong><?= CURRENCY_SYMBOL ?><?= number_format($po['total_amount'], 2) ?></strong></td>
                                    <td><?= CURRENCY_SYMBOL ?><?= number_format($po['tax_amount'], 2) ?></td>
                                    <td>
                                        <span class="badge <?= $po['status'] === 'Fulfilled' ? 'badge-success' : 'badge-warning' ?>">
                                            <?= htmlspecialchars($po['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($po['created_by_name'] ?? 'Purchase Officer') ?></td>
                                    <td><?= date('d M Y', strtotime($po['created_at'])) ?></td>
                                    <td>
                                        <div style="display: flex; gap: 6px;">
                                            <a href="index.php?route=view_po&id=<?= $po['id'] ?>" class="btn btn-secondary btn-sm" target="_blank">🖨️ View / Print PO</a>
                                            <?php if ($po['status'] !== 'Fulfilled' && Auth::can('create_grn')): ?>
                                                <button onclick="openGRNForPO(<?= $po['id'] ?>, <?= $po['vendor_id'] ?>, '<?= htmlspecialchars($po['po_number']) ?>')" class="btn btn-success btn-sm">Receive GRN</button>
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

<?php elseif ($activeTab === 'quotes'): ?>
    <!-- TAB 2: QUOTATION COMPARISON STATEMENT (Section 17) -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Comparative Quotation Matrix (L1 Recommendation & Commercial Terms)</div>
            <span class="badge badge-success">3-Quote Rule Enforced</span>
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="data-table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Quote Reference</th>
                            <th>Vendor Name</th>
                            <th>Rating</th>
                            <th>Total Quoted (Net)</th>
                            <th>Tax Amount</th>
                            <th>Delivery Period</th>
                            <th>Warranty Terms</th>
                            <th>Evaluation / L1 Status</th>
                            <th>Purchase Officer Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($quotations)): ?>
                            <tr><td colspan="9" style="text-align: center; color: #64748b; padding: 25px;">No vendor quotations logged.</td></tr>
                        <?php else: ?>
                            <?php foreach ($quotations as $q): ?>
                                <tr style="<?= !empty($q['is_selected']) ? 'background: #f0fdf4;' : '' ?>">
                                    <td><code><?= htmlspecialchars($q['quote_reference']) ?></code></td>
                                    <td>
                                        <strong><?= htmlspecialchars($q['company_name']) ?></strong>
                                        <div style="font-size: 11px; color: #64748b;">GST: <?= htmlspecialchars($q['gst_number']) ?></div>
                                    </td>
                                    <td><span class="badge badge-warning">★ <?= number_format($q['performance_rating'], 1) ?></span></td>
                                    <td><strong style="font-size: 14px; color: #1e3a8a;"><?= CURRENCY_SYMBOL ?><?= number_format($q['total_amount'], 2) ?></strong></td>
                                    <td><?= CURRENCY_SYMBOL ?><?= number_format($q['tax_amount'], 2) ?></td>
                                    <td><?= htmlspecialchars($q['delivery_period'] ?: '7 Days') ?></td>
                                    <td><?= htmlspecialchars($q['warranty_terms'] ?: '1 Year') ?></td>
                                    <td>
                                        <?php if (!empty($q['is_selected'])): ?>
                                            <span class="badge badge-success" style="font-weight: 700;">★ Recommended (L1)</span>
                                        <?php else: ?>
                                            <span class="badge badge-neutral">L2 Competitor</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-size: 12px; color: #475569;"><?= htmlspecialchars($q['remarks']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php elseif ($activeTab === 'grn'): ?>
    <!-- TAB 3: GOODS RECEIPT NOTES (Section 6.1) -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Goods Receipt Notes (GRN Inward Log & Quality Inspection)</div>
            <span class="badge badge-success">Inventory Updated</span>
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
                            <th>Inspection Remarks</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($grns)): ?>
                            <tr><td colspan="8" style="text-align: center; color: #64748b; padding: 25px;">No GRN receipts logged.</td></tr>
                        <?php else: ?>
                            <?php foreach ($grns as $g): ?>
                                <tr>
                                    <td><code><?= htmlspecialchars($g['grn_number']) ?></code></td>
                                    <td><strong><?= htmlspecialchars($g['company_name']) ?></strong></td>
                                    <td><?= htmlspecialchars($g['store_name']) ?></td>
                                    <td><?= htmlspecialchars($g['invoice_no'] ?: 'INV-PO-981') ?></td>
                                    <td><?= htmlspecialchars($g['verified_by_name']) ?></td>
                                    <td><?= htmlspecialchars($g['received_date']) ?></td>
                                    <td style="font-size: 12px; color: #475569;"><?= htmlspecialchars($g['remarks']) ?></td>
                                    <td><span class="badge badge-success">Stock Credited ✓</span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- TAB 4: VENDOR REGISTRY (Section 17) -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Approved Institutional Vendors (MSEC Empaneled Suppliers)</div>
            <span class="badge badge-neutral"><?= count($vendors) ?> Active Suppliers</span>
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="data-table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Vendor Code</th>
                            <th>Company Name</th>
                            <th>Contact Person</th>
                            <th>GSTIN / PAN</th>
                            <th>Product Categories</th>
                            <th>Performance Rating</th>
                            <th>Address</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vendors as $v): ?>
                            <tr>
                                <td><code><?= htmlspecialchars($v['vendor_code']) ?></code></td>
                                <td><strong><?= htmlspecialchars($v['company_name']) ?></strong></td>
                                <td>
                                    <div><?= htmlspecialchars($v['contact_person']) ?></div>
                                    <div style="font-size: 11px; color: #64748b;"><?= htmlspecialchars($v['mobile_number']) ?> &bull; <?= htmlspecialchars($v['email']) ?></div>
                                </td>
                                <td>
                                    <div style="font-size: 12px;"><strong>GST:</strong> <?= htmlspecialchars($v['gst_number']) ?></div>
                                    <div style="font-size: 11px; color: #64748b;"><strong>PAN:</strong> <?= htmlspecialchars($v['pan_number']) ?></div>
                                </td>
                                <td style="font-size: 12px;"><?= htmlspecialchars($v['product_categories']) ?></td>
                                <td><span class="badge badge-warning">★ <?= number_format($v['performance_rating'], 1) ?></span></td>
                                <td style="font-size: 11px; color: #64748b; max-width: 200px;"><?= htmlspecialchars($v['address']) ?></td>
                                <td><span class="badge badge-success"><?= htmlspecialchars($v['status']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- MODAL: GENERATE PURCHASE ORDER (PO) -->
<div id="newPOModal" style="display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.6); z-index: 999; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div style="background: white; border-radius: 12px; width: 640px; max-width: 95%; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.2);">
        <div style="padding: 18px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
            <h3 style="font-size: 16px; font-weight: 700;">Generate Official Purchase Order (Section 16)</h3>
            <button onclick="closeModal('newPOModal')" style="border: none; background: none; font-size: 20px; cursor: pointer;">&times;</button>
        </div>
        <form method="POST" action="index.php?route=create_po" style="padding: 24px;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                <div class="form-group">
                    <label class="form-label">Selected Vendor (L1 Recommended)</label>
                    <select name="vendor_id" class="form-control" required>
                        <?php foreach ($vendors as $v): ?>
                            <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['company_name']) ?> (★ <?= $v['performance_rating'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Linked Requisition / Indent ID</label>
                    <input type="number" name="indent_id" class="form-control" value="1" placeholder="e.g. 1">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                <div class="form-group">
                    <label class="form-label">Total Purchase Amount (Net ₹)</label>
                    <input type="number" step="0.01" name="total_amount" class="form-control" required value="12350.00">
                </div>
                <div class="form-group">
                    <label class="form-label">Applicable Tax / GST (₹)</label>
                    <input type="number" step="0.01" name="tax_amount" class="form-control" value="1884.00">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Commercial Terms & Conditions (Section 16)</label>
                <textarea name="terms" class="form-control" rows="3">1. Delivery within 10 days to MSEC Central Store, Kilakarai.
2. 100% payment released upon physical verification and GRN certification.
3. 1-Year standard on-site comprehensive replacement warranty.</textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button type="button" onclick="closeModal('newPOModal')" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Authorize & Issue Purchase Order</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: ADD VENDOR QUOTATION -->
<div id="newQuoteModal" style="display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.6); z-index: 999; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div style="background: white; border-radius: 12px; width: 620px; max-width: 95%; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.2);">
        <div style="padding: 18px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
            <h3 style="font-size: 16px; font-weight: 700;">Record Vendor Quotation (Section 17)</h3>
            <button onclick="closeModal('newQuoteModal')" style="border: none; background: none; font-size: 20px; cursor: pointer;">&times;</button>
        </div>
        <form method="POST" action="index.php?route=add_quotation" style="padding: 24px;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                <div class="form-group">
                    <label class="form-label">Vendor</label>
                    <select name="vendor_id" class="form-control" required>
                        <?php foreach ($vendors as $v): ?>
                            <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['company_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Quotation Reference Number</label>
                    <input type="text" name="quote_reference" class="form-control" required placeholder="e.g. QT-VND-2026-901" value="QT-VND-2026-<?= rand(100, 999) ?>">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px;">
                <div class="form-group">
                    <label class="form-label">Total Amount (₹)</label>
                    <input type="number" step="0.01" name="total_amount" class="form-control" required placeholder="12500">
                </div>
                <div class="form-group">
                    <label class="form-label">GST Tax (₹)</label>
                    <input type="number" step="0.01" name="tax_amount" class="form-control" value="1900">
                </div>
                <div class="form-group">
                    <label class="form-label">Delivery Period</label>
                    <input type="text" name="delivery_period" class="form-control" value="7 Days">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Warranty Terms</label>
                <input type="text" name="warranty_terms" class="form-control" value="1 Year Standard Manufacturer Warranty">
            </div>

            <div class="form-group">
                <label class="form-label">Evaluation Remarks</label>
                <input type="text" name="remarks" class="form-control" placeholder="Specifications match laboratory indent specifications">
            </div>

            <div style="margin-top: 10px;">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13.5px; font-weight: 600;">
                    <input type="checkbox" name="is_selected" value="1">
                    Designate as L1 (Lowest Quoted / Recommended)
                </label>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button type="button" onclick="closeModal('newQuoteModal')" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Quotation to Comparison Matrix</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: RECORD GOODS RECEIPT NOTE (GRN) -->
<div id="newGRNModal" style="display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.6); z-index: 999; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div style="background: white; border-radius: 12px; width: 620px; max-width: 95%; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.2);">
        <div style="padding: 18px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
            <h3 style="font-size: 16px; font-weight: 700;">Record Goods Receipt Note (GRN) & Physical Inspection</h3>
            <button onclick="closeModal('newGRNModal')" style="border: none; background: none; font-size: 20px; cursor: pointer;">&times;</button>
        </div>
        <form method="POST" action="index.php?route=create_grn" style="padding: 24px;">
            <input type="hidden" name="po_id" id="grn_po_id" value="1">

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                <div class="form-group">
                    <label class="form-label">Vendor (Supplier)</label>
                    <select name="vendor_id" id="grn_vendor_id" class="form-control" required>
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
                <h4 style="font-size: 13.5px; font-weight: 700; margin-bottom: 8px;">Received Items (Physical Verification & Quality Check):</h4>
                <div style="display: grid; grid-template-columns: 3fr 1fr 1.5fr; gap: 10px;">
                    <select name="items[0][item_id]" class="form-control" required>
                        <?php foreach ($items as $it): ?>
                            <option value="<?= $it['id'] ?>" <?= $it['item_code'] === 'STN-PPR-001' ? 'selected' : '' ?>>
                                <?= htmlspecialchars($it['item_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="number" name="items[0][accepted_qty]" class="form-control" value="50" required placeholder="Accepted Qty">
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

<script>
function openGRNForPO(poId, vendorId, poNum) {
    document.getElementById('grn_po_id').value = poId;
    document.getElementById('grn_vendor_id').value = vendorId;
    openModal('newGRNModal');
}
</script>

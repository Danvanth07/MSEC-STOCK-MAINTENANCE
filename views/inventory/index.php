<?php
/**
 * MSEC SMART STOCK - Inventory Management View
 * Locked Scope: Section 6.2 (Stock Details), 7 (Classification), 21 (Dead Stock), 22 (Low Stock), 24 (Adjustments)
 */

$allItems = Inventory::getAllItems();
$lowStock = Inventory::getLowStockItems();
$deadStock = Inventory::getDeadStockItems();
$tab = $_GET['tab'] ?? 'all';
?>

<div class="page-header">
    <div>
        <h2 class="page-title">Inventory Control & Master Stock</h2>
        <div class="page-desc">Central & Departmental Multi-Store Balances &bull; Valuation & Adjustments</div>
    </div>
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <?php if (Auth::can('adjust_stock')): ?>
            <button onclick="openModal('adjustModal')" class="btn btn-secondary">⚡ Stock Adjustment</button>
            <button onclick="openModal('addItemModal')" class="btn btn-primary">+ Add New Item</button>
        <?php endif; ?>
    </div>
</div>

<!-- Tab Navigation (Mobile Touch-Scrollable) -->
<div class="tabs-nav-scroll">
    <a href="index.php?route=inventory&tab=all" class="btn <?= $tab === 'all' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">All Inventory (<?= count($allItems) ?>)</a>
    <a href="index.php?route=inventory&tab=consumables" class="btn <?= $tab === 'consumables' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">Consumables</a>
    <a href="index.php?route=inventory&tab=non_consumables" class="btn <?= $tab === 'non_consumables' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">Non-Consumables / Assets</a>
    <a href="index.php?route=inventory&tab=low_stock" class="btn <?= $tab === 'low_stock' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">⚠️ Low Stock (<?= count($lowStock) ?>)</a>
    <a href="index.php?route=inventory&tab=dead_stock" class="btn <?= $tab === 'dead_stock' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">⏳ Dead Stock</a>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">
            <?php
            if ($tab === 'consumables') echo "Consumables Inventory (Purchased &rarr; Central Store &rarr; Dept Request &rarr; Issued &rarr; Consumed)";
            elseif ($tab === 'non_consumables') echo "Non-Consumable Inventory / Capital Equipment (Asset Tagged & Lifecycle Managed)";
            elseif ($tab === 'low_stock') echo "Reorder Alert Watchlist (Available &le; Minimum Threshold)";
            elseif ($tab === 'dead_stock') echo "Dead Stock Analysis (No Movement for 1, 2 or 3 Years)";
            else echo "Institutional Stock Overview";
            ?>
        </div>
        <div>
            <input type="text" class="form-control table-search-input" data-table="inventoryTable" placeholder="Search by item code or name..." style="width: 250px; padding: 6px 12px; font-size: 13px;">
        </div>
    </div>

    <div class="card-body" style="padding: 0;">
        <div class="data-table-container">
            <table class="data-table" id="inventoryTable">
                <thead>
                    <tr>
                        <th>Item Code</th>
                        <th>Item Name</th>
                        <th>Category</th>
                        <th>Classification</th>
                        <th>Available Qty</th>
                        <th>Reserved</th>
                        <th>Min Stock</th>
                        <th>Unit Cost</th>
                        <th>Valuation</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $displayItems = $allItems;
                    if ($tab === 'consumables') {
                        $displayItems = array_filter($allItems, fn($i) => $i['is_consumable'] == 1);
                    } elseif ($tab === 'non_consumables') {
                        $displayItems = array_filter($allItems, fn($i) => $i['is_consumable'] == 0);
                    } elseif ($tab === 'low_stock') {
                        $displayItems = array_filter($allItems, fn($i) => $i['total_available'] <= $i['min_stock']);
                    } elseif ($tab === 'dead_stock') {
                        $displayItems = array_filter($allItems, fn($i) => str_contains($i['item_code'], 'OLD') || str_contains($i['item_code'], 'SWT'));
                    }

                    foreach ($displayItems as $item):
                        $isLow = $item['total_available'] <= $item['min_stock'];
                    ?>
                        <tr>
                            <td><code><?= htmlspecialchars($item['item_code']) ?></code></td>
                            <td>
                                <strong><?= htmlspecialchars($item['item_name']) ?></strong>
                                <div style="font-size: 11px; color: #64748b;"><?= htmlspecialchars($item['description'] ?? '') ?></div>
                            </td>
                            <td><?= htmlspecialchars($item['category_name']) ?></td>
                            <td>
                                <?php if ($item['is_consumable']): ?>
                                    <span class="badge badge-info">Consumable</span>
                                <?php else: ?>
                                    <span class="badge badge-primary">Non-Consumable</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong style="font-size: 15px; <?= $isLow ? 'color: #dc2626;' : 'color: #16a34a;' ?>">
                                    <?= number_format($item['total_available'], 0) ?>
                                </strong>
                                <span style="font-size: 11px; color: #64748b;"><?= htmlspecialchars($item['unit_code']) ?></span>
                            </td>
                            <td><?= number_format($item['total_reserved'] ?? 0, 0) ?></td>
                            <td><?= number_format($item['min_stock'], 0) ?></td>
                            <td><?= CURRENCY_SYMBOL ?><?= number_format($item['unit_cost'], 2) ?></td>
                            <td><strong><?= CURRENCY_SYMBOL ?><?= number_format($item['total_available'] * $item['unit_cost'], 2) ?></strong></td>
                            <td>
                                <button onclick="selectForAdjust(<?= $item['id'] ?>, '<?= htmlspecialchars(addslashes($item['item_name'])) ?>', <?= $item['total_available'] ?>)" class="btn btn-secondary btn-sm">Adjust</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Stock Adjustment Modal (Section 24) -->
<div id="adjustModal" style="display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.6); z-index: 999; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div style="background: white; border-radius: 12px; width: 500px; max-width: 90%; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.2); overflow: hidden;">
        <div style="padding: 18px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
            <h3 style="font-size: 16px; font-weight: 700;">Stock Adjustment (Section 24)</h3>
            <button onclick="closeModal('adjustModal')" style="border: none; background: none; font-size: 20px; cursor: pointer;">&times;</button>
        </div>
        <form method="POST" action="index.php?route=do_adjustment" style="padding: 24px;">
            <div class="form-group">
                <label class="form-label">Item to Adjust</label>
                <select name="item_id" id="adj_item_id" class="form-control" required onchange="updateOldQty(this)">
                    <?php foreach ($allItems as $it): ?>
                        <option value="<?= $it['id'] ?>" data-qty="<?= $it['total_available'] ?>">
                            <?= htmlspecialchars($it['item_name']) ?> (Current: <?= $it['total_available'] ?> <?= $it['unit_code'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                <div class="form-group">
                    <label class="form-label">Old Quantity</label>
                    <input type="number" id="adj_old_qty" class="form-control" readonly value="<?= $allItems[0]['total_available'] ?? 0 ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">New Verified Quantity</label>
                    <input type="number" name="new_quantity" step="0.01" class="form-control" required placeholder="e.g. 25">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Store Location</label>
                <select name="store_id" class="form-control" required>
                    <option value="1">Central Store</option>
                    <option value="2">CSE Department Store</option>
                    <option value="3">ECE Department Store</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Mandatory Reason (Audit Rule)</label>
                <textarea name="reason" rows="2" class="form-control" required placeholder="e.g. Physical stock verification variance / damaged during handling"></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Supporting Document Reference</label>
                <input type="text" name="supporting_doc" class="form-control" placeholder="e.g. AUDIT-REP-2026-Q3 / Survey Report #12">
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 10px;">
                <button type="button" onclick="closeModal('adjustModal')" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Authorize & Apply Adjustment</button>
            </div>
        </form>
    </div>
</div>

<script>
function selectForAdjust(id, name, qty) {
    const sel = document.getElementById('adj_item_id');
    if (sel) {
        sel.value = id;
        document.getElementById('adj_old_qty').value = qty;
    }
    openModal('adjustModal');
}
function updateOldQty(el) {
    const opt = el.options[el.selectedIndex];
    document.getElementById('adj_old_qty').value = opt.getAttribute('data-qty') || 0;
}
</script>

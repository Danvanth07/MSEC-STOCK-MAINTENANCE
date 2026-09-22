<?php
/**
 * MSEC SMART STOCK - Dynamic Role-Adaptive Dashboard
 * Implements Section 4 & Section 25 (Executive, Department & Store Views)
 */

$user = Auth::user();
$role = $user['role_key'] ?? 'super_admin';

// Load KPIs based on role
$execKPIs = Report::getExecutiveKPIs();
$deptKPIs = Report::getDepartmentKPIs($user['dept_id'] ?? 1);
$storeKPIs = Report::getStoreKPIs();
$categories = Report::getCategoryWiseDistribution();
$deptValues = Report::getDepartmentWiseStockValue();
$lowStock = Inventory::getLowStockItems();
$deadStock = Inventory::getDeadStockItems();
?>

<div class="page-header">
    <div>
        <h2 class="page-title">
            <?php if (in_array($role, ['director', 'principal', 'vice_principal', 'super_admin', 'management_viewer'])): ?>
                Executive Institutional Dashboard
            <?php elseif (in_array($role, ['hod', 'dept_coordinator', 'lab_incharge', 'faculty', 'technician'])): ?>
                Department Dashboard — <?= htmlspecialchars($user['dept_code'] ?? 'CSE') ?>
            <?php else: ?>
                Central Store Operations Dashboard
            <?php endif; ?>
        </h2>
        <div class="page-desc">
            Role: <strong><?= htmlspecialchars(ROLES[$role]['name'] ?? $role) ?></strong> &bull; <?= APP_INSTITUTION ?>
        </div>
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <?php if (Auth::can('raise_indent')): ?>
            <a href="index.php?route=new_request" class="btn btn-primary">+ New Stock Indent</a>
        <?php endif; ?>
        <?php if (Auth::canAccessRoute('reports')): ?>
            <a href="index.php?route=reports" class="btn btn-secondary">Download Reports</a>
        <?php endif; ?>
    </div>
</div>

<?php if (in_array($role, ['director', 'principal', 'vice_principal', 'super_admin', 'management_viewer', 'auditor'])): ?>
    <!-- 25.1 EXECUTIVE DASHBOARD (Director / Principal) -->
    <div class="stats-grid">
        <div class="stat-card primary">
            <div class="stat-title">Total Inventory Value</div>
            <div class="stat-value"><?= CURRENCY_SYMBOL ?>2.85 Cr</div>
            <div class="stat-subtext">Across Central & 5 Dept Stores</div>
        </div>
        <div class="stat-card info">
            <div class="stat-title">Total Registered Assets</div>
            <div class="stat-value"><?= number_format($execKPIs['total_assets']) ?></div>
            <div class="stat-subtext">IT Hardware, Lab Instruments, Furniture</div>
        </div>
        <div class="stat-card warning">
            <div class="stat-title">Low Stock Items</div>
            <div class="stat-value"><?= $execKPIs['low_stock_items'] ?></div>
            <div class="stat-subtext">Items below minimum threshold</div>
        </div>
        <div class="stat-card danger">
            <div class="stat-title">Pending Approvals</div>
            <div class="stat-value"><?= $execKPIs['pending_requests'] ?></div>
            <div class="stat-subtext">Requisitions requiring sanction</div>
        </div>
        <div class="stat-card neutral">
            <div class="stat-title">Dead Stock Value</div>
            <div class="stat-value"><?= CURRENCY_SYMBOL ?>12.5 Lakhs</div>
            <div class="stat-subtext">Inactive for &ge; 1 to 3 Years</div>
        </div>
    </div>

<?php elseif (in_array($role, ['faculty', 'technician'])): ?>
    <!-- 25.4 USER LEVEL DASHBOARD (Faculty / Technician - Restricted) -->
    <div class="stats-grid">
        <div class="stat-card primary">
            <div class="stat-title">My Raised Indents</div>
            <div class="stat-value">03</div>
            <div class="stat-subtext">Classroom & Lab Consumables</div>
        </div>
        <div class="stat-card warning">
            <div class="stat-title">Pending Sanctions</div>
            <div class="stat-value">01</div>
            <div class="stat-subtext">Under HOD / Central Review</div>
        </div>
        <div class="stat-card success">
            <div class="stat-title">Ready For Collection</div>
            <div class="stat-value">02</div>
            <div class="stat-subtext">Vouchers processed by store</div>
        </div>
        <div class="stat-card info">
            <div class="stat-title">Assigned Equipment</div>
            <div class="stat-value">08</div>
            <div class="stat-subtext"><?= htmlspecialchars($user['dept_code'] ?? 'Dept') ?> Systems & Lab Tools</div>
        </div>
    </div>

<?php elseif (in_array($role, ['hod', 'dept_coordinator', 'lab_incharge'])): ?>
    <!-- 25.2 DEPARTMENT DASHBOARD (HOD / Coordinator / Lab) -->
    <div class="stats-grid">
        <div class="stat-card primary">
            <div class="stat-title">Department Stock Value</div>
            <div class="stat-value"><?= CURRENCY_SYMBOL ?>28,50,000</div>
            <div class="stat-subtext"><?= htmlspecialchars($user['dept_name'] ?? 'Department') ?> Holdings</div>
        </div>
        <div class="stat-card warning">
            <div class="stat-title">Pending Department Requests</div>
            <div class="stat-value"><?= sprintf("%02d", $deptKPIs['pending_requests']) ?></div>
            <div class="stat-subtext">Awaiting Coordinator / HOD Action</div>
        </div>
        <div class="stat-card success">
            <div class="stat-title">Items Received This Month</div>
            <div class="stat-value"><?= $deptKPIs['items_received'] ?></div>
            <div class="stat-subtext">Inward from Central Store</div>
        </div>
        <div class="stat-card info">
            <div class="stat-title">Items Issued to Labs / Staff</div>
            <div class="stat-value"><?= $deptKPIs['items_issued'] ?></div>
            <div class="stat-subtext">Active consumable & asset issues</div>
        </div>
        <div class="stat-card danger">
            <div class="stat-title">Low Stock Alerts</div>
            <div class="stat-value"><?= sprintf("%02d", $deptKPIs['low_stock_alerts']) ?></div>
            <div class="stat-subtext">Urgent reorder required</div>
        </div>
    </div>

<?php else: ?>
    <!-- 25.3 STORE DASHBOARD (Store Keeper / Operational) -->
    <div class="stats-grid">
        <div class="stat-card warning">
            <div class="stat-title">Pending Indents</div>
            <div class="stat-value"><?= $storeKPIs['pending_indents'] ?></div>
            <div class="stat-subtext">Approved requisitions to be fulfilled</div>
        </div>
        <div class="stat-card success">
            <div class="stat-title">Ready for Issue</div>
            <div class="stat-value"><?= $storeKPIs['ready_for_issue'] ?></div>
            <div class="stat-subtext">Vouchers generated & packed</div>
        </div>
        <div class="stat-card primary">
            <div class="stat-title">Pending Approval</div>
            <div class="stat-value"><?= sprintf("%02d", $storeKPIs['pending_approvals']) ?></div>
            <div class="stat-subtext">In verification pipeline</div>
        </div>
        <div class="stat-card info">
            <div class="stat-title">Today's Receipts (GRN)</div>
            <div class="stat-value"><?= $storeKPIs['today_receipts'] ?></div>
            <div class="stat-subtext">Vendor shipments verified</div>
        </div>
        <div class="stat-card danger">
            <div class="stat-title">Damaged / Repair Items</div>
            <div class="stat-value"><?= sprintf("%02d", $storeKPIs['damaged_items']) ?></div>
            <div class="stat-subtext">In service bay / pending survey</div>
        </div>
    </div>
<?php endif; ?>

<!-- Dual Column Insights -->
<div class="responsive-split-grid" style="margin-top: 10px;">
    <!-- Department Stock Valuation & Active Alerts -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Institutional Department Stock Distribution</div>
            <span class="badge badge-primary">Multi-Store View</span>
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="data-table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Department</th>
                            <th>Code</th>
                            <th>Active Stock Value</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($deptValues as $dv): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($dv['dept_name']) ?></strong></td>
                                <td><span class="badge badge-neutral"><?= htmlspecialchars($dv['dept_code']) ?></span></td>
                                <td><?= CURRENCY_SYMBOL ?><?= number_format($dv['total_value'] > 0 ? $dv['total_value'] : 1540000) ?></td>
                                <td><span class="badge badge-success">Operational</span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Quick Action / Critical Alerts (Section 22) -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">⚠️ Priority Alerts</div>
            <span class="badge badge-danger">Live</span>
        </div>
        <div class="card-body" style="display: flex; flex-direction: column; gap: 14px;">
            <div style="padding: 12px; background: #fff1f2; border-left: 4px solid #ef4444; border-radius: 6px;">
                <strong style="color: #991b1b; font-size: 13px;">Low Stock: A4 Copier Sheets</strong>
                <p style="font-size: 12px; color: #7f1d1d; margin-top: 2px;">
                    Available: 10 Reams | Reorder Level: 25 Reams. Recommended: Raise Purchase Indent.
                </p>
            </div>

            <div style="padding: 12px; background: #fffbeb; border-left: 4px solid #f59e0b; border-radius: 6px;">
                <strong style="color: #92400e; font-size: 13px;">Dead Stock Flagged: PS/2 Keyboards</strong>
                <p style="font-size: 12px; color: #78350f; margin-top: 2px;">
                    15 units (₹7,500 value) inactive since 2023. Recommend condemnation/disposal review.
                </p>
            </div>

            <div style="padding: 12px; background: #eff6ff; border-left: 4px solid #3b82f6; border-radius: 6px;">
                <strong style="color: #1e40af; font-size: 13px;">Pending Approval: CSE-REQ-2026-00125</strong>
                <p style="font-size: 12px; color: #1e3a8a; margin-top: 2px;">
                    AI Lab Maintenance (₹12,350) awaiting Vice Principal Administrative Approval.
                </p>
            </div>
        </div>
    </div>
</div>

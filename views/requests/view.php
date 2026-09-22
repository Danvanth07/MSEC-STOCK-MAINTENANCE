<?php
/**
 * MSEC SMART STOCK - Stock Request Details & Approval Engine Timeline
 * Locked Scope: Section 10 & 11 (Approval Workflow Pipeline)
 */

$requestId = (int)($_GET['id'] ?? 1);
$req = StockRequest::getById($requestId);
if (!$req) {
    echo "<div class='page-header'><h2>Request Not Found</h2></div>";
    return;
}

$items = StockRequest::getItems($requestId);
$approvals = StockRequest::getApprovals($requestId);
$currentUser = Auth::user();
$currentRole = $currentUser['role_key'] ?? '';

// Check if current user is active approver
$activeApproval = null;
foreach ($approvals as $appr) {
    if ($appr['status'] === 'Pending') {
        $activeApproval = $appr;
        break;
    }
}
$canApprove = $activeApproval && ($activeApproval['approver_role'] === $currentRole || $currentRole === 'super_admin');
?>

<div class="page-header">
    <div>
        <a href="index.php?route=requests" style="font-size: 13px; color: var(--primary); text-decoration: none; font-weight: 600;">&larr; Back to Requests</a>
        <h2 class="page-title" style="margin-top: 4px;">Requisition: <?= htmlspecialchars($req['request_code']) ?></h2>
        <div class="page-desc">Department: <strong><?= htmlspecialchars($req['dept_name']) ?></strong> &bull; Requested on <?= date('d M Y', strtotime($req['created_at'])) ?></div>
    </div>
    <div style="display: flex; gap: 10px; align-items: center;">
        <span class="badge <?= $req['status'] === 'Approved' ? 'badge-success' : ($req['status'] === 'Fulfilled' ? 'badge-primary' : 'badge-warning') ?>" style="font-size: 14px; padding: 6px 14px;">
            <?= htmlspecialchars($req['status']) ?>
        </span>
        <?php if ($req['status'] === 'Approved' && Auth::can('issue_stock')): ?>
            <a href="index.php?route=issue_stock&request_id=<?= $req['id'] ?>" class="btn btn-primary">Generate Store Issue Voucher &rarr;</a>
        <?php endif; ?>
    </div>
</div>

<div class="responsive-split-grid">
    <!-- Left Column: Requisition Meta & Items -->
    <div>
        <div class="card">
            <div class="card-header">
                <div class="card-title">Requisition Details & Purpose</div>
            </div>
            <div class="card-body">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; font-size: 13.5px;">
                    <div><strong>Department:</strong> <?= htmlspecialchars($req['dept_name']) ?> (<?= htmlspecialchars($req['dept_code']) ?>)</div>
                    <div><strong>Laboratory:</strong> <?= htmlspecialchars($req['lab_name'] ?? 'General') ?></div>
                    <div><strong>Requested By:</strong> <?= htmlspecialchars($req['requester_name']) ?> (<?= htmlspecialchars($req['requester_role']) ?>)</div>
                    <div><strong>Required By Date:</strong> <?= htmlspecialchars($req['required_date']) ?></div>
                </div>
                <div style="background: #f8fafc; border-left: 4px solid #4f46e5; padding: 12px 16px; border-radius: 6px; font-size: 13.5px;">
                    <strong>Purpose:</strong> <?= htmlspecialchars($req['purpose']) ?>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="card-title">Requested Line Items</div>
                <span style="font-weight: 700; color: #0f172a; font-size: 14px;">Total Value: <?= CURRENCY_SYMBOL ?><?= number_format($req['total_estimated_value'], 2) ?></span>
            </div>
            <div class="card-body" style="padding: 0;">
                <div class="data-table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Item Code</th>
                                <th>Item Name</th>
                                <th>Requested Qty</th>
                                <th>Approved Qty</th>
                                <th>Estimated Unit Price</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $it): ?>
                                <tr>
                                    <td><code><?= htmlspecialchars($it['item_code']) ?></code></td>
                                    <td>
                                        <strong><?= htmlspecialchars($it['item_name']) ?></strong>
                                        <div style="font-size: 11px; color: #64748b;"><?= htmlspecialchars($it['notes']) ?></div>
                                    </td>
                                    <td><strong><?= number_format($it['requested_quantity'], 0) ?></strong> <?= htmlspecialchars($it['unit_code']) ?></td>
                                    <td><?= number_format($it['approved_quantity'], 0) ?> <?= htmlspecialchars($it['unit_code']) ?></td>
                                    <td><?= CURRENCY_SYMBOL ?><?= number_format($it['unit_cost'], 2) ?></td>
                                    <td><strong><?= CURRENCY_SYMBOL ?><?= number_format($it['requested_quantity'] * $it['unit_cost'], 2) ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Dynamic Approval Timeline (Section 11) -->
    <div>
        <div class="card">
            <div class="card-header">
                <div class="card-title">Approval Hierarchy Pipeline</div>
                <span class="badge badge-primary">Matrix Engine</span>
            </div>
            <div class="card-body">
                <div style="display: flex; flex-direction: column; gap: 20px;">
                    <?php foreach ($approvals as $idx => $appr): 
                        $statusBg = match($appr['status']) {
                            'Approved' => '#dcfce7',
                            'Pending'  => '#fef3c7',
                            'Rejected' => '#fee2e2',
                            default    => '#f1f5f9'
                        };
                        $statusColor = match($appr['status']) {
                            'Approved' => '#15803d',
                            'Pending'  => '#b45309',
                            'Rejected' => '#b91c1c',
                            default    => '#64748b'
                        };
                    ?>
                        <div style="display: flex; gap: 14px; position: relative;">
                            <div style="width: 32px; height: 32px; border-radius: 50%; background: <?= $statusBg ?>; color: <?= $statusColor ?>; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px; flex-shrink: 0; border: 2px solid <?= $statusColor ?>;">
                                <?= $appr['status'] === 'Approved' ? '✓' : ($idx + 1) ?>
                            </div>
                            <div style="flex-grow: 1;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <strong style="font-size: 13.5px;"><?= htmlspecialchars($appr['level_title']) ?></strong>
                                    <span class="badge" style="background: <?= $statusBg ?>; color: <?= $statusColor ?>; font-size: 11px;">
                                        <?= htmlspecialchars($appr['status']) ?>
                                    </span>
                                </div>
                                <div style="font-size: 12px; color: #64748b; margin-top: 2px;">
                                    Role: <code><?= htmlspecialchars($appr['approver_role']) ?></code>
                                    <?php if (!empty($appr['approver_name'])): ?>
                                        &bull; by <?= htmlspecialchars($appr['approver_name']) ?>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($appr['comments'])): ?>
                                    <div style="font-size: 12px; font-style: italic; background: #f8fafc; padding: 6px 10px; border-radius: 4px; margin-top: 6px; border: 1px solid #e2e8f0;">
                                        "<?= htmlspecialchars($appr['comments']) ?>"
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Interactive Action Panel for Authorized Approver -->
                <?php if ($canApprove): ?>
                    <div style="margin-top: 28px; padding-top: 20px; border-top: 2px dashed #cbd5e1;">
                        <h4 style="font-size: 14px; font-weight: 700; color: #1e293b; margin-bottom: 12px;">
                            🎯 Your Action Required (<?= htmlspecialchars(ROLES[$currentRole]['name'] ?? $currentRole) ?>):
                        </h4>
                        <form method="POST" action="index.php?route=process_approval">
                            <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                            <div class="form-group">
                                <label class="form-label">Review / Sanction Comments</label>
                                <textarea name="comments" class="form-control" rows="2" placeholder="e.g. Verified and approved from departmental budget quota"></textarea>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                <button type="submit" name="action" value="Approve" class="btn btn-success">✓ Sanction & Approve</button>
                                <button type="submit" name="action" value="Reject" class="btn btn-danger">✕ Reject Requisition</button>
                            </div>
                        </form>
                    </div>
                <?php elseif ($activeApproval): ?>
                    <div style="margin-top: 24px; padding: 12px; background: #f8fafc; border-radius: 8px; font-size: 12px; color: #64748b; text-align: center;">
                        ⏳ Awaiting decision from <strong><?= htmlspecialchars(ROLES[$activeApproval['approver_role']]['name'] ?? $activeApproval['approver_role']) ?></strong>.
                        <div style="margin-top: 6px; font-size: 11px;">(Use the Demo Role switcher at the top to simulate that persona!)</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

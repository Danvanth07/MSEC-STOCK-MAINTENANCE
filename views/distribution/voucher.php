<?php
/**
 * MSEC SMART STOCK - Printable Official Distribution Voucher / Delivery Challan
 * Locked Requirements Baseline: Section 12 & 14
 */

$issueId = (int)($_GET['id'] ?? 1);
$issue = Distribution::getById($issueId);
if (!$issue) {
    die("Voucher not found.");
}

$items = Distribution::getItems($issueId);
$grandTotal = 0;
foreach ($items as $it) {
    $grandTotal += $it['total_value'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($issue['voucher_type']) ?> — <?= htmlspecialchars($issue['issue_code']) ?></title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { background: #e2e8f0; padding: 20px 0; }
        .barcode-box {
            font-family: monospace;
            font-size: 24px;
            letter-spacing: 5px;
            background: #f1f5f9;
            padding: 8px 16px;
            border-radius: 4px;
            display: inline-block;
            border: 1px dashed #64748b;
        }
    </style>
</head>
<body>

<div style="max-width: 850px; margin: 0 auto 16px; display: flex; justify-content: space-between; align-items: center;" class="no-print">
    <a href="index.php?route=distribution" class="btn btn-secondary">&larr; Back to Distribution List</a>
    <button onclick="window.print()" class="btn btn-primary">🖨️ Print Official Voucher</button>
</div>

<div class="voucher-paper">
    <div class="voucher-header" style="border-bottom: 2px solid #0f172a; padding-bottom: 14px; margin-bottom: 18px;">
        <div style="margin-bottom: 10px; text-align: center;">
            <img src="images/msec_letterhead.png" alt="Mohamed Sathak Engineering College" style="width: 100%; max-width: 100%; height: auto; display: block; margin: 0 auto;">
        </div>
        <div style="display: flex; justify-content: space-between; align-items: center; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px; padding: 10px 16px; margin-top: 10px;">
            <div style="text-align: left;">
                <div style="font-size: 11px; font-weight: 800; color: #475569; letter-spacing: 1px; text-transform: uppercase;">Central Inventory & Asset Directorate</div>
                <div class="voucher-type-title" style="margin: 2px 0 0 0; font-size: 17px; font-weight: 800; color: #0369a1;"><?= strtoupper(htmlspecialchars($issue['voucher_type'])) ?></div>
            </div>
            <div style="text-align: right;">
                <span class="barcode-box" style="font-size: 18px; padding: 6px 14px; background: #ffffff;">||| |||| || ||||| ||| <?= htmlspecialchars($issue['issue_code']) ?></span>
            </div>
        </div>
    </div>

    <div class="voucher-meta-grid">
        <div>
            <div><strong>Voucher No:</strong> <code><?= htmlspecialchars($issue['issue_code']) ?></code></div>
            <div><strong>Dispatch Source:</strong> <?= htmlspecialchars($issue['source_store_name']) ?></div>
            <div><strong>Issued By:</strong> <?= htmlspecialchars($issue['issued_by_name']) ?> (<?= htmlspecialchars($issue['issued_by_role']) ?>)</div>
        </div>
        <div style="text-align: right;">
            <div><strong>Issue Date:</strong> <?= date('d F Y, H:i A', strtotime($issue['issue_date'])) ?></div>
            <div><strong>Recipient Department:</strong> <strong><?= htmlspecialchars($issue['target_dept_name']) ?> (<?= htmlspecialchars($issue['target_dept_code']) ?>)</strong></div>
            <div><strong>Designated Lab:</strong> <?= htmlspecialchars($issue['target_lab_name'] ?? 'Department Tech Reserve') ?></div>
        </div>
    </div>

    <table class="data-table" style="border: 1px solid #cbd5e1; margin-bottom: 24px;">
        <thead>
            <tr style="background: #f1f5f9;">
                <th style="width: 50px;">S.No</th>
                <th>Item Code</th>
                <th>Item Description</th>
                <th style="text-align: right;">Qty Dispatched</th>
                <th>Unit</th>
                <th style="text-align: right;">Unit Rate (₹)</th>
                <th style="text-align: right;">Total Value (₹)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $idx => $it): ?>
                <tr>
                    <td><?= $idx + 1 ?></td>
                    <td><code><?= htmlspecialchars($it['item_code']) ?></code></td>
                    <td><strong><?= htmlspecialchars($it['item_name']) ?></strong></td>
                    <td style="text-align: right;"><strong><?= number_format($it['quantity'], 0) ?></strong></td>
                    <td><?= htmlspecialchars($it['unit_code']) ?></td>
                    <td style="text-align: right;"><?= number_format($it['unit_cost'], 2) ?></td>
                    <td style="text-align: right;"><?= number_format($it['total_value'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
            <tr style="background: #f8fafc; font-weight: 700;">
                <td colspan="6" style="text-align: right;">Grand Valuation Total:</td>
                <td style="text-align: right; font-size: 15px; color: #4338ca;"><?= CURRENCY_SYMBOL ?><?= number_format($grandTotal, 2) ?></td>
            </tr>
        </tbody>
    </table>

    <div style="font-size: 12px; color: #475569; margin-bottom: 20px;">
        <strong>Department Acknowledgement Status:</strong>
        <?php if (!empty($issue['receipt_id'])): ?>
            <span style="color: #15803d; font-weight: 700;">✓ Digitally Acknowledged by <?= htmlspecialchars($issue['acknowledged_by_name'] ?? 'Department') ?> on <?= date('d M Y, H:i A', strtotime($issue['acknowledgement_date'])) ?></span>
        <?php else: ?>
            <span style="color: #b45309; font-weight: 700;">Pending Physical & Digital Acceptance by Department Coordinator</span>
        <?php endif; ?>
    </div>

    <!-- Official 3-Party Signatures Block -->
    <div class="voucher-signatures">
        <div>
            <div class="signature-box">
                <div>(<?= htmlspecialchars($issue['issued_by_name']) ?>)</div>
                <div>Central Store Keeper</div>
            </div>
        </div>
        <div>
            <div class="signature-box">
                <div><?= htmlspecialchars($issue['acknowledged_by_name'] ?? '..................................') ?></div>
                <div>Department Receiving Officer</div>
            </div>
        </div>
        <div>
            <div class="signature-box">
                <div>Authorized Signatory</div>
                <div>Administrative Authority / VP</div>
            </div>
        </div>
    </div>
</div>

</body>
</html>

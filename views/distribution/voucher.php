<?php
/**
 * MSEC SMART STOCK - Printable Official Distribution Voucher / Delivery Challan
 * Mohamed Sathak Engineering College (MSEC) — Kilakarai
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
    $grandTotal += (float)$it['total_value'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($issue['voucher_type']) ?> — <?= htmlspecialchars($issue['issue_code']) ?></title>
    <link rel="stylesheet" href="css/style.css?v=<?= filemtime(dirname(__DIR__, 2) . '/public/css/style.css') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">
    <style>
        html, body {
            margin: 0;
            padding: 0;
            background: #e2e8f0;
            color: #0f172a;
            font-family: 'Plus Jakarta Sans', sans-serif;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }

        .voucher-viewport-wrap {
            padding: 24px 16px 40px;
            width: 100%;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .voucher-top-bar {
            width: 100%;
            max-width: 900px;
            margin-bottom: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            box-sizing: border-box;
        }

        .voucher-paper {
            background: #ffffff;
            width: 100%;
            max-width: 900px;
            box-sizing: border-box;
            padding: 36px 44px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
            border-radius: 12px;
            border: 1px solid #cbd5e1;
            font-size: 13px;
            line-height: 1.5;
        }

        .barcode-box {
            font-family: monospace;
            font-size: 16px;
            letter-spacing: 3px;
            background: #f8fafc;
            padding: 6px 14px;
            border-radius: 4px;
            display: inline-block;
            border: 1px dashed #64748b;
            color: #1e293b;
        }

        .voucher-meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 14px 18px;
            margin-bottom: 22px;
            background: #f8fafc;
            font-size: 12.5px;
        }

        /* Non-Overflowing Fixed Table Layout */
        .voucher-table-container {
            width: 100%;
            margin-bottom: 22px;
            box-sizing: border-box;
        }

        .voucher-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            font-size: 12.5px;
        }

        .voucher-table th {
            background: #f1f5f9;
            padding: 9px 10px;
            font-weight: 700;
            color: #334155;
            border: 1px solid #cbd5e1;
            font-size: 11.5px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            text-align: left;
            word-wrap: break-word;
        }

        .voucher-table td {
            padding: 9px 10px;
            border: 1px solid #e2e8f0;
            color: #1e293b;
            vertical-align: middle;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .voucher-table tr:hover td {
            background: #fbfcfe;
        }

        .voucher-signatures {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            text-align: center;
            margin-top: 36px;
            padding-top: 20px;
        }

        .signature-box {
            border-top: 1px dashed #64748b;
            padding-top: 8px;
            font-weight: 700;
            font-size: 12.5px;
            color: #0f172a;
        }

        .signature-box .sig-sub {
            font-size: 11px;
            color: #64748b;
            margin-top: 2px;
            font-weight: normal;
        }

        @media (max-width: 900px) {
            .voucher-paper {
                padding: 20px 16px;
                border-radius: 0;
            }
            .voucher-meta-grid {
                grid-template-columns: 1fr;
            }
            .voucher-table-container {
                overflow-x: auto;
            }
            .voucher-table {
                min-width: 650px;
            }
        }

        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .voucher-viewport-wrap {
                padding: 0 !important;
            }
            .voucher-paper {
                box-shadow: none !important;
                border: none !important;
                padding: 10px !important;
                max-width: 100% !important;
                width: 100% !important;
                margin: 0 !important;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>

<div class="voucher-viewport-wrap">
    <div class="voucher-top-bar no-print">
        <a href="index.php?route=distribution" class="btn btn-secondary">&larr; Back to Distribution List</a>
        <div style="display: flex; gap: 10px;">
            <button onclick="window.print()" class="btn btn-primary">🖨️ Print Official Voucher</button>
        </div>
    </div>

    <div class="voucher-paper">
        <div style="border-bottom: 2px solid #0f172a; padding-bottom: 14px; margin-bottom: 18px;">
            <div style="margin-bottom: 10px; text-align: center;">
                <img src="images/msec_letterhead.png" alt="Mohamed Sathak Engineering College" style="width: 100%; max-width: 100%; height: auto; display: block; margin: 0 auto;">
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; background: #0f172a; color: #ffffff; border-radius: 6px; padding: 10px 16px; margin-top: 10px;">
                <div style="text-align: left;">
                    <div style="font-size: 10.5px; font-weight: 700; color: #94a3b8; letter-spacing: 1px; text-transform: uppercase;">Central Inventory & Asset Directorate</div>
                    <div style="margin: 2px 0 0 0; font-size: 16px; font-weight: 800; color: #ffffff;"><?= strtoupper(htmlspecialchars($issue['voucher_type'])) ?></div>
                </div>
                <div style="text-align: right;">
                    <span class="barcode-box" style="background: #ffffff; color: #0f172a;">||| |||| || ||||| ||| <?= htmlspecialchars($issue['issue_code']) ?></span>
                </div>
            </div>
        </div>

        <div class="voucher-meta-grid">
            <div>
                <div><strong>Voucher No:</strong> <code><?= htmlspecialchars($issue['issue_code']) ?></code></div>
                <div style="margin-top: 3px;"><strong>Dispatch Source:</strong> <?= htmlspecialchars($issue['source_store_name']) ?></div>
                <div style="margin-top: 3px;"><strong>Issued By:</strong> <?= htmlspecialchars($issue['issued_by_name']) ?> (<?= htmlspecialchars($issue['issued_by_role']) ?>)</div>
            </div>
            <div style="text-align: right;">
                <div><strong>Issue Date:</strong> <?= date('d F Y, H:i A', strtotime($issue['issue_date'])) ?></div>
                <div style="margin-top: 3px;"><strong>Recipient Dept:</strong> <strong><?= htmlspecialchars($issue['target_dept_name']) ?> (<?= htmlspecialchars($issue['target_dept_code']) ?>)</strong></div>
                <div style="margin-top: 3px;"><strong>Designated Lab:</strong> <?= htmlspecialchars($issue['target_lab_name'] ?? 'Department Tech Reserve') ?></div>
            </div>
        </div>

        <!-- 100% Fixed Table Layout (No Overflow) -->
        <div class="voucher-table-container">
            <table class="voucher-table">
                <colgroup>
                    <col style="width: 6%;">
                    <col style="width: 16%;">
                    <col style="width: 36%;">
                    <col style="width: 12%;">
                    <col style="width: 8%;">
                    <col style="width: 10%;">
                    <col style="width: 12%;">
                </colgroup>
                <thead>
                    <tr>
                        <th style="text-align: center;">S.No</th>
                        <th>Item Code</th>
                        <th>Item Description</th>
                        <th style="text-align: right;">Dispatched</th>
                        <th style="text-align: center;">Unit</th>
                        <th style="text-align: right;">Unit Rate</th>
                        <th style="text-align: right;">Total Value</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $idx => $it): ?>
                        <tr>
                            <td style="text-align: center; color: #64748b;"><?= $idx + 1 ?></td>
                            <td><code><?= htmlspecialchars($it['item_code']) ?></code></td>
                            <td><strong><?= htmlspecialchars($it['item_name']) ?></strong></td>
                            <td style="text-align: right;"><strong><?= number_format($it['quantity'], 0) ?></strong></td>
                            <td style="text-align: center;"><?= htmlspecialchars($it['unit_code']) ?></td>
                            <td style="text-align: right;"><?= number_format($it['unit_cost'], 2) ?></td>
                            <td style="text-align: right;"><strong><?= number_format($it['total_value'], 2) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr style="background: #eef2ff; font-weight: 800; font-size: 13.5px;">
                        <td colspan="6" style="text-align: right; color: #1e3a8a;">Grand Valuation Total:</td>
                        <td style="text-align: right; color: #1e3a8a; font-size: 14px;"><?= CURRENCY_SYMBOL ?><?= number_format($grandTotal, 2) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div style="font-size: 12px; color: #475569; margin-bottom: 24px; padding: 10px 14px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px;">
            <strong>Department Acknowledgement Status:</strong>
            <?php if (!empty($issue['receipt_id'])): ?>
                <span style="color: #15803d; font-weight: 700; margin-left: 6px;">✓ Digitally Acknowledged by <?= htmlspecialchars($issue['acknowledged_by_name'] ?? 'Department') ?> on <?= date('d M Y, H:i A', strtotime($issue['acknowledgement_date'])) ?></span>
            <?php else: ?>
                <span style="color: #b45309; font-weight: 700; margin-left: 6px;">⏳ Pending Physical & Digital Acceptance by Department Coordinator</span>
            <?php endif; ?>
        </div>

        <!-- Official 3-Party Signatures Block -->
        <div class="voucher-signatures">
            <div>
                <div class="signature-box">
                    <div>(<?= htmlspecialchars($issue['issued_by_name']) ?>)</div>
                    <div class="sig-sub">Central Store Keeper</div>
                </div>
            </div>
            <div>
                <div class="signature-box">
                    <div><?= htmlspecialchars($issue['acknowledged_by_name'] ?? '..................................') ?></div>
                    <div class="sig-sub">Department Receiving Officer</div>
                </div>
            </div>
            <div>
                <div class="signature-box">
                    <div>Authorized Signatory</div>
                    <div class="sig-sub">Administrative Authority / VP</div>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>

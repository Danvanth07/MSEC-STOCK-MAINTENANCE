<?php
/**
 * MSEC SMART STOCK - Printable Official Purchase Order (PO)
 * Mohamed Sathak Engineering College (MSEC) — Kilakarai
 * Locked Requirements: Section 16 (Purchase Orders)
 */

$poId = (int)($_GET['id'] ?? 1);
$po = Procurement::getPOById($poId);
if (!$po) {
    die("Purchase Order not found.");
}

$pdo = Database::getInstance();
// Fetch items from the associated requisition or GRN
$itemsStmt = $pdo->prepare("
    SELECT sri.*, i.item_name, i.item_code, u.code as unit_code
    FROM stock_request_items sri
    JOIN items i ON sri.item_id = i.id
    JOIN units u ON i.unit_id = u.id
    WHERE sri.request_id = ?
");
$itemsStmt->execute([$po['indent_id']]);
$items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

// If requisition items empty, fallback to catalog items
if (empty($items)) {
    $items = [
        ['item_code' => 'IT-CBL-001', 'item_name' => 'HDMI Cable 2.0 (3 Meter)', 'approved_quantity' => 10, 'unit_code' => 'Nos', 'unit_cost' => 350],
        ['item_code' => 'IT-KEY-002', 'item_name' => 'Dell USB Multimedia Keyboard', 'approved_quantity' => 5, 'unit_code' => 'Nos', 'unit_cost' => 650],
        ['item_code' => 'STN-PPR-001', 'item_name' => 'A4 Copier Sheets (75 GSM, 500 Sheets)', 'approved_quantity' => 20, 'unit_code' => 'Reams', 'unit_cost' => 280]
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Order — <?= htmlspecialchars($po['po_number']) ?></title>
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

        .po-viewport-wrap {
            padding: 24px 16px 40px;
            width: 100%;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .po-top-bar {
            width: 100%;
            max-width: 900px;
            margin-bottom: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            box-sizing: border-box;
        }

        .po-paper {
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

        .po-letterhead {
            text-align: center;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 14px;
            margin-bottom: 18px;
        }

        .po-letterhead img {
            width: 100%;
            max-width: 100%;
            height: auto;
            display: block;
            margin: 0 auto;
        }

        .po-banner-bar {
            margin-top: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #0f172a;
            color: #ffffff;
            padding: 8px 16px;
            border-radius: 6px;
        }

        .po-banner-title {
            font-weight: 800;
            letter-spacing: 1.5px;
            font-size: 13.5px;
        }

        .po-banner-code {
            font-family: monospace;
            font-size: 14px;
            font-weight: 700;
            color: #93c5fd;
        }

        .meta-grid {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 18px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 14px 18px;
            margin-bottom: 20px;
            background: #f8fafc;
            font-size: 12.5px;
        }

        .vendor-title {
            font-size: 15px;
            font-weight: 800;
            color: #0f172a;
            margin: 2px 0 4px;
        }

        /* Document-Specific Non-Overflowing Fixed Table */
        .po-table-container {
            width: 100%;
            margin-bottom: 22px;
            box-sizing: border-box;
        }

        .po-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            font-size: 12.5px;
        }

        .po-table th {
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

        .po-table td {
            padding: 9px 10px;
            border: 1px solid #e2e8f0;
            color: #1e293b;
            vertical-align: middle;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .po-table tr:hover td {
            background: #fbfcfe;
        }

        .terms-box {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 14px 18px;
            margin-bottom: 24px;
            font-size: 12px;
            line-height: 1.6;
        }

        .po-signatures {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            text-align: center;
            margin-top: 36px;
            padding-top: 20px;
        }

        .sig-line {
            border-top: 1px dashed #64748b;
            padding-top: 8px;
            font-weight: 700;
            font-size: 12.5px;
            color: #0f172a;
        }

        .sig-sub {
            font-size: 11px;
            color: #64748b;
            margin-top: 2px;
        }

        @media (max-width: 900px) {
            .po-paper {
                padding: 20px 16px;
                border-radius: 0;
            }
            .meta-grid {
                grid-template-columns: 1fr;
            }
            .po-table-container {
                overflow-x: auto;
            }
            .po-table {
                min-width: 650px;
            }
        }

        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .po-viewport-wrap {
                padding: 0 !important;
            }
            .po-paper {
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

<div class="po-viewport-wrap">
    <!-- Top Action Bar -->
    <div class="po-top-bar no-print">
        <a href="index.php?route=procurement" class="btn btn-secondary">&larr; Back to Procurement</a>
        <div style="display: flex; gap: 10px;">
            <button onclick="window.print()" class="btn btn-primary">🖨️ Print Purchase Order</button>
        </div>
    </div>

    <!-- Official Document Paper -->
    <div class="po-paper">
        <!-- Institutional Letterhead -->
        <div class="po-letterhead">
            <img src="images/msec_letterhead.png" alt="Mohamed Sathak Engineering College">
            <div class="po-banner-bar">
                <span class="po-banner-title">OFFICIAL PURCHASE ORDER</span>
                <span class="po-banner-code"><?= htmlspecialchars($po['po_number']) ?></span>
            </div>
        </div>

        <!-- Vendor and PO Meta Grid -->
        <div class="meta-grid">
            <div>
                <div style="font-size: 10.5px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Supplier / Vendor Details:</div>
                <div class="vendor-title"><?= htmlspecialchars($po['company_name']) ?></div>
                <div style="color: #475569;"><?= htmlspecialchars($po['vendor_address']) ?></div>
                <div style="margin-top: 4px;"><strong>Contact:</strong> <?= htmlspecialchars($po['contact_person']) ?> (<?= htmlspecialchars($po['mobile_number']) ?>)</div>
                <div style="margin-top: 2px;"><strong>GSTIN:</strong> <code><?= htmlspecialchars($po['gst_number']) ?></code> &bull; <strong>PAN:</strong> <code><?= htmlspecialchars($po['pan_number']) ?></code></div>
            </div>
            <div style="text-align: right; display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div><strong>PO Number:</strong> <code style="font-weight: 700; color: #1e40af;"><?= htmlspecialchars($po['po_number']) ?></code></div>
                    <div style="margin-top: 2px;"><strong>Date:</strong> <?= date('d F Y', strtotime($po['created_at'])) ?></div>
                    <div style="margin-top: 2px;"><strong>Indent Reference:</strong> <code><?= htmlspecialchars($po['request_code'] ?? 'CSE-REQ-2026-00125') ?></code></div>
                    <div style="margin-top: 2px;"><strong>Department:</strong> <?= htmlspecialchars($po['dept_name'] ?? 'Computer Science & Engineering') ?></div>
                </div>
                <div style="margin-top: 6px;">
                    <strong>Status:</strong> <span class="badge badge-success"><?= htmlspecialchars($po['status']) ?></span>
                </div>
            </div>
        </div>

        <!-- Items Table (100% Fixed Width Layout — Guaranteed No Overflow) -->
        <div class="po-table-container">
            <table class="po-table">
                <colgroup>
                    <col style="width: 6%;">
                    <col style="width: 15%;">
                    <col style="width: 35%;">
                    <col style="width: 10%;">
                    <col style="width: 8%;">
                    <col style="width: 12%;">
                    <col style="width: 14%;">
                </colgroup>
                <thead>
                    <tr>
                        <th style="text-align: center;">S.No</th>
                        <th>Item Code</th>
                        <th>Item Description & Technical Specs</th>
                        <th style="text-align: right;">Quantity</th>
                        <th style="text-align: center;">Unit</th>
                        <th style="text-align: right;">Unit Rate (₹)</th>
                        <th style="text-align: right;">Amount (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $subTotal = 0;
                    foreach ($items as $idx => $it): 
                        $qty = (float)($it['approved_quantity'] ?? $it['requested_quantity'] ?? 1);
                        $rate = (float)$it['unit_cost'];
                        $lineAmt = $qty * $rate;
                        $subTotal += $lineAmt;
                    ?>
                        <tr>
                            <td style="text-align: center; color: #64748b;"><?= $idx + 1 ?></td>
                            <td><code><?= htmlspecialchars($it['item_code']) ?></code></td>
                            <td><strong><?= htmlspecialchars($it['item_name']) ?></strong></td>
                            <td style="text-align: right;"><strong><?= number_format($qty, 0) ?></strong></td>
                            <td style="text-align: center;"><?= htmlspecialchars($it['unit_code'] ?? 'Nos') ?></td>
                            <td style="text-align: right;"><?= number_format($rate, 2) ?></td>
                            <td style="text-align: right;"><strong><?= number_format($lineAmt, 2) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr style="background: #f8fafc;">
                        <td colspan="6" style="text-align: right; font-weight: 700; color: #475569;">Sub-Total:</td>
                        <td style="text-align: right; font-weight: 700;"><?= CURRENCY_SYMBOL ?><?= number_format($subTotal, 2) ?></td>
                    </tr>
                    <tr style="background: #f8fafc;">
                        <td colspan="6" style="text-align: right; font-weight: 700; color: #475569;">GST / Applicable Taxes:</td>
                        <td style="text-align: right; font-weight: 700;"><?= CURRENCY_SYMBOL ?><?= number_format($po['tax_amount'], 2) ?></td>
                    </tr>
                    <tr style="background: #eef2ff; font-weight: 800; font-size: 13.5px;">
                        <td colspan="6" style="text-align: right; color: #1e3a8a;">Total Purchase Order Value (Net):</td>
                        <td style="text-align: right; color: #1e3a8a; font-size: 14px;"><?= CURRENCY_SYMBOL ?><?= number_format($po['total_amount'], 2) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Commercial Terms & Conditions (Section 16) -->
        <div class="terms-box">
            <strong style="color: #0f172a; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px;">Institutional Terms & Conditions:</strong>
            <div style="margin-top: 6px; white-space: pre-line; color: #475569;">
                <?= htmlspecialchars($po['terms'] ?? "1. Delivery to Central Store, Mohamed Sathak Engineering College, Kilakarai within 10 days.\n2. Materials will be subjected to physical inspection; rejected lots must be replaced at vendor's cost.\n3. Payment will be released within 15 days upon GRN certification.\n4. Comprehensive 1-year on-site replacement warranty applicable.") ?>
            </div>
        </div>

        <!-- Official Signatures Block -->
        <div class="po-signatures">
            <div>
                <div class="sig-line">Purchase Officer</div>
                <div class="sig-sub">(M. Senthil Kumar)</div>
            </div>
            <div>
                <div class="sig-line">Central Store Keeper</div>
                <div class="sig-sub">(S. Mohamed Riaz)</div>
            </div>
            <div>
                <div class="sig-line">Principal / Director</div>
                <div class="sig-sub">Mohamed Sathak Engg College</div>
            </div>
        </div>
    </div>
</div>

</body>
</html>

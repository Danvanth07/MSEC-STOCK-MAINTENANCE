<?php
/**
 * MSEC SMART STOCK - Front Controller & Application Router
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/core/Database.php';
require_once dirname(__DIR__) . '/core/Auth.php';
require_once dirname(__DIR__) . '/core/ApprovalEngine.php';

// Models
require_once dirname(__DIR__) . '/models/Inventory.php';
require_once dirname(__DIR__) . '/models/Request.php';
require_once dirname(__DIR__) . '/models/Distribution.php';
require_once dirname(__DIR__) . '/models/Movement.php';
require_once dirname(__DIR__) . '/models/Asset.php';
require_once dirname(__DIR__) . '/models/Procurement.php';
require_once dirname(__DIR__) . '/models/Report.php';

Auth::init();

// Quick Persona Switcher handler (for easy testing by Team Leader & Juniors)
if (isset($_GET['switch_role']) && !empty($_GET['switch_role'])) {
    Auth::loginAs($_GET['switch_role']);
    $redirectRoute = $_GET['route'] ?? 'dashboard';
    header("Location: index.php?route={$redirectRoute}");
    exit;
}

$route = $_GET['route'] ?? 'dashboard';

// Handle Public / Auth Routes
if ($route === 'login') {
    include dirname(__DIR__) . '/views/auth/login.php';
    exit;
}

if ($route === 'switch_persona') {
    $role = $_GET['role'] ?? 'store_keeper';
    Auth::loginAs($role);
    header("Location: index.php?route=dashboard");
    exit;
}

if ($route === 'do_login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if (Auth::login($username, $password)) {
        header("Location: index.php?route=dashboard");
    } else {
        header("Location: index.php?route=login&error=Invalid+credentials.+Use+default+password123");
    }
    exit;
}

if ($route === 'logout') {
    Auth::logout();
    header("Location: index.php?route=login");
    exit;
}

// Auto-authenticate as store_keeper if visiting root directly for seamless first demo experience
if (!Auth::check()) {
    Auth::loginAs('store_keeper');
}

$user = Auth::user();

// ==========================================
// ACTION HANDLERS (POST)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($route) {
        case 'do_adjustment':
            if (!Auth::can('adjust_stock')) {
                header("Location: index.php?route=unauthorized");
                exit;
            }
            $storeId = (int)$_POST['store_id'];
            $itemId = (int)$_POST['item_id'];
            $newQty = (float)$_POST['new_quantity'];
            $reason = $_POST['reason'];
            $doc = $_POST['supporting_doc'] ?? null;
            Inventory::adjustStock($storeId, $itemId, $newQty, $reason, $doc, $user['id']);
            Auth::logActivity('Stock Adjustment Applied', 'Inventory', "Adjusted item #{$itemId} in store #{$storeId} to {$newQty}");
            header("Location: index.php?route=inventory&msg=Stock+adjusted+successfully");
            exit;

        case 'create_request':
            if (!Auth::can('create_request')) {
                header("Location: index.php?route=unauthorized");
                exit;
            }
            $deptId = (int)$_POST['dept_id'];
            $labId = !empty($_POST['lab_id']) ? (int)$_POST['lab_id'] : null;
            $purpose = $_POST['purpose'];
            $reqDate = $_POST['required_date'];
            $items = $_POST['items'] ?? [];
            $reqId = StockRequest::create($deptId, $labId, $user['id'], $purpose, $reqDate, $items);
            if ($reqId) {
                Auth::logActivity('Raised Stock Requisition', 'Stock Request', "Requisition #{$reqId} generated");
                header("Location: index.php?route=view_request&id={$reqId}");
            } else {
                header("Location: index.php?route=requests&error=Failed+to+create+request");
            }
            exit;

        case 'process_approval':
            if (!Auth::can('approve_request')) {
                header("Location: index.php?route=unauthorized");
                exit;
            }
            $reqId = (int)$_POST['request_id'];
            $action = $_POST['action'] ?? 'Approve';
            $comments = $_POST['comments'] ?? null;
            ApprovalEngine::processAction($reqId, $action, $comments);
            header("Location: index.php?route=view_request&id={$reqId}");
            exit;

        case 'create_issue':
            if (!Auth::can('issue_stock')) {
                header("Location: index.php?route=unauthorized");
                exit;
            }
            $sourceStoreId = (int)$_POST['source_store_id'];
            $targetDeptId = (int)$_POST['target_dept_id'];
            $targetLabId = !empty($_POST['target_lab_id']) ? (int)$_POST['target_lab_id'] : null;
            $voucherType = $_POST['voucher_type'];
            $items = $_POST['items'] ?? [];
            $filteredItems = array_filter($items, fn($it) => !empty($it['item_id']) && !empty($it['quantity']));
            $issueId = Distribution::createIssue($sourceStoreId, $targetDeptId, $targetLabId, $user['id'], $voucherType, $filteredItems);
            if ($issueId) {
                Auth::logActivity('Issued Stock', 'Stock Distribution', "Voucher #{$issueId} issued");
                header("Location: index.php?route=view_voucher&id={$issueId}");
            } else {
                header("Location: index.php?route=distribution&error=Failed+to+issue");
            }
            exit;

        case 'ack_issue':
            if (!Auth::can('ack_receipt')) {
                header("Location: index.php?route=unauthorized");
                exit;
            }
            $issueId = (int)$_POST['issue_id'];
            $deptId = (int)$_POST['dept_id'];
            $remarks = $_POST['remarks'] ?? null;
            Distribution::acknowledge($issueId, $deptId, $user['id'], $remarks);
            Auth::logActivity('Department Acknowledged Receipt', 'Stock Distribution', "Voucher #{$issueId} acknowledged");
            header("Location: index.php?route=distribution&msg=Receipt+acknowledged");
            exit;

        case 'create_movement':
            if (!Auth::can('create_transfer')) {
                header("Location: index.php?route=unauthorized");
                exit;
            }
            $movType = $_POST['movement_type'];
            $sourceDeptId = !empty($_POST['source_dept_id']) ? (int)$_POST['source_dept_id'] : null;
            $targetDeptId = !empty($_POST['target_dept_id']) ? (int)$_POST['target_dept_id'] : null;
            $notes = $_POST['notes'] ?? '';
            $items = $_POST['items'] ?? [];
            $movId = Movement::createTransfer($movType, $sourceDeptId, $targetDeptId, null, null, $user['id'], $notes, $items);
            Auth::logActivity('Initiated Stock Transfer', 'Stock Movement', "Transfer #{$movId} created");
            header("Location: index.php?route=movement");
            exit;

        case 'create_asset':
            if (!Auth::can('create_asset')) {
                header("Location: index.php?route=unauthorized");
                exit;
            }
            $assetData = $_POST;
            $newAssetId = Asset::create($assetData);
            Auth::logActivity('Registered Asset', 'Asset Management', "Asset {$assetData['asset_id']} created");
            header("Location: index.php?route=assets");
            exit;

        case 'report_damage':
            if (!Auth::can('report_damage')) {
                header("Location: index.php?route=unauthorized");
                exit;
            }
            $assetId = (int)$_POST['asset_id'];
            $issue = $_POST['issue'];
            $status = $_POST['status'] ?? 'Under Repair';
            Asset::addMaintenance($assetId, $issue, $user['id'], $status);
            Auth::logActivity('Reported Damaged Asset', 'Damaged Stock', "Asset #{$assetId} sent to repair bay");
            header("Location: index.php?route=assets&status=Under+Repair");
            exit;

        case 'create_grn':
            if (!Auth::can('create_grn')) {
                header("Location: index.php?route=unauthorized");
                exit;
            }
            $grnData = [
                'vendor_id'     => (int)$_POST['vendor_id'],
                'store_id'      => (int)$_POST['store_id'],
                'received_date' => $_POST['received_date'],
                'verified_by'   => $user['id'],
                'invoice_no'    => $_POST['invoice_no'],
                'remarks'       => $_POST['remarks'] ?? ''
            ];
            $items = $_POST['items'] ?? [];
            Procurement::createGRN($grnData, $items);
            Auth::logActivity('Generated GRN Receipt', 'Procurement', "GRN verified and inventory updated");
            header("Location: index.php?route=procurement");
            exit;

        case 'record_audit':
            if (!Auth::can('conduct_audit')) {
                header("Location: index.php?route=unauthorized");
                exit;
            }
            Auth::logActivity('Conducted Physical Verification', 'Stock Verification', "Audit executed for Store #{$_POST['store_id']}");
            header("Location: index.php?route=verification&msg=Audit+recorded");
            exit;
    }
}

// GET status transition handlers
if ($route === 'advance_movement' && isset($_GET['id'], $_GET['status'])) {
    if (!Auth::can('advance_transfer')) {
        header("Location: index.php?route=unauthorized");
        exit;
    }
    Movement::advanceStatus((int)$_GET['id'], $_GET['status'], $user['id']);
    Auth::logActivity("Updated Movement Status to {$_GET['status']}", 'Stock Movement', "Movement #{$_GET['id']}");
    header("Location: index.php?route=movement");
    exit;
}

// Standalone Printable Voucher route (renders without application frame)
if ($route === 'view_voucher') {
    include dirname(__DIR__) . '/views/distribution/voucher.php';
    exit;
}

// RBAC Route Guard: Verify role permissions before rendering
if (!Auth::canAccessRoute($route)) {
    $route = 'unauthorized';
}

// ==========================================
// RENDER STANDARD VIEW WITH LAYOUT
// ==========================================
include dirname(__DIR__) . '/views/layout/header.php';

switch ($route) {
    case 'unauthorized':
        include dirname(__DIR__) . '/views/errors/unauthorized.php';
        break;
    case 'dashboard':
        include dirname(__DIR__) . '/views/dashboard/index.php';
        break;
    case 'inventory':
        include dirname(__DIR__) . '/views/inventory/index.php';
        break;
    case 'requests':
    case 'new_request':
        include dirname(__DIR__) . '/views/requests/index.php';
        break;
    case 'view_request':
        include dirname(__DIR__) . '/views/requests/view.php';
        break;
    case 'distribution':
    case 'issue_stock':
        include dirname(__DIR__) . '/views/distribution/index.php';
        break;
    case 'movement':
        include dirname(__DIR__) . '/views/movement/index.php';
        break;
    case 'assets':
        include dirname(__DIR__) . '/views/assets/index.php';
        break;
    case 'procurement':
        include dirname(__DIR__) . '/views/procurement/index.php';
        break;
    case 'verification':
        include dirname(__DIR__) . '/views/verification/index.php';
        break;
    case 'reports':
        include dirname(__DIR__) . '/views/reports/index.php';
        break;
    case 'admin_masters':
        include dirname(__DIR__) . '/views/admin/masters.php';
        break;
    case 'admin_users':
        include dirname(__DIR__) . '/views/admin/users.php';
        break;
    case 'admin_audit':
        include dirname(__DIR__) . '/views/admin/audit.php';
        break;
    default:
        include dirname(__DIR__) . '/views/dashboard/index.php';
        break;
}

include dirname(__DIR__) . '/views/layout/footer.php';

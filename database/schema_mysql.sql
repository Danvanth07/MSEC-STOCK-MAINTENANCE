-- ==========================================================
-- MSEC SMART STOCK - Enterprise MySQL/MariaDB 8.0 Schema
-- Smart Inventory, Asset & Distribution Management Portal
-- Mohamed Sathak Engineering College (Autonomous), Kilakarai
-- ==========================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. INSTITUTIONAL & ACCESS CONTROL TABLES
CREATE TABLE IF NOT EXISTS organizations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    address TEXT,
    phone VARCHAR(50),
    email VARCHAR(100),
    established_year INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    org_id INT DEFAULT 1,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    hod_user_id INT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS laboratories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dept_id INT NOT NULL,
    code VARCHAR(50) NOT NULL,
    name VARCHAR(255) NOT NULL,
    incharge_user_id INT NULL,
    location VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (dept_id) REFERENCES departments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    building VARCHAR(100) NOT NULL,
    floor VARCHAR(50),
    room_number VARCHAR(50),
    description VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS stores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    type ENUM('CENTRAL', 'DEPARTMENT') DEFAULT 'DEPARTMENT',
    dept_id INT NULL,
    keeper_user_id INT NULL,
    location_id INT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_key VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    level VARCHAR(100) NOT NULL,
    description TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(50),
    employee_id VARCHAR(50) NULL,
    designation VARCHAR(150) NULL,
    role_key VARCHAR(50) NOT NULL,
    dept_id INT NULL,
    lab_id INT NULL,
    status ENUM('Active', 'Inactive', 'Locked') DEFAULT 'Active',
    failed_attempts INT DEFAULT 0,
    locked_until DATETIME NULL,
    last_login_at DATETIME NULL,
    last_login_ip VARCHAR(50) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_role (role_key),
    INDEX idx_user_dept (dept_id),
    INDEX idx_user_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. INVENTORY & STOCK MASTER TABLES
CREATE TABLE IF NOT EXISTS item_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    type ENUM('CONSUMABLE', 'NON_CONSUMABLE') NOT NULL,
    description TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS units (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_code VARCHAR(50) NOT NULL UNIQUE,
    item_name VARCHAR(255) NOT NULL,
    category_id INT NOT NULL,
    subcategory_id INT NULL,
    unit_id INT NOT NULL,
    is_consumable TINYINT(1) DEFAULT 1,
    specification TEXT NULL,
    manufacturer VARCHAR(150) NULL,
    model VARCHAR(150) NULL,
    min_stock DECIMAL(10,2) DEFAULT 0.00,
    max_stock DECIMAL(10,2) DEFAULT 1000.00,
    unit_cost DECIMAL(12,2) DEFAULT 0.00,
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES item_categories(id),
    FOREIGN KEY (unit_id) REFERENCES units(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS stock (
    id INT AUTO_INCREMENT PRIMARY KEY,
    store_id INT NOT NULL,
    item_id INT NOT NULL,
    available_quantity DECIMAL(10,2) DEFAULT 0.00,
    reserved_quantity DECIMAL(10,2) DEFAULT 0.00,
    store_location VARCHAR(100),
    last_movement_date DATE NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_store_item (store_id, item_id),
    FOREIGN KEY (store_id) REFERENCES stores(id),
    FOREIGN KEY (item_id) REFERENCES items(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. STOCK REQUISITIONS & MULTI-TIER APPROVAL ENGINE
CREATE TABLE IF NOT EXISTS stock_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_code VARCHAR(50) NOT NULL UNIQUE,
    request_type ENUM('EXISTING_STOCK', 'NEW_PURCHASE', 'TRANSFER') DEFAULT 'NEW_PURCHASE',
    priority ENUM('Low', 'Normal', 'High', 'Emergency') DEFAULT 'Normal',
    dept_id INT NOT NULL,
    lab_id INT NULL,
    requested_by INT NOT NULL,
    purpose TEXT NOT NULL,
    required_date DATE NOT NULL,
    total_estimated_value DECIMAL(12,2) DEFAULT 0.00,
    current_level INT DEFAULT 1,
    status VARCHAR(50) DEFAULT 'Pending Approval',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (dept_id) REFERENCES departments(id),
    FOREIGN KEY (requested_by) REFERENCES users(id),
    INDEX idx_req_code (request_code),
    INDEX idx_req_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS stock_request_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    item_id INT NOT NULL,
    requested_quantity DECIMAL(10,2) NOT NULL,
    approved_quantity DECIMAL(10,2) DEFAULT 0.00,
    unit_cost DECIMAL(12,2) DEFAULT 0.00,
    notes VARCHAR(255),
    FOREIGN KEY (request_id) REFERENCES stock_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS request_approvals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    level_number INT NOT NULL,
    level_title VARCHAR(100) NOT NULL,
    approver_role VARCHAR(50) NOT NULL,
    approver_user_id INT NULL,
    status ENUM('Pending', 'Approved', 'Rejected', 'Returned') DEFAULT 'Pending',
    comments TEXT,
    action_date DATETIME NULL,
    FOREIGN KEY (request_id) REFERENCES stock_requests(id) ON DELETE CASCADE,
    INDEX idx_appr_req (request_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- OFFICIAL STORE KEEPER APPROVAL SHEET
CREATE TABLE IF NOT EXISTS approval_sheets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sheet_number VARCHAR(50) NOT NULL UNIQUE,
    request_id INT NOT NULL,
    store_keeper_id INT NOT NULL,
    existing_stock_notes TEXT,
    spec_verification_notes TEXT,
    vendor_id INT NULL,
    quotation_ref VARCHAR(100),
    item_price DECIMAL(12,2) DEFAULT 0.00,
    gst_percent DECIMAL(5,2) DEFAULT 18.00,
    total_cost DECIMAL(12,2) DEFAULT 0.00,
    recommendation TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES stock_requests(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. PROCUREMENT, VENDORS, QUOTATIONS & GRN
CREATE TABLE IF NOT EXISTS vendors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vendor_code VARCHAR(50) NOT NULL UNIQUE,
    company_name VARCHAR(255) NOT NULL,
    gst_number VARCHAR(50),
    pan_number VARCHAR(50),
    contact_person VARCHAR(100),
    mobile_number VARCHAR(50),
    email VARCHAR(100),
    address TEXT,
    product_categories TEXT,
    performance_rating DECIMAL(3,1) DEFAULT 4.5,
    status ENUM('Active', 'Blacklisted', 'Inactive') DEFAULT 'Active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS quotations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NULL,
    vendor_id INT NOT NULL,
    quote_reference VARCHAR(100) NOT NULL,
    quote_date DATE NOT NULL,
    total_amount DECIMAL(12,2) NOT NULL,
    tax_amount DECIMAL(12,2) DEFAULT 0.00,
    delivery_period VARCHAR(50),
    warranty_terms VARCHAR(100),
    is_selected TINYINT(1) DEFAULT 0,
    remarks TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vendor_id) REFERENCES vendors(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS purchase_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    po_number VARCHAR(50) NOT NULL UNIQUE,
    indent_id INT NULL,
    vendor_id INT NOT NULL,
    total_amount DECIMAL(12,2) DEFAULT 0.00,
    tax_amount DECIMAL(12,2) DEFAULT 0.00,
    terms TEXT,
    status ENUM('Issued', 'Material Received', 'Closed', 'Cancelled') DEFAULT 'Issued',
    created_by INT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vendor_id) REFERENCES vendors(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS goods_receipts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    grn_number VARCHAR(50) NOT NULL UNIQUE,
    po_id INT NULL,
    vendor_id INT NOT NULL,
    store_id INT NOT NULL,
    received_date DATE NOT NULL,
    verified_by INT NOT NULL,
    invoice_no VARCHAR(100),
    invoice_date DATE,
    remarks TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vendor_id) REFERENCES vendors(id),
    FOREIGN KEY (store_id) REFERENCES stores(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS goods_receipt_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    grn_id INT NOT NULL,
    item_id INT NOT NULL,
    ordered_qty DECIMAL(10,2) NOT NULL,
    received_qty DECIMAL(10,2) NOT NULL,
    accepted_qty DECIMAL(10,2) NOT NULL,
    rejected_qty DECIMAL(10,2) DEFAULT 0.00,
    unit_price DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (grn_id) REFERENCES goods_receipts(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. STOCK DISTRIBUTION & ISSUE VOUCHERS
CREATE TABLE IF NOT EXISTS stock_issues (
    id INT AUTO_INCREMENT PRIMARY KEY,
    issue_code VARCHAR(50) NOT NULL UNIQUE,
    request_id INT NULL,
    source_store_id INT NOT NULL,
    target_dept_id INT NOT NULL,
    target_lab_id INT NULL,
    issued_by INT NOT NULL,
    recipient_id INT NULL,
    issue_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    voucher_type ENUM('Issue Voucher', 'Delivery Challan', 'Transfer Note') DEFAULT 'Issue Voucher',
    status ENUM('Issued', 'Acknowledged', 'Cancelled') DEFAULT 'Issued',
    remarks TEXT,
    FOREIGN KEY (source_store_id) REFERENCES stores(id),
    FOREIGN KEY (target_dept_id) REFERENCES departments(id),
    FOREIGN KEY (issued_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS stock_issue_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    issue_id INT NOT NULL,
    item_id INT NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    notes VARCHAR(255),
    FOREIGN KEY (issue_id) REFERENCES stock_issues(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS department_receipts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    receipt_code VARCHAR(50) NOT NULL UNIQUE,
    issue_id INT NOT NULL,
    dept_id INT NOT NULL,
    acknowledged_by INT NOT NULL,
    acknowledgement_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    digital_acknowledgement TINYINT(1) DEFAULT 1,
    remarks TEXT,
    FOREIGN KEY (issue_id) REFERENCES stock_issues(id),
    FOREIGN KEY (dept_id) REFERENCES departments(id),
    FOREIGN KEY (acknowledged_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. STOCK MOVEMENT & INTER-DEPARTMENT TRANSFERS
CREATE TABLE IF NOT EXISTS stock_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    movement_code VARCHAR(50) NOT NULL UNIQUE,
    movement_type VARCHAR(50) NOT NULL,
    source_dept_id INT NULL,
    target_dept_id INT NULL,
    source_lab_id INT NULL,
    target_lab_id INT NULL,
    initiated_by INT NOT NULL,
    status VARCHAR(50) DEFAULT 'Pending Approval',
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (initiated_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS stock_movement_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    movement_id INT NOT NULL,
    item_id INT NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    remarks VARCHAR(255),
    FOREIGN KEY (movement_id) REFERENCES stock_movements(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. ASSET REGISTER & HARDWARE LIFECYCLE
CREATE TABLE IF NOT EXISTS assets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asset_id VARCHAR(50) NOT NULL UNIQUE,
    asset_name VARCHAR(255) NOT NULL,
    category_id INT NOT NULL,
    brand VARCHAR(100),
    model VARCHAR(100),
    serial_number VARCHAR(100),
    mac_address VARCHAR(50),
    ip_address VARCHAR(50),
    warranty_expiry DATE,
    amc_details TEXT,
    configuration TEXT,
    purchase_date DATE,
    purchase_cost DECIMAL(12,2) DEFAULT 0.00,
    dept_id INT NOT NULL,
    lab_id INT NULL,
    location VARCHAR(100),
    custodian_id INT NULL,
    asset_status ENUM('Available', 'Allocated', 'In Use', 'Under Maintenance', 'Under Repair', 'Damaged', 'Lost', 'Returned', 'Disposed', 'Condemned') DEFAULT 'Available',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (dept_id) REFERENCES departments(id),
    FOREIGN KEY (category_id) REFERENCES item_categories(id),
    INDEX idx_asset_tag (asset_id),
    INDEX idx_asset_serial (serial_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS asset_maintenance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_number VARCHAR(50) NOT NULL UNIQUE,
    asset_id INT NOT NULL,
    reported_by INT NOT NULL,
    technician_id INT NULL,
    issue_reported TEXT NOT NULL,
    diagnosis TEXT,
    status ENUM('Open', 'Assigned', 'In Progress', 'Under Repair', 'Repaired', 'Condemned') DEFAULT 'Open',
    repair_cost DECIMAL(10,2) DEFAULT 0.00,
    resolution_notes TEXT,
    completed_date DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. BUDGETS, NOTIFICATIONS, AUDIT & SYSTEM SETTINGS
CREATE TABLE IF NOT EXISTS budgets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dept_id INT NOT NULL,
    budget_head_code VARCHAR(100) NOT NULL UNIQUE,
    academic_year VARCHAR(20) NOT NULL,
    category ENUM('CAPITAL', 'OPERATIONAL') DEFAULT 'OPERATIONAL',
    allocated_amount DECIMAL(14,2) DEFAULT 0.00,
    utilized_amount DECIMAL(14,2) DEFAULT 0.00,
    committed_amount DECIMAL(14,2) DEFAULT 0.00,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (dept_id) REFERENCES departments(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    role_key VARCHAR(50) NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    link_url VARCHAR(255) NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_notif_role (role_key, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    role VARCHAR(50),
    action VARCHAR(100) NOT NULL,
    module VARCHAR(100) NOT NULL,
    entity_type VARCHAR(100) NULL,
    entity_id INT NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    remarks TEXT NULL,
    ip_address VARCHAR(50),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_action (action),
    INDEX idx_audit_module (module),
    INDEX idx_audit_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS stock_verifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    verification_code VARCHAR(50) NOT NULL UNIQUE,
    store_id INT NOT NULL,
    verification_type ENUM('Monthly', 'Quarterly', 'Half-yearly', 'Annual') DEFAULT 'Monthly',
    conducted_by INT NOT NULL,
    verified_date DATE NOT NULL,
    status ENUM('Completed', 'Approved', 'Adjustments Applied') DEFAULT 'Completed',
    remarks TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (store_id) REFERENCES stores(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS system_settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT NOT NULL,
    category VARCHAR(50) DEFAULT 'General',
    description TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

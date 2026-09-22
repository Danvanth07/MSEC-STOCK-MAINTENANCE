<?php
/**
 * MSEC SMART STOCK - Unified Database Seeder & Migrator
 * Automatically provisions tables and initial baseline data
 */

require_once dirname(__DIR__) . '/config/app.php';

class DatabaseSeeder {

    public static function run(PDO $pdo, string $driver = 'sqlite') {
        // 1. Create Tables
        self::createTables($pdo, $driver);

        // 2. Populate Seed Data if empty
        self::seedData($pdo, $driver);
    }

    private static function createTables(PDO $pdo, string $driver) {
        $pk = ($driver === 'sqlite') ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'INT AUTO_INCREMENT PRIMARY KEY';
        $now = ($driver === 'sqlite') ? "DATETIME DEFAULT (datetime('now', 'localtime'))" : 'DATETIME DEFAULT CURRENT_TIMESTAMP';

        $queries = [
            // Organizations
            "CREATE TABLE IF NOT EXISTS organizations (
                id $pk,
                code VARCHAR(50) NOT NULL UNIQUE,
                name VARCHAR(255) NOT NULL,
                address TEXT,
                phone VARCHAR(50),
                email VARCHAR(100),
                established_year INT,
                created_at $now
            )",

            // Departments
            "CREATE TABLE IF NOT EXISTS departments (
                id $pk,
                org_id INT DEFAULT 1,
                code VARCHAR(50) NOT NULL UNIQUE,
                name VARCHAR(255) NOT NULL,
                hod_user_id INT NULL,
                created_at $now
            )",

            // Laboratories
            "CREATE TABLE IF NOT EXISTS laboratories (
                id $pk,
                dept_id INT NOT NULL,
                code VARCHAR(50) NOT NULL,
                name VARCHAR(255) NOT NULL,
                incharge_user_id INT NULL,
                location VARCHAR(255),
                created_at $now
            )",

            // Locations
            "CREATE TABLE IF NOT EXISTS locations (
                id $pk,
                building VARCHAR(100) NOT NULL,
                floor VARCHAR(50),
                room_number VARCHAR(50),
                description VARCHAR(255)
            )",

            // Stores
            "CREATE TABLE IF NOT EXISTS stores (
                id $pk,
                code VARCHAR(50) NOT NULL UNIQUE,
                name VARCHAR(255) NOT NULL,
                type VARCHAR(50) DEFAULT 'DEPARTMENT',
                dept_id INT NULL,
                keeper_user_id INT NULL,
                location_id INT NULL,
                created_at $now
            )",

            // Roles
            "CREATE TABLE IF NOT EXISTS roles (
                id $pk,
                role_key VARCHAR(50) NOT NULL UNIQUE,
                name VARCHAR(100) NOT NULL,
                level VARCHAR(100) NOT NULL,
                description TEXT
            )",

            // Users
            "CREATE TABLE IF NOT EXISTS users (
                id $pk,
                username VARCHAR(100) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                full_name VARCHAR(150) NOT NULL,
                email VARCHAR(150) NOT NULL UNIQUE,
                phone VARCHAR(50),
                role_key VARCHAR(50) NOT NULL,
                dept_id INT NULL,
                lab_id INT NULL,
                status VARCHAR(50) DEFAULT 'Active',
                created_at $now
            )",

            // Item Categories
            "CREATE TABLE IF NOT EXISTS item_categories (
                id $pk,
                name VARCHAR(150) NOT NULL,
                type VARCHAR(50) NOT NULL,
                description TEXT
            )",

            // Units
            "CREATE TABLE IF NOT EXISTS units (
                id $pk,
                code VARCHAR(20) NOT NULL UNIQUE,
                name VARCHAR(100) NOT NULL
            )",

            // Items
            "CREATE TABLE IF NOT EXISTS items (
                id $pk,
                item_code VARCHAR(50) NOT NULL UNIQUE,
                item_name VARCHAR(255) NOT NULL,
                category_id INT NOT NULL,
                subcategory_id INT NULL,
                unit_id INT NOT NULL,
                is_consumable INT DEFAULT 1,
                min_stock NUMERIC DEFAULT 0,
                max_stock NUMERIC DEFAULT 1000,
                unit_cost NUMERIC DEFAULT 0,
                description TEXT,
                created_at $now
            )",

            // Stock
            "CREATE TABLE IF NOT EXISTS stock (
                id $pk,
                store_id INT NOT NULL,
                item_id INT NOT NULL,
                available_quantity NUMERIC DEFAULT 0,
                reserved_quantity NUMERIC DEFAULT 0,
                store_location VARCHAR(100),
                last_movement_date VARCHAR(50) NULL,
                updated_at $now
            )",

            // Stock Transactions
            "CREATE TABLE IF NOT EXISTS stock_transactions (
                id $pk,
                transaction_type VARCHAR(50) NOT NULL,
                item_id INT NOT NULL,
                store_id INT NOT NULL,
                quantity NUMERIC NOT NULL,
                unit_cost NUMERIC DEFAULT 0,
                reference_id INT NULL,
                reference_type VARCHAR(50) NULL,
                notes TEXT,
                created_by INT NULL,
                created_at $now
            )",

            // Stock Requests
            "CREATE TABLE IF NOT EXISTS stock_requests (
                id $pk,
                request_code VARCHAR(50) NOT NULL UNIQUE,
                dept_id INT NOT NULL,
                lab_id INT NULL,
                requested_by INT NOT NULL,
                purpose TEXT NOT NULL,
                required_date VARCHAR(50) NOT NULL,
                total_estimated_value NUMERIC DEFAULT 0,
                current_level INT DEFAULT 1,
                status VARCHAR(50) DEFAULT 'Pending Approval',
                created_at $now
            )",

            // Stock Request Items
            "CREATE TABLE IF NOT EXISTS stock_request_items (
                id $pk,
                request_id INT NOT NULL,
                item_id INT NOT NULL,
                requested_quantity NUMERIC NOT NULL,
                approved_quantity NUMERIC DEFAULT 0,
                unit_cost NUMERIC DEFAULT 0,
                notes VARCHAR(255)
            )",

            // Approval Workflows
            "CREATE TABLE IF NOT EXISTS approval_workflows (
                id $pk,
                workflow_name VARCHAR(150) NOT NULL,
                level_number INT NOT NULL,
                level_title VARCHAR(100) NOT NULL,
                approver_role VARCHAR(50) NOT NULL,
                min_value NUMERIC DEFAULT 0,
                max_value NUMERIC DEFAULT 99999999,
                condition_notes TEXT
            )",

            // Request Approvals
            "CREATE TABLE IF NOT EXISTS request_approvals (
                id $pk,
                request_id INT NOT NULL,
                level_number INT NOT NULL,
                level_title VARCHAR(100) NOT NULL,
                approver_role VARCHAR(50) NOT NULL,
                approver_user_id INT NULL,
                status VARCHAR(50) DEFAULT 'Pending',
                comments TEXT,
                action_date VARCHAR(50) NULL
            )",

            // Stock Issues & Vouchers
            "CREATE TABLE IF NOT EXISTS stock_issues (
                id $pk,
                issue_code VARCHAR(50) NOT NULL UNIQUE,
                request_id INT NULL,
                source_store_id INT NOT NULL,
                target_dept_id INT NOT NULL,
                target_lab_id INT NULL,
                issued_by INT NOT NULL,
                issue_date $now,
                voucher_type VARCHAR(50) DEFAULT 'Issue Voucher',
                status VARCHAR(50) DEFAULT 'Issued',
                remarks TEXT
            )",

            // Stock Issue Items
            "CREATE TABLE IF NOT EXISTS stock_issue_items (
                id $pk,
                issue_id INT NOT NULL,
                item_id INT NOT NULL,
                quantity NUMERIC NOT NULL,
                notes VARCHAR(255)
            )",

            // Department Receipts
            "CREATE TABLE IF NOT EXISTS department_receipts (
                id $pk,
                receipt_code VARCHAR(50) NOT NULL UNIQUE,
                issue_id INT NOT NULL,
                dept_id INT NOT NULL,
                acknowledged_by INT NOT NULL,
                acknowledgement_date $now,
                digital_acknowledgement INT DEFAULT 1,
                remarks TEXT
            )",

            // Stock Movements
            "CREATE TABLE IF NOT EXISTS stock_movements (
                id $pk,
                movement_code VARCHAR(50) NOT NULL UNIQUE,
                movement_type VARCHAR(50) NOT NULL,
                source_dept_id INT NULL,
                target_dept_id INT NULL,
                source_lab_id INT NULL,
                target_lab_id INT NULL,
                initiated_by INT NOT NULL,
                status VARCHAR(50) DEFAULT 'Pending Approval',
                notes TEXT,
                created_at $now
            )",

            // Stock Movement Items
            "CREATE TABLE IF NOT EXISTS stock_movement_items (
                id $pk,
                movement_id INT NOT NULL,
                item_id INT NOT NULL,
                quantity NUMERIC NOT NULL,
                remarks VARCHAR(255)
            )",

            // Assets & IT Tracking
            "CREATE TABLE IF NOT EXISTS assets (
                id $pk,
                asset_id VARCHAR(50) NOT NULL UNIQUE,
                asset_name VARCHAR(255) NOT NULL,
                category_id INT NOT NULL,
                brand VARCHAR(100),
                model VARCHAR(100),
                serial_number VARCHAR(100),
                mac_address VARCHAR(50),
                ip_address VARCHAR(50),
                warranty_expiry VARCHAR(50),
                amc_details TEXT,
                configuration TEXT,
                purchase_date VARCHAR(50),
                purchase_cost NUMERIC DEFAULT 0,
                dept_id INT NOT NULL,
                lab_id INT NULL,
                location VARCHAR(100),
                custodian_id INT NULL,
                asset_status VARCHAR(50) DEFAULT 'Available',
                created_at $now
            )",

            // Asset Maintenance
            "CREATE TABLE IF NOT EXISTS asset_maintenance (
                id $pk,
                asset_id INT NOT NULL,
                issue_reported TEXT NOT NULL,
                reported_by INT NOT NULL,
                report_date $now,
                status VARCHAR(50) DEFAULT 'Repairable',
                repair_cost NUMERIC DEFAULT 0,
                vendor_details VARCHAR(255),
                resolution_notes TEXT,
                completed_date VARCHAR(50) NULL
            )",

            // Vendors
            "CREATE TABLE IF NOT EXISTS vendors (
                id $pk,
                vendor_code VARCHAR(50) NOT NULL UNIQUE,
                company_name VARCHAR(255) NOT NULL,
                gst_number VARCHAR(50),
                contact_person VARCHAR(100),
                mobile_number VARCHAR(50),
                email VARCHAR(100),
                address TEXT,
                product_categories TEXT,
                performance_rating NUMERIC DEFAULT 4.5,
                created_at $now
            )",

            // Purchase Indents
            "CREATE TABLE IF NOT EXISTS purchase_indents (
                id $pk,
                indent_code VARCHAR(50) NOT NULL UNIQUE,
                dept_id INT NOT NULL,
                requested_by INT NOT NULL,
                total_amount NUMERIC DEFAULT 0,
                justification TEXT,
                status VARCHAR(50) DEFAULT 'Submitted',
                created_at $now
            )",

            // Purchase Orders
            "CREATE TABLE IF NOT EXISTS purchase_orders (
                id $pk,
                po_number VARCHAR(50) NOT NULL UNIQUE,
                indent_id INT NULL,
                vendor_id INT NOT NULL,
                total_amount NUMERIC DEFAULT 0,
                terms TEXT,
                status VARCHAR(50) DEFAULT 'Issued',
                created_at $now
            )",

            // Goods Receipts (GRN)
            "CREATE TABLE IF NOT EXISTS goods_receipts (
                id $pk,
                grn_number VARCHAR(50) NOT NULL UNIQUE,
                po_id INT NULL,
                vendor_id INT NOT NULL,
                store_id INT NOT NULL,
                received_date VARCHAR(50) NOT NULL,
                verified_by INT NOT NULL,
                invoice_no VARCHAR(100),
                invoice_date VARCHAR(50),
                remarks TEXT,
                created_at $now
            )",

            // Stock Verifications
            "CREATE TABLE IF NOT EXISTS stock_verifications (
                id $pk,
                verification_code VARCHAR(50) NOT NULL UNIQUE,
                store_id INT NOT NULL,
                verification_type VARCHAR(50) DEFAULT 'Monthly',
                conducted_by INT NOT NULL,
                verified_date VARCHAR(50) NOT NULL,
                status VARCHAR(50) DEFAULT 'Completed',
                remarks TEXT,
                created_at $now
            )",

            // Stock Verification Items
            "CREATE TABLE IF NOT EXISTS stock_verification_items (
                id $pk,
                verification_id INT NOT NULL,
                item_id INT NOT NULL,
                system_quantity NUMERIC NOT NULL,
                physical_quantity NUMERIC NOT NULL,
                variance_quantity NUMERIC NOT NULL,
                remarks VARCHAR(255)
            )",

            // Stock Adjustments
            "CREATE TABLE IF NOT EXISTS stock_adjustments (
                id $pk,
                adjustment_code VARCHAR(50) NOT NULL UNIQUE,
                store_id INT NOT NULL,
                item_id INT NOT NULL,
                old_quantity NUMERIC NOT NULL,
                new_quantity NUMERIC NOT NULL,
                difference NUMERIC NOT NULL,
                reason TEXT NOT NULL,
                supporting_document VARCHAR(255),
                approved_by INT NOT NULL,
                created_at $now
            )",

            // Activity Logs (Permanent)
            "CREATE TABLE IF NOT EXISTS activity_logs (
                id $pk,
                user_id INT NULL,
                role VARCHAR(50),
                activity TEXT NOT NULL,
                module VARCHAR(100) NOT NULL,
                action_details TEXT NULL,
                ip_address VARCHAR(50),
                created_at $now
            )"
        ];

        foreach ($queries as $sql) {
            $pdo->exec($sql);
        }
    }

    private static function seedData(PDO $pdo, string $driver) {
        $check = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        if ($check > 0) {
            return; // Already seeded
        }

        // Organizations
        $pdo->exec("INSERT INTO organizations (id, code, name, address, phone, email, established_year) VALUES
            (1, 'MSEC', 'Mohamed Sathak Engineering College', 'Kilakarai - 623 806, Ramanathapuram Dist., Tamil Nadu', '+91 4567 241327', 'stock@msec.org.in', 1984)");

        // Departments
        $pdo->exec("INSERT INTO departments (id, org_id, code, name) VALUES
            (1, 1, 'CSE', 'Computer Science & Engineering'),
            (2, 1, 'ECE', 'Electronics & Communication Engineering'),
            (3, 1, 'EEE', 'Electrical & Electronics Engineering'),
            (4, 1, 'MECH', 'Mechanical Engineering'),
            (5, 1, 'CIVIL', 'Civil Engineering'),
            (6, 1, 'ADMIN_DEPT', 'Central Administration')");

        // Laboratories
        $pdo->exec("INSERT INTO laboratories (id, dept_id, code, name, location) VALUES
            (1, 1, 'CSE-PROG', 'Programming Lab', 'Main Block - Floor 2 - Lab 201'),
            (2, 1, 'CSE-AI', 'AI Lab', 'Main Block - Floor 3 - Lab 304'),
            (3, 1, 'CSE-NET', 'Network Lab', 'Main Block - Floor 2 - Lab 205'),
            (4, 1, 'CSE-OFF', 'Department Office', 'Main Block - Floor 2 - CSE HOD Suite'),
            (5, 2, 'ECE-COMM', 'Communication Systems Lab', 'Science Block - Floor 1 - Lab 102'),
            (6, 3, 'EEE-POW', 'Power Electronics Lab', 'Science Block - Floor 2 - Lab 201'),
            (7, 4, 'MECH-CAD', 'CAD/CAM Lab', 'Workshop Block - Floor 1 - Lab 101')");

        // Roles
        foreach (ROLES as $role_key => $info) {
            $stmt = $pdo->prepare("INSERT INTO roles (role_key, name, level, description) VALUES (?, ?, ?, ?)");
            $stmt->execute([$role_key, $info['name'], $info['level'], "Role authorization for {$info['name']}"]);
        }

        // Default password hash for 'password123'
        $passHash = password_hash('password123', PASSWORD_BCRYPT);

        // Users
        $users = [
            [1, 'superadmin', $passHash, 'Super Administrator', 'superadmin@msec.edu.in', '+91 9876543210', 'super_admin', 6, null],
            [2, 'director', $passHash, 'Dr. K. S. Babai (Director)', 'director@msec.edu.in', '+91 9876543211', 'director', 6, null],
            [3, 'principal', $passHash, 'Dr. C. Umarani (Principal)', 'principal@msec.edu.in', '+91 9876543212', 'principal', 6, null],
            [4, 'viceprincipal', $passHash, 'Dr. S. Rajendran (Vice Principal)', 'vp@msec.edu.in', '+91 9876543213', 'vice_principal', 6, null],
            [5, 'admin', $passHash, 'System Administrator', 'admin@msec.edu.in', '+91 9876543214', 'admin', 6, null],
            [6, 'storekeeper', $passHash, 'K. Ramasamy (Central Store Keeper)', 'store@msec.edu.in', '+91 9876543215', 'store_keeper', 6, null],
            [7, 'purchaseofficer', $passHash, 'P. Natarajan (Purchase Officer)', 'purchase@msec.edu.in', '+91 9876543216', 'purchase_officer', 6, null],
            [8, 'accountsofficer', $passHash, 'A. Mohamed Hameed (Accounts Officer)', 'accounts@msec.org.in', '+91 9876543217', 'accounts_officer', 6, null],
            [9, 'hod_cse', $passHash, 'Dr. A. Chandrasekar (HOD CSE)', 'hod.cse@msec.edu.in', '+91 9876543218', 'hod', 1, null],
            [10, 'hod_ece', $passHash, 'Dr. G. Sumathi (HOD ECE)', 'hod.ece@msec.edu.in', '+91 9876543219', 'hod', 2, null],
            [11, 'coord_cse', $passHash, 'Prof. M. Vijay (CSE Coordinator)', 'coord.cse@msec.edu.in', '+91 9876543220', 'dept_coordinator', 1, null],
            [12, 'lab_ai', $passHash, 'Er. R. Ramesh (AI Lab Incharge)', 'lab.ai@msec.edu.in', '+91 9876543221', 'lab_incharge', 1, 2],
            [13, 'faculty_cse', $passHash, 'Dr. S. Anitha (Faculty CSE)', 'faculty.cse@msec.edu.in', '+91 9876543222', 'faculty', 1, null],
            [14, 'tech_cse', $passHash, 'T. Murugan (Lab Technician)', 'tech.cse@msec.edu.in', '+91 9876543223', 'technician', 1, 1],
            [15, 'auditor', $passHash, 'S. Narayanan (Internal Auditor)', 'audit@msec.edu.in', '+91 9876543224', 'auditor', 6, null],
            [16, 'viewer', $passHash, 'Governing Body Observer', 'viewer@msec.edu.in', '+91 9876543225', 'management_viewer', 6, null]
        ];

        $stmtUser = $pdo->prepare("INSERT INTO users (id, username, password_hash, full_name, email, phone, role_key, dept_id, lab_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active')");
        foreach ($users as $u) {
            $stmtUser->execute($u);
        }

        // Stores
        $pdo->exec("INSERT INTO stores (id, code, name, type, dept_id, keeper_user_id) VALUES
            (1, 'STORE-CENTRAL', 'MSEC Central Store', 'CENTRAL', NULL, 6),
            (2, 'STORE-CSE', 'CSE Department Store', 'DEPARTMENT', 1, 11),
            (3, 'STORE-ECE', 'ECE Department Store', 'DEPARTMENT', 2, 10),
            (4, 'STORE-EEE', 'EEE Department Store', 'DEPARTMENT', 3, NULL),
            (5, 'STORE-MECH', 'Mechanical Department Store', 'DEPARTMENT', 4, NULL),
            (6, 'STORE-CIVIL', 'Civil Department Store', 'DEPARTMENT', 5, NULL)");

        // Units
        $pdo->exec("INSERT INTO units (id, code, name) VALUES
            (1, 'Nos', 'Numbers / Units'),
            (2, 'Reams', 'Paper Reams'),
            (3, 'Box', 'Box / Pack'),
            (4, 'Meters', 'Meters'),
            (5, 'Kg', 'Kilograms'),
            (6, 'Litre', 'Litres'),
            (7, 'Set', 'Set')");

        // Item Categories
        $pdo->exec("INSERT INTO item_categories (id, name, type, description) VALUES
            (1, 'Stationery & Paper', 'CONSUMABLE', 'A4 sheets, pens, notebooks, office paper'),
            (2, 'IT Consumables', 'CONSUMABLE', 'Printer cartridges, toners, ribbons'),
            (3, 'Electrical & Wiring', 'CONSUMABLE', 'Electrical wires, connectors, fuses, switches'),
            (4, 'Electronic Components', 'CONSUMABLE', 'Breadboards, resistors, ICs, microcontrollers'),
            (5, 'Lab Chemicals & Reagents', 'CONSUMABLE', 'Acids, bases, solvents, indicators'),
            (6, 'IT Equipment & Hardware', 'NON_CONSUMABLE', 'Desktop systems, laptops, servers, switches, printers, monitors'),
            (7, 'Lab Instruments & Machinery', 'NON_CONSUMABLE', 'Oscilloscopes, function generators, testing rigs'),
            (8, 'Furniture & Fixtures', 'NON_CONSUMABLE', 'Executive chairs, lab benches, faculty desks')");

        // Items
        $pdo->exec("INSERT INTO items (id, item_code, item_name, category_id, subcategory_id, unit_id, is_consumable, min_stock, max_stock, unit_cost, description) VALUES
            (1, 'IT-CBL-001', 'HDMI Cable 2.0 (3 Meter)', 2, NULL, 1, 1, 15, 100, 350, 'High speed gold plated HDMI cable for smart classrooms & labs'),
            (2, 'IT-KEY-002', 'Dell USB Multimedia Keyboard', 6, NULL, 1, 0, 10, 200, 650, 'Standard 104-key USB wired keyboard'),
            (3, 'STN-PPR-001', 'A4 Copier Sheets (75 GSM, 500 Sheets)', 1, NULL, 2, 1, 25, 500, 280, 'White multipurpose photocopying and printing paper'),
            (4, 'IT-TNR-001', 'HP LaserJet 88A Toner Cartridge', 2, NULL, 1, 1, 5, 40, 1450, 'Black genuine toner cartridge for office printers'),
            (5, 'ELC-WIR-001', 'Copper Wire 1.5 sq mm (90m Roll)', 3, NULL, 4, 1, 10, 100, 1250, 'FR PVC insulated building copper wire'),
            (6, 'CHM-SLN-001', 'Hydrochloric Acid GR 37% (2.5L)', 5, NULL, 6, 1, 4, 20, 850, 'Analytical grade lab chemical reagent'),
            (7, 'IT-SYS-001', 'Dell OptiPlex 7090 Desktop (i7, 16GB, 512GB SSD)', 6, NULL, 1, 0, 5, 150, 68000, 'High performance workstation for AI & programming laboratories'),
            (8, 'IT-SWT-001', 'Cisco Catalyst 24-Port Gigabit Managed Switch', 6, NULL, 1, 0, 2, 30, 30000, 'Layer 2/3 enterprise core switch with PoE'),
            (9, 'IT-PRJ-001', 'Epson EB-E01 XGA 3300 Lumens Projector', 6, NULL, 1, 0, 3, 50, 38500, 'Ceiling mount classroom display projector'),
            (10, 'IT-KEY-OLD', 'Legacy PS/2 Keyboard (Surplus Batch)', 6, NULL, 1, 0, 5, 50, 500, 'Obsolete keyboard stock without movement since 2023 - Dead Stock')");

        // Stock Balances
        $pdo->exec("INSERT INTO stock (store_id, item_id, available_quantity, reserved_quantity, store_location, last_movement_date) VALUES
            (1, 1, 65, 10, 'Central Rack B-04', '2026-09-15'),
            (1, 2, 45, 5, 'Central Rack IT-01', '2026-09-18'),
            (1, 3, 10, 0, 'Central Rack Paper-A1', '2026-09-10'),
            (1, 4, 18, 2, 'Central Rack IT-03', '2026-09-12'),
            (1, 7, 24, 0, 'Central Secure Vault', '2026-09-01'),
            (1, 8, 3, 0, 'Central Network Rack', '2022-11-20'),
            (1, 10, 15, 0, 'Central Scrap Shelf C', '2023-04-10'),
            (2, 1, 12, 0, 'CSE Lab Cabinet A', '2026-09-12'),
            (2, 2, 20, 0, 'CSE Tech Store Rack 1', '2026-09-15'),
            (3, 2, 2, 0, 'ECE Component Lab', '2026-08-20')");

        // IT Assets (Section 8 & 18)
        $pdo->exec("INSERT INTO assets (id, asset_id, asset_name, category_id, brand, model, serial_number, mac_address, ip_address, warranty_expiry, amc_details, configuration, purchase_date, purchase_cost, dept_id, lab_id, location, custodian_id, asset_status) VALUES
            (1, 'MSEC-CSE-PC-001', 'Dell OptiPlex 7090 Desktop', 6, 'Dell', 'OptiPlex 7090', 'SN-DELL-887410', '00:1A:2B:3C:4D:5E', '192.168.10.101', '2027-08-30', 'Dell ProSupport Plus till Aug 2027', 'Intel Core i7 11th Gen, 16GB DDR4, 512GB NVMe SSD, Nvidia GTX 1650', '2024-09-01', 68000, 1, 2, 'AI Lab - Terminal 01', 12, 'In Use'),
            (2, 'MSEC-CSE-PC-002', 'Dell OptiPlex 7090 Desktop', 6, 'Dell', 'OptiPlex 7090', 'SN-DELL-887411', '00:1A:2B:3C:4D:5F', '192.168.10.102', '2027-08-30', 'Dell ProSupport Plus till Aug 2027', 'Intel Core i7 11th Gen, 16GB DDR4, 512GB NVMe SSD', '2024-09-01', 68000, 1, 2, 'AI Lab - Terminal 02', 12, 'In Use'),
            (3, 'MSEC-CSE-SW-001', 'Cisco Catalyst 24-Port Switch', 6, 'Cisco', 'Catalyst 2960-X', 'SN-CSCO-998231', '00:25:9C:12:FE:AA', '192.168.10.254', '2025-12-31', 'Cisco SMARTnet Annual AMC Active', '24x 1G Ethernet + 4x 1G SFP, Managed Layer 2', '2022-03-15', 30000, 1, 3, 'Network Lab Server Rack A', 11, 'In Use'),
            (4, 'MSEC-CSE-PRJ-01', 'Epson EB-E01 Projector', 6, 'Epson', 'EB-E01', 'SN-EPSN-334102', NULL, NULL, '2026-06-30', 'On-site lamp warranty till mid-2026', 'XGA, 3300 Lumens, HDMI/VGA', '2023-07-15', 38500, 1, 1, 'Programming Lab 201 Ceiling', 14, 'In Use'),
            (5, 'MSEC-CSE-PC-003', 'Dell OptiPlex 7090 Desktop (SMPS Issue)', 6, 'Dell', 'OptiPlex 7090', 'SN-DELL-887412', '00:1A:2B:3C:4D:60', '192.168.10.103', '2027-08-30', 'Dell ProSupport Plus', 'Intel Core i7, 16GB DDR4', '2024-09-01', 68000, 1, 2, 'Central Store Service Bay', 6, 'Under Repair')");

        // Approval Workflows
        $pdo->exec("INSERT INTO approval_workflows (id, workflow_name, level_number, level_title, approver_role, min_value, max_value, condition_notes) VALUES
            (1, 'Tier 1 Standard Request', 1, 'Department Stock Coordinator', 'dept_coordinator', 0, 99999999, 'Initial department-level technical verification'),
            (2, 'Tier 1 Standard Request', 2, 'Head of Department (HOD)', 'hod', 0, 99999999, 'HOD approval required for all departmental requisitions'),
            (3, 'Tier 1 Standard Request', 3, 'Central Store Keeper', 'store_keeper', 0, 5000, 'Store keeper executes fulfillment for requests <= ₹5,000'),
            (4, 'Tier 2 Mid-Value Request', 3, 'Vice Principal', 'vice_principal', 5001, 99999999, 'VP approval required for requisitions exceeding ₹5,000'),
            (5, 'Tier 3 High-Value Request', 4, 'Principal', 'principal', 25001, 99999999, 'Principal approval mandatory for requisitions exceeding ₹25,000'),
            (6, 'Tier 4 Major Capital Request', 5, 'Director', 'director', 100001, 99999999, 'Director approval required for major procurement exceeding ₹1,00,000')");

        // Vendors
        $pdo->exec("INSERT INTO vendors (id, vendor_code, company_name, gst_number, contact_person, mobile_number, email, address, product_categories, performance_rating) VALUES
            (1, 'VND-001', 'CompCare Solutions Pvt Ltd', '33AABCC1234F1Z5', 'M. Ananthan', '+91 94441 23456', 'sales@compcare.in', 'No. 45, Mount Road, Chennai - 600002', 'Desktops, Laptops, Servers, Networking', 4.8),
            (2, 'VND-002', 'Sri Balaji Office Supplies', '33AAACB9876G2Z1', 'K. Balaji', '+91 98401 67890', 'balaji.supplies@gmail.com', 'No. 12, Usman Road, T. Nagar, Chennai - 600017', 'Stationery, A4 Sheets, Printer Toners', 4.6),
            (3, 'VND-003', 'Apex Electrical & Instruments', '33AABDE5544H3Z8', 'R. Sridhar', '+91 97910 11223', 'apexinstruments@yahoo.co.in', 'Industrial Estate, Guindy, Chennai - 600032', 'Electrical Wires, Lab Meters, Oscilloscopes', 4.4)");

        // Stock Request Example
        $pdo->exec("INSERT INTO stock_requests (id, request_code, dept_id, lab_id, requested_by, purpose, required_date, total_estimated_value, current_level, status) VALUES
            (1, 'CSE-REQ-2026-00125', 1, 2, 12, 'AI Laboratory Maintenance & Semester Lab Exam Preparation', '2026-10-09', 12350, 3, 'Pending Approval')");

        $pdo->exec("INSERT INTO stock_request_items (id, request_id, item_id, requested_quantity, approved_quantity, unit_cost, notes) VALUES
            (1, 1, 1, 10, 10, 350, 'HDMI Cable – 10 Nos for lab monitor displays'),
            (2, 1, 2, 5, 5, 650, 'Keyboard – 5 Nos replacement for damaged terminals'),
            (3, 1, 3, 20, 20, 280, 'A4 Sheets – 20 Reams for record notebooks & tests')");

        $pdo->exec("INSERT INTO request_approvals (request_id, level_number, level_title, approver_role, approver_user_id, status, comments, action_date) VALUES
            (1, 1, 'Department Stock Coordinator', 'dept_coordinator', 11, 'Approved', 'Verified necessity for upcoming semester examinations in AI lab', '2026-09-20 10:15:00'),
            (1, 2, 'HOD CSE', 'hod', 9, 'Approved', 'Approved from departmental quota. Recommended for store issue.', '2026-09-20 14:30:00'),
            (1, 3, 'Vice Principal', 'vice_principal', 4, 'Pending', 'Awaiting administrative verification (Value ₹12,350)', NULL)");

        // Stock Movement (CSE to ECE Transfer)
        $pdo->exec("INSERT INTO stock_movements (id, movement_code, movement_type, source_dept_id, target_dept_id, initiated_by, status, notes) VALUES
            (1, 'MOV-2026-00042', 'DEPT_TO_DEPT', 1, 2, 11, 'In Transit', 'Inter-department transfer: CSE has 10 extra keyboards and ECE requested 5 units')");

        $pdo->exec("INSERT INTO stock_movement_items (id, movement_id, item_id, quantity, remarks) VALUES
            (1, 1, 2, 5, 'Dell USB Keyboards (CSE tech stock to ECE lab)')");

        // Activity Logs
        $pdo->exec("INSERT INTO activity_logs (user_id, role, activity, module, action_details, ip_address, created_at) VALUES
            (6, 'store_keeper', 'Issued 10 Keyboards', 'Stock Issue', 'Generated Issue Voucher VCH-2026-0089 for CSE Programming Lab', '192.168.1.50', '2026-09-01 10:30:00'),
            (9, 'hod', 'Approved Request', 'Approval', 'HOD approved indent CSE-REQ-2026-00125 for AI Laboratory Maintenance', '192.168.10.5', '2026-09-01 14:45:00'),
            (5, 'admin', 'Updated Item Master', 'Inventory', 'Adjusted reorder thresholds and safety parameters for A4 Sheets', '192.168.1.10', '2026-09-02 09:15:00')");
    }
}

// CLI runner
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    $config = require dirname(__DIR__) . '/config/database.php';
    $driver = $config['default'];
    $conn = $config['connections'][$driver];

    if ($driver === 'sqlite') {
        $pdo = new PDO("sqlite:" . $conn['database'], null, null, $conn['options']);
    } else {
        $dsn = "mysql:host={$conn['host']};port={$conn['port']};dbname={$conn['database']};charset={$conn['charset']}";
        $pdo = new PDO($dsn, $conn['username'], $conn['password'], $conn['options']);
    }

    DatabaseSeeder::run($pdo, $driver);
    echo "MSEC SMART STOCK database successfully seeded via CLI ({$driver}).\n";
}

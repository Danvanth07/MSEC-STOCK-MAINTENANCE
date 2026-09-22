<?php
/**
 * MSEC SMART STOCK - Application Configuration
 * Locked Requirements Baseline
 */

define('APP_NAME', 'MSEC SMART STOCK');
define('APP_FULL_NAME', 'Smart Inventory, Asset & Distribution Management System');
define('APP_INSTITUTION', 'Mohamed Sathak Engineering College (MSEC)');
define('APP_INSTITUTION_FULL', 'Mohamed Sathak Engineering College');
define('APP_INSTITUTION_TAGLINE', 'An Autonomous Institution');
define('APP_INSTITUTION_LOCATION', 'Kilakarai - 623 806, Ramanathapuram Dist.');
define('APP_INSTITUTION_APPROVALS', 'Approved by AICTE, COA New Delhi, DGS Mumbai, Affiliated to Anna University, Sponsored by Mohamed Sathak Trust');
define('APP_INSTITUTION_ACCREDITATIONS', 'NAAC Accredited A+ | NBA Accredited (CSE & IT) | IIC | TNEA Code 5907');
define('APP_INSTITUTION_LETTERHEAD', 'images/msec_letterhead.png');
define('APP_VERSION', '1.0.0-PROD');
define('APP_TIMEZONE', 'Asia/Kolkata');
define('CURRENCY_SYMBOL', '₹');

// Date & Time Defaults
date_default_timezone_set(APP_TIMEZONE);

// Available Roles mapped to system levels (Section 3)
const ROLES = [
    'super_admin'         => ['name' => 'Super Admin',                 'level' => 'System Level'],
    'director'            => ['name' => 'Director',                    'level' => 'Management'],
    'principal'           => ['name' => 'Principal',                   'level' => 'Institution Level'],
    'vice_principal'      => ['name' => 'Vice Principal',              'level' => 'Administration'],
    'admin'               => ['name' => 'Admin',                       'level' => 'Central Administration'],
    'store_keeper'        => ['name' => 'Store Keeper',                'level' => 'Operational'],
    'purchase_officer'    => ['name' => 'Purchase Officer',            'level' => 'Procurement'],
    'accounts_officer'    => ['name' => 'Accounts Officer',            'level' => 'Finance'],
    'hod'                 => ['name' => 'HOD',                         'level' => 'Department'],
    'dept_coordinator'    => ['name' => 'Department Stock Coordinator','level' => 'Department'],
    'lab_incharge'        => ['name' => 'Lab Incharge',                'level' => 'Laboratory'],
    'faculty'             => ['name' => 'Faculty',                     'level' => 'User'],
    'technician'          => ['name' => 'Technician',                  'level' => 'User'],
    'auditor'             => ['name' => 'Auditor',                     'level' => 'Read Only'],
    'management_viewer'   => ['name' => 'Management Viewer',           'level' => 'Read Only']
];

// Approval Thresholds (Section 11.1)
const APPROVAL_THRESHOLDS = [
    'TIER_LOW'      => 5000,       // Below 5,000: HOD + Store Keeper
    'TIER_MID'      => 25000,      // 5,001 - 25,000: HOD + Vice Principal
    'TIER_HIGH'     => 100000,     // 25,001 - 1,00,000: HOD + Vice Principal + Principal
    // Above 1,00,000: HOD + Vice Principal + Principal + Director
];

// Asset Statuses (Section 18.2)
const ASSET_STATUSES = [
    'Available',
    'Allocated',
    'In Use',
    'Under Maintenance',
    'Under Repair',
    'Damaged',
    'Lost',
    'Returned',
    'Disposed',
    'Condemned'
];

// Stock Movement Statuses (Section 14.2)
const MOVEMENT_STATUSES = [
    'Draft',
    'Submitted',
    'Pending Approval',
    'Approved',
    'Rejected',
    'Ready for Dispatch',
    'Dispatched',
    'In Transit',
    'Received',
    'Acknowledged',
    'Closed'
];

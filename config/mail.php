<?php
/**
 * MSEC SMART STOCK - Institutional Email & Notification Configuration
 * Mohamed Sathak Engineering College (MSEC) — Kilakarai
 * Supports Google Workspace for Education, Microsoft 365, or Campus SMTP Server
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Default Mailer
    |--------------------------------------------------------------------------
    | Options: 'smtp' or 'log' (for local testing without active internet)
    */
    'default' => 'smtp',

    'mailers' => [
        'smtp' => [
            'transport'  => 'smtp',
            'host'       => 'smtp.gmail.com', // or 'smtp.office365.com' or college campus mail server IP
            'port'       => 587,              // 587 (TLS) or 465 (SSL)
            'encryption' => 'tls',            // 'tls' or 'ssl'
            'username'   => 'smartstock@msec.org.in', // Official institutional service email
            'password'   => '',               // Google Workspace App Password (16-character) or SMTP secret
            'timeout'    => 10,
        ],

        'log' => [
            'transport'  => 'log',
            'path'       => dirname(__DIR__) . '/database/mail_log.txt',
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Global "From" Address
    |--------------------------------------------------------------------------
    | All automated system notifications, indent approvals, voucher issuances,
    | and low-stock alerts will be dispatched using this identity.
    */
    'from' => [
        'address' => 'smartstock@msec.org.in',
        'name'    => 'MSEC SMART STOCK Portal'
    ],

    /*
    |--------------------------------------------------------------------------
    | Institutional Email Validation Domain
    |--------------------------------------------------------------------------
    | Enforces that all user accounts must belong to the college domain.
    */
    'allowed_domains' => [
        'msec.org.in',
        'msec.edu.in'
    ],

    /*
    |--------------------------------------------------------------------------
    | Automated Notification Triggers
    |--------------------------------------------------------------------------
    | Configure which institutional stakeholders receive emails on system events.
    */
    'notifications' => [
        'indent_submitted'      => true, // Alert sent to HOD when staff raises indent
        'approval_required'     => true, // Alert sent to VP / Principal / Director for high tiers
        'indent_approved'       => true, // Alert sent to requester & store keeper once approved
        'voucher_issued'        => true, // Delivery challan alert sent to department receiving officer
        'voucher_acknowledged'  => true, // Acknowledgment alert sent back to central store
        'low_stock_warning'     => true, // Alert sent to Store Keeper when item reaches minimum threshold
        'equipment_breakdown'   => true, // Alert sent to Lab Incharge & Maintenance bay on damage log
        'annual_audit_reminder' => true  // Advance alert sent to Audit Committee members
    ]
];

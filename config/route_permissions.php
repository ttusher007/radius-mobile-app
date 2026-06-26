<?php

/**
 * Permission requirements for named web routes.
 *
 * - `any`: user must hold at least one of these permission gates.
 * - `require_scope`: user must also have manager/POP scope (BillingScope).
 *
 * Routes not listed here are only protected by `auth` middleware.
 */
return [
    'billing.bill-view' => [
        'label' => 'Bill View',
        'require_scope' => true,
    ],

    'billing.money-receipt' => [
        'label' => 'Money Receipt',
        'any' => [
            'money-receipt-entry',
            'money-receipt-entry-admin',
            'super-admin',
            'perm_all_manager',
        ],
    ],

    'reports.collection' => [
        'label' => 'Collection Report',
        'any' => [
            'report_mac-payment',
            'super-admin',
            'perm_all_manager',
        ],
        'require_scope' => true,
    ],
];

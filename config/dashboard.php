<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Low stock threshold
    |--------------------------------------------------------------------------
    |
    | A product shows up on the Dashboard's low-stock card when its stock is
    | strictly below this number. Kept in configuration (not hardcoded) so it
    | can be tuned per environment without a code change.
    |
    */

    'low_stock_threshold' => (int) env('DASHBOARD_LOW_STOCK_THRESHOLD', 2),

    /*
    |--------------------------------------------------------------------------
    | Dashboard cards
    |--------------------------------------------------------------------------
    |
    | The canonical list of cards the Dashboard can show, each with a label
    | (for the settings screen) and the permission required to see it at all
    | (null = no permission needed). Which of these are actually enabled is
    | a single, admin-editable setting stored in the `settings` table (see
    | App\Models\Setting and the Settings module) — everything is enabled
    | by default until an admin turns some off.
    |
    */

    'cards' => [
        'recent_logins' => ['label' => 'История заходов', 'permission' => null],
        'recent_orders' => ['label' => 'Последние 5 заказов', 'permission' => 'orders.view'],
        'order_status_counts' => ['label' => 'Заказы по статусам', 'permission' => 'orders.view'],
        'low_stock_products' => ['label' => 'Мало на складе', 'permission' => 'products.view'],
    ],
];

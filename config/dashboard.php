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
];

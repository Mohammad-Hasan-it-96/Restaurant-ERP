<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Admin dashboard tuning
    |--------------------------------------------------------------------------
    |
    | Cache TTLs (seconds) and list/chart sizes for the admin dashboard and the
    | order-index status badges.
    |
    */

    // Cache TTLs (seconds), all flushed by Order model events on writes.
    'stats_ttl' => (int) env('DASHBOARD_STATS_TTL', 120),
    'detailed_stats_ttl' => (int) env('DASHBOARD_DETAILED_STATS_TTL', 300),
    'status_counts_ttl' => (int) env('DASHBOARD_STATUS_COUNTS_TTL', 300),

    // How many rows/points each widget shows.
    'recent_orders' => 10,
    'top_customers' => 5,
    'top_products' => 5,
    'chart_days' => 7,

    // "Top products" limit on the reports page.
    'report_top_products' => 10,

];

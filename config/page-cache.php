<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Page / route data cache TTLs (seconds)
    |--------------------------------------------------------------------------
    | Uses CACHE_STORE (redis in production). Bust via Setting updates or cache:clear.
    */

    'landing_public_ttl' => (int) env('PAGE_CACHE_LANDING_TTL', 900),

    'admin_overview_ttl' => (int) env('PAGE_CACHE_ADMIN_OVERVIEW_TTL', 120),

    'coordinator_stats_ttl' => (int) env('PAGE_CACHE_COORDINATOR_STATS_TTL', 120),

    'student_class_groups_ttl' => (int) env('PAGE_CACHE_STUDENT_CLASS_GROUPS_TTL', 120),

    'student_dashboard_banner_ttl' => (int) env('PAGE_CACHE_STUDENT_BANNER_TTL', 600),

];

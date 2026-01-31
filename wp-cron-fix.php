<?php
/**
 * Disable WordPress built-in cron
 * Add this to your wp-config.php
 */
define('DISABLE_WP_CRON', true);

// Alternative: Add to functions.php of your theme
// add_filter('cron_schedules', function($schedules) {
//     $schedules['every_5_minutes'] = array(
//         'interval' => 300,
//         'display' => __('Every 5 Minutes')
//     );
//     return $schedules;
// });
?>

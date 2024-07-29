<?php
/**
 * Plugin Name: Supabase Hotels Importer
 * Description: Imports hotels from Supabase into WordPress posts with ACF.
 * Version: 1.0
 * Author: Your Name
 * Requires ACF: true
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Check if ACF is installed
if (!class_exists('ACF')) {
    add_action('admin_notices', function() {
        echo '<div class="error"><p>Supabase Hotels Importer requires Advanced Custom Fields to be installed and activated.</p></div>';
    });
    return;
}

require_once plugin_dir_path(__FILE__) . 'includes/admin-menu.php';
require_once plugin_dir_path(__FILE__) . 'includes/supabase-functions.php';

// Register custom query variable
function custom_query_vars($vars) {
    $vars[] = 'location';
    return $vars;
}
add_filter('query_vars', 'custom_query_vars');

// Flush rewrite rules on activation/deactivation
register_activation_hook(__FILE__, 'flush_rewrite_rules');
register_deactivation_hook(__FILE__, 'flush_rewrite_rules');

// Modify Elementor Query
function modify_elementor_query($query) {
    // Get the location parameter from the URL
    $location = get_query_var('location');

    if ($location) {
        // Modify the query to get hotels by location
        $query->set('post_type', 'hotel');
        $query->set('meta_query', array(
            array(
                'key' => 'country',
                'value' => $location,
                'compare' => '='
            )
        ));
    }
}
add_action('elementor/query/custom_query', 'modify_elementor_query');

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
    $location = get_query_var('location');
    if ($location) {
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

// Custom Walker Class for Country Location URLs
class Country_Location_Walker extends Walker_Nav_Menu {
    function start_el(&$output, $item, $depth = 0, $args = array(), $id = 0) {
        if ($item->object == 'country') {
            $item->url = home_url('/location/?location=' . urlencode($item->title));
        }
        parent::start_el($output, $item, $depth, $args, $id);
    }
}

// Hook to modify the menu
function modify_nav_menu_args($args) {
    if (!is_admin()) {
        $args['walker'] = new Country_Location_Walker();
    }
    return $args;
}

add_filter('wp_nav_menu_args', 'modify_nav_menu_args');

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

<?php
/**
 * Template Name: Location
 */

get_header();

// Ensure Elementor recognizes the query
function custom_location_query($query) {
    if (!is_admin() && $query->is_main_query() && is_page_template('template-location.php')) {
        // Get the location parameter from the URL
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
}
add_action('pre_get_posts', 'custom_location_query');

// Debugging to verify the query parameters and results
global $wp_query;
if (is_page_template('template-location.php')) {
    echo '<pre>';
    print_r($wp_query->request);  // SQL query being executed
    echo '</pre>';
}

// Start the Loop for Elementor to recognize
while (have_posts()) : the_post();
    the_content();
endwhile;

get_footer();

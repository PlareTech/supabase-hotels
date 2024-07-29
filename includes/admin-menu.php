<?php

function create_acf_fields() {
    if (function_exists('acf_add_local_field_group')) {
        acf_add_local_field_group(array(
            'key' => 'group_1',
            'title' => 'Hotel Details',
            'fields' => array(
                array(
                    'key' => 'field_country',
                    'label' => 'Country',
                    'name' => 'country',
                    'type' => 'text',
                ),
                array(
                    'key' => 'field_name',
                    'label' => 'Name',
                    'name' => 'name',
                    'type' => 'text',
                ),
                array(
                    'key' => 'field_imgone',
                    'label' => 'Image One',
                    'name' => 'imgone',
                    'type' => 'image',
                    'return_format' => 'id',
                ),
                array(
                    'key' => 'field_imgtwo',
                    'label' => 'Image Two',
                    'name' => 'imgtwo',
                    'type' => 'image',
                    'return_format' => 'id',
                ),
                array(
                    'key' => 'field_imgthree',
                    'label' => 'Image Three',
                    'name' => 'imgthree',
                    'type' => 'image',
                    'return_format' => 'id',
                ),
                array(
                    'key' => 'field_imgfour',
                    'label' => 'Image Four',
                    'name' => 'imgfour',
                    'type' => 'image',
                    'return_format' => 'id',
                ),
                array(
                    'key' => 'field_desc',
                    'label' => 'Description',
                    'name' => 'desc',
                    'type' => 'textarea',
                ),
                array(
                    'key' => 'field_wifi',
                    'label' => 'Wifi',
                    'name' => 'wifi',
                    'type' => 'text',
                ),
                array(
                    'key' => 'field_tv',
                    'label' => 'TV',
                    'name' => 'tv',
                    'type' => 'text',
                ),
                array(
                    'key' => 'field_casino',
                    'label' => 'Casino',
                    'name' => 'casino',
                    'type' => 'text',
                ),
                array(
                    'key' => 'field_library',
                    'label' => 'Library',
                    'name' => 'library',
                    'type' => 'text',
                ),
                array(
                    'key' => 'field_shops',
                    'label' => 'Shops',
                    'name' => 'shops',
                    'type' => 'text',
                ),
                array(
                    'key' => 'field_pool',
                    'label' => 'Pool',
                    'name' => 'pool',
                    'type' => 'text',
                ),
                array(
                    'key' => 'field_ecar',
                    'label' => 'E-Car',
                    'name' => 'ecar',
                    'type' => 'text',
                ),
                array(
                    'key' => 'field_binternet',
                    'label' => 'Business Internet',
                    'name' => 'binternet',
                    'type' => 'text',
                ),
                array(
                    'key' => 'field_spa',
                    'label' => 'Spa',
                    'name' => 'spa',
                    'type' => 'text',
                ),
                array(
                    'key' => 'field_express',
                    'label' => 'Express',
                    'name' => 'express',
                    'type' => 'text',
                ),
				array(
                    'key' => 'field_link',
                    'label' => 'Link',
                    'name' => 'link',
                    'type' => 'url',
                ),
				array(
                    'key' => 'field_stars',
                    'label' => 'Stars',
                    'name' => 'stars',
                    'type' => 'number',
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'hotel',
                    ),
                ),
            ),
        ));
    }
}

add_action('acf/init', 'create_acf_fields');

function create_hotel_post_type() {
    register_post_type('hotel',
        array(
            'labels' => array(
                'name' => __('Hotels'),
                'singular_name' => __('Hotel')
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'thumbnail'),
            'rewrite' => array('slug' => 'hotel'),
        )
    );
}
add_action('init', 'create_hotel_post_type');


function add_hotel_admin_menu() {
    add_menu_page('Hotels', 'Hotels', 'manage_options', 'hotel_importer', 'hotel_importer_page');
    add_submenu_page('hotel_importer', 'Imported Hotels', 'Imported Hotels', 'manage_options', 'imported_hotels', 'imported_hotels_page');
}
add_action('admin_menu', 'add_hotel_admin_menu');

function hotel_importer_page() {
    ?>
    <div class="wrap">
        <h1>Supabase Hotels Importer</h1>
        <div id="hotel-list">
            <!-- Hotel list will be populated by JavaScript -->
        </div>
    </div>
    <?php
}

function imported_hotels_page() {
    $args = array(
        'post_type' => 'hotel',
        'post_status' => 'publish',
        'posts_per_page' => -1
    );

    $hotels_query = new WP_Query($args);

    if ($hotels_query->have_posts()) {
        echo '<div class="wrap"><h1>Imported Hotels</h1><table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Title</th><th>Country</th><th>Date Imported</th><th>Actions</th></tr></thead>';
        echo '<tbody>';
        while ($hotels_query->have_posts()) {
            $hotels_query->the_post();
            $country = get_field('country');
            echo '<tr>';
            echo '<td>' . get_the_title() . '</td>';
            echo '<td>' . esc_html($country) . '</td>';
            echo '<td>' . get_the_date() . '</td>';
            echo '<td><a href="' . get_edit_post_link() . '">Edit</a> | <a href="' . get_delete_post_link(get_the_ID()) . '">Delete</a></td>';
            echo '</tr>';
        }
        echo '</tbody></table></div>';
    } else {
        echo '<div class="wrap"><h1>Imported Hotels</h1><p>No imported hotels found.</p></div>';
    }

    wp_reset_postdata();
}

function enqueue_admin_scripts() {
    wp_enqueue_script('supabase-hotels-admin', plugin_dir_url(__FILE__) . '../assets/admin.js', array('jquery'), '1.0', true);
    wp_localize_script('supabase-hotels-admin', 'importHotelData', array(
        'nonce' => wp_create_nonce('import_hotel_nonce')
    ));
}

add_action('admin_enqueue_scripts', 'enqueue_admin_scripts');



function display_property_amenities() {
    if (!is_singular('hotel')) {
        return ''; // Only display on single hotel posts
    }

    $amenities = array(
        'wifi' => array('label' => 'Wifi', 'icon' => 'wifi.svg'),
        'tv' => array('label' => 'TV', 'icon' => 'monitor.svg'),
        'casino' => array('label' => 'Casino', 'icon' => 'gift.svg'),
        'library' => array('label' => 'Library', 'icon' => 'book.svg'),
        'shops' => array('label' => 'Shops', 'icon' => 'shopping-bag.svg'),
        'pool' => array('label' => 'Pool', 'icon' => 'droplet.svg'), // Choose an appropriate icon
        'ecar' => array('label' => 'E-Car', 'icon' => 'battery-charging.svg'),
        'binternet' => array('label' => 'Business Internet', 'icon' => 'briefcase.svg'),
        'spa' => array('label' => 'Spa', 'icon' => 'heart.svg'), // Choose an appropriate icon
        'express' => array('label' => 'Express', 'icon' => 'fast-forward.svg'), // Choose an appropriate icon
        'link' => array('label' => 'Link', 'icon' => 'link.svg')
    );

    $output = '<ul class="property-amenities">';
    foreach ($amenities as $field => $info) {
        $value = get_field($field);
        if ($value === 'true' || $value === true) {
            $output .= '<li>';
            $output .= '<img src="' . plugin_dir_url(__FILE__) . 'icons/' . esc_attr($info['icon']) . '" alt="' . esc_attr($info['label']) . ' icon" class="amenity-icon" style="margin-right:5px;" />';
            $output .= ' ' . esc_html($info['label']);
            $output .= '</li>';
        }
    }
    $output .= '</ul>';

    return $output;
}
add_shortcode('property_amenities', 'display_property_amenities');

<?php

function fetch_supabase_hotels() {
    $supabaseUrl = 'https://owohlvkbxxwzbqixaeov.supabase.co';
    $supabaseKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Im93b2hsdmtieHh3emJxaXhhZW92Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3MjAzNDU2MjYsImV4cCI6MjAzNTkyMTYyNn0.rDU2SACEt0nBeHPBg5HOgSG2TrGhUBBSzOKkTGlNwYI';

    $response = wp_remote_get("$supabaseUrl/rest/v1/hotels", array(
        'headers' => array(
            'apikey' => $supabaseKey,
            'Authorization' => "Bearer $supabaseKey",
        ),
    ));

    if (is_wp_error($response)) {
        error_log('Supabase API error: ' . $response->get_error_message());
        return array();
    }

    $body = wp_remote_retrieve_body($response);
    error_log('Supabase API response: ' . $body); // Log the response for debugging

    $hotels = json_decode($body, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('JSON decode error: ' . json_last_error_msg());
        return array();
    }

    return $hotels;
}

function import_image_from_url($url) {
    $tmp = download_url($url);

    if (is_wp_error($tmp)) {
        return $tmp;
    }

    $file = array(
        'name'     => basename($url),
        'type'     => mime_content_type($tmp),
        'tmp_name' => $tmp,
        'error'    => 0,
        'size'     => filesize($tmp),
    );

    $sideload = wp_handle_sideload($file, array('test_form' => false));

    if (!empty($sideload['error'])) {
        @unlink($tmp);
        return new WP_Error('upload_error', $sideload['error']);
    }

    $attachment_id = wp_insert_attachment(array(
        'guid'           => $sideload['url'],
        'post_mime_type' => $sideload['type'],
        'post_title'     => basename($sideload['file']),
        'post_content'   => '',
        'post_status'    => 'inherit',
    ), $sideload['file']);

    if (is_wp_error($attachment_id)) {
        @unlink($sideload['file']);
        return $attachment_id;
    }

    require_once(ABSPATH . 'wp-admin/includes/image.php');

    wp_update_attachment_metadata($attachment_id, wp_generate_attachment_metadata($attachment_id, $sideload['file']));

    return $attachment_id;
}


function import_hotel($hotel, $link) {
    $post_id = wp_insert_post(array(
        'post_title' => $hotel['Name'],
        'post_type' => 'hotel',
        'post_status' => 'publish',
    ));

    if ($post_id) {
        update_field('field_country', $hotel['Country'], $post_id);
        update_field('field_name', $hotel['Name'], $post_id);
        update_field('field_desc', $hotel['Desc'], $post_id);
        update_field('field_wifi', $hotel['Wifi'], $post_id);
        update_field('field_tv', $hotel['Tv'], $post_id);
        update_field('field_casino', $hotel['Casino'], $post_id);
        update_field('field_library', $hotel['Library'], $post_id);
        update_field('field_shops', $hotel['Shops'], $post_id);
        update_field('field_pool', $hotel['Pool'], $post_id);
        update_field('field_ecar', $hotel['Ecar'], $post_id);
        update_field('field_binternet', $hotel['Binternet'], $post_id);
        update_field('field_spa', $hotel['Spa'], $post_id);
        update_field('field_express', $hotel['Express'], $post_id);

        // Upload images and update fields
        if (!empty($hotel['ImgOne'])) {
            $imgOne_id = import_image_from_url($hotel['ImgOne']);
            if (!is_wp_error($imgOne_id)) {
                update_field('field_imgone', $imgOne_id, $post_id);
            }
        }

        if (!empty($hotel['ImgTwo'])) {
            $imgTwo_id = import_image_from_url($hotel['ImgTwo']);
            if (!is_wp_error($imgTwo_id)) {
                update_field('field_imgtwo', $imgTwo_id, $post_id);
            }
        }

        if (!empty($hotel['ImgThree'])) {
            $imgThree_id = import_image_from_url($hotel['ImgThree']);
            if (!is_wp_error($imgThree_id)) {
                update_field('field_imgthree', $imgThree_id, $post_id);
            }
        }

        if (!empty($hotel['ImgFour'])) {
            $imgFour_id = import_image_from_url($hotel['ImgFour']);
            if (!is_wp_error($imgFour_id)) {
                update_field('field_imgfour', $imgFour_id, $post_id);
            }
        }

        // Update the link
        update_field('field_link', $link, $post_id);
    }
}


add_action('wp_ajax_import_hotel', 'import_hotel_ajax_handler');
function import_hotel_ajax_handler() {
    check_ajax_referer('import_hotel_nonce', 'nonce');

    $hotel_id = intval($_POST['hotel_id']);
    $link = esc_url($_POST['link']);

    $hotels = fetch_supabase_hotels();
    $hotel = array_filter($hotels, function($h) use ($hotel_id) {
        return $h['id'] == $hotel_id;
    });

    if ($hotel) {
        import_hotel(reset($hotel), $link);
        wp_send_json_success();
    } else {
        wp_send_json_error('Hotel not found');
    }
}

add_action('wp_ajax_fetch_hotels', 'fetch_hotels_ajax_handler');
function fetch_hotels_ajax_handler() {
    check_ajax_referer('import_hotel_nonce', 'nonce');

    $hotels = fetch_supabase_hotels();

    if (!is_array($hotels) || empty($hotels)) {
        wp_send_json_error('No hotels found or API error');
        return;
    }

    ob_start();
    foreach ($hotels as $hotel) {
        if (is_array($hotel)) {
            echo '<div class="hotel">';
            echo '<h2>' . esc_html($hotel['Name']) . '</h2>';
            echo '<p>' . esc_html($hotel['Country']) . '</p>';
            echo '<button class="import-hotel" data-hotel-id="' . esc_attr($hotel['id']) . '">Import</button>';
            echo '</div>';
        }
    }
    $content = ob_get_clean();
    wp_send_json_success($content);
}

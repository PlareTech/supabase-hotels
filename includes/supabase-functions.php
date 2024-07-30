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
	    // Check if the country term exists, if not create it
    $term = term_exists($hotel['Country'], 'country');
    if (!$term) {
        $term = wp_insert_term($hotel['Country'], 'country');
    }

    // Get the term ID
    if (is_array($term)) {
        $term_id = $term['term_id'];
    } else {
        $term_id = $term;
    }
    $post_id = wp_insert_post(array(
        'post_title' => $hotel['Name'],
        'post_type' => 'hotel',
        'post_status' => 'publish',
    ));

    if ($post_id) {
        // Assign the country as a taxonomy term
        wp_set_post_terms($post_id, array($term_id), 'country');

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
        update_field('field_stars', $hotel['stars'], $post_id);

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

    $country = isset($_GET['country']) ? sanitize_text_field($_GET['country']) : '';
    $hotels = fetch_supabase_hotels();

    if (!is_array($hotels) || empty($hotels)) {
        wp_send_json_error('No hotels found or API error');
        return;
    }

    if ($country) {
        $hotels = array_filter($hotels, function($hotel) use ($country) {
            return $hotel['Country'] === $country;
        });
    }

    // Comparison function to sort hotels by country
    function compareByCountry($a, $b) {
        return strcmp($a['Country'], $b['Country']);
    }

    // Sort the hotels array by country
    usort($hotels, 'compareByCountry');

    ob_start(); // Start output buffering

    foreach ($hotels as $hotel) {
        if (is_array($hotel)) {
            $longLinkText = !empty($hotel['LongLink']) ? 'Copy Long Link' : 'Insert Link';
            $longLinkData = !empty($hotel['LongLink']) ? esc_url($hotel['LongLink']) : '';
            ?>
            <div class="notice notice-info inline" style="position: relative; padding-right: 150px;">
                <h2><?php echo esc_html($hotel['Name']); ?></h2>
                <p><?php echo esc_html($hotel['Country']); ?></p>
                <p><?php echo esc_html($hotel['Desc']); ?></p>
                <div style="display: flex; margin-bottom: 10px;">
                    <img src="<?php echo esc_html($hotel['ImgOne']); ?>" style="margin-right: 10px; height: 100px;">
                    <img src="<?php echo esc_html($hotel['ImgTwo']); ?>" style="margin-right: 10px; height: 100px;">
                    <img src="<?php echo esc_html($hotel['ImgThree']); ?>" style="margin-right: 10px; height: 100px;">
                    <img src="<?php echo esc_html($hotel['ImgFour']); ?>" style="margin-right: 10px; height: 100px;">
                </div>
                <button class="button button-primary import-hotel" style="position: absolute; right: 20px; top: 20px;" data-hotel-id="<?php echo esc_attr($hotel['id']); ?>">
                    <?php esc_html_e('Import', 'text-domain'); ?>
                </button>
                <a href="#" class="copy-or-insert-link" data-hotel-id="<?php echo esc_attr($hotel['id']); ?>" data-long-link="<?php echo $longLinkData; ?>" style="position: absolute; right: 20px; bottom: 10px;">
                    <?php esc_html_e($longLinkText, 'text-domain'); ?>
                </a>
            </div>
            <?php
        }
    }

    $content = ob_get_clean(); // Get the content from the output buffer
    wp_send_json_success($content);
}


function update_long_link_ajax_handler() {
    check_ajax_referer('import_hotel_nonce', 'nonce');

    $hotel_id = intval($_POST['hotel_id']);
    $long_link = esc_url_raw($_POST['long_link']);

    // Fetch the hotel post
    $post = get_post($hotel_id);
    if ($post) {
        // Update the LongLink field in Supabase
        $result = update_supabase_long_link($hotel_id, $long_link);

        if ($result) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Failed to update Supabase');
        }
    } else {
        wp_send_json_error('Hotel not found');
    }
}

add_action('wp_ajax_update_long_link', 'update_long_link_ajax_handler');



function update_supabase_long_link($hotel_id, $long_link) {
    $supabaseUrl = 'https://owohlvkbxxwzbqixaeov.supabase.co';
    $supabaseKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Im93b2hsdmtieHh3emJxaXhhZW92Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3MjAzNDU2MjYsImV4cCI6MjAzNTkyMTYyNn0.rDU2SACEt0nBeHPBg5HOgSG2TrGhUBBSzOKkTGlNwYI';

    $response = wp_remote_post("$supabaseUrl/rest/v1/hotels?id=eq.$hotel_id", array(
        'headers' => array(
            'apikey' => $supabaseKey,
            'Authorization' => "Bearer $supabaseKey",
            'Content-Type' => 'application/json',
            'Prefer' => 'return=representation'
        ),
        'body' => json_encode(array('LongLink' => $long_link)),
        'method' => 'PATCH',
    ));

    if (is_wp_error($response)) {
        error_log('Supabase API error: ' . $response->get_error_message());
        return false;
    }

    $body = wp_remote_retrieve_body($response);
    $result = json_decode($body, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('JSON decode error: ' . json_last_error_msg());
        return false;
    }

    return isset($result[0]) && !empty($result[0]);
}


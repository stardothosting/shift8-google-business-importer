<?php
/**
 * Shift8 Google Business Main Functions
 *
 * Collection of functions used throughout the operation of the plugin
 *
 */

if ( !defined( 'ABSPATH' ) ) {
    die();
}

/**
 * The main function that polls Google API for each Place ID,
 * updates existing business posts or creates new ones.
 */
function shift8_business_update()
{
    $api_key = get_option('shift8_google_api_key', '');
    $place_ids_raw = get_option('shift8_google_place_ids', '');

    // Bail if no API key or no place IDs
    if (empty($api_key) || empty($place_ids_raw)) {
        shift8_log_message("Shift8 GMB Updater: No API key or Place IDs found in settings.");
        return;
    }

    $place_ids = array_filter(array_map('trim', explode("\n", $place_ids_raw)));

    foreach ($place_ids as $place_id) {
        // Build the request URL
        $url = "https://maps.googleapis.com/maps/api/place/details/json?place_id=" . urlencode($place_id) . "&key=" . urlencode($api_key);
        shift8_log_message("Polling Place ID: {$place_id}");

        $response = shift8_make_google_api_request($url);

        if (!$response || !isset($response['result'])) {
            shift8_log_message("Invalid response for Place ID: {$place_id}");
            continue;
        }

        // Convert response data to JSON
        $business_data_json = json_encode($response['result'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if (json_last_error() !== JSON_ERROR_NONE) {
            shift8_log_message("JSON encoding error for Place ID: {$place_id} - " . json_last_error_msg());
            continue;
        }

        // Check if a post for this Place ID exists
        $existing_posts = get_posts(array(
            'post_type'  => 'shift8_business',
            'meta_key'   => 'shift8_google_place_id',
            'meta_value' => $place_id,
            'numberposts' => 1
        ));

        if (!empty($existing_posts)) {
            // Update the existing post
            $post_id = $existing_posts[0]->ID;
            wp_update_post(array(
                'ID'           => $post_id,
                'post_content' => $business_data_json,  // Store JSON in post_content
            ));

            shift8_log_message("Updated existing post (ID: {$post_id}) for Place ID: {$place_id}");
        } else {
            // Create a new post
            $post_id = wp_insert_post(array(
                'post_title'   => sanitize_text_field($response['result']['name'] ?? 'Untitled Business'),
                'post_content' => $business_data_json,  // Store JSON in post_content
                'post_type'    => 'shift8_business',
                'post_status'  => 'publish'
            ));

            // Store the place_id as post meta
            if ($post_id && !is_wp_error($post_id)) {
                update_post_meta($post_id, 'shift8_google_place_id', $place_id);
                shift8_log_message("Created new post (ID: {$post_id}) for Place ID: {$place_id}");
            }
        }
    }
}

/**
 * Helper function to make the Google API Request with retries.
 */
function shift8_make_google_api_request($url)
{
    for ($i = 0; $i < 3; $i++) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_FAILONERROR, true); // Fail on HTTP errors

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);

        if ($http_code !== 200) {
            error_log("Shift8 API Request Failed - HTTP Code: " . $http_code);
            error_log("Shift8 API Request cURL Error: " . $curl_error);
        }

        if ($curl_error) {
            shift8_log_message('cURL Error: ' . $curl_error);
        } else {
            curl_close($ch);
            return json_decode($response, true);
        }

        curl_close($ch);
        sleep(1); // Delay before retry
    }
    return null;
}

/**
 * Logging helper.
 */
function shift8_log_message($message)
{
    if (defined('WP_CLI') && WP_CLI) {
        WP_CLI::log($message);
    } else {
        error_log("[Shift8 GMB Updater] " . $message);
    }
}

// WP-CLI Integration
if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('shift8_business_updater', 'shift8_business_update');
}

add_action('wp_ajax_shift8_business_test_api', 'shift8_business_test_api');

function shift8_business_test_api()
{
    error_log("Shift8 Test API: AJAX request received");
    error_log("Shift8 Test API: REQUEST DATA - " . print_r($_REQUEST, true));

    if (!current_user_can('manage_options')) {
        error_log("Shift8 Test API: Unauthorized user.");
        wp_send_json_error(['message' => __('Unauthorized request.', 'shift8')]);
    }

    if (!isset($_REQUEST['nonce']) || !wp_verify_nonce($_REQUEST['nonce'], 'shift8_business_test_api')) {
        error_log("Shift8 Test API: Invalid Nonce.");
        wp_send_json_error(['message' => __('Invalid security nonce.', 'shift8')]);
    }

    $api_key = get_option('shift8_google_api_key', '');
    $place_ids_raw = get_option('shift8_google_place_ids', '');

    if (empty($api_key)) {
        error_log("Shift8 Test API: Google API key missing.");
        wp_send_json_error(['message' => __('Google API key is missing.', 'shift8')]);
    }

    if (empty($place_ids_raw)) {
        error_log("Shift8 Test API: No Place IDs found.");
        wp_send_json_error(['message' => __('No Place IDs found.', 'shift8')]);
    }

    $place_ids = array_unique(array_filter(array_map('trim', explode("\n", $place_ids_raw))));

    if (empty($place_ids)) {
        error_log("Shift8 Test API: No valid Place IDs.");
        wp_send_json_error(['message' => __('No valid Place IDs found.', 'shift8')]);
    }

    $first_place_id = reset($place_ids);
    $url = "https://maps.googleapis.com/maps/api/place/details/json?place_id=" . urlencode($first_place_id) . "&key=" . urlencode($api_key);

    error_log("Shift8 Test API: Sending request to Google API - " . $url);

    $response = shift8_make_google_api_request($url);

    if (!$response) {
        error_log("Shift8 Test API: No response from Google API.");
        wp_send_json_error(['message' => __('Failed to connect to Google API.', 'shift8')]);
    }

    if (!isset($response['status']) || $response['status'] !== 'OK') {
        error_log("Shift8 Test API: Google API error - " . json_encode($response));
        wp_send_json_error([
            'message' => __('Google API returned an error.', 'shift8'),
            'details' => $response
        ]);
    }

    error_log("Shift8 Test API: API Success - " . json_encode($response['result']));

    wp_send_json_success([
        'message' => __('API Key is working!', 'shift8'),
        'data' => json_encode($response['result'], JSON_PRETTY_PRINT),
    ]);
}

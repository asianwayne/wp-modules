<?php
/**
 * 1. Process Post Views via AJAX
 */
function wppro_process_post_views()
{
    // 1. Security Check: Verify Nonce
    if (!check_ajax_referer('wppro_views_nonce', 'nonce', false)) {
        wp_send_json_error('Invalid Nonce');
    }

    // 2. Validate Post ID
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    if (!$post_id) {
        wp_send_json_error('Invalid Post ID');
    }

    // 3. (Optional) Accuracy Settings: Don't count logged-in users or specifically Admins
    // Remove this block if you want to count everyone including yourself.
    if (current_user_can('manage_options')) {
        wp_send_json_success('Admin view not counted');
    }

    // 4. Check if this visitor has already viewed this post within the last hour
    // Use IP address + post ID to create a unique transient key
    $visitor_ip = wppro_get_visitor_ip();
    $transient_key = 'wppro_view_' . md5($visitor_ip . '_' . $post_id);

    // If transient exists, this visitor already viewed this post within the hour
    if (get_transient($transient_key)) {
        $count = get_post_meta($post_id, 'wppro_post_views_count', true);
        wp_send_json_success(array(
            'new_count' => $count,
            'already_counted' => true,
            'message' => 'View already counted within the hour'
        ));
    }

    // Set transient to expire in 1 hour (3600 seconds)
    set_transient($transient_key, true, HOUR_IN_SECONDS);

    // 5. Increment the View Count
    // We check if the key exists to avoid issues with new posts
    $count_key = 'wppro_post_views_count';
    $count = get_post_meta($post_id, $count_key, true);

    if ($count === '') {
        $count = 1;
        delete_post_meta($post_id, $count_key);
        add_post_meta($post_id, $count_key, '1');
    } else {
        $count++;
        update_post_meta($post_id, $count_key, $count);
    }

    wp_send_json_success(array('new_count' => $count));
}

/**
 * Helper function to get visitor's IP address
 */
function wppro_get_visitor_ip()
{
    $ip = '';

    // Check for various IP headers (in order of reliability)
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // Can contain multiple IPs, get the first one
        $ip_list = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ip_list[0]);
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED'];
    } elseif (!empty($_SERVER['HTTP_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_FORWARDED_FOR'];
    } elseif (!empty($_SERVER['HTTP_FORWARDED'])) {
        $ip = $_SERVER['HTTP_FORWARDED'];
    } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }

    // Sanitize the IP address
    $ip = filter_var($ip, FILTER_VALIDATE_IP);

    return $ip ? $ip : 'unknown';
}

// Hook into WordPress AJAX (both for logged-in and logged-out users)
add_action('wp_ajax_wppro_increment_views', 'wppro_process_post_views');
add_action('wp_ajax_nopriv_wppro_increment_views', 'wppro_process_post_views');


/**
 * 2. Enqueue the JavaScript to trigger the view count
 */
function wppro_enqueue_view_counter_script()
{
    // Only run on single posts, pages, or custom post types
    if (is_singular()) {

        global $post;

        // Print the JS inline to avoid an extra HTTP request (Efficiency)
        ?>
        <script type="text/javascript">
            document.addEventListener("DOMContentLoaded", function () {
                var data = new FormData();
                data.append('action', 'wppro_increment_views');
                data.append('nonce', '<?php echo wp_create_nonce("wppro_views_nonce"); ?>');
                data.append('post_id', '<?php echo $post->ID; ?>');

                // Send Request using Fetch API (Modern & Fast)
                fetch('<?php echo admin_url("admin-ajax.php"); ?>', {
                    method: 'POST',
                    body: data
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Optional: If you want to update a counter on the page dynamically
                            console.log('View Counted: ' + data.data.new_count);
                        }
                    })
                    .catch(error => console.error('Error:', error));
            });
        </script>
        <?php
    }
}
add_action('wp_footer', 'wppro_enqueue_view_counter_script');


/**
 * 3. Helper to Get/Display Views
 */
function wppro_get_post_views($post_id = 0)
{
    if (!$post_id) {
        global $post;
        $post_id = $post ? $post->ID : 0;
    }

    $count_key = 'wppro_post_views_count';
    $count = get_post_meta($post_id, $count_key, true);

    if ($count === '') {
        delete_post_meta($post_id, $count_key);
        add_post_meta($post_id, $count_key, '0');
        return "0 Views";
    }

    return $count . ($count == 1 ? ' View' : ' Views');
}

// Shortcode: [post_views]
add_shortcode('post_views', function () {
    return wppro_get_post_views();
});


/**
 * 4. Add Views Column to WP Admin Dashboard
 */

// Add the column to Posts and Pages (and Custom Post Types if needed)
function wppro_add_views_column($columns)
{
    $columns['post_views'] = 'Views';
    return $columns;
}
add_filter('manage_posts_columns', 'wppro_add_views_column');
add_filter('manage_pages_columns', 'wppro_add_views_column');

// Populate the column
function wppro_display_views_column($column, $post_id)
{
    if ('post_views' === $column) {
        $count = get_post_meta($post_id, 'wppro_post_views_count', true);
        echo ($count) ? number_format(intval($count)) : '0';
    }
}
add_action('manage_posts_custom_column', 'wppro_display_views_column', 10, 2);
add_action('manage_pages_custom_column', 'wppro_display_views_column', 10, 2);



// Make the column sortable
function wppro_register_sortable_views_column($columns)
{
    $columns['post_views'] = 'post_views';
    return $columns;
}
add_filter('manage_edit-post_sortable_columns', 'wppro_register_sortable_views_column');
add_filter('manage_edit-page_sortable_columns', 'wppro_register_sortable_views_column');

// Handle the sorting logic
function wppro_sort_views_column($query)
{
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }

    if ('post_views' === $query->get('orderby')) {
        $query->set('meta_key', 'wppro_post_views_count');
        $query->set('orderby', 'meta_value_num');
    }
}
add_action('pre_get_posts', 'wppro_sort_views_column');
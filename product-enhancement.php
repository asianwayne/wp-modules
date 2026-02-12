<?php
/** product enhancements, admin columns, filters and actions */

function ruixing_product_admin_columns($columns)
{

    $new_cols = [];
    $inserted = false;
    foreach ($columns as $key => $val) {
        $new_cols[$key] = $val;
        if ($key == 'cb') {
            $new_cols['product_image'] = 'Product Image';
            $new_cols['featured'] = '<span class="dashicons dashicons-star-filled text-warning" title="Featured"></span>';
            $inserted = true;
        }
    }

    if (!$inserted) {
        $new_cols['product_image'] = '<span class="dashicons dashicons-format-image" title="Thumbnail"></span>';
        $new_cols['featured'] = '<span class="dashicons dashicons-star-filled text-warning" title="Featured"></span>';
    }

    return $new_cols;
}
add_filter('manage_edit-product_columns', 'ruixing_product_admin_columns');


/** reder content for custom admin columns */

function ruixing_show_product_admin_column_content($column, $post_id)
{
    switch ($column) {
        case 'product_image':
            if (has_post_thumbnail($post_id)) {
                echo get_the_post_thumbnail($post_id, array(50, 50));
            } else {
                echo '<span class="dashicons dashicons-format-image" style="color: #ccc;"></span>';
            }
            break;

        case 'featured':

            $is_featured = get_post_meta($post_id, 'featured', true);

            $icon_class = $is_featured === '1' ? 'dashicons-star-filled text-warning' : 'dashicons-star-empty text-muted';

            $title = $is_featured === '1' ? __('Yes', '  ruixing') : __('No', 'ruixing');

            $color = $is_featured === '1' ? '#f01d4e' : '#ccc';

            echo sprintf(
                '<a href="#" class="toggle-featured-product" data-id="%d" data-nonce="%s" title="%s"><span class="dashicons %s" style="color: %s;"></span></a>',
                $post_id,
                wp_create_nonce('toggle_featured_product' . $post_id),
                $title,
                $icon_class,
                $color
            );

            break;


    }
}

add_action('manage_product_posts_custom_column', 'ruixing_show_product_admin_column_content', 10, 2);

/** make the featured post coumsn sortable */

function ruixing_sortable_product_adimin_column($columns)
{

    $columns['featured'] = __('Featured', 'ruixing');
    return $columns;
}

add_filter('manage_edit-product_sortable_columns', 'ruixing_sortable_product_adimin_column');

/**
 * Filter by featured link above table
 */
function ruixing_add_featured_product_view($views)
{

    $current = isset($_GET['featured_filter']) && $_GET['featured_filter'] === '1' ? 'class="current"' : '';

    $args = [
        'post_type' => 'product',
        'meta_key' => 'featured',
        'meta_value' => '1',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'post_status' => 'any',
    ];

    $featured_posts = get_posts($args);

    $count = count($featured_posts);

    if ($count > 0) {
        $views['featured'] = sprintf(
            '<a href="%s" %s>%s <span class="count">(%d)</span></a>',
            admin_url('edit.php?post_type=product&featured_filter=1'),
            $current,
            __('Featured', 'ruixing'),
            $count

        );

    }

    return $views;

}

add_filter('views_edit-product', 'ruixing_add_featured_product_view');

/**
 * modify query for featured filter
 * 
 */

function ruixing_filter_featured_products($query)
{

    global $pagenow;

    if (is_admin() && $pagenow === 'edit.php' && isset($_GET['featured_filter']) && $_GET['featured_filter'] === '1' && $query->get('post_type') === 'product') {
        $query->set('meta_key', 'featured');
        $query->set('meta_value', '1');
    }

}

add_action('pre_get_posts', 'ruixing_filter_featured_products');

/**
 * Ajax handler to toggle featured status
 */

function ruixing_ajax_toggle_featured_product()
{
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

    if (!$post_id || !check_ajax_referer('toggle_featured_product' . $post_id, 'nonce', false)) {
        wp_send_json_error(__('Invalid Request', 'ruixing'));

    }
    if (!current_user_can('edit_post', $post_id)) {
        wp_send_json_error(__('You do not have permission to perform this action', 'ruixing'));
    }

    $current_status = get_post_meta($post_id, 'featured', true);

    $new_status = $current_status ? '' : '1';

    update_post_meta($post_id, 'featured', $new_status);

    wp_send_json_success(array(

        'new_status' => $new_status,
        'icon_class' => $new_status ? 'dashicons-star-filled' : 'dashicons-star-empty',
        'color' => $new_status ? '#f01d4e' : '#ccc'

    ));


}
//toggle_featured_product
add_action('wp_ajax_toggle_featured_product', 'ruixing_ajax_toggle_featured_product');

/** enqueue admin scripts */

function ruixing_admin_enqueue_products_scripts($hook)
{

    if ($hook !== 'edit.php' && get_post_type() !== 'product') {
        return;
    }

    wp_enqueue_script('ruixing-product-featured', get_template_directory_uri() . '/framework/admin/js/product-featured.js', ['jquery'], '1.0', true);

    // enqueue socrtable and dragable js 
    if (isset($_GET['featured_filter']) && $_GET['featured_filter'] === '1') {
        wp_enqueue_script('jquery-ui-sortable');

        wp_enqueue_script('ruixing-product-reorder', get_template_directory_uri() . '/framework/admin/js/product-reorder.js', ['jquery', 'jquery-ui-sortable'], '1.0', true);

        wp_localize_script('ruixing-product-reorder', 'ruixing_product_reorder', array(
            'nonce' => wp_create_nonce('ruixing_product_reorder_nonce'),
        ));
    }

}

add_action('admin_enqueue_scripts', 'ruixing_admin_enqueue_products_scripts');

/**
 * modify query for featured filter
 */

function ruixing_filter_featured_products_sort($query)
{
    global $pagenow;
    if (is_admin() && $pagenow === 'edit.php' && isset($_GET['featured_filter']) && $_GET['featured_filter'] === '1' && $query->get('post_type') === 'product') {
        $query->set('orderby', 'menu_order');
        $query->set('order', 'ASC');
    }

}
add_action('pre_get_posts', 'ruixing_filter_featured_products_sort');

/**
 * save menu order for featured products
 */
function ruixing_save_featured_products_order()
{

    if (!check_ajax_referer('ruixing_product_reorder_nonce', 'nonce', false)) {
        wp_send_json_error(__('Invalid requests', 'ruixing'));
    }

    if (!current_user_can('edit_others_posts')) {
        wp_send_json_error('Permission denied');

    }
    $order = isset($_POST['order']) ? $_POST['order'] : [];

    if (empty($order)) {
        wp_send_json_error('no order data recieved');
    }

    foreach ($order as $position => $post_id) {
        $post_id = intval($post_id);

        if ($post_id) {
            wp_update_post([
                'ID' => $post_id,
                'menu_order' => $position
            ]);
        }


    }
    wp_send_json_success(__('Order saved', 'ruixing'));


}

//toggle_featured_product
add_action('wp_ajax_ruixing_save_product_order', 'ruixing_save_featured_products_order');

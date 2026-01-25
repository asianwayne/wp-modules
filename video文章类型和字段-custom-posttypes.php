<?php

// Register Custom Post Type: Product
function create_product_post_type()
{

    $labels = array(
        'name' => _x('Products', 'Post Type General Name', 'ruixing'),
        'singular_name' => _x('Product', 'Post Type Singular Name', 'ruixing'),
        'menu_name' => __('Products', 'ruixing'),
        'name_admin_bar' => __('Product', 'ruixing'),
        'add_new' => __('Add New', 'ruixing'),
        'add_new_item' => __('Add New Product', 'ruixing'),
        'edit_item' => __('Edit Product', 'ruixing'),
        'new_item' => __('New Product', 'ruixing'),
        'view_item' => __('View Product', 'ruixing'),
        'view_items' => __('View Products', 'ruixing'),
        'search_items' => __('Search Product', 'ruixing'),
        'not_found' => __('No products found', 'ruixing'),
        'not_found_in_trash' => __('No products found in Trash', 'ruixing'),
    );

    $args = array(
        'label' => __('Product', 'ruixing'),
        'description' => __('A custom post type for products', 'ruixing'),
        'labels' => $labels,
        'supports' => array('title', 'editor', 'thumbnail', 'excerpt'),
        'taxonomies' => array('product_category', 'post_tag'),
        'hierarchical' => false,
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_position' => 5,
        'menu_icon' => 'dashicons-cart',
        'show_in_admin_bar' => true,
        'show_in_nav_menus' => true,
        'can_export' => true,
        'has_archive' => true,
        'rewrite' => array('slug' => 'products'),
        'exclude_from_search' => false,
        'publicly_queryable' => true,
        'capability_type' => 'post',
        'show_in_rest' => true, // Enables Gutenberg and REST API
    );

    register_post_type('product', $args);
}
add_action('init', 'create_product_post_type');

// Register Custom Taxonomy: Product Category
function create_product_category_taxonomy()
{

    $labels = array(
        'name' => _x('Product Categories', 'taxonomy general name', 'ruixing'),
        'singular_name' => _x('Product Category', 'taxonomy singular name', 'ruixing'),
        'search_items' => __('Search Product Categories', 'ruixing'),
        'all_items' => __('All Product Categories', 'ruixing'),
        'parent_item' => __('Parent Category', 'ruixing'),
        'parent_item_colon' => __('Parent Category:', 'ruixing'),
        'edit_item' => __('Edit Category', 'ruixing'),
        'update_item' => __('Update Category', 'ruixing'),
        'add_new_item' => __('Add New Category', 'ruixing'),
        'new_item_name' => __('New Category Name', 'ruixing'),
        'menu_name' => __('Categories', 'ruixing'),
    );

    $args = array(
        'hierarchical' => true, // true = behaves like categories; false = like tags
        'labels' => $labels,
        'show_ui' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'product-category'),
        'show_in_rest' => true,
    );

    register_taxonomy('product_category', array('product'), $args);
}
add_action('init', 'create_product_category_taxonomy');



//重组管理列和添加自定义列内容

add_filter('manage_product_posts_columns', 'custom_product_columns');


function custom_product_columns($cols)
{
    $new_cols = array(
        'cb' => $cols['cb'],
        // 'thumbnail' => __('Thumbnail'),
        'title' => __('Product Title'),
        'taxonomy-product_category' => $cols['taxonomy-product_category'],
        'tags' => 'Product Tags',
        'post_id' => __('Post Id'),
        'date' => __('Publish Date'),
    );

    return $new_cols;

}


add_action('manage_product_posts_custom_column', 'product_custom_column', 10, 2);


function product_custom_column($cols, $post_id)
{

    switch ($cols) {
        case 'thumbnail':
            echo get_the_post_thumbnail($post_id, 'thumbnail');
            break;

        case 'post_id':
            echo get_the_ID();
            break;

    }

}


// =============================================================================
// VIDEO CUSTOM POST TYPE
// =============================================================================

/**
 * Register Custom Post Type: Video
 */
function create_video_post_type()
{
    $labels = array(
        'name' => _x('Videos', 'Post Type General Name', 'ruixing'),
        'singular_name' => _x('Video', 'Post Type Singular Name', 'ruixing'),
        'menu_name' => __('Videos', 'ruixing'),
        'name_admin_bar' => __('Video', 'ruixing'),
        'add_new' => __('Add New', 'ruixing'),
        'add_new_item' => __('Add New Video', 'ruixing'),
        'edit_item' => __('Edit Video', 'ruixing'),
        'new_item' => __('New Video', 'ruixing'),
        'view_item' => __('View Video', 'ruixing'),
        'view_items' => __('View Videos', 'ruixing'),
        'search_items' => __('Search Videos', 'ruixing'),
        'not_found' => __('No videos found', 'ruixing'),
        'not_found_in_trash' => __('No videos found in Trash', 'ruixing'),
        'featured_image' => __('Video Thumbnail', 'ruixing'),
        'set_featured_image' => __('Set video thumbnail', 'ruixing'),
        'remove_featured_image' => __('Remove video thumbnail', 'ruixing'),
        'use_featured_image' => __('Use as video thumbnail', 'ruixing'),
    );

    $args = array(
        'label' => __('Video', 'ruixing'),
        'description' => __('Video gallery post type', 'ruixing'),
        'labels' => $labels,
        'supports' => array('title', 'editor', 'thumbnail'),
        'taxonomies' => array('video_category'),
        'hierarchical' => false,
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_position' => 6,
        'menu_icon' => 'dashicons-video-alt3',
        'show_in_admin_bar' => true,
        'show_in_nav_menus' => true,
        'can_export' => true,
        'has_archive' => false,
        'rewrite' => array('slug' => 'videos'),
        'exclude_from_search' => false,
        'publicly_queryable' => true,
        'capability_type' => 'post',
        'show_in_rest' => false,
    );

    register_post_type('video', $args);
}
add_action('init', 'create_video_post_type');

/**
 * Register Custom Taxonomy: Video Category
 */
function create_video_category_taxonomy()
{
    $labels = array(
        'name' => _x('Video Categories', 'taxonomy general name', 'ruixing'),
        'singular_name' => _x('Video Category', 'taxonomy singular name', 'ruixing'),
        'search_items' => __('Search Video Categories', 'ruixing'),
        'all_items' => __('All Video Categories', 'ruixing'),
        'parent_item' => __('Parent Category', 'ruixing'),
        'parent_item_colon' => __('Parent Category:', 'ruixing'),
        'edit_item' => __('Edit Category', 'ruixing'),
        'update_item' => __('Update Category', 'ruixing'),
        'add_new_item' => __('Add New Category', 'ruixing'),
        'new_item_name' => __('New Category Name', 'ruixing'),
        'menu_name' => __('Categories', 'ruixing'),
    );

    $args = array(
        'hierarchical' => true,
        'labels' => $labels,
        'show_ui' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'video-category'),
        'show_in_rest' => true,
    );

    register_taxonomy('video_category', array('video'), $args);
}
add_action('init', 'create_video_category_taxonomy');

/**
 * Add Video Meta Box
 */
function video_add_meta_boxes()
{
    add_meta_box(
        'video_source_meta_box',
        __('Video Source', 'ruixing'),
        'video_source_meta_box_callback',
        'video',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'video_add_meta_boxes');

/**
 * Video Source Meta Box Callback
 */
function video_source_meta_box_callback($post)
{
    wp_nonce_field('video_source_meta_box', 'video_source_meta_box_nonce');

    $video_source_type = get_post_meta($post->ID, '_video_source_type', true);
    $youtube_url = get_post_meta($post->ID, '_video_youtube_url', true);
    $uploaded_video = get_post_meta($post->ID, '_video_uploaded_file', true);
    $video_duration = get_post_meta($post->ID, '_video_duration', true);

    // Default to youtube if not set
    if (empty($video_source_type)) {
        $video_source_type = 'youtube';
    }
    ?>
    <style>
        .video-meta-box {
            padding: 15px 0;
        }

        .video-meta-row {
            margin-bottom: 20px;
        }

        .video-meta-row label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .video-meta-row input[type="text"],
        .video-meta-row input[type="url"] {
            width: 100%;
            padding: 8px 12px;
        }

        .video-source-options {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .video-source-option {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            border: 2px solid #ddd;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .video-source-option:hover {
            border-color: #2271b1;
        }

        .video-source-option.active {
            border-color: #2271b1;
            background: #f0f6fc;
        }

        .video-source-option input {
            margin: 0;
        }

        .video-source-fields {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
        }

        .video-source-field {
            display: none;
        }

        .video-source-field.active {
            display: block;
        }

        .upload-video-btn {
            margin-top: 10px;
        }

        .video-preview {
            margin-top: 15px;
            max-width: 400px;
        }

        .video-preview video {
            width: 100%;
            border-radius: 8px;
        }

        .video-preview iframe {
            width: 100%;
            aspect-ratio: 16/9;
            border-radius: 8px;
            border: none;
        }
    </style>

    <div class="video-meta-box">
        <div class="video-meta-row">
            <label><?php esc_html_e('Select Video Source', 'ruixing'); ?></label>
            <div class="video-source-options">
                <label class="video-source-option <?php echo $video_source_type === 'youtube' ? 'active' : ''; ?>">
                    <input type="radio" name="video_source_type" value="youtube" <?php checked($video_source_type, 'youtube'); ?>>
                    <span class="dashicons dashicons-youtube"></span>
                    <?php esc_html_e('YouTube Video', 'ruixing'); ?>
                </label>
                <label class="video-source-option <?php echo $video_source_type === 'upload' ? 'active' : ''; ?>">
                    <input type="radio" name="video_source_type" value="upload" <?php checked($video_source_type, 'upload'); ?>>
                    <span class="dashicons dashicons-upload"></span>
                    <?php esc_html_e('Upload Video', 'ruixing'); ?>
                </label>
            </div>
        </div>

        <div class="video-source-fields">
            <!-- YouTube Field -->
            <div class="video-source-field <?php echo $video_source_type === 'youtube' ? 'active' : ''; ?>"
                id="youtube-field">
                <div class="video-meta-row">
                    <label for="video_youtube_url"><?php esc_html_e('YouTube Video URL', 'ruixing'); ?></label>
                    <input type="url" id="video_youtube_url" name="video_youtube_url"
                        value="<?php echo esc_attr($youtube_url); ?>" placeholder="https://www.youtube.com/watch?v=xxxxx">
                    <p class="description"><?php esc_html_e('Enter the full YouTube video URL or embed URL.', 'ruixing'); ?>
                    </p>
                    <?php if (!empty($youtube_url)):
                        $video_id = ruixing_get_youtube_id($youtube_url);
                        if ($video_id): ?>
                            <div class="video-preview">
                                <iframe src="https://www.youtube.com/embed/<?php echo esc_attr($video_id); ?>"
                                    allowfullscreen></iframe>
                            </div>
                        <?php endif; endif; ?>
                </div>
            </div>

            <!-- Upload Field -->
            <div class="video-source-field <?php echo $video_source_type === 'upload' ? 'active' : ''; ?>"
                id="upload-field">
                <div class="video-meta-row">
                    <label for="video_uploaded_file"><?php esc_html_e('Upload Video File', 'ruixing'); ?></label>
                    <input type="url" id="video_uploaded_file" name="video_uploaded_file"
                        value="<?php echo esc_attr($uploaded_video); ?>"
                        placeholder="<?php esc_attr_e('Video URL', 'ruixing'); ?>">
                    <button type="button" class="button upload-video-btn" id="upload_video_button">
                        <?php esc_html_e('Upload Video', 'ruixing'); ?>
                    </button>
                    <p class="description"><?php esc_html_e('Supported formats: MP4, WebM, OGG', 'ruixing'); ?></p>
                    <?php if (!empty($uploaded_video)): ?>
                        <div class="video-preview">
                            <video controls>
                                <source src="<?php echo esc_url($uploaded_video); ?>">
                            </video>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="video-meta-row">
            <label for="video_duration"><?php esc_html_e('Video Duration', 'ruixing'); ?></label>
            <input type="text" id="video_duration" name="video_duration" value="<?php echo esc_attr($video_duration); ?>"
                placeholder="e.g., 5:30" style="width: 150px;">
            <p class="description">
                <?php esc_html_e('Enter the video duration (e.g., 5:30 for 5 minutes 30 seconds).', 'ruixing'); ?>
            </p>
        </div>
    </div>

    <script>
        jQuery(document).ready(function ($) {
            // Toggle video source fields
            $('input[name="video_source_type"]').on('change', function () {
                var value = $(this).val();
                $('.video-source-option').removeClass('active');
                $(this).closest('.video-source-option').addClass('active');
                $('.video-source-field').removeClass('active');
                $('#' + value + '-field').addClass('active');
            });

            // Media uploader for video
            $('#upload_video_button').on('click', function (e) {
                e.preventDefault();
                var mediaUploader = wp.media({
                    title: '<?php esc_html_e('Select or Upload Video', 'ruixing'); ?>',
                    button: { text: '<?php esc_html_e('Use this video', 'ruixing'); ?>' },
                    library: { type: 'video' },
                    multiple: false
                });
                mediaUploader.on('select', function () {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    $('#video_uploaded_file').val(attachment.url);
                });
                mediaUploader.open();
            });
        });
    </script>
    <?php
}

/**
 * Save Video Meta Box Data
 */
function video_save_meta_box_data($post_id)
{
    // Security checks
    if (
        !isset($_POST['video_source_meta_box_nonce']) ||
        !wp_verify_nonce($_POST['video_source_meta_box_nonce'], 'video_source_meta_box')
    ) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Save video source type
    if (isset($_POST['video_source_type'])) {
        update_post_meta($post_id, '_video_source_type', sanitize_text_field($_POST['video_source_type']));
    }

    // Save YouTube URL
    if (isset($_POST['video_youtube_url'])) {
        update_post_meta($post_id, '_video_youtube_url', esc_url_raw($_POST['video_youtube_url']));
    }

    // Save uploaded video URL
    if (isset($_POST['video_uploaded_file'])) {
        update_post_meta($post_id, '_video_uploaded_file', esc_url_raw($_POST['video_uploaded_file']));
    }

    // Save video duration
    if (isset($_POST['video_duration'])) {
        update_post_meta($post_id, '_video_duration', sanitize_text_field($_POST['video_duration']));
    }
}
add_action('save_post_video', 'video_save_meta_box_data');

/**
 * Helper function to extract YouTube video ID
 */
function ruixing_get_youtube_id($url)
{
    $video_id = '';

    // Match various YouTube URL formats
    $patterns = array(
        '/youtube\.com\/watch\?v=([^\&\?\/]+)/',
        '/youtube\.com\/embed\/([^\&\?\/]+)/',
        '/youtube\.com\/v\/([^\&\?\/]+)/',
        '/youtu\.be\/([^\&\?\/]+)/',
    );

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $url, $matches)) {
            $video_id = $matches[1];
            break;
        }
    }

    return $video_id;
}

/**
 * Get video embed HTML
 */
function ruixing_get_video_embed($post_id = null)
{
    if (!$post_id) {
        $post_id = get_the_ID();
    }

    $source_type = get_post_meta($post_id, '_video_source_type', true);
    $output = '';

    if ($source_type === 'youtube') {
        $youtube_url = get_post_meta($post_id, '_video_youtube_url', true);
        $video_id = ruixing_get_youtube_id($youtube_url);
        if ($video_id) {
            $output = '<iframe src="https://www.youtube.com/embed/' . esc_attr($video_id) . '?rel=0" 
                        title="' . esc_attr(get_the_title($post_id)) . '" 
                        frameborder="0" 
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" 
                        allowfullscreen></iframe>';
        }
    } else {
        $video_url = get_post_meta($post_id, '_video_uploaded_file', true);
        if ($video_url) {
            $thumbnail = ruixing_get_video_thumbnail($post_id);
            $output = '<video controls poster="' . esc_url($thumbnail) . '">
                        <source src="' . esc_url($video_url) . '" type="video/mp4">
                        ' . esc_html__('Your browser does not support the video tag.', 'ruixing') . '
                       </video>';
        }
    }

    return $output;
}

/**
 * Get video thumbnail URL (for YouTube or featured image)
 */
function ruixing_get_video_thumbnail($post_id = null)
{
    if (!$post_id) {
        $post_id = get_the_ID();
    }

    // First check for featured image
    if (has_post_thumbnail($post_id)) {
        return get_the_post_thumbnail_url($post_id, 'large');
    }

    // If YouTube video, get YouTube thumbnail
    $source_type = get_post_meta($post_id, '_video_source_type', true);
    if ($source_type === 'youtube') {
        $youtube_url = get_post_meta($post_id, '_video_youtube_url', true);
        $video_id = ruixing_get_youtube_id($youtube_url);
        if ($video_id) {
            return 'https://img.youtube.com/vi/' . $video_id . '/maxresdefault.jpg';
        }
    }

    // Default placeholder
    return get_template_directory_uri() . '/assets/images/video-placeholder.jpg';
}

/**
 * Customize Video Admin Columns
 */
add_filter('manage_video_posts_columns', 'custom_video_columns');

function custom_video_columns($cols)
{
    $new_cols = array(
        'cb' => $cols['cb'],
        'thumbnail' => __('Thumbnail', 'ruixing'),
        'title' => __('Video Title', 'ruixing'),
        'video_type' => __('Source', 'ruixing'),
        'taxonomy-video_category' => __('Category', 'ruixing'),
        'video_duration' => __('Duration', 'ruixing'),
        'date' => __('Date', 'ruixing'),
    );

    return $new_cols;
}

add_action('manage_video_posts_custom_column', 'video_custom_column', 10, 2);

function video_custom_column($col, $post_id)
{
    switch ($col) {
        case 'thumbnail':
            $thumb = ruixing_get_video_thumbnail($post_id);
            echo '<img src="' . esc_url($thumb) . '" style="width:80px;height:45px;object-fit:cover;border-radius:4px;">';
            break;

        case 'video_type':
            $type = get_post_meta($post_id, '_video_source_type', true);
            if ($type === 'youtube') {
                echo '<span class="dashicons dashicons-youtube" style="color:#ff0000;"></span> YouTube';
            } else {
                echo '<span class="dashicons dashicons-video-alt3" style="color:#0073aa;"></span> Uploaded';
            }
            break;

        case 'video_duration':
            echo esc_html(get_post_meta($post_id, '_video_duration', true));
            break;
    }
}
<?php
/**
 * Image Collection Custom Post Type with Sortable Gallery Meta Box
 * Paste this code into your theme's functions.php file
 */

// Register Custom Post Type
function register_image_collection_post_type() {
    $labels = array(
        'name'               => 'Image Collections',
        'singular_name'      => 'Image Collection',
        'menu_name'          => 'Image Collections',
        'add_new'            => 'Add New',
        'add_new_item'       => 'Add New Image Collection',
        'edit_item'          => 'Edit Image Collection',
        'new_item'           => 'New Image Collection',
        'view_item'          => 'View Image Collection',
        'search_items'       => 'Search Image Collections',
        'not_found'          => 'No image collections found',
        'not_found_in_trash' => 'No image collections found in Trash',
    );

    $args = array(
        'labels'              => $labels,
        'public'              => true,
        'has_archive'         => true,
        'publicly_queryable'  => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'query_var'           => true,
        'rewrite'             => array('slug' => 'image-collection'),
        'capability_type'     => 'post',
        'has_archive'         => true,
        'hierarchical'        => false,
        'menu_position'       => 5,
        'menu_icon'           => 'dashicons-format-gallery',
        'supports'            => array('title', 'editor', 'thumbnail'),
    );

    register_post_type('image_collection', $args);
}
add_action('init', 'register_image_collection_post_type');

// Add Gallery Meta Box
function add_gallery_meta_box() {
    add_meta_box(
        'image_collection_gallery',
        'Gallery Images',
        'render_gallery_meta_box',
        'image_collection',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'add_gallery_meta_box');

// Render Gallery Meta Box
function render_gallery_meta_box($post) {
    wp_nonce_field('save_gallery_meta', 'gallery_meta_nonce');
    
    $gallery_images = get_post_meta($post->ID, '_gallery_images', true);
    $gallery_images = !empty($gallery_images) ? $gallery_images : array();
    
    // Pagination settings
    $per_page = 12;
    $current_page = isset($_GET['gallery_page']) ? max(1, intval($_GET['gallery_page'])) : 1;
    $total_images = count($gallery_images);
    $total_pages = ceil($total_images / $per_page);
    $offset = ($current_page - 1) * $per_page;
    $paginated_images = array_slice($gallery_images, $offset, $per_page, true);
    ?>
    
    <div class="gallery-meta-box">
        <style>
            .gallery-meta-box {
                padding: 10px 0;
            }
            .gallery-images-container {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 15px;
                margin: 20px 0;
                min-height: 100px;
            }
            .gallery-image-item {
                position: relative;
                border: 1px solid #ddd;
                border-radius: 4px;
                overflow: hidden;
                background: #f9f9f9;
                cursor: move;
                transition: all 0.2s ease;
            }
            .gallery-image-item:hover {
                border-color: #0073aa;
                box-shadow: 0 2px 8px rgba(0,115,170,0.2);
            }
            .gallery-image-item.ui-sortable-helper {
                opacity: 0.8;
                transform: rotate(2deg);
                box-shadow: 0 5px 15px rgba(0,0,0,0.3);
                z-index: 1000;
            }
            .gallery-image-item.ui-sortable-placeholder {
                border: 2px dashed #0073aa;
                background: #e5f5fa;
                visibility: visible !important;
            }
            .gallery-image-item img {
                width: 100%;
                height: 150px;
                object-fit: cover;
                display: block;
                pointer-events: none;
            }
            .gallery-image-actions {
                position: absolute;
                top: 5px;
                right: 5px;
                display: flex;
                gap: 5px;
                opacity: 0;
                transition: opacity 0.2s ease;
            }
            .gallery-image-item:hover .gallery-image-actions {
                opacity: 1;
            }
            .gallery-image-actions button {
                background: rgba(0,0,0,0.7);
                color: white;
                border: none;
                border-radius: 3px;
                padding: 5px 8px;
                cursor: pointer;
                font-size: 12px;
            }
            .gallery-image-actions button:hover {
                background: rgba(0,0,0,0.9);
            }
            .drag-handle {
                position: absolute;
                top: 5px;
                left: 5px;
                background: rgba(0,115,170,0.9);
                color: white;
                padding: 5px 8px;
                border-radius: 3px;
                font-size: 12px;
                cursor: move;
                opacity: 0;
                transition: opacity 0.2s ease;
            }
            .gallery-image-item:hover .drag-handle {
                opacity: 1;
            }
            .gallery-add-button {
                display: inline-block;
                margin-bottom: 15px;
            }
            .gallery-pagination {
                margin-top: 20px;
                padding-top: 15px;
                border-top: 1px solid #ddd;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .gallery-pagination button {
                padding: 5px 10px;
            }
            .gallery-pagination span {
                color: #666;
            }
            .no-images-message {
                padding: 40px;
                text-align: center;
                color: #666;
                background: #f9f9f9;
                border: 2px dashed #ddd;
                border-radius: 4px;
            }
            .gallery-controls {
                display: flex;
                gap: 10px;
                margin-bottom: 15px;
                align-items: center;
            }
            .sort-notice {
                display: none;
                background: #d4edda;
                border: 1px solid #c3e6cb;
                color: #155724;
                padding: 8px 12px;
                border-radius: 4px;
                font-size: 13px;
            }
            .sort-notice.active {
                display: inline-block;
            }
        </style>

        <div class="gallery-controls">
            <button type="button" class="button button-primary gallery-add-button" id="add-gallery-images">
                Add Images
            </button>
            <span class="sort-notice" id="sort-notice">
                ✓ Order changed - Save post to keep changes
            </span>
        </div>
        
        <input type="hidden" id="gallery-images-data" name="gallery_images" value="<?php echo esc_attr(json_encode($gallery_images)); ?>">
        
        <div class="gallery-images-container" id="gallery-images-container">
            <?php if (empty($paginated_images)): ?>
                <div class="no-images-message">No images added yet. Click "Add Images" to get started.</div>
            <?php else: ?>
                <?php foreach ($paginated_images as $index => $image_id): 
                    $image_url = wp_get_attachment_image_url($image_id, 'medium');
                    if (!$image_url) continue;
                ?>
                    <div class="gallery-image-item" data-image-id="<?php echo esc_attr($image_id); ?>" data-index="<?php echo esc_attr($index); ?>">
                        <span class="drag-handle">⋮⋮</span>
                        <img src="<?php echo esc_url($image_url); ?>" alt="">
                        <div class="gallery-image-actions">
                            <button type="button" class="edit-gallery-image" data-image-id="<?php echo esc_attr($image_id); ?>">Edit</button>
                            <button type="button" class="delete-gallery-image" data-image-id="<?php echo esc_attr($image_id); ?>">Delete</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <?php if ($total_pages > 1): ?>
            <div class="gallery-pagination">
                <button type="button" class="button gallery-prev-page" <?php echo $current_page <= 1 ? 'disabled' : ''; ?>>Previous</button>
                <span>Page <?php echo $current_page; ?> of <?php echo $total_pages; ?> (<?php echo $total_images; ?> images total)</span>
                <button type="button" class="button gallery-next-page" <?php echo $current_page >= $total_pages ? 'disabled' : ''; ?>>Next</button>
                <input type="hidden" id="gallery-current-page" value="<?php echo $current_page; ?>">
                <input type="hidden" id="gallery-total-pages" value="<?php echo $total_pages; ?>">
            </div>
        <?php endif; ?>

        <script type="text/javascript">
        jQuery(document).ready(function($) {
            var galleryData = [];
            var currentPage = parseInt($('#gallery-current-page').val()) || 1;
            var perPage = 12;
            
            // Initialize gallery data
            try {
                galleryData = JSON.parse($('#gallery-images-data').val()) || [];
            } catch(e) {
                galleryData = [];
            }

            // Initialize sortable
            function initSortable() {
                $('#gallery-images-container').sortable({
                    items: '.gallery-image-item',
                    cursor: 'move',
                    opacity: 0.8,
                    placeholder: 'ui-sortable-placeholder',
                    tolerance: 'pointer',
                    update: function(event, ui) {
                        // Get new order from current page
                        var newPageOrder = [];
                        $('#gallery-images-container .gallery-image-item').each(function() {
                            newPageOrder.push(parseInt($(this).data('image-id')));
                        });
                        
                        // Calculate the offset for current page
                        var offset = (currentPage - 1) * perPage;
                        
                        // Create new complete array with reordered page
                        var newGalleryData = [];
                        
                        // Add items before current page
                        for (var i = 0; i < offset; i++) {
                            if (galleryData[i]) {
                                newGalleryData.push(galleryData[i]);
                            }
                        }
                        
                        // Add reordered items from current page
                        newPageOrder.forEach(function(id) {
                            newGalleryData.push(id);
                        });
                        
                        // Add items after current page
                        for (var i = offset + perPage; i < galleryData.length; i++) {
                            newGalleryData.push(galleryData[i]);
                        }
                        
                        galleryData = newGalleryData;
                        updateGalleryData();
                        
                        // Show save notice
                        $('#sort-notice').addClass('active');
                    }
                });
            }

            // Initialize on load if there are images
            if (galleryData.length > 0) {
                initSortable();
            }

            // Add images
            $('#add-gallery-images').on('click', function(e) {
                e.preventDefault();
                
                var frame = wp.media({
                    title: 'Select or Upload Images',
                    button: { text: 'Add to Gallery' },
                    multiple: true
                });

                frame.on('select', function() {
                    var attachments = frame.state().get('selection').toJSON();
                    
                    attachments.forEach(function(attachment) {
                        if (!galleryData.includes(attachment.id)) {
                            galleryData.push(attachment.id);
                        }
                    });
                    
                    updateGalleryData();
                    renderGallery();
                });

                frame.open();
            });

            // Edit image
            $(document).on('click', '.edit-gallery-image', function(e) {
                e.preventDefault();
                var imageId = $(this).data('image-id');
                
                var frame = wp.media({
                    title: 'Edit Image',
                    button: { text: 'Update Image' },
                    multiple: false
                });

                frame.on('open', function() {
                    var selection = frame.state().get('selection');
                    var attachment = wp.media.attachment(imageId);
                    attachment.fetch();
                    selection.add(attachment);
                });

                frame.on('select', function() {
                    var attachment = frame.state().get('selection').first().toJSON();
                    var index = galleryData.indexOf(imageId);
                    
                    if (index !== -1) {
                        galleryData[index] = attachment.id;
                        updateGalleryData();
                        renderGallery();
                    }
                });

                frame.open();
            });

            // Delete image
            $(document).on('click', '.delete-gallery-image', function(e) {
                e.preventDefault();
                
                if (!confirm('Are you sure you want to delete this image from the gallery?')) {
                    return;
                }
                
                var imageId = $(this).data('image-id');
                var index = galleryData.indexOf(imageId);
                
                if (index !== -1) {
                    galleryData.splice(index, 1);
                    updateGalleryData();
                    renderGallery();
                }
            });

            // Pagination
            $('.gallery-prev-page').on('click', function() {
                if (currentPage > 1) {
                    currentPage--;
                    renderGallery();
                }
            });

            $('.gallery-next-page').on('click', function() {
                var totalPages = Math.ceil(galleryData.length / perPage);
                if (currentPage < totalPages) {
                    currentPage++;
                    renderGallery();
                }
            });

            function updateGalleryData() {
                $('#gallery-images-data').val(JSON.stringify(galleryData));
            }

            function renderGallery() {
                var totalPages = Math.ceil(galleryData.length / perPage);
                var offset = (currentPage - 1) * perPage;
                var paginatedData = galleryData.slice(offset, offset + perPage);
                
                if (currentPage > totalPages && totalPages > 0) {
                    currentPage = totalPages;
                    paginatedData = galleryData.slice((currentPage - 1) * perPage, currentPage * perPage);
                }

                var container = $('#gallery-images-container');
                
                // Destroy sortable before clearing
                if (container.hasClass('ui-sortable')) {
                    container.sortable('destroy');
                }
                
                container.empty();

                if (galleryData.length === 0) {
                    container.html('<div class="no-images-message">No images added yet. Click "Add Images" to get started.</div>');
                    $('.gallery-pagination').hide();
                    return;
                }

                var imagesLoaded = 0;
                paginatedData.forEach(function(imageId, idx) {
                    wp.media.attachment(imageId).fetch().then(function(data) {
                        var imageUrl = data.sizes && data.sizes.medium ? data.sizes.medium.url : data.url;
                        var imageHtml = '<div class="gallery-image-item" data-image-id="' + imageId + '">' +
                            '<span class="drag-handle">⋮⋮</span>' +
                            '<img src="' + imageUrl + '" alt="">' +
                            '<div class="gallery-image-actions">' +
                            '<button type="button" class="edit-gallery-image" data-image-id="' + imageId + '">Edit</button>' +
                            '<button type="button" class="delete-gallery-image" data-image-id="' + imageId + '">Delete</button>' +
                            '</div></div>';
                        container.append(imageHtml);
                        
                        imagesLoaded++;
                        // Reinitialize sortable after all images are loaded
                        if (imagesLoaded === paginatedData.length) {
                            initSortable();
                        }
                    });
                });

                // Update pagination
                if (totalPages > 1) {
                    $('.gallery-pagination').show();
                    $('.gallery-pagination span').html('Page ' + currentPage + ' of ' + totalPages + ' (' + galleryData.length + ' images total)');
                    $('.gallery-prev-page').prop('disabled', currentPage <= 1);
                    $('.gallery-next-page').prop('disabled', currentPage >= totalPages);
                } else {
                    $('.gallery-pagination').hide();
                }
            }
        });
        </script>
    </div>
    <?php
}

// Save Gallery Meta Box Data
function save_gallery_meta($post_id) {
    // Security checks
    if (!isset($_POST['gallery_meta_nonce']) || !wp_verify_nonce($_POST['gallery_meta_nonce'], 'save_gallery_meta')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Save gallery images
    if (isset($_POST['gallery_images'])) {
        $gallery_images = json_decode(stripslashes($_POST['gallery_images']), true);
        
        if (is_array($gallery_images)) {
            // Validate that all items are integers (attachment IDs)
            $gallery_images = array_filter($gallery_images, 'is_numeric');
            $gallery_images = array_map('intval', $gallery_images);
            update_post_meta($post_id, '_gallery_images', $gallery_images);
        } else {
            delete_post_meta($post_id, '_gallery_images');
        }
    } else {
        delete_post_meta($post_id, '_gallery_images');
    }
}
add_action('save_post_image_collection', 'save_gallery_meta');

// Enqueue WordPress Media Scripts
function enqueue_gallery_scripts($hook) {
    global $post;

    if ($hook == 'post-new.php' || $hook == 'post.php') {
        if ('image_collection' === $post->post_type) {
            wp_enqueue_media();
            wp_enqueue_script('jquery-ui-sortable');
        }
    }
}
add_action('admin_enqueue_scripts', 'enqueue_gallery_scripts');

// Optional: Display gallery on frontend with pagination
function display_image_collection_gallery($post_id = null, $per_page = 12) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }

    $gallery_images = get_post_meta($post_id, '_gallery_images', true);
    
    if (empty($gallery_images) || !is_array($gallery_images)) {
        return '<p>No images in this collection.</p>';
    }

    // Get current page from URL parameter
    $current_page = isset($_GET['gallery_page']) ? max(1, intval($_GET['gallery_page'])) : 1;
    
    // Calculate pagination
    $total_images = count($gallery_images);
    $total_pages = ceil($total_images / $per_page);
    $current_page = min($current_page, $total_pages); // Don't exceed max pages
    $offset = ($current_page - 1) * $per_page;
    
    // Get images for current page
    $paginated_images = array_slice($gallery_images, $offset, $per_page);

    // Start output with styles
    $output = '<style>
        .image-collection-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        .gallery-item {
            position: relative;
            overflow: hidden;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .gallery-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .gallery-item img {
            width: 100%;
            height: 300px;
            object-fit: cover;
            display: block;
        }
        .gallery-pagination-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
            margin: 30px 0;
            flex-wrap: wrap;
        }
        .gallery-pagination-wrapper a,
        .gallery-pagination-wrapper span {
            padding: 8px 15px;
            text-decoration: none;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: #fff;
            color: #333;
            transition: all 0.3s ease;
        }
        .gallery-pagination-wrapper a:hover {
            background: #0073aa;
            color: #fff;
            border-color: #0073aa;
        }
        .gallery-pagination-wrapper .current-page {
            background: #0073aa;
            color: #fff;
            border-color: #0073aa;
            font-weight: bold;
        }
        .gallery-pagination-wrapper .disabled {
            opacity: 0.5;
            pointer-events: none;
            cursor: not-allowed;
        }
        .gallery-page-numbers {
            display: flex;
            gap: 5px;
        }
        @media (max-width: 768px) {
            .image-collection-gallery {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 10px;
            }
            .gallery-item img {
                height: 200px;
            }
        }
    </style>';
    
    $output .= '<div class="image-collection-gallery">';
    
    foreach ($paginated_images as $image_id) {
        $image_url = wp_get_attachment_image_url($image_id, 'large');
        $image_alt = get_post_meta($image_id, '_wp_attachment_image_alt', true);
        $image_title = get_the_title($image_id);
        
        if ($image_url) {
            $output .= '<div class="gallery-item">';
            $output .= '<a href="' . esc_url(wp_get_attachment_url($image_id)) . '" target="_blank">';
            $output .= '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($image_alt) . '" title="' . esc_attr($image_title) . '">';
            $output .= '</a>';
            $output .= '</div>';
        }
    }
    
    $output .= '</div>';
    
    // Add pagination if there's more than one page
    if ($total_pages > 1) {
        $output .= '<div class="gallery-pagination-wrapper">';
        
        // Get current URL without gallery_page parameter
        $current_url = remove_query_arg('gallery_page');
        
        // Previous button
        if ($current_page > 1) {
            $prev_url = add_query_arg('gallery_page', $current_page - 1, $current_url);
            $output .= '<a href="' . esc_url($prev_url) . '" class="prev-page">← Previous</a>';
        } else {
            $output .= '<span class="prev-page disabled">← Previous</span>';
        }
        
        // Page numbers
        $output .= '<div class="gallery-page-numbers">';
        
        // Show first page
        if ($current_page > 3) {
            $page_url = add_query_arg('gallery_page', 1, $current_url);
            $output .= '<a href="' . esc_url($page_url) . '">1</a>';
            if ($current_page > 4) {
                $output .= '<span>...</span>';
            }
        }
        
        // Show pages around current page
        for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++) {
            if ($i == $current_page) {
                $output .= '<span class="current-page">' . $i . '</span>';
            } else {
                $page_url = add_query_arg('gallery_page', $i, $current_url);
                $output .= '<a href="' . esc_url($page_url) . '">' . $i . '</a>';
            }
        }
        
        // Show last page
        if ($current_page < $total_pages - 2) {
            if ($current_page < $total_pages - 3) {
                $output .= '<span>...</span>';
            }
            $page_url = add_query_arg('gallery_page', $total_pages, $current_url);
            $output .= '<a href="' . esc_url($page_url) . '">' . $total_pages . '</a>';
        }
        
        $output .= '</div>';
        
        // Next button
        if ($current_page < $total_pages) {
            $next_url = add_query_arg('gallery_page', $current_page + 1, $current_url);
            $output .= '<a href="' . esc_url($next_url) . '" class="next-page">Next →</a>';
        } else {
            $output .= '<span class="next-page disabled">Next →</span>';
        }
        
        $output .= '</div>';
        
        // Page info
        $output .= '<div style="text-align: center; color: #666; margin-top: 10px;">';
        $output .= 'Showing ' . (($offset + 1)) . '-' . min(($offset + $per_page), $total_images) . ' of ' . $total_images . ' images';
        $output .= '</div>';
    }
    
    return $output;
}

// Shortcode to display gallery: [image_collection_gallery] or [image_collection_gallery id="123" per_page="20"]
function image_collection_gallery_shortcode($atts) {
    $atts = shortcode_atts(array(
        'id' => get_the_ID(),
        'per_page' => 12,
    ), $atts);

    return display_image_collection_gallery($atts['id'], $atts['per_page']);
}
add_shortcode('image_collection_gallery', 'image_collection_gallery_shortcode');
?>
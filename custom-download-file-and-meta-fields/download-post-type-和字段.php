<?php
/**
 * Register Download custom post type.
 *
 * @since Iceberg 1.0
 */
function iceberg_register_download_post_type()
{
    $labels = array(
        'name' => _x('Downloads', 'Post Type General Name', 'iceberg'),
        'singular_name' => _x('Download', 'Post Type Singular Name', 'iceberg'),
        'menu_name' => __('Downloads', 'iceberg'),
        'name_admin_bar' => __('Download', 'iceberg'),
        'archives' => __('Download Archives', 'iceberg'),
        'attributes' => __('Download Attributes', 'iceberg'),
        'parent_item_colon' => __('Parent Download:', 'iceberg'),
        'all_items' => __('All Downloads', 'iceberg'),
        'add_new_item' => __('Add New Download', 'iceberg'),
        'add_new' => __('Add New', 'iceberg'),
        'new_item' => __('New Download', 'iceberg'),
        'edit_item' => __('Edit Download', 'iceberg'),
        'update_item' => __('Update Download', 'iceberg'),
        'view_item' => __('View Download', 'iceberg'),
        'view_items' => __('View Downloads', 'iceberg'),
        'search_items' => __('Search Download', 'iceberg'),
        'not_found' => __('Not found', 'iceberg'),
        'not_found_in_trash' => __('Not found in Trash', 'iceberg'),
        'featured_image' => __('Featured Image', 'iceberg'),
        'set_featured_image' => __('Set featured image', 'iceberg'),
        'remove_featured_image' => __('Remove featured image', 'iceberg'),
        'use_featured_image' => __('Use as featured image', 'iceberg'),
        'insert_into_item' => __('Insert into download', 'iceberg'),
        'uploaded_to_this_item' => __('Uploaded to this download', 'iceberg'),
        'items_list' => __('Downloads list', 'iceberg'),
        'items_list_navigation' => __('Downloads list navigation', 'iceberg'),
        'filter_items_list' => __('Filter downloads list', 'iceberg'),
    );
    $args = array(
        'label' => __('Download', 'iceberg'),
        'description' => __('Download custom post type.', 'iceberg'),
        'labels' => $labels,
        'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'author', 'revisions'),
        'hierarchical' => false,
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_position' => 5,
        'menu_icon' => 'dashicons-download',
        'show_in_admin_bar' => true,
        'show_in_nav_menus' => true,
        'can_export' => true,
        'has_archive' => true,
        'exclude_from_search' => false,
        'publicly_queryable' => true,
        'capability_type' => 'post',
        'show_in_rest' => true,
    );
    register_post_type('download', $args);
}
add_action('init', 'iceberg_register_download_post_type', 0);

/* ────────────────────────────────────────────────────────────
 *  Download Files – repeatable meta box
 * ──────────────────────────────────────────────────────────── */

/**
 * Register the meta box.
 */
function iceberg_download_meta_box()
{
    add_meta_box(
        'iceberg_download_files',
        __('Download Files', 'iceberg'),
        'iceberg_download_meta_box_callback',
        'download',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'iceberg_download_meta_box');

/**
 * Enqueue media uploader on the download edit screen.
 */
function iceberg_download_admin_scripts($hook)
{
    global $post_type;
    if (('post.php' === $hook || 'post-new.php' === $hook) && 'download' === $post_type) {
        wp_enqueue_media();
    }
}
add_action('admin_enqueue_scripts', 'iceberg_download_admin_scripts');

/**
 * Meta box callback – renders the repeatable file rows.
 */
function iceberg_download_meta_box_callback($post)
{
    wp_nonce_field('iceberg_download_files_nonce_action', 'iceberg_download_files_nonce');

    $files = get_post_meta($post->ID, '_iceberg_download_files', true);
    if (!is_array($files) || empty($files)) {
        $files = array(
            array(
                'type' => 'self_host',
                'title' => '',
                'url' => '',
                'file_size' => '',
                'format' => '',
                'attach_id' => '',
            ),
        );
    }
    ?>
    <style>
        /* ── Download meta box styles ── */
        .iceberg-download-files {
            margin: 0;
        }

        .iceberg-file-row {
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 16px 20px;
            margin-bottom: 14px;
            position: relative;
            transition: box-shadow .2s;
        }

        .iceberg-file-row:hover {
            box-shadow: 0 2px 8px rgba(0, 0, 0, .08);
        }

        .iceberg-file-row .row-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
            cursor: move;
        }

        .iceberg-file-row .row-header .row-number {
            font-weight: 600;
            font-size: 13px;
            color: #23282d;
            background: #e2e4e7;
            padding: 2px 10px;
            border-radius: 3px;
        }

        .iceberg-file-row .remove-file {
            color: #a00;
            cursor: pointer;
            font-size: 12px;
            text-decoration: none;
            padding: 4px 8px;
            border: 1px solid transparent;
            border-radius: 3px;
            transition: all .15s;
        }

        .iceberg-file-row .remove-file:hover {
            background: #fbeaea;
            border-color: #dba4a4;
        }

        .iceberg-field-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px 18px;
            margin-bottom: 10px;
        }

        .iceberg-field-group.full-width {
            grid-template-columns: 1fr;
        }

        .iceberg-field-group label {
            display: block;
            font-weight: 600;
            font-size: 12px;
            color: #555;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: .3px;
        }

        .iceberg-field-group input[type="text"],
        .iceberg-field-group input[type="url"],
        .iceberg-field-group select {
            width: 100%;
            padding: 6px 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 13px;
        }

        .iceberg-field-group input[type="text"]:focus,
        .iceberg-field-group input[type="url"]:focus,
        .iceberg-field-group select:focus {
            border-color: #007cba;
            box-shadow: 0 0 0 1px #007cba;
            outline: none;
        }

        /* Type switcher radio buttons */
        .iceberg-type-switcher {
            display: flex;
            gap: 0;
            margin-bottom: 14px;
            border: 1px solid #ccc;
            border-radius: 5px;
            overflow: hidden;
            width: fit-content;
        }

        .iceberg-type-switcher label {
            padding: 7px 18px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            background: #fff;
            color: #555;
            border-right: 1px solid #ccc;
            transition: all .15s;
            text-transform: uppercase;
            letter-spacing: .3px;
            margin: 0;
            user-select: none;
        }

        .iceberg-type-switcher label:last-child {
            border-right: none;
        }

        .iceberg-type-switcher input[type="radio"] {
            display: none;
        }

        .iceberg-type-switcher input[type="radio"]:checked+label {
            background: #007cba;
            color: #fff;
        }

        /* Upload field wrapper */
        .iceberg-upload-field {
            display: flex;
            gap: 8px;
            align-items: flex-start;
        }

        .iceberg-upload-field input[type="url"],
        .iceberg-upload-field input[type="text"] {
            flex: 1;
        }

        .iceberg-upload-field .button {
            flex-shrink: 0;
            margin-top: 0;
        }

        .iceberg-upload-preview {
            margin-top: 6px;
            font-size: 12px;
            color: #666;
            word-break: break-all;
        }

        .iceberg-upload-preview .filename {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: #eef7ee;
            padding: 3px 10px;
            border-radius: 3px;
            color: #2e7d32;
            font-weight: 500;
        }

        /* Footer */
        .iceberg-add-file-wrap {
            margin-top: 4px;
        }

        .iceberg-add-file-wrap .button {
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        /* Hide sections based on type */
        .iceberg-file-row[data-type="online"] .self-host-section {
            display: none;
        }

        .iceberg-file-row[data-type="self_host"] .online-section {
            display: none;
        }
    </style>

    <div class="iceberg-download-files" id="iceberg-download-files">
        <?php foreach ($files as $i => $file):
            $type = isset($file['type']) ? $file['type'] : 'self_host';
            $title = isset($file['title']) ? $file['title'] : '';
            $url = isset($file['url']) ? $file['url'] : '';
            $file_size = isset($file['file_size']) ? $file['file_size'] : '';
            $format = isset($file['format']) ? $file['format'] : '';
            $attach_id = isset($file['attach_id']) ? $file['attach_id'] : '';
            $filename = '';
            if ($attach_id) {
                $filename = basename(get_attached_file($attach_id));
            } elseif ($url && $type === 'self_host') {
                $filename = basename(parse_url($url, PHP_URL_PATH));
            }
            ?>
            <div class="iceberg-file-row" data-type="<?php echo esc_attr($type); ?>">
                <div class="row-header">
                    <span class="row-number">
                        <?php printf(__('File #%d', 'iceberg'), $i + 1); ?>
                    </span>
                    <a href="#" class="remove-file" title="<?php esc_attr_e('Remove', 'iceberg'); ?>">✕
                        <?php esc_html_e('Remove', 'iceberg'); ?>
                    </a>
                </div>

                <!-- Type switcher -->
                <div class="iceberg-type-switcher">
                    <input type="radio" name="iceberg_files[<?php echo $i; ?>][type]" id="type_self_host_<?php echo $i; ?>"
                        value="self_host" <?php checked($type, 'self_host'); ?>
                    class="iceberg-type-radio">
                    <label for="type_self_host_<?php echo $i; ?>">📁
                        <?php esc_html_e('Self-hosted Upload', 'iceberg'); ?>
                    </label>

                    <input type="radio" name="iceberg_files[<?php echo $i; ?>][type]" id="type_online_<?php echo $i; ?>"
                        value="online" <?php checked($type, 'online'); ?>
                    class="iceberg-type-radio">
                    <label for="type_online_<?php echo $i; ?>">🔗
                        <?php esc_html_e('Online Drive Link', 'iceberg'); ?>
                    </label>
                </div>

                <!-- Self-host upload section -->
                <div class="iceberg-field-group full-width self-host-section">
                    <div>
                        <label>
                            <?php esc_html_e('Upload File', 'iceberg'); ?>
                        </label>
                        <div class="iceberg-upload-field">
                            <input type="text" name="iceberg_files[<?php echo $i; ?>][url]"
                                value="<?php echo esc_url($type === 'self_host' ? $url : ''); ?>" class="iceberg-file-url"
                                placeholder="<?php esc_attr_e('File URL (auto-filled on upload)', 'iceberg'); ?>" readonly>
                            <input type="hidden" name="iceberg_files[<?php echo $i; ?>][attach_id]"
                                value="<?php echo esc_attr($attach_id); ?>" class="iceberg-attach-id">
                            <button type="button" class="button iceberg-upload-btn">
                                <?php esc_html_e('Upload', 'iceberg'); ?>
                            </button>
                            <button type="button" class="button iceberg-remove-upload-btn"
                                style="color:#a00;<?php echo empty($url) || $type !== 'self_host' ? 'display:none;' : ''; ?>">
                                <?php esc_html_e('Clear', 'iceberg'); ?>
                            </button>
                        </div>
                        <?php if ($filename && $type === 'self_host'): ?>
                            <div class="iceberg-upload-preview">
                                <span class="filename">📄
                                    <?php echo esc_html($filename); ?>
                                </span>
                            </div>
                        <?php else: ?>
                            <div class="iceberg-upload-preview"></div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Online link section -->
                <div class="iceberg-field-group full-width online-section">
                    <div>
                        <label>
                            <?php esc_html_e('Online Drive URL', 'iceberg'); ?>
                        </label>
                        <input type="url" name="iceberg_files[<?php echo $i; ?>][online_url]"
                            value="<?php echo esc_url($type === 'online' ? $url : ''); ?>" class="iceberg-online-url"
                            placeholder="<?php esc_attr_e('https://drive.google.com/file/d/…', 'iceberg'); ?>">
                    </div>
                </div>

                <!-- Common metadata -->
                <div class="iceberg-field-group">
                    <div>
                        <label>
                            <?php esc_html_e('File Title', 'iceberg'); ?>
                        </label>
                        <input type="text" name="iceberg_files[<?php echo $i; ?>][title]"
                            value="<?php echo esc_attr($title); ?>"
                            placeholder="<?php esc_attr_e('e.g. User Manual v2.1', 'iceberg'); ?>">
                    </div>
                    <div>
                        <label>
                            <?php esc_html_e('File Format', 'iceberg'); ?>
                        </label>
                        <input type="text" name="iceberg_files[<?php echo $i; ?>][format]"
                            value="<?php echo esc_attr($format); ?>"
                            placeholder="<?php esc_attr_e('e.g. PDF, ZIP, DOCX', 'iceberg'); ?>">
                    </div>
                </div>
                <div class="iceberg-field-group">
                    <div>
                        <label>
                            <?php esc_html_e('File Size', 'iceberg'); ?>
                        </label>
                        <input type="text" name="iceberg_files[<?php echo $i; ?>][file_size]"
                            value="<?php echo esc_attr($file_size); ?>"
                            placeholder="<?php esc_attr_e('e.g. 4.5 MB', 'iceberg'); ?>">
                    </div>
                    <div></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="iceberg-add-file-wrap">
        <button type="button" class="button button-primary" id="iceberg-add-file">
            <span class="dashicons dashicons-plus-alt2" style="margin-top:3px;"></span>
            <?php esc_html_e('Add File', 'iceberg'); ?>
        </button>
    </div>

    <script>
        (function ($) {
            /* ── Helpers ── */
            function reindex() {
                $('#iceberg-download-files .iceberg-file-row').each(function (idx) {
                    var $row = $(this);
                    $row.find('.row-number').text('<?php echo esc_js(__('File #', 'iceberg')); ?>' + (idx + 1));
                    // Re-index all input names & radio IDs
                    $row.find('input, select').each(function () {
                        var name = $(this).attr('name');
                        if (name) $(this).attr('name', name.replace(/iceberg_files\[\d+\]/, 'iceberg_files[' + idx + ']'));
                        var id = $(this).attr('id');
                        if (id) $(this).attr('id', id.replace(/_\d+$/, '_' + idx));
                    });
                    $row.find('label[for]').each(function () {
                        var f = $(this).attr('for');
                        if (f) $(this).attr('for', f.replace(/_\d+$/, '_' + idx));
                    });
                });
            }

            function applyTypeToggle($row) {
                $row.find('.iceberg-type-radio').off('change.iceberg').on('change.iceberg', function () {
                    $row.attr('data-type', $(this).val());
                });
            }

            function applyUpload($row) {
                $row.find('.iceberg-upload-btn').off('click.iceberg').on('click.iceberg', function (e) {
                    e.preventDefault();
                    var $btn = $(this);
                    var $wrap = $btn.closest('.iceberg-upload-field');
                    var $url = $wrap.find('.iceberg-file-url');
                    var $aid = $wrap.find('.iceberg-attach-id');
                    var $prev = $wrap.siblings('.iceberg-upload-preview');
                    var $clear = $wrap.find('.iceberg-remove-upload-btn');
                    var $sizeInput = $row.find('input[name$="[file_size]"]');
                    var $formatInput = $row.find('input[name$="[format]"]');
                    var $titleInput = $row.find('input[name$="[title]"]');

                    var frame = wp.media({
                        title: '<?php echo esc_js(__('Select or Upload File', 'iceberg')); ?>',
                        button: { text: '<?php echo esc_js(__('Use this file', 'iceberg')); ?>' },
                        multiple: false
                    });

                    frame.on('select', function () {
                        var attachment = frame.state().get('selection').first().toJSON();
                        $url.val(attachment.url);
                        $aid.val(attachment.id);
                        var fname = attachment.filename || attachment.url.split('/').pop();
                        $prev.html('<span class="filename">📄 ' + fname + '</span>');
                        $clear.show();

                        // Auto-fill metadata if empty
                        if (!$titleInput.val()) {
                            // Use title without extension
                            var nameNoExt = fname.replace(/\.[^.]+$/, '');
                            $titleInput.val(nameNoExt);
                        }
                        if (!$formatInput.val()) {
                            var ext = fname.split('.').pop().toUpperCase();
                            $formatInput.val(ext);
                        }
                        if (!$sizeInput.val() && attachment.filesizeHumanReadable) {
                            $sizeInput.val(attachment.filesizeHumanReadable);
                        }
                    });

                    frame.open();
                });

                $row.find('.iceberg-remove-upload-btn').off('click.iceberg').on('click.iceberg', function (e) {
                    e.preventDefault();
                    var $wrap = $(this).closest('.iceberg-upload-field');
                    $wrap.find('.iceberg-file-url').val('');
                    $wrap.find('.iceberg-attach-id').val('');
                    $wrap.siblings('.iceberg-upload-preview').html('');
                    $(this).hide();
                });
            }

            /* ── Init existing rows ── */
            $('#iceberg-download-files .iceberg-file-row').each(function () {
                applyTypeToggle($(this));
                applyUpload($(this));
            });

            /* ── Remove row ── */
            $(document).on('click', '.iceberg-file-row .remove-file', function (e) {
                e.preventDefault();
                var $container = $('#iceberg-download-files');
                if ($container.find('.iceberg-file-row').length <= 1) {
                    alert('<?php echo esc_js(__('You must keep at least one file row.', 'iceberg')); ?>');
                    return;
                }
                $(this).closest('.iceberg-file-row').slideUp(200, function () {
                    $(this).remove();
                    reindex();
                });
            });

            /* ── Add row ── */
            $('#iceberg-add-file').on('click', function () {
                var idx = $('#iceberg-download-files .iceberg-file-row').length;
                var tpl = '\
                <div class="iceberg-file-row" data-type="self_host" style="display:none">\
                    <div class="row-header">\
                        <span class="row-number"><?php echo esc_js(__('File #', 'iceberg')); ?>' + (idx + 1) + '</span>\
                        <a href="#" class="remove-file" title="<?php echo esc_js(__('Remove', 'iceberg')); ?>">✕ <?php echo esc_js(__('Remove', 'iceberg')); ?></a>\
                    </div>\
                    <div class="iceberg-type-switcher">\
                        <input type="radio" name="iceberg_files[' + idx + '][type]" id="type_self_host_' + idx + '" value="self_host" checked class="iceberg-type-radio">\
                        <label for="type_self_host_' + idx + '">📁 <?php echo esc_js(__('Self-hosted Upload', 'iceberg')); ?></label>\
                        <input type="radio" name="iceberg_files[' + idx + '][type]" id="type_online_' + idx + '" value="online" class="iceberg-type-radio">\
                        <label for="type_online_' + idx + '">🔗 <?php echo esc_js(__('Online Drive Link', 'iceberg')); ?></label>\
                    </div>\
                    <div class="iceberg-field-group full-width self-host-section">\
                        <div>\
                            <label><?php echo esc_js(__('Upload File', 'iceberg')); ?></label>\
                            <div class="iceberg-upload-field">\
                                <input type="text" name="iceberg_files[' + idx + '][url]" value="" class="iceberg-file-url" placeholder="<?php echo esc_js(__('File URL (auto-filled on upload)', 'iceberg')); ?>" readonly>\
                                <input type="hidden" name="iceberg_files[' + idx + '][attach_id]" value="" class="iceberg-attach-id">\
                                <button type="button" class="button iceberg-upload-btn"><?php echo esc_js(__('Upload', 'iceberg')); ?></button>\
                                <button type="button" class="button iceberg-remove-upload-btn" style="color:#a00;display:none;"><?php echo esc_js(__('Clear', 'iceberg')); ?></button>\
                            </div>\
                            <div class="iceberg-upload-preview"></div>\
                        </div>\
                    </div>\
                    <div class="iceberg-field-group full-width online-section">\
                        <div>\
                            <label><?php echo esc_js(__('Online Drive URL', 'iceberg')); ?></label>\
                            <input type="url" name="iceberg_files[' + idx + '][online_url]" value="" class="iceberg-online-url" placeholder="<?php echo esc_js(__('https://drive.google.com/file/d/…', 'iceberg')); ?>">\
                        </div >\
                    </div >\
                <div class="iceberg-field-group">\
                    <div>\
                        <label><?php echo esc_js(__('File Title', 'iceberg')); ?></label>\
                        <input type="text" name="iceberg_files[' + idx + '][title]" value="" placeholder="<?php echo esc_js(__('e.g. User Manual v2.1', 'iceberg')); ?>">\
                    </div>\
                    <div>\
                        <label><?php echo esc_js(__('File Format', 'iceberg')); ?></label>\
                        <input type="text" name="iceberg_files[' + idx + '][format]" value="" placeholder="<?php echo esc_js(__('e.g. PDF, ZIP, DOCX', 'iceberg')); ?>">\
                    </div>\
                </div>\
                <div class="iceberg-field-group">\
                    <div>\
                        <label><?php echo esc_js(__('File Size', 'iceberg')); ?></label>\
                        <input type="text" name="iceberg_files[' + idx + '][file_size]" value="" placeholder="<?php echo esc_js(__('e.g. 4.5 MB', 'iceberg')); ?>">\
                    </div>\
                    <div></div>\
                </div>\
                </div > ';

                var $newRow = $(tpl);
                $('#iceberg-download-files').append($newRow);
                $newRow.slideDown(200);
                applyTypeToggle($newRow);
                applyUpload($newRow);
            });

        })(jQuery);
    </script>
    <?php
}

/**
 * Save the download files meta.
 */
function iceberg_save_download_files($post_id)
{
    // Verify nonce
    if (
        !isset($_POST['iceberg_download_files_nonce']) ||
        !wp_verify_nonce($_POST['iceberg_download_files_nonce'], 'iceberg_download_files_nonce_action')
    ) {
        return;
    }
    // Check autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    // Check permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if (!isset($_POST['iceberg_files']) || !is_array($_POST['iceberg_files'])) {
        delete_post_meta($post_id, '_iceberg_download_files');
        return;
    }

    $clean = array();

    foreach ($_POST['iceberg_files'] as $file) {
        $type = isset($file['type']) && in_array($file['type'], array('self_host', 'online'), true)
            ? $file['type']
            : 'self_host';

        // Determine URL based on type
        if ($type === 'online') {
            $url = isset($file['online_url']) ? esc_url_raw($file['online_url']) : '';
        } else {
            $url = isset($file['url']) ? esc_url_raw($file['url']) : '';
        }

        $clean[] = array(
            'type' => $type,
            'title' => isset($file['title']) ? sanitize_text_field($file['title']) : '',
            'url' => $url,
            'file_size' => isset($file['file_size']) ? sanitize_text_field($file['file_size']) : '',
            'format' => isset($file['format']) ? sanitize_text_field($file['format']) : '',
            'attach_id' => ($type === 'self_host' && isset($file['attach_id'])) ? absint($file['attach_id']) : '',
        );
    }

    update_post_meta($post_id, '_iceberg_download_files', $clean);
}
add_action('save_post_download', 'iceberg_save_download_files');

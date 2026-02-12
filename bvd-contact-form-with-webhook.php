<?php
/**
 * BVD Contact Form - Complete WordPress Form Solution
 * Paste this entire code into your theme's functions.php or a custom plugin file
 */

// Prevent direct access
if (!defined('ABSPATH'))
    exit;

// Database table creation on activation
function bvd_contact_form_create_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'bvd_contact_submissions';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        company varchar(255) DEFAULT NULL,
        email varchar(255) NOT NULL,
        country varchar(255) NOT NULL,
        phone varchar(100) DEFAULT NULL,
        whatsapp varchar(100) NOT NULL,
        message text DEFAULT NULL,
        ip_address varchar(100) DEFAULT NULL,
        user_agent text DEFAULT NULL,
        submitted_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
add_action('after_setup_theme', 'bvd_contact_form_create_table');

// Settings Page
function bvd_contact_form_settings_init()
{
    register_setting('bvd_contact_form_settings', 'bvd_webhook_url');
    register_setting('bvd_contact_form_settings', 'bvd_webhook_enabled');
    register_setting('bvd_contact_form_settings', 'bvd_webhook_token');

    add_settings_section(
        'bvd_contact_form_webhook_section',
        __('Webhook Integration Settings', 'taiyuetu'),
        'bvd_contact_form_webhook_section_cb',
        'bvd-contact-form-settings'
    );

    add_settings_field(
        'bvd_webhook_enabled',
        __('Enable Webhook', 'taiyuetu'),
        'bvd_webhook_enabled_cb',
        'bvd-contact-form-settings',
        'bvd_contact_form_webhook_section'
    );

    add_settings_field(
        'bvd_webhook_url',
        __('Webhook URL', 'taiyuetu'),
        'bvd_webhook_url_cb',
        'bvd-contact-form-settings',
        'bvd_contact_form_webhook_section'
    );

    add_settings_field(
        'bvd_webhook_token',
        __('Authorization Token', 'taiyuetu'),
        'bvd_webhook_token_cb',
        'bvd-contact-form-settings',
        'bvd_contact_form_webhook_section'
    );
}
add_action('admin_init', 'bvd_contact_form_settings_init');

function bvd_contact_form_webhook_section_cb()
{
    echo '<p>' . esc_html__('Configure webhook integration to send form submissions to external services like Baserow, Zapier, Make.com, etc.', 'taiyuetu') . '</p>';
    echo '<p class="description" style="color: #666; font-style: italic;">' . esc_html__('Important for Baserow: Your table column names must match exactly: name, company, email, country, phone, whatsapp, message, ip_address, submitted_at.', 'taiyuetu') . '</p>';
}

function bvd_webhook_enabled_cb()
{
    $enabled = get_option('bvd_webhook_enabled', '0');
    echo '<input type="checkbox" name="bvd_webhook_enabled" value="1" ' . checked(1, $enabled, false) . ' />';
    echo '<label> ' . esc_html__('Enable webhook integration', 'taiyuetu') . '</label>';
}

function bvd_webhook_url_cb()
{
    $url = get_option('bvd_webhook_url', '');
    echo '<input type="url" name="bvd_webhook_url" value="' . esc_attr($url) . '" class="regular-text" placeholder="' . esc_attr__('https://api.baserow.io/api/...', 'taiyuetu') . '" />';
    echo '<p class="description">' . esc_html__('Enter your webhook URL (Baserow, Zapier, Make.com, etc.)', 'taiyuetu') . '</p>';
}

function bvd_webhook_token_cb()
{
    $token = get_option('bvd_webhook_token', '');
    echo '<input type="text" name="bvd_webhook_token" value="' . esc_attr($token) . '" class="regular-text" />';
    echo '<p class="description">' . esc_html__('Enter your API Authorization token (e.g., Token your_token_here)', 'taiyuetu') . '</p>';
}

// Admin Menu
function bvd_contact_form_admin_menu()
{
    add_menu_page(
        __('BVD Contact Submissions', 'taiyuetu'),
        __('Contact Forms', 'taiyuetu'),
        'manage_options',
        'bvd-contact-submissions',
        'bvd_contact_submissions_page',
        'dashicons-email',
        30
    );

    add_submenu_page(
        'bvd-contact-submissions',
        __('Settings', 'taiyuetu'),
        __('Settings', 'taiyuetu'),
        'manage_options',
        'bvd-contact-form-settings',
        'bvd_contact_form_settings_page'
    );
}
add_action('admin_menu', 'bvd_contact_form_admin_menu');

// Settings Page Display
function bvd_contact_form_settings_page()
{
    if (!current_user_can('manage_options'))
        return;

    if (isset($_GET['settings-updated'])) {
        add_settings_error('bvd_contact_form_messages', 'bvd_message', __('Settings Saved', 'taiyuetu'), 'updated');
    }
    settings_errors('bvd_contact_form_messages');
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('bvd_contact_form_settings');
            do_settings_sections('bvd-contact-form-settings');
            submit_button(__('Save Settings', 'taiyuetu'));
            ?>
        </form>
    </div>
    <?php
}

// Submissions Page
function bvd_contact_submissions_page()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'bvd_contact_submissions';

    // Handle delete action
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id']) && current_user_can('manage_options')) {
        $id = intval($_GET['id']);
        check_admin_referer('delete_submission_' . $id);
        $wpdb->delete($table_name, array('id' => $id), array('%d'));
        echo '<div class="notice notice-success"><p>' . esc_html__('Submission deleted successfully.', 'taiyuetu') . '</p></div>';
    }

    // Handle bulk delete
    if (isset($_POST['action']) && $_POST['action'] === 'bulk_delete' && isset($_POST['submissions']) && current_user_can('manage_options')) {
        check_admin_referer('bulk_delete_submissions');
        $ids = array_map('intval', $_POST['submissions']);
        foreach ($ids as $id) {
            $wpdb->delete($table_name, array('id' => $id), array('%d'));
        }
        echo '<div class="notice notice-success"><p>' . esc_html__('Selected submissions deleted successfully.', 'taiyuetu') . '</p></div>';
    }

    // Pagination
    $per_page = 20;
    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $offset = ($current_page - 1) * $per_page;

    $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    $total_pages = ceil($total_items / $per_page);

    $submissions = $wpdb->get_results(
        $wpdb->prepare("SELECT * FROM $table_name ORDER BY submitted_at DESC LIMIT %d OFFSET %d", $per_page, $offset)
    );

    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">
            <?php esc_html_e('Contact Form Submissions', 'taiyuetu'); ?>
        </h1>
        <a href="<?php echo admin_url('admin.php?page=bvd-contact-form-settings'); ?>" class="page-title-action">
            <?php esc_html_e('Settings', 'taiyuetu'); ?>
        </a>
        <hr class="wp-header-end">

        <?php if ($submissions): ?>
            <form method="post">
                <?php wp_nonce_field('bulk_delete_submissions'); ?>
                <input type="hidden" name="action" value="bulk_delete">
                <div class="tablenav top">
                    <div class="alignleft actions bulkactions">
                        <button type="submit" class="button action"
                            onclick="return confirm('<?php echo esc_js(__('Are you sure you want to delete selected submissions?', 'taiyuetu')); ?>');">
                            <?php esc_html_e('Delete Selected', 'taiyuetu'); ?>
                        </button>
                    </div>
                </div>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <td class="manage-column column-cb check-column"><input type="checkbox" id="select-all"></td>
                            <th>
                                <?php esc_html_e('ID', 'taiyuetu'); ?>
                            </th>
                            <th>
                                <?php esc_html_e('Name', 'taiyuetu'); ?>
                            </th>
                            <th>
                                <?php esc_html_e('Email', 'taiyuetu'); ?>
                            </th>
                            <th>
                                <?php esc_html_e('Company', 'taiyuetu'); ?>
                            </th>
                            <th>
                                <?php esc_html_e('Country', 'taiyuetu'); ?>
                            </th>
                            <th>
                                <?php esc_html_e('Phone', 'taiyuetu'); ?>
                            </th>
                            <th>
                                <?php esc_html_e('WhatsApp', 'taiyuetu'); ?>
                            </th>
                            <th>
                                <?php esc_html_e('Message', 'taiyuetu'); ?>
                            </th>
                            <th>
                                <?php esc_html_e('Submitted', 'taiyuetu'); ?>
                            </th>
                            <th>
                                <?php esc_html_e('Actions', 'taiyuetu'); ?>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($submissions as $submission): ?>
                            <tr>
                                <th scope="row" class="check-column">
                                    <input type="checkbox" name="submissions[]" value="<?php echo esc_attr($submission->id); ?>">
                                </th>
                                <td><?php echo esc_html($submission->id); ?></td>
                                <td><strong><?php echo esc_html($submission->name); ?></strong></td>
                                <td><?php echo esc_html($submission->email); ?></td>
                                <td><?php echo esc_html($submission->company); ?></td>
                                <td><?php echo esc_html($submission->country); ?></td>
                                <td><?php echo esc_html($submission->phone); ?></td>
                                <td><?php echo esc_html($submission->whatsapp); ?></td>
                                <td><?php echo esc_html(wp_trim_words($submission->message, 10)); ?></td>
                                <td><?php echo esc_html($submission->submitted_at); ?></td>
                                <td>
                                    <a href="?page=bvd-contact-submissions&action=view&id=<?php echo esc_attr($submission->id); ?>"
                                        class="button button-small">
                                        <?php esc_html_e('View', 'taiyuetu'); ?>
                                    </a>
                                    <a href="?page=bvd-contact-submissions&action=delete&id=<?php echo esc_attr($submission->id); ?>&_wpnonce=<?php echo wp_create_nonce('delete_submission_' . $submission->id); ?>"
                                        class="button button-small"
                                        onclick="return confirm('<?php echo esc_js(__('Are you sure?', 'taiyuetu')); ?>');">
                                        <?php esc_html_e('Delete', 'taiyuetu'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </form>

            <?php if ($total_pages > 1): ?>
                <div class="tablenav bottom">
                    <div class="tablenav-pages">
                        <?php
                        echo paginate_links(array(
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'prev_text' => '&laquo;',
                            'next_text' => '&raquo;',
                            'total' => $total_pages,
                            'current' => $current_page
                        ));
                        ?>
                    </div>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <p>
                <?php esc_html_e('No submissions yet.', 'taiyuetu'); ?>
            </p>
        <?php endif; ?>

        <?php
        // View individual submission
        if (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $submission = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));
            if ($submission):
                ?>
                <div class="bvd-submission-detail"
                    style="margin-top: 30px; background: #fff; padding: 20px; border: 1px solid #ccc;">
                    <h2>
                        <?php printf(esc_html__('Submission Details #%s', 'taiyuetu'), esc_html($submission->id)); ?>
                    </h2>
                    <table class="form-table">
                        <tr>
                            <th><?php esc_html_e('Name:', 'taiyuetu'); ?>
                            </th>
                            <td><?php echo esc_html($submission->name); ?></td>
                        </tr>
                        <tr>
                            <th>
                                <?php esc_html_e('Company:', 'taiyuetu'); ?>
                            </th>
                            <td><?php echo esc_html($submission->company); ?></td>
                        </tr>
                        <tr>
                            <th>
                                <?php esc_html_e('Email:', 'taiyuetu'); ?>
                            </th>
                            <td><a
                                    href="mailto:<?php echo esc_attr($submission->email); ?>"><?php echo esc_html($submission->email); ?></a>
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <?php esc_html_e('Country:', 'taiyuetu'); ?>
                            </th>
                            <td><?php echo esc_html($submission->country); ?></td>
                        </tr>
                        <tr>
                            <th>
                                <?php esc_html_e('Phone:', 'taiyuetu'); ?>
                            </th>
                            <td><?php echo esc_html($submission->phone); ?></td>
                        </tr>
                        <tr>
                            <th>
                                <?php esc_html_e('WhatsApp:', 'taiyuetu'); ?>
                            </th>
                            <td><?php echo esc_html($submission->whatsapp); ?></td>
                        </tr>
                        <tr>
                            <th>
                                <?php esc_html_e('Message:', 'taiyuetu'); ?>
                            </th>
                            <td><?php echo nl2br(esc_html($submission->message)); ?></td>
                        </tr>
                        <tr>
                            <th>
                                <?php esc_html_e('IP Address:', 'taiyuetu'); ?>
                            </th>
                            <td><?php echo esc_html($submission->ip_address); ?></td>
                        </tr>
                        <tr>
                            <th>
                                <?php esc_html_e('User Agent:', 'taiyuetu'); ?>
                            </th>
                            <td><?php echo esc_html($submission->user_agent); ?></td>
                        </tr>
                        <tr>
                            <th>
                                <?php esc_html_e('Submitted:', 'taiyuetu'); ?>
                            </th>
                            <td><?php echo esc_html($submission->submitted_at); ?></td>
                        </tr>
                    </table>
                    <a href="?page=bvd-contact-submissions" class="button">←
                        <?php esc_html_e('Back to List', 'taiyuetu'); ?>
                    </a>
                </div>
                <?php
            endif;
        }
        ?>
    </div>
    <script>
        document.getElementById('select-all').addEventListener('change', function () {
            var checkboxes = document.querySelectorAll('input[name="submissions[]"]');
            checkboxes.forEach(function (checkbox) {
                checkbox.checked = this.checked;
            }, this);
        });
    </script>
    <?php
}

// Shortcode
function bvd_contact_form_shortcode($atts)
{
    $atts = shortcode_atts(array(
        'class' => 'cta__form',
        'id' => 'contact-form',
    ), $atts);

    // Generate math challenge
    $n1 = rand(1, 9);
    $n2 = rand(1, 9);
    $sum = $n1 + $n2;

    ob_start();
    ?>
    <div id="bvd-form-messages-<?php echo esc_attr($atts['id']); ?>" class="bvd-form-messages"></div>
    <form class="<?php echo esc_attr($atts['class']); ?>" id="<?php echo esc_attr($atts['id']); ?>" data-bvd-form>
        <?php wp_nonce_field('bvd_contact_form_submit', 'bvd_nonce'); ?>

        <!-- Honeypot Field -->
        <div style="display:none; visibility:hidden; position:absolute; left:-9999px;">
            <label>Don't fill this out: <input type="text" name="bvd_website_hp" value="" tabindex="-1"
                    autocomplete="off"></label>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="bvd-name" class="form-label">
                    <?php esc_html_e('Full Name', 'taiyuetu'); ?> <span style="color:red;">*</span>
                </label>
                <input type="text" id="bvd-name" name="name" class="form-input" required>
            </div>
            <div class="form-group">
                <label for="bvd-company" class="form-label">
                    <?php esc_html_e('Company', 'taiyuetu'); ?>
                </label>
                <input type="text" id="bvd-company" name="company" class="form-input">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="bvd-email" class="form-label">
                    <?php esc_html_e('Business Email', 'taiyuetu'); ?> <span style="color:red;">*</span>
                </label>
                <input type="email" id="bvd-email" name="email" class="form-input" required>
            </div>
            <div class="form-group">
                <label for="bvd-country" class="form-label">
                    <?php esc_html_e('Country', 'taiyuetu'); ?> <span style="color:red;">*</span>
                </label>
                <input type="text" id="bvd-country" name="country" class="form-input" required>
            </div>
            <div class="form-group">
                <label for="bvd-phone" class="form-label">
                    <?php esc_html_e('Phone', 'taiyuetu'); ?>
                </label>
                <input type="tel" id="bvd-phone" name="phone" class="form-input">
            </div>
            <div class="form-group">
                <label for="bvd-whatsapp" class="form-label">
                    <?php esc_html_e('WhatsApp', 'taiyuetu'); ?> <span style="color:red;">*</span>
                </label>
                <input type="tel" id="bvd-whatsapp" name="whatsapp" class="form-input" required>
            </div>
        </div>
        <div class="form-group">
            <label for="bvd-message" class="form-label">
                <?php esc_html_e('Project Details', 'taiyuetu'); ?>
            </label>
            <textarea id="bvd-message" name="message" class="form-input form-textarea" rows="4"></textarea>
        </div>
        <div class="form-group">
            <label for="bvd-math" class="form-label">
                <?php printf(esc_html__('Security Question: %d + %d = ?', 'taiyuetu'), $n1, $n2); ?> <span
                    style="color:red;">*</span>
            </label>
            <input type="number" id="bvd-math" name="bvd_math_answer" class="form-input" required
                data-n1="<?php echo intval($n1); ?>" data-n2="<?php echo intval($n2); ?>">
            <input type="hidden" name="bvd_math_check" value="<?php echo esc_attr(wp_hash($sum)); ?>">
        </div>

        <button type="submit" class="btn btn--primary btn--full">
            <?php esc_html_e('Submit Inquiry', 'taiyuetu'); ?>
        </button>
    </form>
    <style>
        .bvd-form-messages {
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 4px;
            display: none;
        }

        .bvd-form-messages.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            display: block;
        }

        .bvd-form-messages.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            display: block;
        }

        .bvd-form-loading {
            opacity: 0.6;
            pointer-events: none;
        }
    </style>
    <script>
        (function () {
            document.addEventListener('DOMContentLoaded', function () {
                var form = document.querySelector('[data-bvd-form]');
                if (!form) return;
                var formId = form.getAttribute('id');
                var messagesDiv = document.getElementById('bvd-form-messages-' + formId);
                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    // Client-side validation
                    var name = form.querySelector('[name="name"]').value.trim();
                    var email = form.querySelector('[name="email"]').value.trim();
                    var country = form.querySelector('[name="country"]').value.trim();
                    var whatsapp = form.querySelector('[name="whatsapp"]').value.trim();
                    var mathInput = form.querySelector('[name="bvd_math_answer"]');
                    var math = mathInput.value.trim();

                    if (!name || !email || !country || !whatsapp || !math) {
                        showMessage('<?php echo esc_js(__('Please fill in all required fields.', 'taiyuetu')); ?>', 'error');
                        return;
                    }

                    // Client-side Math Verification
                    var n1 = parseInt(mathInput.getAttribute('data-n1'), 10);
                    var n2 = parseInt(mathInput.getAttribute('data-n2'), 10);
                    if (parseInt(math, 10) !== (n1 + n2)) {
                        showMessage('<?php echo esc_js(__('Incorrect security question answer.', 'taiyuetu')); ?>', 'error');
                        return;
                    }

                    if (!isValidEmail(email)) {
                        showMessage('<?php echo esc_js(__('Please enter a valid email address.', 'taiyuetu')); ?>', 'error');
                        return;
                    }
                    // Submit form
                    var formData = new FormData(form);
                    formData.append('action', 'bvd_contact_form_submit');
                    form.classList.add('bvd-form-loading');
                    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                        method: 'POST',
                        body: formData,
                        credentials: 'same-origin'
                    })
                        .then(function (response) { return response.json(); })
                        .then(function (data) {
                            form.classList.remove('bvd-form-loading');
                            if (data.success) {
                                showMessage(data.data.message, 'success');
                                form.reset();
                                setTimeout(function () {
                                    messagesDiv.style.display = 'none';
                                }, 5000);
                            } else {
                                showMessage(data.data.message || '<?php echo esc_js(__('An error occurred. Please try again.', 'taiyuetu')); ?>', 'error');
                            }
                        })
                        .catch(function (error) {
                            form.classList.remove('bvd-form-loading');
                            showMessage('<?php echo esc_js(__('An error occurred. Please try again.', 'taiyuetu')); ?>', 'error');
                        });
                });
                function showMessage(message, type) {
                    messagesDiv.textContent = message;
                    messagesDiv.className = 'bvd-form-messages ' + type;
                    messagesDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
                function isValidEmail(email) {
                    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
                }
            });
        })();
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('bvd_contact_form', 'bvd_contact_form_shortcode');

// AJAX Handler
function bvd_contact_form_submit_handler()
{
    // Verify nonce
    if (!isset($_POST['bvd_nonce']) || !wp_verify_nonce($_POST['bvd_nonce'], 'bvd_contact_form_submit')) {
        wp_send_json_error(array('message' => __('Security verification failed.', 'taiyuetu')));
    }

    // Honeypot check
    if (!empty($_POST['bvd_website_hp'])) {
        wp_send_json_error(array('message' => __('Spam detected.', 'taiyuetu')));
    }

    // Math verification
    $math_answer = isset($_POST['bvd_math_answer']) ? intval($_POST['bvd_math_answer']) : 0;
    $math_check = isset($_POST['bvd_math_check']) ? sanitize_text_field($_POST['bvd_math_check']) : '';

    if (wp_hash($math_answer) !== $math_check) {
        wp_send_json_error(array('message' => __('Incorrect security question answer. Please try again.', 'taiyuetu')));
    }

    // Server-side validation
    $name = isset($_POST['name']) ? sanitize_text_field(trim($_POST['name'])) : '';
    $company = isset($_POST['company']) ? sanitize_text_field(trim($_POST['company'])) : '';
    $email = isset($_POST['email']) ? sanitize_email(trim($_POST['email'])) : '';
    $country = isset($_POST['country']) ? sanitize_text_field(trim($_POST['country'])) : '';
    $phone = isset($_POST['phone']) ? sanitize_text_field(trim($_POST['phone'])) : '';
    $whatsapp = isset($_POST['whatsapp']) ? sanitize_text_field(trim($_POST['whatsapp'])) : '';
    $message = isset($_POST['message']) ? sanitize_textarea_field(trim($_POST['message'])) : '';

    // Validate required fields
    if (empty($name) || empty($email) || empty($country) || empty($whatsapp)) {
        wp_send_json_error(array('message' => __('Please fill in all required fields.', 'taiyuetu')));
    }

    if (!is_email($email)) {
        wp_send_json_error(array('message' => __('Please enter a valid email address.', 'taiyuetu')));
    }

    // Get IP and User Agent
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];

    // Save to database
    global $wpdb;
    $table_name = $wpdb->prefix . 'bvd_contact_submissions';

    $inserted = $wpdb->insert(
        $table_name,
        array(
            'name' => $name,
            'company' => $company,
            'email' => $email,
            'country' => $country,
            'phone' => $phone,
            'whatsapp' => $whatsapp,
            'message' => $message,
            'ip_address' => $ip_address,
            'user_agent' => $user_agent,
        ),
        array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
    );

    if (!$inserted) {
        wp_send_json_error(array('message' => __('Failed to save submission. Please try again.', 'taiyuetu')));
    }

    // Send email to admin
    $admin_email = get_option('admin_email');
    $subject = sprintf(__('New Contact Form Submission from %s', 'taiyuetu'), $name);

    $email_message = __("New contact form submission:", "taiyuetu") . "\n\n";
    $email_message .= __("Name:", "taiyuetu") . " $name\n";
    $email_message .= __("Company:", "taiyuetu") . " $company\n";
    $email_message .= __("Email:", "taiyuetu") . " $email\n";
    $email_message .= __("Country:", "taiyuetu") . " $country\n";
    $email_message .= __("Phone:", "taiyuetu") . " $phone\n";
    $email_message .= __("WhatsApp:", "taiyuetu") . " $whatsapp\n";
    $email_message .= __("Message:", "taiyuetu") . "\n$message\n\n";
    $email_message .= __("IP Address:", "taiyuetu") . " $ip_address\n";
    $email_message .= __("Submitted:", "taiyuetu") . " " . current_time('mysql') . "\n";

    $headers = array('Content-Type: text/plain; charset=UTF-8', 'From: ' . get_bloginfo('name') . ' <' . $admin_email . '>');

    wp_mail($admin_email, $subject, $email_message, $headers);

    // Send to webhook if enabled
    if (get_option('bvd_webhook_enabled') == '1') {
        $webhook_url = get_option('bvd_webhook_url');
        if (!empty($webhook_url)) {
            $webhook_data = array(
                'name' => $name,
                'company' => $company,
                'email' => $email,
                'country' => $country,
                'phone' => $phone,
                'whatsapp' => $whatsapp,
                'message' => $message,
                'ip_address' => $ip_address,
                'submitted_at' => current_time('mysql')
            );

            $webhook_token = trim(get_option('bvd_webhook_token'));

            // Auto-convert Baserow UI URL to API URL
            // Pattern: https://baserow.io/database/{db_id}/table/{table_id}/{view_id}
            if (strpos($webhook_url, 'baserow.io/database') !== false && preg_match('/table\/(\d+)/', $webhook_url, $matches)) {
                $table_id = $matches[1];
                $webhook_url = 'https://api.baserow.io/api/database/rows/table/' . $table_id . '/';
            }


            // Clean token if user included "Token " prefix
            if (stripos($webhook_token, 'Token ') === 0) {
                $webhook_token = trim(substr($webhook_token, 6));
            }

            // Ensure Baserow URLs have user_field_names=true parameter
            if (strpos($webhook_url, 'baserow') !== false && strpos($webhook_url, 'user_field_names') === false) {
                $webhook_url = add_query_arg('user_field_names', 'true', $webhook_url);
            }

            $response = wp_remote_post($webhook_url, array(
                'body' => json_encode($webhook_data),
                'headers' => array(
                    'Authorization' => 'Token ' . $webhook_token,
                    'Content-Type' => 'application/json'
                ),
                'timeout' => 45
            ));

            if (is_wp_error($response)) {
                error_log('BVD Contact Form Webhook Error: ' . $response->get_error_message());
            } else {
                $code = wp_remote_retrieve_response_code($response);
                if ($code >= 400) {
                    error_log('BVD Contact Form Webhook Failed. Code: ' . $code . ' Body: ' . wp_remote_retrieve_body($response));
                }
            }
        }
    }

    wp_send_json_success(array('message' => __('Thank you! Your inquiry has been submitted successfully. We will contact you soon.', 'taiyuetu')));
}
add_action('wp_ajax_bvd_contact_form_submit', 'bvd_contact_form_submit_handler');
add_action('wp_ajax_nopriv_bvd_contact_form_submit', 'bvd_contact_form_submit_handler');
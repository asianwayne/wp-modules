<?php
/**
 * Custom SMTP Configuration with Admin Settings Page
 * 
 * This file configures the WordPress email system to use a custom SMTP server.
 * Settings can be configured from the WordPress admin panel.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register SMTP Settings
 */
function ruixing_smtp_register_settings()
{
    // Register settings group
    register_setting('ruixing_smtp_settings', 'ruixing_smtp_options', 'ruixing_smtp_sanitize_options');
}
add_action('admin_init', 'ruixing_smtp_register_settings');

/**
 * Sanitize options before saving
 */
function ruixing_smtp_sanitize_options($input)
{
    $sanitized = array();

    $sanitized['enabled'] = isset($input['enabled']) ? 1 : 0;
    $sanitized['host'] = sanitize_text_field($input['host'] ?? '');
    $sanitized['port'] = absint($input['port'] ?? 587);
    $sanitized['encryption'] = sanitize_text_field($input['encryption'] ?? 'tls');
    $sanitized['auth'] = isset($input['auth']) ? 1 : 0;
    $sanitized['username'] = sanitize_text_field($input['username'] ?? '');

    // Only update password if a new one is provided
    if (!empty($input['password'])) {
        $sanitized['password'] = $input['password']; // Don't sanitize password to preserve special chars
    } else {
        // Keep existing password
        $existing = get_option('ruixing_smtp_options', array());
        $sanitized['password'] = $existing['password'] ?? '';
    }

    $sanitized['from_email'] = sanitize_email($input['from_email'] ?? '');
    $sanitized['from_name'] = sanitize_text_field($input['from_name'] ?? '');

    return $sanitized;
}

/**
 * Get default SMTP options
 */
function ruixing_smtp_get_defaults()
{
    return array(
        'enabled' => 0,
        'host' => '',
        'port' => 587,
        'encryption' => 'tls',
        'auth' => 1,
        'username' => '',
        'password' => '',
        'from_email' => get_option('admin_email'),
        'from_name' => get_bloginfo('name')
    );
}

/**
 * Get SMTP options with defaults
 */
function ruixing_smtp_get_options()
{
    $defaults = ruixing_smtp_get_defaults();
    $options = get_option('ruixing_smtp_options', array());
    return wp_parse_args($options, $defaults);
}

/**
 * Add admin menu page
 */
function ruixing_smtp_admin_menu()
{
    add_options_page(
        __('SMTP Settings', 'ruixing'),
        __('SMTP Settings', 'ruixing'),
        'manage_options',
        'ruixing-smtp-settings',
        'ruixing_smtp_settings_page'
    );
}
add_action('admin_menu', 'ruixing_smtp_admin_menu');

/**
 * Admin settings page HTML
 */
function ruixing_smtp_settings_page()
{
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }

    // Get current options
    $options = ruixing_smtp_get_options();

    // Handle test email
    $test_result = '';
    if (isset($_POST['ruixing_smtp_test']) && check_admin_referer('ruixing_smtp_test_nonce')) {
        $test_email = sanitize_email($_POST['test_email']);
        if (is_email($test_email)) {
            $test_result = ruixing_smtp_send_test_email($test_email);
        } else {
            $test_result = array('success' => false, 'message' => __('Please enter a valid email address.', 'ruixing'));
        }
    }

    // Show settings saved message
    if (isset($_GET['settings-updated'])) {
        add_settings_error('ruixing_smtp_messages', 'ruixing_smtp_message', __('Settings Saved', 'ruixing'), 'updated');
    }

    // Show any messages
    settings_errors('ruixing_smtp_messages');
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

        <div class="ruixing-smtp-container" style="display: flex; gap: 20px; flex-wrap: wrap;">
            <!-- Settings Form -->
            <div class="ruixing-smtp-settings" style="flex: 1; min-width: 400px;">
                <form action="options.php" method="post">
                    <?php settings_fields('ruixing_smtp_settings'); ?>

                    <table class="form-table" role="presentation">
                        <!-- Enable SMTP -->
                        <tr>
                            <th scope="row"><?php _e('Enable SMTP', 'ruixing'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="ruixing_smtp_options[enabled]" value="1" <?php checked($options['enabled'], 1); ?>>
                                    <?php _e('Use custom SMTP server for sending emails', 'ruixing'); ?>
                                </label>
                                <p class="description">
                                    <?php _e('When disabled, WordPress will use the default PHP mail() function.', 'ruixing'); ?>
                                </p>
                            </td>
                        </tr>

                        <!-- SMTP Host -->
                        <tr>
                            <th scope="row">
                                <label for="smtp_host"><?php _e('SMTP Host', 'ruixing'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="smtp_host" name="ruixing_smtp_options[host]"
                                    value="<?php echo esc_attr($options['host']); ?>" class="regular-text"
                                    placeholder="e.g., smtp.gmail.com, mail.yourdomain.com">
                                <p class="description"><?php _e('The SMTP server address.', 'ruixing'); ?></p>
                            </td>
                        </tr>

                        <!-- SMTP Port -->
                        <tr>
                            <th scope="row">
                                <label for="smtp_port"><?php _e('SMTP Port', 'ruixing'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="smtp_port" name="ruixing_smtp_options[port]"
                                    value="<?php echo esc_attr($options['port']); ?>" class="small-text">
                                <p class="description">
                                    <?php _e('Common ports: 25 (no encryption), 465 (SSL), 587 (TLS)', 'ruixing'); ?>
                                </p>
                            </td>
                        </tr>

                        <!-- Encryption -->
                        <tr>
                            <th scope="row">
                                <label for="smtp_encryption"><?php _e('Encryption', 'ruixing'); ?></label>
                            </th>
                            <td>
                                <select id="smtp_encryption" name="ruixing_smtp_options[encryption]">
                                    <option value="" <?php selected($options['encryption'], ''); ?>>
                                        <?php _e('None', 'ruixing'); ?>
                                    </option>
                                    <option value="ssl" <?php selected($options['encryption'], 'ssl'); ?>>SSL</option>
                                    <option value="tls" <?php selected($options['encryption'], 'tls'); ?>>TLS</option>
                                </select>
                                <p class="description">
                                    <?php _e('Recommended: TLS for port 587, SSL for port 465', 'ruixing'); ?>
                                </p>
                            </td>
                        </tr>

                        <!-- Authentication -->
                        <tr>
                            <th scope="row"><?php _e('Authentication', 'ruixing'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="ruixing_smtp_options[auth]" value="1" <?php checked($options['auth'], 1); ?>>
                                    <?php _e('Use SMTP authentication', 'ruixing'); ?>
                                </label>
                                <p class="description"><?php _e('Most SMTP servers require authentication.', 'ruixing'); ?>
                                </p>
                            </td>
                        </tr>

                        <!-- Username -->
                        <tr>
                            <th scope="row">
                                <label for="smtp_username"><?php _e('SMTP Username', 'ruixing'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="smtp_username" name="ruixing_smtp_options[username]"
                                    value="<?php echo esc_attr($options['username']); ?>" class="regular-text"
                                    autocomplete="off">
                                <p class="description">
                                    <?php _e('Usually your email address or account username.', 'ruixing'); ?>
                                </p>
                            </td>
                        </tr>

                        <!-- Password -->
                        <tr>
                            <th scope="row">
                                <label for="smtp_password"><?php _e('SMTP Password', 'ruixing'); ?></label>
                            </th>
                            <td>
                                <input type="password" id="smtp_password" name="ruixing_smtp_options[password]" value=""
                                    class="regular-text" autocomplete="new-password"
                                    placeholder="<?php echo !empty($options['password']) ? '••••••••' : ''; ?>">
                                <p class="description">
                                    <?php
                                    if (!empty($options['password'])) {
                                        _e('Password is saved. Leave blank to keep current password.', 'ruixing');
                                    } else {
                                        _e('Enter your SMTP password or app-specific password.', 'ruixing');
                                    }
                                    ?>
                                </p>
                            </td>
                        </tr>

                        <!-- From Email -->
                        <tr>
                            <th scope="row">
                                <label for="smtp_from_email"><?php _e('From Email', 'ruixing'); ?></label>
                            </th>
                            <td>
                                <input type="email" id="smtp_from_email" name="ruixing_smtp_options[from_email]"
                                    value="<?php echo esc_attr($options['from_email']); ?>" class="regular-text">
                                <p class="description">
                                    <?php _e('The email address that emails will be sent from.', 'ruixing'); ?>
                                </p>
                            </td>
                        </tr>

                        <!-- From Name -->
                        <tr>
                            <th scope="row">
                                <label for="smtp_from_name"><?php _e('From Name', 'ruixing'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="smtp_from_name" name="ruixing_smtp_options[from_name]"
                                    value="<?php echo esc_attr($options['from_name']); ?>" class="regular-text">
                                <p class="description"><?php _e('The name that emails will be sent from.', 'ruixing'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>

                    <?php submit_button(__('Save Settings', 'ruixing')); ?>
                </form>
            </div>

            <!-- Test Email & Info Panel -->
            <div class="ruixing-smtp-sidebar" style="flex: 0 0 350px;">
                <!-- Test Email Card -->
                <div class="card" style="max-width: 100%; padding: 15px;">
                    <h2 style="margin-top: 0;"><?php _e('Test Email', 'ruixing'); ?></h2>

                    <?php if ($test_result): ?>
                        <div class="notice <?php echo $test_result['success'] ? 'notice-success' : 'notice-error'; ?>"
                            style="margin: 10px 0;">
                            <p><?php echo esc_html($test_result['message']); ?></p>
                            <?php if (!empty($test_result['debug'])): ?>
                                <details style="margin-top: 10px;">
                                    <summary><?php _e('Debug Info', 'ruixing'); ?></summary>
                                    <pre
                                        style="background: #f5f5f5; padding: 10px; overflow-x: auto; font-size: 11px;"><?php echo esc_html($test_result['debug']); ?></pre>
                                </details>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <form method="post">
                        <?php wp_nonce_field('ruixing_smtp_test_nonce'); ?>
                        <p>
                            <label for="test_email"><?php _e('Send test email to:', 'ruixing'); ?></label>
                            <input type="email" id="test_email" name="test_email"
                                value="<?php echo esc_attr(get_option('admin_email')); ?>" class="regular-text"
                                style="width: 100%;">
                        </p>
                        <p>
                            <button type="submit" name="ruixing_smtp_test" class="button button-secondary">
                                <?php _e('Send Test Email', 'ruixing'); ?>
                            </button>
                        </p>
                    </form>
                </div>

                <!-- Common SMTP Settings Reference -->
                <div class="card" style="max-width: 100%; padding: 15px; margin-top: 20px;">
                    <h2 style="margin-top: 0;"><?php _e('Common SMTP Settings', 'ruixing'); ?></h2>

                    <h4 style="margin-bottom: 5px;">SiteGround</h4>
                    <p style="margin-top: 0; font-size: 12px; color: #666;">
                        Host: <code>mail.yourdomain.com</code><br>
                        Port: <code>465</code> (SSL) or <code>587</code> (TLS)<br>
                        Username: Your full email address
                    </p>

                    <h4 style="margin-bottom: 5px;">Gmail</h4>
                    <p style="margin-top: 0; font-size: 12px; color: #666;">
                        Host: <code>smtp.gmail.com</code><br>
                        Port: <code>587</code> (TLS) or <code>465</code> (SSL)<br>
                        Use App Password (not regular password)
                    </p>

                    <h4 style="margin-bottom: 5px;">QQ Mail</h4>
                    <p style="margin-top: 0; font-size: 12px; color: #666;">
                        Host: <code>smtp.qq.com</code><br>
                        Port: <code>465</code> (SSL) or <code>587</code> (TLS)<br>
                        Use Authorization Code as password
                    </p>

                    <h4 style="margin-bottom: 5px;">Outlook/Office 365</h4>
                    <p style="margin-top: 0; font-size: 12px; color: #666;">
                        Host: <code>smtp.office365.com</code><br>
                        Port: <code>587</code> (TLS)
                    </p>
                </div>

                <!-- Status Card -->
                <div class="card" style="max-width: 100%; padding: 15px; margin-top: 20px;">
                    <h2 style="margin-top: 0;"><?php _e('Current Status', 'ruixing'); ?></h2>
                    <?php if ($options['enabled'] && !empty($options['host'])): ?>
                        <p style="color: #46b450;">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <?php printf(__('SMTP Enabled: %s', 'ruixing'), '<strong>' . esc_html($options['host']) . '</strong>'); ?>
                        </p>
                    <?php else: ?>
                        <p style="color: #dc3232;">
                            <span class="dashicons dashicons-warning"></span>
                            <?php _e('SMTP is disabled or not configured', 'ruixing'); ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <style>
        .ruixing-smtp-container .card {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
        }

        .ruixing-smtp-container code {
            background: #f0f0f1;
            padding: 2px 5px;
            border-radius: 3px;
        }

        @media (max-width: 960px) {
            .ruixing-smtp-container {
                flex-direction: column;
            }

            .ruixing-smtp-sidebar {
                flex: 1 1 100% !important;
            }
        }
    </style>
    <?php
}

/**
 * Send test email
 */
function ruixing_smtp_send_test_email($to)
{
    $subject = sprintf(__('[%s] SMTP Test Email', 'ruixing'), get_bloginfo('name'));
    $message = sprintf(
        __("This is a test email sent from your WordPress site.\n\nIf you received this email, your SMTP settings are working correctly!\n\nSent at: %s\n\nSite: %s", 'ruixing'),
        current_time('mysql'),
        home_url()
    );

    // Enable debug mode temporarily
    global $ruixing_smtp_debug;
    $ruixing_smtp_debug = '';

    add_action('phpmailer_init', 'ruixing_smtp_capture_debug', 999);

    $result = wp_mail($to, $subject, $message);

    remove_action('phpmailer_init', 'ruixing_smtp_capture_debug', 999);

    if ($result) {
        return array(
            'success' => true,
            'message' => sprintf(__('Test email sent successfully to %s', 'ruixing'), $to)
        );
    } else {
        global $phpmailer;
        $error = '';
        if (isset($phpmailer) && is_object($phpmailer)) {
            $error = $phpmailer->ErrorInfo;
        }
        return array(
            'success' => false,
            'message' => __('Failed to send test email.', 'ruixing'),
            'debug' => !empty($error) ? $error : (!empty($ruixing_smtp_debug) ? $ruixing_smtp_debug : __('No debug information available.', 'ruixing'))
        );
    }
}

/**
 * Capture debug info
 */
function ruixing_smtp_capture_debug($phpmailer)
{
    global $ruixing_smtp_debug;
    $phpmailer->SMTPDebug = 2;
    $phpmailer->Debugoutput = function ($str, $level) {
        global $ruixing_smtp_debug;
        $ruixing_smtp_debug .= $str;
    };
}

/**
 * Configure PHPMailer to use SMTP
 */
function ruixing_configure_smtp($phpmailer)
{
    $options = ruixing_smtp_get_options();

    // Check if SMTP is enabled
    if (!$options['enabled'] || empty($options['host'])) {
        return;
    }

    // Apply configuration to PHPMailer
    $phpmailer->isSMTP();
    $phpmailer->Host = $options['host'];
    $phpmailer->Port = $options['port'];
    $phpmailer->SMTPAuth = (bool) $options['auth'];

    if (!empty($options['encryption'])) {
        $phpmailer->SMTPSecure = $options['encryption'];
    }

    if ($options['auth']) {
        $phpmailer->Username = $options['username'];
        $phpmailer->Password = $options['password'];
    }

    // Set From address
    if (!empty($options['from_email'])) {
        $phpmailer->From = $options['from_email'];
    }
    if (!empty($options['from_name'])) {
        $phpmailer->FromName = $options['from_name'];
    }
}
add_action('phpmailer_init', 'ruixing_configure_smtp');

/**
 * Force From email to match SMTP username (required by many SMTP servers)
 */
function ruixing_smtp_force_from_email($from_email)
{
    $options = ruixing_smtp_get_options();

    // Only override if SMTP is enabled and we have a username
    if ($options['enabled'] && !empty($options['username'])) {
        // Use the from_email setting if it's set and matches the SMTP domain
        // Otherwise, use the SMTP username directly
        if (!empty($options['from_email']) && is_email($options['from_email'])) {
            // Check if from_email domain matches username domain (for servers that allow same-domain sending)
            $from_domain = substr(strrchr($options['from_email'], "@"), 1);
            $username_domain = substr(strrchr($options['username'], "@"), 1);

            if ($from_domain === $username_domain) {
                return $options['from_email'];
            }
        }
        // Default: use SMTP username as from email (safest option)
        return $options['username'];
    }

    return $from_email;
}
add_filter('wp_mail_from', 'ruixing_smtp_force_from_email', 9999);

/**
 * Force From name
 */
function ruixing_smtp_force_from_name($from_name)
{
    $options = ruixing_smtp_get_options();

    if ($options['enabled'] && !empty($options['from_name'])) {
        return $options['from_name'];
    }

    return $from_name;
}
add_filter('wp_mail_from_name', 'ruixing_smtp_force_from_name', 9999);

/**
 * Add settings link on plugins page (for theme, add to theme settings)
 */
function ruixing_smtp_settings_link($links)
{
    $settings_link = '<a href="options-general.php?page=ruixing-smtp-settings">' . __('SMTP Settings', 'ruixing') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}

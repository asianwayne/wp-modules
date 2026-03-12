<?php
/**
 * Template Name: Custom Login Page
 *
 * @package Iceberg
 */

// If user is already logged in, redirect them to the admin dashboard.
if (is_user_logged_in()) {
    wp_redirect(admin_url());
    exit;
}

get_header(); ?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">
        <div class="custom-login-wrapper"
            style="max-width: 400px; margin: 60px auto; padding: 30px; border: 1px solid #eaeaea; border-radius: 8px; background: #ffffff; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
            <header class="entry-header" style="text-align: center; margin-bottom: 20px;">
                <h1 class="entry-title"><?php esc_html_e('Login', 'iceberg'); ?></h1>
            </header>

            <div class="entry-content">
                <?php
                $login_error = isset($_GET['login']) ? sanitize_text_field($_GET['login']) : '';
                if ($login_error === 'failed') {
                    echo '<div class="login-alert login-alert-error" style="color: #d9534f; background-color: #fdf7f7; border: 1px solid #d9534f; padding: 10px; margin-bottom: 20px; border-radius: 4px; text-align: center;">' . esc_html__('Invalid Username or Password.', 'iceberg') . '</div>';
                } elseif ($login_error === 'empty') {
                    echo '<div class="login-alert login-alert-warning" style="color: #f0ad4e; background-color: #fcf8e3; border: 1px solid #f0ad4e; padding: 10px; margin-bottom: 20px; border-radius: 4px; text-align: center;">' . esc_html__('Username and Password cannot be empty.', 'iceberg') . '</div>';
                }

                wp_login_form(array(
                    'echo' => true,
                    'redirect' => admin_url(),
                    'form_id' => 'custom-loginform',
                    'label_username' => esc_html__('Username', 'iceberg'),
                    'label_password' => esc_html__('Password', 'iceberg'),
                    'label_remember' => esc_html__('Remember Me', 'iceberg'),
                    'label_log_in' => esc_html__('Log In', 'iceberg'),
                    'id_username' => 'user_login',
                    'id_password' => 'user_pass',
                    'id_remember' => 'rememberme',
                    'id_submit' => 'wp-submit',
                    'remember' => true,
                    'value_username' => '',
                    'value_remember' => false
                ));
                ?>
            </div>
        </div>
    </main><!-- .site-main -->
</div><!-- .content-area -->

<?php get_footer(); ?>
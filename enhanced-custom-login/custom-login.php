<?php
/**
 * Custom Login Page Functionality
 *
 * Hides the default wp-login.php and wp-admin entry points from
 * unauthenticated users to improve site security.
 *
 * EMERGENCY ACCESS:
 * If you forget your custom login page URL, you can still access
 * the default WordPress login by visiting:
 *     yoursite.com/wp-login.php?access=iceberg2026
 *
 * You can change the secret key below.
 *
 * @package Iceberg
 */

// ╔═══════════════════════════════════════════════════════════════╗
// ║  CHANGE THIS to your own secret key                         ║
// ╚═══════════════════════════════════════════════════════════════╝
define('ICEBERG_LOGIN_SECRET_KEY', 'iceberg2026');

// Hide default login/admin entry points from non-logged-in users.
function iceberg_hide_default_login()
{
    // Logged-in users can access everything normally.
    if (is_user_logged_in()) {
        return;
    }

    $request_uri = $_SERVER['REQUEST_URI'];
    $is_post = ($_SERVER['REQUEST_METHOD'] === 'POST');

    // ── wp-login.php ────────────────────────────────────────────────
    if (strpos($request_uri, 'wp-login.php') !== false) {

        // Allow POST requests (the login form submits here).
        if ($is_post) {
            return;
        }

        // Allow access if the secret key is provided in the URL.
        // e.g. yoursite.com/wp-login.php?access=iceberg2026
        if (isset($_GET['access']) && $_GET['access'] === ICEBERG_LOGIN_SECRET_KEY) {
            return;
        }

        // Allow specific GET actions that WordPress itself needs.
        if (
            isset($_GET['action']) && in_array($_GET['action'], array(
                'logout',
                'lostpassword',
                'rp',
                'resetpass',
                'postpass'
            ), true)
        ) {
            return;
        }

        // Block everything else – redirect silently to home page.
        wp_safe_redirect(home_url('/'));
        exit;
    }

    // ── /wp-admin/ ──────────────────────────────────────────────────
    // Allow admin-ajax.php (needed by front-end AJAX features).
    if (strpos($request_uri, '/wp-admin/') !== false && strpos($request_uri, 'admin-ajax.php') === false) {
        wp_safe_redirect(home_url('/'));
        exit;
    }
}
add_action('init', 'iceberg_hide_default_login');

// After a failed login redirect back to the custom login page.
function iceberg_custom_login_failed()
{
    $login_page_url = iceberg_get_custom_login_url();
    if (!$login_page_url) {
        return;
    }

    wp_redirect(add_query_arg('login', 'failed', $login_page_url));
    exit;
}
add_action('wp_login_failed', 'iceberg_custom_login_failed');

// If username or password is empty, redirect back with a message.
function iceberg_verify_username_password($user, $username, $password)
{
    if (empty($username) || empty($password)) {
        $login_page_url = iceberg_get_custom_login_url();
        if (!$login_page_url) {
            return $user;
        }

        wp_redirect(add_query_arg('login', 'empty', $login_page_url));
        exit;
    }
    return $user;
}
add_filter('authenticate', 'iceberg_verify_username_password', 10, 3);

// Helper: find the URL of the page that uses the Custom Login template.
function iceberg_get_custom_login_url()
{
    $pages = get_pages(array(
        'meta_key' => '_wp_page_template',
        'meta_value' => 'page-login.php',
        'number' => 1,
    ));

    if ($pages) {
        return get_permalink($pages[0]->ID);
    }

    return false;
}

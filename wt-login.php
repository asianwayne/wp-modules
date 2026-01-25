<?php
/**
 * Plugin Name:   Custom Login & Redirects
 * Description:   Creates a custom login page, hides wp-login.php, and protects wp-admin.
 * Version:       1.0.0
 * Author:        Pro WP Developer
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// --- CONFIGURATION ---
// Define your custom login page slug. 
// IMPORTANT: You MUST create a page in WordPress with this slug (e.g., "https://yoursite.com/member-login")
define( 'CUSTOM_LOGIN_SLUG', 'member-login' );


/**
 * 1. Create the Shortcode for the Custom Login Form
 *
 * Place [custom_login_form] on your new login page.
 */
function custom_login_form_shortcode() {

    // If the user is already logged in, show a message and a logout link.
    if ( is_user_logged_in() ) {
        return '<p>You are already logged in.</p>' . 
               '<a href="' . wp_logout_url( home_url( '/' ) ) . '" class="button">Log Out</a>'; // CHANGED: Redirect to homepage on logout
    }

    $output = '';

    // --- Display Login Error Messages ---
    // Check for a 'login' query variable to display errors.
    if ( isset( $_GET['login'] ) ) {
        $error_message = '';
        if ( $_GET['login'] === 'failed' ) {
            $error_message = '<strong>Error:</strong> Invalid username or password.';
        } elseif ( $_GET['login'] === 'empty' ) {
            $error_message = '<strong>Error:</strong> Username and password cannot be empty.';
        } elseif ( $_GET['login'] === 'loggedout' ) {
            $error_message = 'You have been successfully logged out.';
        }
        
        if ( ! empty( $error_message ) ) {
            $output .= '<p class="custom-login-error">' . $error_message . '</p>';
        }
    }

    // --- Display the Login Form ---
    $login_form_args = array(
        'echo'           => false,
        'redirect'       => admin_url(), // Redirect to wp-admin on successful login
        'form_id'        => 'custom_login_form',
        'label_username' => __( 'Username or Email Address' ),
        'label_password' => __( 'Password' ),
        'label_remember' => __( 'Remember Me' ),
        'label_log_in'   => __( 'Log In' ),
        'id_username'    => 'user_login',
        'id_password'    => 'user_pass',
        'id_remember'    => 'rememberme',
        'id_submit'      => 'wp-submit',
        'remember'       => true,
        'value_username' => '', // Do not repopulate username on failed login for security
        'value_remember' => true,
    );

    $output .= wp_login_form( $login_form_args );

    // Add 'Forgot Password?' link (points to the default WP reset screen)
    $output .= '<a class="custom-login-lostpassword" href="' . wp_lostpassword_url() . '">Forgot your password?</a>';

    return $output;
}
add_shortcode( 'custom_login_form', 'custom_login_form_shortcode' );


/**
 * 2. Redirect `wp-login.php` to the Custom Login Page
 *
 * This function intercepts requests for `wp-login.php` and redirects them.
 */
function redirect_default_login_page() {
    global $pagenow;

    // Get the URL of our custom login page
    $custom_login_url = home_url( '/' . CUSTOM_LOGIN_SLUG . '/' );

    // Check if the current page is `wp-login.php`.
    if ( $pagenow === 'wp-login.php' ) {
        
        // Allow specific actions to still work (logout, lostpassword, register, resetpass)
        $action = isset( $_GET['action'] ) ? $_GET['action'] : '';

        if ( ! $action || $action === 'login' ) {
            
            // If it's a POST request, it's our custom form submitting. Let it process.
            if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
                return;
            }
            
            // CHANGED: If it's any other GET request (a direct visit), show a 404 error.
            global $wp_query;
            $wp_query->set_404();
            status_header( 404 );
            get_template_part( 404 ); // Load the theme's 404 template
            exit;
        }
    }
}
add_action( 'init', 'redirect_default_login_page' );


/**
 * 3. Redirect Logged-Out Users from `/wp-admin`
 *
 * If a logged-out user tries to access any admin page, send them to the custom login page.
 */
function redirect_logged_out_users_from_admin() {
    
    // Check if we are in the admin area, the user is NOT logged in, and it's not an AJAX request.
    if ( is_admin() && ! is_user_logged_in() && ! wp_doing_ajax() ) {
        // CHANGED: Send them to a 404 page.
        global $wp_query;
        $wp_query->set_404();
        status_header( 404 );
        get_template_part( 404 ); // Load the theme's 404 template
        exit;
    }
}
add_action( 'admin_init', 'redirect_logged_out_users_from_admin' );


/**
 * 4. Handle Login Failures
 *
 * When a login fails, WordPress normally redirects back to `wp-login.php`.
 * We intercept this and redirect back to our *custom* login page with an error flag.
 */
function custom_login_failed_redirect( $username ) {
    
    // Get the referer URL (the page the login form was submitted from).
    $referer = isset( $_SERVER['HTTP_REFERER'] ) ? $_SERVER['HTTP_REFERER'] : '';
    $custom_login_url = home_url( '/' . CUSTOM_LOGIN_SLUG . '/' );

    // If the login came from our custom login page...
    if ( $referer === $custom_login_url ) {
        
        // Determine the error type
        $error_type = 'failed'; // Default: invalid credentials
        if ( empty( $_POST['log'] ) || empty( $_POST['pwd'] ) ) {
            $error_type = 'empty';
        }

        // Redirect back to the custom login page with the error flag.
        wp_redirect( add_query_arg( 'login', $error_type, $custom_login_url ) );
        exit;
    }
}
add_action( 'wp_login_failed', 'custom_login_failed_redirect' );


/**
 * 5. Handle Logout Redirect
 *
 * When a user logs out, send them to the homepage (since the login page is secret).
 */
function custom_logout_redirect( $logout_url, $redirect ) {
    // CHANGED: Set the redirect destination to the homepage.
    $new_redirect = home_url( '/' );
    return add_query_arg( 'redirect_to', urlencode( $new_redirect ), $logout_url );
}
add_filter( 'logout_url', 'custom_logout_redirect', 10, 2 );

?>
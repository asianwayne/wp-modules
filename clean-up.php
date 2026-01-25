<?php
/*
Plugin Name: Pro Optimization Suite
Description: Complete disable Google Fonts, Image Size cleanup, Security hardening, and Bloat removal.
Version: 3.0
*/

// =========================================================================
// 1. GOOGLE FONTS KILL SWITCH (Aggressive)
// =========================================================================

// Method 1: Scan all registered styles and dequeue anything from Google
function pro_optim_remove_google_fonts_dequeue() {
    global $wp_styles;
    if ( isset( $wp_styles->registered ) ) {
        foreach ( $wp_styles->registered as $handle => $data ) {
            // Check for google fonts domains
            if ( strpos( $data->src, 'fonts.googleapis.com' ) !== false || strpos( $data->src, 'fonts.gstatic.com' ) !== false ) {
                wp_dequeue_style( $handle );
                wp_deregister_style( $handle );
            }
        }
    }
}
// Run late to catch themes/plugins
add_action( 'wp_enqueue_scripts', 'pro_optim_remove_google_fonts_dequeue', 100 );
add_action( 'admin_enqueue_scripts', 'pro_optim_remove_google_fonts_dequeue', 100 );
add_action( 'enqueue_block_editor_assets', 'pro_optim_remove_google_fonts_dequeue', 100 );

// Method 2: Filter the generated HTML link tags just in case
function pro_optim_disable_google_fonts_src( $src, $handle ) {
    if ( strpos( $src, 'fonts.googleapis.com' ) !== false || strpos( $src, 'fonts.gstatic.com' ) !== false ) {
        return false;
    }
    return $src;
}
add_filter( 'style_loader_src', 'pro_optim_disable_google_fonts_src', 999, 2 );

// Method 3: Remove DNS Prefetch for Fonts
function pro_optim_remove_font_hints( $hints, $relation_type ) {
    if ( 'dns-prefetch' === $relation_type || 'preconnect' === $relation_type ) {
        foreach ( $hints as $key => $hint ) {
            if ( strpos( $hint, 'fonts.googleapis.com' ) !== false || strpos( $hint, 'fonts.gstatic.com' ) !== false ) {
                unset( $hints[$key] );
            }
        }
    }
    return $hints;
}
add_filter( 'wp_resource_hints', 'pro_optim_remove_font_hints', 10, 2 );

// =========================================================================
// 2. IMAGE OPTIMIZATION (Aggressive but Safe)
// =========================================================================

function pro_optim_disable_image_sizes( $sizes ) {
    // We KEEP 'thumbnail' because removing it breaks the WP Admin Media Library Grid
    // We remove everything else to save disk space
    
    // Default WP sizes
    unset( $sizes['medium'] );
    unset( $sizes['medium_large'] );
    unset( $sizes['large'] );
    unset( $sizes['1536x1536'] );
    unset( $sizes['2048x2048'] );
    
    // If you want to be extremely aggressive and kill 'thumbnail' too (NOT RECOMMENDED), uncomment below:
    // unset( $sizes['thumbnail'] ); 
    
    return $sizes;
}
add_filter( 'intermediate_image_sizes_advanced', 'pro_optim_disable_image_sizes' );

// Prevent WP from calculating responsive images (srcset)
add_filter( 'wp_calculate_image_srcset_meta', '__return_null' );
add_filter( 'wp_calculate_image_srcset', '__return_false' );

// // Disable Scaling of big images (Threshold)
add_filter( 'big_image_size_threshold', '__return_false' );

// // Add Lazy Load to all images
add_filter( 'wp_get_attachment_image_attributes', function( $attr ) {
    $attr['loading'] = 'lazy';
    return $attr;
});

// =========================================================================
// 3. HEAD & BLOAT CLEANUP
// =========================================================================

function pro_optim_head_cleanup() {
    // Emojis
    remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
    remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
    remove_action( 'wp_print_styles', 'print_emoji_styles' );
    remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
    remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
    remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );

    // OEmbeds (Embeds from other sites)
    remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
    remove_action( 'wp_head', 'wp_oembed_add_host_js' );
    remove_action( 'rest_api_init', 'wp_oembed_register_route' );
    remove_filter( 'oembed_dataparse', 'wp_filter_oembed_result' );

    // Meta Tags
    remove_action( 'wp_head', 'wp_generator' ); // Version number
    remove_action( 'wp_head', 'wlwmanifest_link' ); // Windows Live Writer
    remove_action( 'wp_head', 'rsd_link' ); // Really Simple Discovery
    remove_action( 'wp_head', 'wp_shortlink_wp_head' ); // Shortlink
    
    // RSS Feeds (Enable if you run a blog)
    remove_action( 'wp_head', 'feed_links', 2 ); 
    remove_action( 'wp_head', 'feed_links_extra', 3 );
}
add_action( 'init', 'pro_optim_head_cleanup' );

// Remove version query parameters (?ver=X.X) from scripts/styles
function pro_optim_remove_ver_param( $src ) {
    if ( strpos( $src, 'ver=' ) ) {
        $src = remove_query_arg( 'ver', $src );
    }
    return $src;
}
add_filter( 'style_loader_src', 'pro_optim_remove_ver_param', 9999 );
add_filter( 'script_loader_src', 'pro_optim_remove_ver_param', 9999 );

// =========================================================================
// 4. SCRIPT & STYLE MANAGEMENT (Gutenberg & jQuery)
// =========================================================================

function pro_optim_asset_management() {
    if ( ! is_admin() ) {
        // Remove jQuery Migrate
        //wp_deregister_script( 'jquery-migrate' );

        // Remove WP Embed script
        wp_deregister_script( 'wp-embed' );

        // Remove Global Styles (The huge inline SVG/CSS block)
        wp_dequeue_style( 'global-styles' );
        
        // Remove Classic Theme Styles
        wp_dequeue_style( 'classic-theme-styles' );

        // --- GUTENBERG MANAGEMENT ---
        // Uncomment the lines below ONLY if you DO NOT use Gutenberg at all.
        // wp_dequeue_style( 'wp-block-library' );
        // wp_dequeue_style( 'wp-block-library-theme' );
        // wp_dequeue_style( 'wc-blocks-style' ); // If WooCommerce
    }
}
add_action( 'wp_enqueue_scripts', 'pro_optim_asset_management', 100 );

// Remove Global Inline Styles (SVG Filters)
remove_action( 'wp_enqueue_scripts', 'wp_enqueue_global_styles' );
remove_action( 'wp_body_open', 'wp_global_styles_render_svg_filters' );

// Smart Gutenberg Loading: Only load CSS for blocks present on the page
add_filter( 'should_load_separate_core_block_assets', '__return_true' );

// =========================================================================
// 5. SECURITY HARDENING
// =========================================================================

// Disable XML-RPC (Major security risk)
add_filter( 'xmlrpc_enabled', '__return_false' );

// Remove X-Pingback Header
add_filter( 'wp_headers', function( $headers ) {
    unset( $headers['X-Pingback'] );
    return $headers;
});

// Block User Enumeration (Scans for /?author=1)
function pro_optim_block_user_enumeration() {
    if ( ! is_admin() && isset( $_REQUEST['author'] ) && preg_match( '/\d/', $_REQUEST['author'] ) ) {
        wp_redirect( home_url(), 301 );
        exit;
    }
}
add_action( 'template_redirect', 'pro_optim_block_user_enumeration' );

// Disable REST API for Users Endpoint ONLY (Prevents listing users via JSON)
add_filter( 'rest_endpoints', function( $endpoints ) {
    if ( isset( $endpoints['/wp/v2/users'] ) ) {
        unset( $endpoints['/wp/v2/users'] );
    }
    if ( isset( $endpoints['/wp/v2/users/(?P<id>[\d]+)'] ) ) {
        unset( $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );
    }
    return $endpoints;
});

// =========================================================================
// 6. ADMIN & PERFORMANCE TWEAKS
// =========================================================================

// Limit Heartbeat API to 60 seconds (saves CPU)
add_filter( 'heartbeat_settings', function( $settings ) {
    $settings['interval'] = 60;
    return $settings;
});

// Disable Self Pingbacks
add_action( 'pre_ping', function( &$links ) {
    $home = get_option( 'home' );
    foreach ( $links as $l => $link ) {
        if ( 0 === strpos( $link, $home ) ) {
            unset( $links[$l] );
        }
    }
});

// Disable Auto-Update Emails (Keep dashboard clean)
add_filter( 'auto_core_update_send_email', '__return_false' );
add_filter( 'auto_plugin_update_send_email', '__return_false' );
add_filter( 'auto_theme_update_send_email', '__return_false' );
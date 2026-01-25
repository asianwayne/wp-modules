<?php
/**
 * User Avatar
 * Version: 2.0
 * Local custom avatar system with SVG initials generator
 */

// 1. Hook for the HTML <img> tag (Standard get_avatar calls)
add_filter('pre_get_avatar', 'custom_svg_avatar_html', 10, 3);

// 2. Hook for the raw URL string (get_avatar_url calls)
add_filter('pre_get_avatar_data', 'custom_svg_avatar_url_filter', 10, 2);

/**
 * HOOK 1: Handle HTML output
 * We keep this to ensure we use esc_attr() for Data URIs instead of esc_url()
 */
function custom_svg_avatar_html($avatar, $id_or_email, $args) {
    // 1. Get the URL/Data URI from our central helper
    $url = get_custom_avatar_uri($id_or_email, $args);

    if (!$url) {
        return $avatar; // Fallback to standard if nothing found
    }

    // 2. Build the tag safely (Your existing logic)
    return build_avatar_img($url, $args);
}

/**
 * HOOK 2: Handle URL output
 * This allows get_avatar_url() to return your SVG Data URI or Custom Image URL
 */
function custom_svg_avatar_url_filter($args, $id_or_email) {
    // Avoid infinite loops if needed, though usually safe here
    $url = get_custom_avatar_uri($id_or_email, $args);

    if ($url) {
        $args['url'] = $url;
        // Optimization: prevent WordPress from checking Gravatar since we found a local one
        $args['found_avatar'] = true; 
    }

    return $args;
}

/**
 * CORE LOGIC: Determines the Avatar URI (Image URL or SVG Data URI)
 * Returns string or false.
 */
function get_custom_avatar_uri($id_or_email, $args = []) {
    $user_id = 0;
    $user    = false;

    // 1. Normalize User Detection
    if (is_numeric($id_or_email)) {
        $user_id = (int) $id_or_email;
    } elseif (is_string($id_or_email) && ($user = get_user_by('email', $id_or_email))) {
        $user_id = $user->ID;
    } elseif (is_object($id_or_email) && !empty($id_or_email->user_id)) {
        $user_id = (int) $id_or_email->user_id;
    }

    // 2. Check for Custom Uploaded Image (Meta field: 'custom_avatar')
    if ($user_id > 0) {
        $custom_url = get_user_meta($user_id, 'custom_avatar', true);
        if (!empty($custom_url)) {
            return $custom_url;
        }
    }

    // 3. Fallback: Generate SVG Initials
    $name = '';
    if ($user_id > 0) {
        $user_data = get_userdata($user_id);
        $name = $user_data->display_name;
    } elseif (is_object($id_or_email) && !empty($id_or_email->comment_author)) {
        $name = $id_or_email->comment_author;
    } else {
        $name = is_string($id_or_email) ? $id_or_email : 'Visitor';
    }

    // Generate Initials & Color
    $initials = strtoupper(get_initials_from_name($name));
    $size     = isset($args['size']) ? (int) $args['size'] : 96;
    
    // Consistent Color Hash
    $hash  = md5($name);
    $color = '#' . substr($hash, 0, 6);

    // Generate SVG HTML
    $svg = create_svg_avatar($initials, $color, $size);

    // Return Data URI
    return 'data:image/svg+xml;base64,' . base64_encode($svg);
}

/**
 * Helper: Build the <img> tag safely
 */
function build_avatar_img($src, $args) {
    $size  = isset($args['size'])  ? (int) $args['size']  : 96;
    $alt   = isset($args['alt'])   ? $args['alt'] : '';
    $class = isset($args['class']) ? $args['class'] : 'avatar';
    
    $class_safe = esc_attr($class . ' avatar-' . $size . ' photo');
    
    // CRITICAL: Allow Data URIs by using esc_attr instead of esc_url for them
    if (strpos($src, 'data:image') === 0) {
        $src_safe = esc_attr($src);
    } else {
        $src_safe = esc_url($src);
    }

    return sprintf(
        '<img alt="%s" src="%s" class="%s" height="%d" width="%d" loading="lazy" />',
        esc_attr($alt),
        $src_safe,
        $class_safe,
        $size,
        $size
    );
}

/**
 * Helper: Extract Initials
 */
function get_initials_from_name($name) {
    if (empty($name)) return '?';
    $clean_name = trim(preg_replace('/[0-9@\.]/', '', $name));
    if (empty($clean_name)) $clean_name = $name;

    $parts = preg_split('/\s+/', $clean_name);
    
    if (count($parts) >= 2) {
        return mb_substr($parts[0], 0, 1) . mb_substr($parts[1], 0, 1);
    }
    return mb_substr($name, 0, 1);
}

/**
 * Helper: Generate SVG Code
 */
function create_svg_avatar($initials, $bg_color, $size) {
    $font_size = round($size * 0.45);
    return '
    <svg xmlns="http://www.w3.org/2000/svg" width="'.$size.'" height="'.$size.'" viewBox="0 0 '.$size.' '.$size.'">
      <rect width="100%" height="100%" fill="'.$bg_color.'" />
      <text x="50%" y="50%" dy=".35em" 
            font-size="'.$font_size.'" 
            fill="#ffffff" 
            text-anchor="middle" 
            font-family="sans-serif, Arial" 
            font-weight="bold">
        '.$initials.'
      </text>
    </svg>';
}
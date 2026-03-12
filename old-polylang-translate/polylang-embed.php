<?php 
/**
 * Plugin Name: Polylang REST API Extension - Enhanced (Fixed)
 * Description: Custom REST API endpoints for Polylang with proper translation linking
 * Version: 2.1.0
 * Author: Your Name
 */

if (!defined('ABSPATH')) {
    exit;
}

class Polylang_REST_API_Extension {
    
    private $namespace = 'polylang-api/v1';
    
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    /**
     * Register all REST API routes
     */
    public function register_routes() {
        // Get all translatable post types
        register_rest_route($this->namespace, '/post-types', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_post_types'),
            'permission_callback' => array($this, 'check_permission'),
        ));
        
        // Get untranslated posts
        register_rest_route($this->namespace, '/untranslated-posts', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_untranslated_posts'),
            'permission_callback' => array($this, 'check_permission'),
            'args' => array(
                'post_type' => array(
                    'default' => 'post',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'source_lang' => array(
                    'default' => 'en',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'target_lang' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));
        
        // Get post content for translation
        register_rest_route($this->namespace, '/post/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_post_content'),
            'permission_callback' => array($this, 'check_permission'),
            'args' => array(
                'id' => array(
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ),
            ),
        ));
        
        // Create translated post
        register_rest_route($this->namespace, '/translate-post', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_translated_post'),
            'permission_callback' => array($this, 'check_permission'),
        ));
        
        // Get languages
        register_rest_route($this->namespace, '/languages', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_languages'),
            'permission_callback' => array($this, 'check_permission'),
        ));
        
        // Get translation status
        register_rest_route($this->namespace, '/translation-status/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_translation_status'),
            'permission_callback' => array($this, 'check_permission'),
        ));
    }
    
    /**
     * Permission callback
     */
    public function check_permission() {
        return current_user_can('edit_posts');
    }
    
    /**
     * Get all public post types that support translation
     */
    public function get_post_types() {
        if (!function_exists('pll_languages_list')) {
            return new WP_Error('polylang_not_active', 'Polylang is not active', array('status' => 500));
        }
        
        $post_types = get_post_types(array(
            'public' => true,
        ), 'objects');
        
        unset($post_types['attachment'], $post_types['video'], $post_types['page']);
        
        $translatable_types = array();
        
        foreach ($post_types as $post_type) {
            if (function_exists('pll_is_translated_post_type') && pll_is_translated_post_type($post_type->name)) {
                $translatable_types[] = array(
                    'name' => $post_type->name,
                    'label' => $post_type->label,
                    'singular_name' => $post_type->labels->singular_name,
                );
            }
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'post_types' => array_column($translatable_types, 'name'),
            'post_types_details' => $translatable_types,
        ));
    }
    
    /**
     * Get all untranslated posts for a specific language
     */
    public function get_untranslated_posts($request) {
        if (!function_exists('pll_languages_list')) {
            return new WP_Error('polylang_not_active', 'Polylang is not active', array('status' => 500));
        }
        
        $post_type = $request->get_param('post_type');
        $source_lang = $request->get_param('source_lang');
        $target_lang = $request->get_param('target_lang');
        
        $args = array(
            'post_type' => $post_type,
            'posts_per_page' => -1,
            'lang' => $source_lang,
            'post_status' => 'publish',
        );
        
        $posts = get_posts($args);
        $untranslated = array();
        
        foreach ($posts as $post) {
            $translation_id = pll_get_post($post->ID, $target_lang);
            
            if (!$translation_id) {
                $untranslated[] = array(
                    'id' => $post->ID,
                    'title' => $post->post_title,
                    'slug' => $post->post_name,
                    'status' => $post->post_status,
                    'date' => $post->post_date,
                    'modified' => $post->post_modified,
                    'post_type' => $post->post_type,
                    'language' => $source_lang,
                );
            }
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'count' => count($untranslated),
            'posts' => $untranslated,
        ));
    }
    
    /**
     * Get full post content including all meta fields
     */
    public function get_post_content($request) {
        $post_id = $request->get_param('id');
        $post = get_post($post_id);
        
        if (!$post) {
            return new WP_Error('post_not_found', 'Post not found', array('status' => 404));
        }
        
        $post_meta = get_post_meta($post_id);
        
        $clean_meta = array();
        foreach ($post_meta as $key => $value) {
            if (substr($key, 0, 1) === '_') continue;
            
            $meta_value = is_array($value) ? $value[0] : $value;
            
            if (!is_string($meta_value) || $this->is_serialized($meta_value)) continue;
            
            $clean_meta[$key] = $meta_value;
        }
        
        $categories = wp_get_post_categories($post_id, array('fields' => 'all'));
        $tags = wp_get_post_tags($post_id, array('fields' => 'all'));
        
        $custom_taxonomies = array();
        $taxonomies = get_object_taxonomies($post->post_type, 'objects');
        foreach ($taxonomies as $taxonomy) {
            if (!in_array($taxonomy->name, array('category', 'post_tag', 'language', 'post_translations'))) {
                $terms = wp_get_post_terms($post_id, $taxonomy->name);
                if (!is_wp_error($terms) && !empty($terms)) {
                    $custom_taxonomies[$taxonomy->name] = $terms;
                }
            }
        }
        
        $thumbnail_id = get_post_thumbnail_id($post_id);
        $thumbnail_url = $thumbnail_id ? wp_get_attachment_url($thumbnail_id) : null;
        
        $acf_fields = array();
        if (function_exists('get_fields')) {
            $acf_fields = get_fields($post_id) ?: array();
        }
        
        $current_lang = pll_get_post_language($post_id);
        
        return rest_ensure_response(array(
            'success' => true,
            'post' => array(
                'id' => $post->ID,
                'title' => $post->post_title,
                'content' => $post->post_content,
                'excerpt' => $post->post_excerpt,
                'slug' => $post->post_name,
                'status' => $post->post_status,
                'type' => $post->post_type,
                'language' => $current_lang,
                'featured_image' => $thumbnail_url,
                'thumbnail_id' => $thumbnail_id,
                'categories' => $categories,
                'tags' => $tags,
                'custom_taxonomies' => $custom_taxonomies,
                'meta' => $clean_meta,
                'acf_fields' => $acf_fields,
            ),
        ));
    }
    
    /**
     * Create or update translated post - FIXED VERSION
     * Now properly preserves all translation relationships
     */
    public function create_translated_post($request) {
        $params = $request->get_json_params();
        
        if (!isset($params['source_post_id']) || !isset($params['target_lang'])) {
            return new WP_Error('missing_params', 'Missing required parameters', array('status' => 400));
        }
        
        $source_post_id = intval($params['source_post_id']);
        $target_lang = sanitize_text_field($params['target_lang']);
        $translated_title = sanitize_text_field($params['title']);
        $translated_content = wp_kses_post($params['content']);
        $translated_excerpt = isset($params['excerpt']) ? sanitize_textarea_field($params['excerpt']) : '';
        
        $source_post = get_post($source_post_id);
        
        if (!$source_post) {
            return new WP_Error('post_not_found', 'Source post not found', array('status' => 404));
        }
        
        // Check if translation exists
        $existing_translation = pll_get_post($source_post_id, $target_lang);
        
        if ($existing_translation) {
            // Update existing translation
            $updated_post = array(
                'ID' => $existing_translation,
                'post_title' => $translated_title,
                'post_content' => $translated_content,
                'post_excerpt' => $translated_excerpt,
            );
            
            $result = wp_update_post($updated_post, true);
            
            if (is_wp_error($result)) {
                return $result;
            }
            
            $translated_post_id = $existing_translation;
            $action = 'updated';
        } else {
            // Create new translation
            $new_post = array(
                'post_title' => $translated_title,
                'post_content' => $translated_content,
                'post_excerpt' => $translated_excerpt,
                'post_status' => $source_post->post_status,
                'post_type' => $source_post->post_type,
                'post_author' => $source_post->post_author,
            );
            
            $translated_post_id = wp_insert_post($new_post, true);
            
            if (is_wp_error($translated_post_id)) {
                return $translated_post_id;
            }
            
            // Set language for new post
            pll_set_post_language($translated_post_id, $target_lang);
            
            // ⭐ CRITICAL FIX: Get ALL existing translations first
            $existing_translations = pll_get_post_translations($source_post_id);
            
            // Add the new translation to the existing group
            $existing_translations[$target_lang] = $translated_post_id;
            
            // Save the complete translation group
            pll_save_post_translations($existing_translations);
            
            $action = 'created';
        }
        
        // Copy or set featured image
        if (isset($params['thumbnail_id']) && $params['thumbnail_id']) {
            set_post_thumbnail($translated_post_id, intval($params['thumbnail_id']));
        } else {
            $thumbnail_id = get_post_thumbnail_id($source_post_id);
            if ($thumbnail_id) {
                set_post_thumbnail($translated_post_id, $thumbnail_id);
            }
        }
        
        // Handle categories
        if (isset($params['categories']) && is_array($params['categories'])) {
            $translated_category_ids = array();
            foreach ($params['categories'] as $category_data) {
                $cat_id = $this->translate_term($category_data, 'category', $target_lang);
                if ($cat_id) {
                    $translated_category_ids[] = $cat_id;
                }
            }
            wp_set_post_categories($translated_post_id, $translated_category_ids);
        }
        
        // Handle tags
        if (isset($params['tags']) && is_array($params['tags'])) {
            $translated_tag_ids = array();
            foreach ($params['tags'] as $tag_data) {
                $tag_id = $this->translate_term($tag_data, 'post_tag', $target_lang);
                if ($tag_id) {
                    $translated_tag_ids[] = $tag_id;
                }
            }
            wp_set_post_tags($translated_post_id, $translated_tag_ids);
        }
        
        // Handle ACF fields
        if (isset($params['acf_fields']) && function_exists('update_field')) {
            foreach ($params['acf_fields'] as $field_key => $field_value) {
                update_field($field_key, $field_value, $translated_post_id);
            }
        }
        
        // Handle custom meta fields
        if (isset($params['meta_fields']) && is_array($params['meta_fields'])) {
            foreach ($params['meta_fields'] as $meta_key => $meta_value) {
                if (substr($meta_key, 0, 1) === '_') continue;
                
                update_post_meta($translated_post_id, $meta_key, sanitize_text_field($meta_value));
            }
        }
        
        // Get final translation status for verification
        $final_translations = pll_get_post_translations($source_post_id);
        
        return rest_ensure_response(array(
            'success' => true,
            'action' => $action,
            'translated_post_id' => $translated_post_id,
            'source_post_id' => $source_post_id,
            'post_type' => $source_post->post_type,
            'target_language' => $target_lang,
            'url' => get_permalink($translated_post_id),
            'all_translations' => $final_translations, // Shows all connected translations
        ));
    }
    
    /**
     * Helper to translate taxonomy terms
     */
    private function translate_term($term_data, $taxonomy, $target_lang) {
        $source_term_id = isset($term_data['term_id']) ? intval($term_data['term_id']) : 0;
        $translated_name = isset($term_data['translated_name']) ? sanitize_text_field($term_data['translated_name']) : '';
        
        if ($source_term_id && function_exists('pll_get_term')) {
            $existing_translation = pll_get_term($source_term_id, $target_lang);
            
            if ($existing_translation) {
                return $existing_translation;
            }
        }
        
        if ($translated_name) {
            $new_term = wp_insert_term($translated_name, $taxonomy);
            
            if (!is_wp_error($new_term)) {
                $new_term_id = $new_term['term_id'];
                
                if (function_exists('pll_set_term_language')) {
                    pll_set_term_language($new_term_id, $target_lang);
                    
                    if ($source_term_id) {
                        // ⭐ SAME FIX for terms: preserve existing translations
                        $existing_term_translations = pll_get_term_translations($source_term_id);
                        $existing_term_translations[$target_lang] = $new_term_id;
                        pll_save_term_translations($existing_term_translations);
                    }
                }
                
                return $new_term_id;
            }
        }
        
        return null;
    }
    
    /**
     * Get all configured languages
     */
    public function get_languages() {
        if (!function_exists('pll_languages_list')) {
            return new WP_Error('polylang_not_active', 'Polylang is not active', array('status' => 500));
        }
        
        $languages = pll_languages_list(array('fields' => 'all'));
        $lang_data = array();
        
        foreach ($languages as $lang) {
            $lang_data[] = array(
                'slug' => $lang->slug,
                'name' => $lang->name,
                'locale' => $lang->locale,
                'is_rtl' => $lang->is_rtl,
                'flag' => $lang->flag,
            );
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'languages' => $lang_data,
        ));
    }
    
    /**
     * Get translation status for a specific post
     */
    public function get_translation_status($request) {
        $post_id = $request->get_param('id');
        
        if (!function_exists('pll_get_post_translations')) {
            return new WP_Error('polylang_not_active', 'Polylang is not active', array('status' => 500));
        }
        
        $translations = pll_get_post_translations($post_id);
        $languages = pll_languages_list();
        
        $status = array();
        foreach ($languages as $lang) {
            $status[$lang] = isset($translations[$lang]) ? array(
                'exists' => true,
                'post_id' => $translations[$lang],
                'url' => get_permalink($translations[$lang]),
            ) : array(
                'exists' => false,
                'post_id' => null,
                'url' => null,
            );
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'post_id' => $post_id,
            'translations' => $status,
        ));
    }
    
    /**
     * Check if a string is serialized
     */
    private function is_serialized($data) {
        if (!is_string($data)) {
            return false;
        }
        $data = trim($data);
        if ($data === 'N;') {
            return true;
        }
        if (strlen($data) < 4 || $data[1] !== ':') {
            return false;
        }
        return @unserialize($data) !== false;
    }
}

// Initialize the plugin
new Polylang_REST_API_Extension();


/**
 * Sync featured image and gallery meta when post is created/updated via REST API
 */
function sync_media_on_rest_insert($post, $request, $creating) {
    // Check if Polylang is active
    if (!function_exists('pll_get_post_translations')) {
        return;
    }
    
    // Get the post ID
    $post_id = $post->ID;
    
    // Check if this post has translations
    $translations = pll_get_post_translations($post_id);
    
    if (empty($translations) || count($translations) <= 1) {
        return; // No translations to sync
    }
    
    // Sync Featured Image
    $thumbnail_id = get_post_thumbnail_id($post_id);
    if ($thumbnail_id) {
        foreach ($translations as $lang_code => $translated_post_id) {
            if ($translated_post_id == $post_id) {
                continue;
            }
            set_post_thumbnail($translated_post_id, $thumbnail_id);
        }
    }
    
    // Sync Gallery Meta Field (adjust meta key as needed)
    $gallery_meta_keys = array('your_gallery_field_name', 'gallery_images'); // Add your meta keys
    
    foreach ($gallery_meta_keys as $meta_key) {
        $gallery_value = get_post_meta($post_id, $meta_key, true);
        
        if (!empty($gallery_value)) {
            foreach ($translations as $lang_code => $translated_post_id) {
                if ($translated_post_id == $post_id) {
                    continue;
                }
                update_post_meta($translated_post_id, $meta_key, $gallery_value);
            }
        }
    }
}

// Hook into REST API post insert/update for all post types
add_action('rest_after_insert_post', 'sync_media_on_rest_insert', 10, 3);
add_action('rest_after_insert_page', 'sync_media_on_rest_insert', 10, 3);

// For custom post types, add specific hooks
// add_action('rest_after_insert_YOUR_POST_TYPE', 'sync_media_on_rest_insert', 10, 3);
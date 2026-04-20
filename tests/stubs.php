<?php

/**
 * Minimal WordPress function stubs for testing wp-proud-document.
 *
 * Covers every call made at file-include time so the plugin can be loaded
 * without a full WordPress install. Uses if(!function_exists) guards so
 * Patchwork can patch these definitions on a per-test basis via Brain\Monkey.
 *
 * Functions only called inside methods (check_ajax_referer, wp_send_json_*,
 * wp_verify_nonce, etc.) are NOT defined here — Brain\Monkey defines them
 * fresh per test.
 */

namespace {
    // Stub ProudPlugin before the plugin file is loaded so the
    // `require_once plugin_dir_path(__FILE__) . '../wp-proud-core/...'`
    // branch is skipped entirely (class_exists('ProudPlugin') returns true).
    if (!class_exists('ProudPlugin')) {
        class ProudPlugin {
            public function hook(string $action, string $method, int $priority = 10, int $args = 1): void {
                add_action($action, [$this, $method], $priority, $args);
            }
        }
    }

    if (!function_exists('add_action')) {
        function add_action(): bool { return true; }
    }
    if (!function_exists('add_filter')) {
        function add_filter(): bool { return true; }
    }
    if (!function_exists('is_admin')) {
        function is_admin(): bool { return false; }
    }
    if (!function_exists('plugin_dir_path')) {
        // Returns a path that resolves correctly for includes within the plugin,
        // but the ProudPlugin stub above means we never reach the core require.
        function plugin_dir_path(string $file): string {
            return dirname($file) . '/';
        }
    }
    if (!function_exists('__')) {
        function __(string $text, string $domain = 'default'): string { return $text; }
    }
    if (!function_exists('_x')) {
        function _x(string $text, string $context, string $domain = 'default'): string { return $text; }
    }
    if (!function_exists('_e')) {
        function _e(string $text, string $domain = 'default'): void { echo $text; }
    }
    if (!function_exists('esc_attr')) {
        function esc_attr(string $text): string {
            return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        }
    }
    if (!function_exists('esc_url_raw')) {
        function esc_url_raw(string $url): string {
            // Strip javascript: and other dangerous schemes; return safe URL.
            $url = trim($url);
            if (preg_match('/^[a-z][a-z0-9+\-.]*:/i', $url) &&
                !preg_match('/^https?:/i', $url) &&
                !preg_match('/^\/\//i', $url)) {
                return '';
            }
            return $url;
        }
    }
    if (!function_exists('sanitize_file_name')) {
        function sanitize_file_name(string $name): string {
            // Remove directory traversal sequences and dangerous chars.
            $name = str_replace(['<', '>', '"', "'", "\0"], '', $name);
            $name = preg_replace('/\.\.+/', '', $name);
            $name = ltrim($name, '.');
            return $name;
        }
    }
    if (!function_exists('sanitize_text_field')) {
        function sanitize_text_field(string $str): string {
            return trim(strip_tags($str));
        }
    }
    if (!function_exists('get_post_meta')) {
        function get_post_meta(int $post_id, string $key = '', bool $single = false): mixed { return ''; }
    }
    if (!function_exists('wp_nonce_field')) {
        function wp_nonce_field(string $action, string $name = '_wpnonce'): void {}
    }
    if (!function_exists('wp_create_nonce')) {
        function wp_create_nonce(string $action): string { return 'test_nonce'; }
    }
    if (!function_exists('register_post_type')) {
        function register_post_type(): void {}
    }
    if (!function_exists('register_taxonomy')) {
        function register_taxonomy(): void {}
    }
    if (!function_exists('register_rest_field')) {
        function register_rest_field(): void {}
    }
    if (!function_exists('wp_enqueue_media')) {
        function wp_enqueue_media(): void {}
    }
    if (!function_exists('wp_enqueue_script')) {
        function wp_enqueue_script(): void {}
    }
    if (!function_exists('wp_enqueue_style')) {
        function wp_enqueue_style(): void {}
    }
}

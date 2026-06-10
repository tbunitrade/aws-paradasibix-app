<?php

/**
 * @package: editorify-plugin
 */

/**
 * Plugin Name: Editorify
 * Description: Boost Your Sales By Adding Products Reviews, Videos & Images to your product page
 * Version: 1.0.10
 * Author: Editorify
 * Author URI: https://editorify.com
 * License: GPLv3 or later
 * Text Domain: editorify-plugin
 * Requires PHP: 7.2
 * WC requires at least: 5.0
 */

if (!defined('ABSPATH')) {
    die;
}

define("EDITORIFY_API_URL", "https://apps.editorify.com");
define('EDITORIFY_VERSION', '1.0.10');
define('EDITORIFY_PATH', dirname(__FILE__));
define('EDITORIFY_FOLDER', basename(EDITORIFY_PATH));
define('EDITORIFY_URL', plugins_url() . '/' . EDITORIFY_FOLDER);
define('EDITORIFY_API_KEY', get_option('editorify_api_key'));
define("EDITORIFY_DEVELOPMENT", (stripos(EDITORIFY_API_URL, "dev.editorify") !== false ? "dev" : ""));
define("EDITORIFY_DEBUG", false);

register_activation_hook(__FILE__, 'editorify_activation_hook');
register_deactivation_hook(__FILE__, 'editorify_deactivation_hook');
register_uninstall_hook(__FILE__, 'editorify_uninstall_hook');
add_action('admin_enqueue_scripts', 'editorify_add_admin_css_js');
add_action('admin_menu', 'editorify_admin_menu');
add_action('wp_head', 'editorify_script');
add_action('wp_footer', 'editorify_product_data');

/**
 * Helper: Create WooCommerce API keys programmatically.
 */
function editorify_create_woo_keys($app_name, $user_id, $scope)
{
    if (!class_exists("WC_Auth")) return false;

    if (!class_exists("Editorify_AuthCustom")) {
        class Editorify_AuthCustom extends WC_Auth
        {
            public function getKeys($app_name, $user_id, $scope)
            {
                return parent::create_keys($app_name, $user_id, $scope);
            }
        }
    }

    $auth = new Editorify_AuthCustom();
    return $auth->getKeys($app_name, $user_id, $scope);
}

function editorify_activation_hook()
{
    $data = array(
        'store' => get_site_url(),
        'email' => get_option('admin_email'),
        'event' => 'install'
    );

    $response = editorify_send_request('/auth/woocomerce-activate', $data);

    if ($response) {
        if ($response['success'] > 0) {
            if (!get_option('editorify_api_key')) {
                add_option('editorify_api_key', $response['api_key']);

                $keys = editorify_create_woo_keys($response['app_name'], $response['user_id'], $response['scope']);
                if ($keys) {
                    $data = array(
                        'store' => get_site_url(),
                        'keys' => $keys,
                        'user_id' => $response['user_id'],
                        'event' => 'update_keys'
                    );
                    $keys_response = editorify_send_request('/auth/woocomerce-activate', $data);

                    if ($keys_response && $keys_response['success'] == 0) {
                        update_option('editorify_error', 'yes');
                        update_option('editorify_error_message', $keys_response['message']);
                    }
                }
            } else {
                update_option('editorify_api_key', $response['api_key']);
            }
        } else {
            $msg = isset($response['message']) ? $response['message'] : 'Error activation plugin!';
            update_option('editorify_error', 'yes');
            update_option('editorify_error_message', $msg);
        }
    } else {
        update_option('editorify_error', 'yes');
        update_option('editorify_error_message', 'Could not connect to Editorify server. Please check your internet connection and try again.');
    }
}

function editorify_deactivation_hook()
{
    if (!current_user_can('activate_plugins')) {
        return;
    }
    $data = array(
        'store' => get_site_url(),
        'event' => 'deactivated',
    );
    return editorify_send_request('/auth/woocomerce-deactivate', $data);
}

function editorify_uninstall_hook()
{
    if (!current_user_can('activate_plugins')) {
        return;
    }

    delete_option('editorify_api_key');
    delete_option('editorify_error');
    delete_option('editorify_error_message');
    delete_option('editorify_check');

    editorify_clear_all_caches();

    $data = array(
        'store' => get_site_url(),
        'event' => 'uninstall',
    );
    return editorify_send_request('/auth/woocomerce-deactivate', $data);
}

function editorify_script()
{
    if (strlen(EDITORIFY_API_KEY) > 0) {
        $attributes = array(
            'id' => EDITORIFY_DEVELOPMENT . 'editorifyScript',
            'async' => true,
            'src' => esc_url(EDITORIFY_API_URL . "/widget/woo-reviews.js?key=" . EDITORIFY_API_KEY),
        );
        wp_print_script_tag($attributes);

        // Showcase + Testimonials: load on all pages, scripts auto-detect if applicable
        wp_print_script_tag(array(
            'id' => 'editorifyShowcase',
            'async' => true,
            'src' => esc_url(EDITORIFY_API_URL . "/widget/editorify-showcase.js?key=" . EDITORIFY_API_KEY),
        ));
        wp_print_script_tag(array(
            'id' => 'editorifyTestimonials',
            'async' => true,
            'src' => esc_url(EDITORIFY_API_URL . "/widget/editorify-testimonials.js?key=" . EDITORIFY_API_KEY),
        ));
    }
}

function editorify_product_data()
{
    if (function_exists('is_product') && is_product()) {
        global $product;
        if ($product) {
            $pid = intval($product->get_id());
            echo '<script>window.editorifyWooConfig=window.editorifyWooConfig||{};window.editorifyWooConfig.product_id=' . $pid . ';</script>';
        }
    }
    if (function_exists('is_shop') && (is_shop() || is_product_taxonomy())) {
        global $wp_query;
        if ($wp_query && !empty($wp_query->posts)) {
            $ids = array();
            foreach ($wp_query->posts as $post) {
                $ids[] = intval($post->ID);
            }
            echo '<script>window.editorifyWooConfig=window.editorifyWooConfig||{};window.editorifyWooConfig.product_ids=' . json_encode($ids) . ';</script>';
        }
    }
}

function editorify_add_admin_css_js()
{
    wp_register_style('editorify_style', EDITORIFY_URL . '/assets/css/style.css');
    wp_enqueue_style('editorify_style');
    wp_register_script('editorify-admin', EDITORIFY_URL . '/assets/js/script.js', array('jquery'), '1.0.0');
    wp_enqueue_script('editorify-admin');
}

function editorify_admin_menu()
{
    add_menu_page('Editorify Settings', 'Editorify', 'manage_options', 'Editorify', 'editorify_admin_menu_page_html', EDITORIFY_URL . '/assets/images/editorify_icon.png');
}

function editorify_has_woocommerce()
{
    return in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')));
}

function editorify_admin_menu_page_html()
{
    $data = array(
        'store' => get_site_url(),
        'event' => 'check_status'
    );

    $store_connected = false;

    $status_response = editorify_send_request('/auth/woocomerce-activate', $data);

    if ($status_response && isset($status_response['success']) && $status_response['success'] == 0) {
        update_option('editorify_error', 'yes');
        update_option('editorify_error_message', isset($status_response['message']) ? $status_response['message'] : 'Connection error');
    }

    if ($status_response && isset($status_response['success']) && $status_response['success'] == 1) {
        delete_option('editorify_error');
        delete_option('editorify_error_message');

        if (isset($status_response['keys_ok']) && $status_response['keys_ok'] == "no") {
            $keys = editorify_create_woo_keys($status_response['app_name'], $status_response['user_id'], $status_response['scope']);
            if ($keys) {
                $data = array(
                    'store' => get_site_url(),
                    'keys' => $keys,
                    'user_id' => $status_response['user_id'],
                    'event' => 'update_keys'
                );
                $keys_response = editorify_send_request('/auth/woocomerce-activate', $data);
            }
        }

        if (!get_option('editorify_api_key')) {
            add_option('editorify_api_key', $status_response['api_key']);
        } else {
            update_option('editorify_api_key', $status_response['api_key']);
        }

        if (isset($status_response['store_connected']) && $status_response['store_connected'] == "yes") {
            $store_connected = true;
        }
    }

    $tmp_check_data = array();

    // SSL check
    $tmp_check_data['ssl_active'] = is_ssl() ? "true" : "false";

    // Permalinks check
    $permalinks = get_option('permalink_structure');
    $tmp_check_data['permalinks'] = is_string($permalinks) ? $permalinks : '';

    // WooCommerce check
    $tmp_check_data['woocomerce_installed'] = editorify_has_woocommerce();

    // PHP version check
    $tmp_check_data['php_version'] = phpversion();
    $tmp_check_data['php_ok'] = version_compare(PHP_VERSION, '7.2', '>=');

    // WooCommerce version check
    $tmp_check_data['woo_version'] = '';
    if ($tmp_check_data['woocomerce_installed'] && function_exists('WC')) {
        $tmp_check_data['woo_version'] = WC()->version;
    }

    $tmp_check_data['firewall_active'] = false;
    $tmp_check_data['cloudflare_active'] = false;
    $tmp_check_data['firewall_name'] = '';

    // Check for blocking plugins only if not connected
    if ($store_connected == FALSE) {
        // Cloudflare check
        $data = array(
            'store' => get_site_url(),
            'event' => 'check_cloudflare'
        );

        $cloudflare_check = editorify_send_request('/auth/woocomerce-activate', $data);

        if ($cloudflare_check && isset($cloudflare_check['success']) && $cloudflare_check['success'] == 1) {
            if (isset($cloudflare_check['cloudflare_enabled']) && $cloudflare_check['cloudflare_enabled'] == "true") {
                $tmp_check_data['cloudflare_active'] = true;
            }
        }

        // Firewall/security plugin detection (expanded list)
        $firewall_plugins = array(
            'wordfence', 'jetpack', 'sucuri', 'ninjafirewall',
            'ithemes-security', 'better-wp-security', 'solid-security',
            'all-in-one-wp-security', 'aios-security',
            'shield-security', 'wp-simple-firewall',
            'malcare', 'developer-developer',
            'defender-security', 'wp-defender',
            'cerber', 'wp-cerber',
            'bulletproof', 'bulletproof-security',
            'secupress',
        );

        $plugin_list = get_plugins();

        foreach ($plugin_list as $key => $value) {
            $plugin_name = strtolower($value['Name']);
            $plugin_slug = strtolower($key);

            foreach ($firewall_plugins as $fw) {
                if (strpos($plugin_name, $fw) !== FALSE || strpos($plugin_slug, $fw) !== FALSE) {
                    if (is_plugin_active($key)) {
                        $tmp_check_data['firewall_active'] = true;
                        $tmp_check_data['firewall_name'] = $value['Name'];
                        break 2;
                    }
                }
            }
        }
    }

    update_option('editorify_check', $tmp_check_data);

    include_once EDITORIFY_PATH . '/views/editorify_admin_page.php';
}

function editorify_send_request($path, $data)
{
    try {
        $headers = array(
            'Content-Type' => 'application/json',
            'User-Agent' => 'Editorify Wp Plugin',
            'x-plugin-version' => EDITORIFY_VERSION,
            'x-site-url' => get_site_url(),
            'x-wp-version' => get_bloginfo('version'),
        );

        if (editorify_has_woocommerce() && function_exists('WC')) {
            $headers['x-woo-version'] = WC()->version;
        }

        $url = EDITORIFY_API_URL . $path;
        $args = array(
            'headers' => $headers,
            'body' => json_encode($data),
            'method' => 'POST',
            'data_format' => 'body',
            'sslverify' => false,
            'timeout' => 15,
        );

        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            update_option('editorify_last_error', $response->get_error_message());
            return 0;
        }

        $decoded_response = json_decode(wp_remote_retrieve_body($response), true);
        return $decoded_response;
    } catch (Exception $err) {
        update_option('editorify_last_error', $err->getMessage());
        return 0;
    }
}

function editorify_plugin_redirect()
{
    exit(wp_redirect("admin.php?page=Editorify"));
}

function editorify_clear_all_caches()
{
    try {
        global $wp_fastest_cache;

        if (function_exists('w3tc_flush_all')) {
            w3tc_flush_all();
        }

        if (function_exists('wp_cache_clean_cache')) {
            global $file_prefix, $supercachedir;
            if (empty($supercachedir) && function_exists('get_supercache_dir')) {
                $supercachedir = get_supercache_dir();
            }
            wp_cache_clean_cache($file_prefix);
        }

        if (method_exists('WpFastestCache', 'deleteCache') && !empty($wp_fastest_cache)) {
            $wp_fastest_cache->deleteCache();
        }

        if (function_exists('rocket_clean_domain')) {
            rocket_clean_domain();
            if (function_exists('run_rocket_sitemap_preload')) {
                run_rocket_sitemap_preload();
            }
        }

        if (class_exists("autoptimizeCache") && method_exists("autoptimizeCache", "clearall")) {
            autoptimizeCache::clearall();
        }

        if (class_exists("LiteSpeed_Cache_API") && method_exists("LiteSpeed_Cache_API", "purge_all")) {
            LiteSpeed_Cache_API::purge_all();
        }

        if (class_exists('\Hummingbird\Core\Utils')) {
            $modules = \Hummingbird\Core\Utils::get_active_cache_modules();
            foreach ($modules as $module => $name) {
                $mod = \Hummingbird\Core\Utils::get_module($module);
                if ($mod->is_active()) {
                    if ('minify' === $module) {
                        $mod->clear_files();
                    } else {
                        $mod->clear_cache();
                    }
                }
            }
        }
    } catch (Exception $e) {
        return 1;
    }
}

?>

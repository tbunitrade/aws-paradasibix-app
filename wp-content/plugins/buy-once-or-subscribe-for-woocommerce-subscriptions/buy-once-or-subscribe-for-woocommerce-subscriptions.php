<?php
/**
 * Plugin Name: Buy Once or Subscribe for WooCommerce Subscriptions
 * Plugin URI:  https://woocommerce.com/products/buy-once-or-subscribe-for-woocommerce-subscriptions/
 * Description: This plugin allows shop owners to easily add subscriptions to existing WooCommerce Simple Products.
 * Version:     5.1.1
 * Author:      eCommerce Tools
 * Author URI:  https://ecommercetools.io
 *
 * Text Domain: bos4w
 * Domain Path: /languages/
 *
 * Requires PHP: 7.2
 *
 * Requires at least: 5.7.0
 * Tested up to: 6.7.2
 *
 * Woo: 18734000055043:c1a7780fb14b3ad8f3d13241e25f4dac
 * WC requires at least: 7.0.0
 * WC tested up to: 9.8.1
 *
 * Copyright: © 2021 eCommerce Tools
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package Buy Once or Subscribe for WooCommerce Subscriptions
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'SFORCE_PLUGIN_FILE' ) ) {
	define( 'SFORCE_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'SFORCE_PLUGIN_URL' ) ) {
	define( 'SFORCE_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );
}

if ( ! defined( 'SFORCE_PLUGIN_PATH' ) ) {
	define( 'SFORCE_PLUGIN_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
}

if ( ! defined( 'BOS_IS_PLUGIN' ) ) {
	define( 'BOS_IS_PLUGIN', true );
	define( 'BOS_PLUGIN_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
	define( 'BOS_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );
}

/**
 * Load text domain
 */
add_action(
	'init',
	function() {
		load_plugin_textdomain( 'bos4w', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}
);

require BOS_PLUGIN_PATH . '/functions/class-load-packages.php';

if ( ! \BOS4W\Load_Packages::init() ) {
	return;
}
\BOS4W\Load_Packages::init();

/**
 * Register data
 */
register_activation_hook(
	__FILE__,
	function() {
		update_option( 'bos4w_activation_date', time() );
	}
);

/**
 * Unregister data
 */
register_deactivation_hook(
	__FILE__,
	function () {
		delete_option( 'bos4w_activation_date' );
	}
);

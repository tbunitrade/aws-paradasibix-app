<?php
/**
 * Load Packages
 *
 * @package Buy Once or Subscribe for WooCommerce Subscriptions
 * @since 1.0.2
 */

namespace BOS4W;

defined( 'ABSPATH' ) || exit;

if ( defined( 'BOS_IS_PLUGIN' ) && BOS_IS_PLUGIN ) {
	define( 'BOS_FUNC_PATH', BOS_PLUGIN_PATH . '/functions/' );
	define( 'BOS_FUNC_URL', BOS_PLUGIN_URL . '/functions/' );
} else {
	define( 'BOS_FUNC_PATH', SFORCE_PLUGIN_PATH . '/functionalities/BOS/' );
	define( 'BOS_FUNC_URL', SFORCE_PLUGIN_URL . '/functionalities/BOS/' );
}

if ( ! class_exists( 'Load_Packages' ) ) {
	/**
	 * Class Load_Packages
	 */
	class Load_Packages {
		/**
		 * Packages
		 *
		 * @var string[]
		 */
		protected static $packages = array(
			'bos4w-admin'       => 'class-bos4w-admin.php',
			'bos4w-cart-option' => 'class-bos4w-cart-options.php',
			'bos4w-front-end'   => 'class-bos4w-front-end.php',
		);

		/**
		 * Include path.
		 *
		 * @var string
		 */
		private static $include_path = '';

		/**
		 * Load_Packages constructor.
		 */
		private function __construct() {
		}

		/**
		 * Set the include path.
		 */
		public static function set_include_path() {
			self::$include_path = BOS_FUNC_PATH;
		}

		/**
		 * Load the file.
		 *
		 * @param string $path Include path.
		 *
		 * @return bool
		 */
		private static function load_the_file( $path ) {
			if ( $path && is_readable( $path ) ) {
				require_once $path;

				return true;
			}

			return false;
		}

		/**
		 * Init
		 */
		public static function init() {
			self::set_include_path();

			add_action( 'plugins_loaded', array( __CLASS__, 'on_init' ) );
		}

		/**
		 * Callback for WordPress init hook.
		 */
		public static function on_init() {
			if ( defined( 'BOS_IS_PLUGIN' ) ) {
				if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
					require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
				}

				if ( is_multisite() ) {
					if ( is_plugin_active_for_network( plugin_basename( __FILE__ ) ) ) {
						$woo_need       = is_plugin_active_for_network( 'woocommerce/woocommerce.php' );
						$woos_need      = is_plugin_active_for_network( 'woocommerce-subscriptions/woocommerce-subscriptions.php' );
						$woos_core_need = is_plugin_active_for_network( 'woocommerce-subscriptions-core/woocommerce-subscriptions-core.php' );

						if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
							$woo_need = true;
						}

						if ( is_plugin_active( 'woocommerce-subscriptions/woocommerce-subscriptions.php' ) ) {
							$woo_need  = true;
							$woos_need = true;
						}

						if ( is_plugin_active( 'woocommerce-subscriptions-core/woocommerce-subscriptions-core.php' ) ) {
							$woo_need       = true;
							$woos_core_need = true;
						}
					} else {
						$woo_need       = is_plugin_active( 'woocommerce/woocommerce.php' );
						$woos_need      = is_plugin_active( 'woocommerce-subscriptions/woocommerce-subscriptions.php' );
						$woos_core_need = is_plugin_active( 'woocommerce-subscriptions-core/woocommerce-subscriptions-core.php' );
					}
					// this plugin runs on a single site.
				} else {
					$woo_need       = is_plugin_active( 'woocommerce/woocommerce.php' );
					$woos_need      = is_plugin_active( 'woocommerce-subscriptions/woocommerce-subscriptions.php' );
					$woos_core_need = is_plugin_active( 'woocommerce-subscriptions-core/woocommerce-subscriptions-core.php' );
				}

				if ( ! $woo_need || ( ! $woos_need || ! $woos_core_need ) ) {
					if ( ! $woo_need ) {
						add_action(
							'admin_notices',
							function () {
								$install_url = wp_nonce_url(
									add_query_arg(
										array(
											'action' => 'install-plugin',
											'plugin' => 'woocommerce',
										),
										admin_url( 'update.php' )
									),
									'install-plugin_woocommerce'
								);
								/* translators: Notice message */
								$admin_notice_content = sprintf( esc_html__( '%1$sBuy Once or Subscribe for WooCommerce Subscriptions is inactive.%2$s The %3$sWooCommerce plugin%4$s must be active for Buy Once or Subscribe for WooCommerce Subscriptions to work. Please %5$sinstall & activate WooCommerce &raquo;%6$s', 'bos4w' ), '<strong>', '</strong>', '<a href="http://wordpress.org/extend/plugins/woocommerce/">', '</a>', '<a href="' . esc_url( $install_url ) . '">', '</a>' );
								/* translators: Notice HTML */
								printf( '<div class="notice notice-error is-dismissible"><p>%s</p></div>', wp_kses_post( $admin_notice_content ) );
							}
						);

						return;
					}

					if ( ! $woos_need && ! $woos_core_need ) {
						add_action(
							'admin_notices',
							function () {
								/* translators: Notice message */
								$admin_notice_content = sprintf( esc_html__( '%1$sBuy Once or Subscribe for WooCommerce Subscriptions is inactive.%2$s Please install and activate %3$sWooCommerce Subscriptions plugin%4$s for Buy Once or Subscribe for WooCommerce Subscriptions to work.', 'bos4w' ), '<strong>', '</strong>', '<a href="https://woocommerce.com/products/woocommerce-subscriptions">', '</a>' );
								/* translators: Notice HTML */
								printf( '<div class="notice notice-error is-dismissible"><p>%s</p></div>', wp_kses_post( $admin_notice_content ) );
							}
						);

						return;
					}
				}
			}

			self::load_packages();
		}

		/**
		 * Loads packages after plugins_loaded hook.
		 *
		 * Each package should include an init file which loads the package so it can be used by core.
		 */
		protected static function load_packages() {
			foreach ( self::$packages as $package_name => $package_file ) {
				if ( ! self::check_if_package_exists( $package_file ) ) {
					self::if_is_missing_package( $package_name );
					continue;
				}

				self::load_the_file( self::$include_path . $package_file );
			}
		}

		/**
		 * Check for package
		 *
		 * @param string $package The package.
		 *
		 * @return bool
		 */
		public static function check_if_package_exists( $package ) {
			return file_exists( self::$include_path . $package );
		}

		/**
		 * Check if it's missing any package
		 *
		 * @param string $package The package.
		 */
		protected static function if_is_missing_package( $package ) {
			add_action(
				'admin_notices',
				function () use ( $package ) {
					?>
					<div class="notice notice-error">
						<p>
							<strong>
								<?php /* translators: %s: missing package */ ?>
								<?php echo sprintf( esc_html__( 'Missing the SForce %s package', 'bos4w' ), '' . esc_html( $package ) . '</code>' ); ?>
							</strong>
							<br>
							<?php
							echo esc_html__( 'Your installation of SForce is incomplete.', 'bos4w' );
							?>
						</p>
					</div>
					<?php
				}
			);
		}
	}
}

if ( defined( 'BOS_IS_PLUGIN' ) && BOS_IS_PLUGIN ) {
	/**
	 * Compatible with HPOS
	 */
	add_action(
		'before_woocommerce_init',
		function () {
			if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', 'buy-once-or-subscribe-for-woocommerce-subscriptions/buy-once-or-subscribe-for-woocommerce-subscriptions.php', true );
			}
		}
	);
}

if ( ! function_exists( 'bos_cart_has_bos_product' ) ) {
	/**
	 * Check if the cart contains a Buy Once or Subscribe product.
	 *
	 * This function iterates through the items in the WooCommerce cart and
	 * checks if any of them are identified as Buy Once or Subscribe products.
	 *
	 * @return bool True if the cart contains at least one Buy Once or Subscribe product, false otherwise.
	 */
	function bos_cart_has_bos_product() {
		$cart_items = WC()->cart->get_cart();
		foreach ( $cart_items as $cart_item ) {
			if ( bos_cart_item_is_bos_product( $cart_item ) ) {
				return true;
			}
		}
		return false;
	}
}

if ( ! function_exists( 'bos_cart_item_is_bos_product' ) ) {
	/**
	 * Checks if the given cart item is a BOS product by verifying the presence of BOS-specific data.
	 *
	 * @param array $cart_item An array representing the cart item to check.
	 *
	 * @return mixed Returns the BOS-specific data if it exists in the cart item, otherwise returns an empty array.
	 */
	function bos_cart_item_is_bos_product( $cart_item ) {
		return isset( $cart_item['bos4w_data'] ) ? $cart_item['bos4w_data'] : array();
	}
}

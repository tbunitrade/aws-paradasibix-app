<?php
/**
 * Admin Product settings
 *
 * @package Buy Once or Subscribe for WooCommerce Subscriptions
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit;
}

if ( ! class_exists( 'BOS4W_Front_End' ) ) {
	/**
	 * BOS4W_Front_End class is responsible for handling the frontend functionality of the plugin in the WooCommerce store.
	 */
	class BOS4W_Front_End {

		/**
		 * BOS4W_Front_End constructor.
		 */
		public function __construct() {
			add_action( 'wp_enqueue_scripts', array( $this, 'bos4w_frontend_scripts' ) );
			add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'bos4w_show_options' ), 100 );
			add_filter( 'woocommerce_product_add_to_cart_text', array( $this, 'bos4w_add_to_cart_text' ), 10, 2 );
			add_filter( 'woocommerce_product_add_to_cart_url', array( $this, 'bos4w_add_to_cart_url' ), 10, 2 );
			add_filter( 'woocommerce_product_supports', array( $this, 'bos4w_supports_ajax_add_to_cart' ), 10, 3 );
			add_filter( 'woocommerce_available_variation', array( $this, 'bos4w_options_to_variation_data' ), 1, 3 );
			add_filter( 'woocommerce_available_variation', array( $this, 'bos4w_add_variation_discount_price' ), 0, 3 );
			add_filter( 'woocommerce_get_price_html', array( $this, 'bos4w_display_the_discount' ), 9999, 2 );
			add_filter( 'woocommerce_coupon_is_valid_for_product', array( $this, 'bos4w_allow_coupon_on_subscription' ), 5, 4 );
			add_action( 'wp_ajax_bos4w_update_cart_item', array( $this, 'bos4w_update_cart_item' ) );
			add_action( 'wp_ajax_nopriv_bos4w_update_cart_item', array( $this, 'bos4w_update_cart_item' ) );
		}

		/**
		 * Updates a cart item with subscription plan, discount, and pricing details.
		 *
		 * This method handles an AJAX request to update the cart item details
		 * based on the provided parameters such as selected subscription plan, discount,
		 * and price. If the subscription discount or details are adjusted, the cart item
		 * is updated accordingly in the cart and total calculations are refreshed.
		 *
		 * @return void This method does not return a value but sends a JSON response
		 *              with success or error status depending on the operation result.
		 */
		public function bos4w_update_cart_item() {
			check_ajax_referer( 'wpr-bos4w-nonce', 'nonce' );

			if ( isset( $_REQUEST['bos4w_cart_item'] ) && isset( $_REQUEST['bos4w_cart_item_key'] ) ) {
				$cart_item_id             = isset( $_REQUEST['bos4w_cart_item_id'] ) ? absint( $_REQUEST['bos4w_cart_item_id'] ) : '';
				$bos4w_cart_item_key      = isset( $_REQUEST['bos4w_cart_item_key'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['bos4w_cart_item_key'] ) ) : '';
				$bos4w_cart_item_plan     = isset( $_REQUEST['bos4w_cart_item'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['bos4w_cart_item'] ) ) : '';
				$bos4w_cart_item_discount = isset( $_REQUEST['bos4w_cart_item_discount'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['bos4w_cart_item_discount'] ) ) : '';
				$bos4w_cart_item_price    = isset( $_REQUEST['bos4w_cart_item_price'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['bos4w_cart_item_price'] ) ) : '';
				$bos4w_cart_item_type     = isset( $_REQUEST['bos4w_cart_item_type'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['bos4w_cart_item_type'] ) ) : '';

				$cart_item                = WC()->cart->get_cart_item( $cart_item_id );
				$product                  = wc_get_product( $cart_item['product_id'] );

				foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
					if ( $cart_item_key == $bos4w_cart_item_key ) {
						$selected_plan = $bos4w_cart_item_plan;
						$variation_id  = $cart_item['variation_id'] > 0 ? $cart_item['variation_id'] : 0;
						$product_id    = $cart_item['product_id'];

						if ( empty( $bos4w_cart_item_discount ) ) {
							unset( $cart_item['bos4w_data'] );
							unset( $cart_item['_subscription_period'] );
							unset( $cart_item['_subscription_period_interval'] );
						} else {
							$req_id  = $variation_id > 0 ? $variation_id : $product_id;
							$product = wc_get_product( $req_id );

							$product_main = $product;
							if ( $variation_id > 0 ) {
								$product_main = wc_get_product( $product_id );
							}

							$plan_data = explode( '_', esc_attr( $selected_plan ) );
							$discount  = end( $plan_data );

							if ( ( new BOS4W_Cart_Options() )->bos4w_validate_plan_selection( $product_id, $selected_plan, $variation_id ) ) {
								if ( $product->is_type( array( 'composite', 'bundle' ) ) ) {
									$item_price = self::bos4w_get_product_price( $product );
								} else {
									/**
									 * Filter bos_use_regular_price.
									 *
									 * @param bool false
									 *
									 * @since 2.0.2
									 */
									$display_the_price = apply_filters( 'bos_use_regular_price', false );

									$item_price = ! $display_the_price ? $product->get_price() : $product->get_regular_price();
								}

								// Determine if a fixed price is set at the variation, product, or global level.
								$fixed_price = false;

								// Check if the variation has a fixed price.
								if ( $variation_id > 0 ) {
									$fixed_price = $product->get_meta( 'bos4w_use_variation_fixed_price_' . $variation_id, true ) === 'yes';
								}

								// If not set at variation level, check at the product level.
								if ( ! $fixed_price && ! $product->get_meta( '_bos4w_saved_variation_subs', true ) ) {
									$fixed_price = $product_main->get_meta( '_bos4w_use_fixed_price', true ) === 'yes';
								}

								// If still not set, check at the global level.
								if ( ! $fixed_price && ! $product_main->get_meta( '_bos4w_saved_variation_subs', true ) ) {
									$global_plans = $this->product_has_global_subscription_plans( $product );
									if ( ! empty( $global_plans ) ) {
										$fixed_price = false;
									}
								}

								// Apply the discount based on the determined fixed price setting.
								if ( isset( $discount ) && $discount > 0 ) {
									if ( $fixed_price ) {
										$discounted_price = $item_price - $discount;
									} else {
										$discounted_price = $item_price - ( $item_price * ( $discount / 100 ) );
									}
								} else {
									$discounted_price = $item_price;
								}

								if ( isset( $bos4w_cart_item_price ) && ! empty( $bos4w_cart_item_price ) && $product->is_type( array( 'bundle', 'composite' ) ) ) {
									/**
									 * Filter formatted price.
									 *
									 * @param float $formatted_price Formatted price.
									 * @param float $price Un-formatted price.
									 * @param int $decimals Number of decimals.
									 * @param string $decimal_separator Decimal separator.
									 * @param string $thousand_separator Thousand separator.
									 * @param float|string $original_price Original price as float, or empty string.
									 *
									 * @since 5.0.0.
									 */
									$discounted_price = apply_filters( 'formatted_woocommerce_price', number_format( sanitize_text_field( wp_unslash( $bos4w_cart_item_price ) ), wc_get_price_decimals(), wc_get_price_decimal_separator(), wc_get_price_thousand_separator() ), sanitize_text_field( wp_unslash( $bos4w_cart_item_price ) ), wc_get_price_decimals(), wc_get_price_decimal_separator(), wc_get_price_thousand_separator(), sanitize_text_field( wp_unslash( $bos4w_cart_item_price ) ) );
								}

								$cart_item['bos4w_data'] = array(
									'selected_subscription' => $selected_plan,
									'discounted_price'      => $discounted_price,
								);

								$cart_item['_subscription_period']          = $plan_data[1];
								$cart_item['_subscription_period_interval'] = $plan_data[0];
							}
						}

						WC()->cart->cart_contents[ $cart_item_key ] = $cart_item;

						break;
					}
				}
			} else {
				wp_send_json_error(
					array(
						'html' => sprintf( '<div class="ssd-display-notification">%s</div>', esc_html__( 'Something went wrong. Please try again.', 'bos4w' ) ),
					)
				);
			}

			WC()->cart->set_session();
			WC()->cart->calculate_totals();

			wp_send_json_success(
				array(
					'html' => sprintf( '<div class="ssd-display-notification">%s</div>', esc_html__( 'Cart item updated.', 'bos4w' ) ),
				)
			);
		}

		/**
		 * Load scripts
		 */
		public function bos4w_frontend_scripts() {
			$plugin_data = get_plugin_data( SFORCE_PLUGIN_FILE );
			wp_enqueue_style( 'bos4w-frontend', BOS_FUNC_URL . 'assets/css/front-end.css', array(), $plugin_data['Version'] );
			wp_enqueue_script( 'bos4w-single-product', BOS_FUNC_URL . 'assets/js/single-product.js', array( 'jquery' ), $plugin_data['Version'], true );

			$sign_up = esc_html__( 'Sign up now', 'bos4w' );
			if ( class_exists( 'WC_Subscriptions_Product' ) ) {
				$sign_up = WC_Subscriptions_Product::get_add_to_cart_text();
			}

			if ( is_singular( 'product' ) ) {
				global $post;
				$product_id = $post->ID;
				$product = wc_get_product( $product_id );

				wp_localize_script(
					'bos4w-single-product',
					'wpr_bos4w_js',
					array(
						'ajax_url'          => admin_url( 'admin-ajax.php' ),
						'nonce'             => wp_create_nonce( 'wpr-bos4w-nonce' ),
						'bos4w_buy_now'     => $product->single_add_to_cart_text(),
						'bos4w_subscribe'   => $sign_up,
						'bos4w_is_product'  => is_product(),
						'decimal_separator' => wc_get_price_decimal_separator(),
						'bos4w_display_text' => $this->get_display_title_text( $product ),
					)
				);
			} else {
				wp_localize_script(
					'bos4w-single-product',
					'wpr_bos4w_js',
					array(
						'ajax_url' => admin_url( 'admin-ajax.php' ),
						'nonce'    => wp_create_nonce( 'wpr-bos4w-nonce' ),
					)
				);
			}
		}

		/**
		 * Display the options
		 */
		public function bos4w_show_options() {
			global $product;

			if ( ! $this->bos4w_display_plans( $product ) ) {
				return;
			}

			$all_plans = $this->get_discounted_prices( $product );

			if ( ! $all_plans ) {
				return;
			}

			wp_enqueue_script( 'bos4w-single-product' );

			$use_fixed_price = $product->get_meta( '_bos4w_use_fixed_price' );

			$plan_type = '';
			if ( $product->is_type( 'variable' ) ) {
				$plan_type = 'variation';
			}

			if ( $this->bos4w_max_discount( $all_plans ) > 0 ) {
				if ( $use_fixed_price && $product->get_meta( '_bos4w_saved_subs', true ) ) {
					$subscribe_and_save = sprintf( '%s %s', esc_html__( 'Subscribe and save up to', 'bos4w' ), strip_tags( wc_price( $this->bos4w_max_discount( $all_plans ) ) ) );
				} else {
					$subscribe_and_save = sprintf( '%s %s&#37;', esc_html__( 'Subscribe and save up to', 'bos4w' ), $this->bos4w_max_discount( $all_plans ) );
				}
			} else {
				$subscribe_and_save = esc_html__( 'Subscribe', 'bos4w' );
			}

			/**
			 * Filter bos4w_and_save_up_to_text
			 *
			 * @param string $subscribe_and_save
			 * @param string $product_plans
			 *
			 * @since 3.1.0
			 */
			$subscribe_and_save = apply_filters( 'bos4w_and_save_up_to_text', $subscribe_and_save, $this->bos4w_max_discount( $all_plans ) );

			if ( get_option( 'bos4w_and_save_up_to_text' ) ) {
				$saved_text         = get_option( 'bos4w_and_save_up_to_text' );
				$subscribe_and_save = esc_html( $saved_text );
				if ( self::is_valid_sprintf_format( $saved_text ) ) {
					if ( $use_fixed_price && $product->get_meta( '_bos4w_saved_subs', true ) ) {
						$discount = strip_tags( wc_price( $this->bos4w_max_discount( $all_plans ) ) );
					} else {
						$discount = $this->bos4w_max_discount( $all_plans ) . '%';
					}

					$subscribe_and_save = sprintf( $saved_text, $discount );
				}
			}

			/**
			 * Filter bos4w_one_time_buy_text
			 *
			 * @param string $subscribe_and_save
			 *
			 * @since 3.1.0
			 */
			$one_time_buy = apply_filters( 'bos4w_one_time_buy_text', esc_html__( 'One-time purchase', 'bos4w' ) );

			$bos4w_one_time_buy_text = get_option( 'bos4w_one_time_buy_text' );
			if ( $bos4w_one_time_buy_text ) {
				$one_time_buy = esc_html( $bos4w_one_time_buy_text );
			}

			/**
			 * Filter bos4w_dropdown_label_text
			 *
			 * @param string Label text
			 *
			 * @since 3.2.0
			 */
			$dropdown_label = apply_filters( 'bos4w_dropdown_label_text', esc_html__( 'Frequency:', 'bos4w' ) );

			$bos4w_dropdown_label_text = get_option( 'bos4w_dropdown_label_text' );
			if ( $bos4w_dropdown_label_text ) {
				$dropdown_label = esc_html( $bos4w_dropdown_label_text );
			}

			return wc_get_template(
				'single-product/display-subscription-plans.php',
				array(
					'product'            => $product,
					'product_id'         => $product->get_parent_id() ? absint( $product->get_parent_id() ) : absint( $product->get_id() ),
					'plan_options'       => $all_plans,
					'dropdown_label'     => esc_html( $dropdown_label ),
					'one_time'           => esc_html( $one_time_buy ),
					'use_fixed_price'    => esc_attr( $use_fixed_price ),
					'subscribe_and_save' => $subscribe_and_save,
					'display_text'       => $this->get_display_title_text( $product ),
					'plan_type'          => $plan_type,
					'plan_no'            => $this->get_the_numbe_of_plans( $this->product_has_subscription_plans( $product ) ),
				),
				false,
				BOS_FUNC_PATH . '/templates/'
			);
		}

		/**
		 * Display label text
		 *
		 * @param object $product Product Object.
		 *
		 * @return string
		 */
		public function get_display_title_text( $product ) {
			$display_text = $product->get_meta( '_bos4w_subscription_title', true );
			if ( ! $product->get_meta( '_bos4w_saved_subs', true ) && $this->product_has_global_subscription_plans( $product ) ) {
				$subscriptions_title = get_option( 'bos4w_global_subscription_title' );
				$display_text        = esc_html( $subscriptions_title );
			}

			return $display_text ? esc_html( $display_text ) : esc_html__( 'Choose frequency', 'bos4w' );
		}

		/**
		 * Add to cart button text
		 *
		 * @param string $button_text Button text label.
		 * @param object $product Product object.
		 *
		 * @return false|mixed|void
		 */
		public function bos4w_single_add_to_cart_text( $button_text, $product ) {
			if ( $this->get_discounted_prices( $product ) ) {
				$button_text = get_option( WC_Subscriptions_Admin::$option_prefix . '_add_to_cart_button_text', esc_html__( 'Sign up', 'bos4w' ) );
			}

			return $button_text;
		}

		/**
		 * Add to cart button text
		 *
		 * @param string $button_text Button text label.
		 * @param object $product Product object.
		 *
		 * @return mixed|void
		 */
		public function bos4w_add_to_cart_text( $button_text, $product ) {
			if ( $this->get_discounted_prices( $product ) && $product->is_purchasable() && $product->is_in_stock() ) {
				$button_text = esc_html__( 'Select options', 'bos4w' );
				/**
				 * Filter bos4w_add_to_cart_text
				 *
				 * @param string $button_text
				 * @param object $product
				 *
				 * @since 1.0.0
				 */
				$button_text = apply_filters( 'bos4w_add_to_cart_text', $button_text, $product );

				$bos4w_add_to_cart_text = get_option( 'bos4w_add_to_cart_text' );
				if ( $bos4w_add_to_cart_text ) {
					$button_text = esc_html( $bos4w_add_to_cart_text );
				}
			}

			return $button_text;
		}

		/**
		 * Add to cart button URL
		 *
		 * @param string $url The URL.
		 * @param object $product Product Object.
		 *
		 * @return mixed
		 */
		public function bos4w_add_to_cart_url( $url, $product ) {
			if ( $this->get_discounted_prices( $product ) && $product->is_purchasable() && $product->is_in_stock() ) {
				$url = esc_url( $product->get_permalink() );
			}

			return $url;
		}

		/**
		 * Add to cart button ajax
		 *
		 * @param boolean $supports Boolean value.
		 * @param string  $feature String.
		 * @param object  $product Product object.
		 *
		 * @return false|mixed
		 */
		public function bos4w_supports_ajax_add_to_cart( $supports, $feature, $product ) {
			if ( 'ajax_add_to_cart' === $feature ) {
				if ( $this->get_discounted_prices( $product ) ) {
					$supports = false;
				}
			}

			return $supports;
		}

		/**
		 * Get variations data
		 *
		 * @param array  $variation_data Variation array data.
		 * @param object $variable_product Variable product data.
		 * @param object $variation_product Variation product data.
		 *
		 * @return mixed
		 */
		public function bos4w_options_to_variation_data( $variation_data, $variable_product, $variation_product ) {
			if ( ! $this->bos4w_display_plans( $variable_product ) ) {
				return $variation_data;
			}

			if ( ! $this->product_has_subscription_plans( $variable_product ) ) {
				return $variation_data;
			}

			$variation_data['bos4w_subscription_title'] = $variation_product->get_meta( '_bos4w_subscription_title', true );

			// Get the plans for this variation.
			$variation_id = $variation_product->get_id();
			$plans        = $this->product_has_subscription_plans( $variable_product );

			// Initialize variables to hold the maximum discount and related information.
			$max_discount       = 0;
			$discount_type      = '';
			$subscribe_and_save = esc_html__( 'Subscribe', 'bos4w' );

			if ( isset( $plans['variation'][ $variation_id ] ) ) {
				// Variation-level discounts.
				$variation_plans = $plans['variation'][ $variation_id ];

				foreach ( $variation_plans as $plan ) {
					if ( isset( $plan['subscription_price'] ) && $plan['subscription_price'] > 0 ) {
						$discount_type = 'fixed_price';
						$max_discount  = max( $max_discount, $plan['subscription_price'] );
					} elseif ( isset( $plan['subscription_discount'] ) && $plan['subscription_discount'] > 0 ) {
						$discount_type = 'percentage';
						$max_discount  = max( $max_discount, $plan['subscription_discount'] );
					}
				}
			} elseif ( isset( $plans['product'] ) ) {
				// Product-level discounts.
				$product_plans = $plans['product'];
				$use_fixed_price = $variable_product->get_meta( '_bos4w_use_fixed_price' );

				foreach ( $product_plans as $plan ) {
					if ( $use_fixed_price ) {
						$discount_type = 'fixed_price';
						$max_discount = max( $max_discount, $plan['subscription_price'] );
					} else {
						$discount_type = 'percentage';
						$max_discount = max( $max_discount, $plan['subscription_discount'] );
					}
				}
			} elseif ( isset( $plans['global'] ) ) {
				// Global discounts.
				$global_plans = $plans['global'];
				$global_use_fixed_price = get_option( 'bos4w_global_use_fixed_price' ) === 'yes';

				foreach ( $global_plans as $plan ) {
					if ( $global_use_fixed_price ) {
						$discount_type = 'fixed_price';
						$max_discount = max( $max_discount, $plan['subscription_price'] );
					} else {
						$discount_type = 'percentage';
						$max_discount = max( $max_discount, $plan['subscription_discount'] );
					}
				}
			}

			// Generate the subscribe and save text.
			if ( $max_discount > 0 ) {
				if ( 'fixed_price' === $discount_type ) {
					$subscribe_and_save = sprintf( '%s %s', esc_html__( 'Subscribe and save', 'bos4w' ), strip_tags( wc_price( $max_discount ) ) );
				} else {
					$subscribe_and_save = sprintf( '%s %s&#37;', esc_html__( 'Subscribe and save', 'bos4w' ), $max_discount );
				}
			}

			/**
			 *  Indicates whether the user has chosen to subscribe and save on a purchase.
			 *  This variable is typically set to true when a user opts to subscribe to a service
			 *  or product, usually at a discounted rate, instead of making a one-time purchase.
			 *
			 * @var boolean $subscribe_and_save
			 *
			 * @since 4.6.0
			 */
			$subscribe_and_save = apply_filters( 'bos4w_and_save_up_to_text', $subscribe_and_save, $max_discount );

			// Check for any custom "save" text from the options.
			if ( get_option( 'bos4w_and_save_up_to_text' ) ) {
				$saved_text         = get_option( 'bos4w_and_save_up_to_text' );
				$subscribe_and_save = esc_html( $saved_text );
				if ( self::is_valid_sprintf_format( $saved_text ) ) {
					if ( 'fixed_price' === $discount_type ) {
						$discount = strip_tags( wc_price( $max_discount ) );
					} else {
						$discount = $max_discount . '%';
					}

					$subscribe_and_save = sprintf( $saved_text, $discount );
				}
			}

			// Store the discount information in the variation data.
			$variation_data['bos4w_max_discount']  = $max_discount;
			$variation_data['bos4w_discount_type'] = $discount_type;
			$variation_data['bos4w_discount_text'] = $subscribe_and_save;

			$is_current_product = false;

			if ( is_object( $variable_product ) && is_a( $variable_product, 'WC_Product' ) && ! doing_action( 'wc_ajax_woocommerce_show_composited_product' ) ) {
				$is_current_product = $variable_product->get_id();
			} elseif ( doing_action( 'wc_ajax_get_variation' ) ) {
				$is_current_product = true;
			}

			if ( ! $is_current_product ) {
				return $variation_data;
			}

			return $variation_data;
		}

		/**
		 * Add variation discount price
		 *
		 * @param array  $variation_data Variation array data.
		 * @param object $product Variable product data.
		 * @param object $variation Variation product data.
		 *
		 * @return mixed
		 */
		public function bos4w_add_variation_discount_price( $variation_data, $product, $variation ) {
			$plans = $this->product_has_subscription_plans( $product );
			if ( ! $plans ) {
				return $variation_data;
			}

			if ( class_exists( 'WC_PB_Product_Prices' ) && WC_PB_Product_Prices::is_bundled_pricing_context( $product ) ) {
				return $variation_data;
			}

			if ( class_exists( 'WC_CP_Products' ) && WC_CP_Products::is_component_option_pricing_context( $product ) ) {
				return $variation_data;
			}

			if ( ! $this->bos4w_display_plans( $product ) ) {
				return $variation_data;
			}

			// Priority: Variation > Product > Global.
			if ( ! empty( $plans['variation'] ) && isset( $plans['variation'][ $variation->get_id() ] ) ) {
				$product_plans = $plans['variation'][ $variation->get_id() ];
			} elseif ( ! empty( $plans['product'] ) ) {
				$product_plans = $plans['product'];
			} elseif ( ! empty( $plans['global'] ) ) {
				$product_plans = $plans['global'];
			} else {
				$product_plans = array();
			}

			foreach ( $product_plans as $key => $plan ) {
				$display_discount = '';

				$discounted_price = self::bos4w_get_product_price( $variation );

				$plan['subscription_discount'] = isset( $plan['subscription_discount'] ) && ! empty( trim( $plan['subscription_discount'] ) ) ? $plan['subscription_discount'] : 0;
				$plan['subscription_price']    = isset( $plan['subscription_price'] ) && ! empty( trim( $plan['subscription_price'] ) ) ? $plan['subscription_price'] : 0;

				if ( isset( $plan['subscription_discount'] ) && $plan['subscription_discount'] > 0 ) {
					$display_discount = sprintf( ' (%s&#37; %s)', esc_attr( $plan['subscription_discount'] ), esc_html__( 'off', 'bos4w' ) );
					$discounted_price = wc_format_decimal( $discounted_price - ( $discounted_price * ( (float) wc_format_decimal( $plan['subscription_discount'] ) / 100 ) ), wc_get_price_decimals() );
				}
				if ( isset( $plan['subscription_price'] ) && $plan['subscription_price'] > 0 ) {
					$display_discount = sprintf( ' (%s %s)', esc_attr( strip_tags( wc_price( $plan['subscription_price'] ) ) ), esc_html__( 'off', 'bos4w' ) );
					$discounted_price = wc_format_decimal( $discounted_price - (float) wc_format_decimal( $plan['subscription_price'] ), wc_get_price_decimals() );
				}
				$period_interval = wcs_get_subscription_period_strings( $plan['subscription_period_interval'], $plan['subscription_period'] );

				/**
				 * Filter ssd_subscription_plan_display
				 *
				 * @param string Interval and discount display.
				 * @param string $period_interval Interval display.
				 * @param string $discounted_price Discounted Price display.
				 * @param string $display_discount The discount display.
				 *
				 * @since 2.0.1
				 */
				$display = apply_filters(
					'ssd_subscription_plan_display',
					sprintf(
					/* translators: %s: interval & discount */
						esc_html__( 'Every %1$s for %2$s %3$s', 'bos4w' ),
						$period_interval,
						wc_price( $discounted_price ),
						$display_discount
					),
					$period_interval,
					wc_price( $discounted_price ),
					$display_discount
				);

				$ssd_subscription_plan_display = get_option( 'ssd_subscription_plan_display' );
				if ( $ssd_subscription_plan_display ) {
					if ( self::is_valid_sprintf_format( $ssd_subscription_plan_display ) ) {
						$display = sprintf(
						/* translators: %s: interval & discount */
							esc_html( $ssd_subscription_plan_display ),
							$period_interval,
							wc_price( $discounted_price ),
							$display_discount
						);
					} else {
						$display = esc_html( $ssd_subscription_plan_display );
					}
				}

				$variation_data['bos4w_discounted_price'][] = array(
					'subscription_period_interval' => $plan['subscription_period_interval'],
					'subscription_period'          => $plan['subscription_period'],
					'subscription_discount'        => ! empty( trim( $plan['subscription_discount'] ) ) ? $plan['subscription_discount'] : 0,
					'subscription_price'           => ! empty( trim( $plan['subscription_price'] ) ) ? $plan['subscription_price'] : 0,
					'discounted_price'             => wc_price( $discounted_price ),
					'float_discounted'             => floatval( $discounted_price ),
					'display_discount'             => $display_discount,
					'period_interval'              => $period_interval,
					'display'                      => $display,
				);
			}

			return $variation_data;
		}

		/**
		 * Display the discount range
		 *
		 * @param string $price_html Price HTML.
		 * @param object $product Product object.
		 *
		 * @return mixed
		 */
		public function bos4w_display_the_discount( $price_html, $product ) {
			if ( class_exists( 'WC_PB_Product_Prices' ) && WC_PB_Product_Prices::is_bundled_pricing_context( $product ) ) {
				return $price_html;
			}

			if ( class_exists( 'WC_CP_Products' ) && WC_CP_Products::is_component_option_pricing_context( $product ) ) {
				return $price_html;
			}

			if ( ! $this->bos4w_display_plans( $product ) ) {
				return $price_html;
			}

			$use_fixed_price = $product->get_meta( '_bos4w_use_fixed_price' );

			$all_plans = $this->get_discounted_prices( $product );

			if ( $all_plans ) {
				if ( $this->bos4w_max_discount( $all_plans ) > 0 ) {
					// Check if it's a variable product.
					if ( $product->is_type( 'variable' ) ) {
						$the_subscribe_text = sprintf( ' - %s', esc_html__( 'or subscribe', 'bos4w' ) );
					} else { // For simple products.
						if ( $use_fixed_price && $product->get_meta( '_bos4w_saved_subs', true ) ) {
							$the_subscribe_text = sprintf( ' - %s %s', esc_html__( 'or subscribe and save up to', 'bos4w' ), strip_tags( wc_price( $this->bos4w_max_discount( $all_plans ) ) ) );
						} else {
							$the_subscribe_text = sprintf( ' - %s %s&#37;', esc_html__( 'or subscribe and save up to', 'bos4w' ), esc_attr( $this->bos4w_max_discount( $all_plans ) ) );
						}
					}
				} else {
					$the_subscribe_text = sprintf( ' - %s', esc_html__( 'or subscribe', 'bos4w' ) );
				}

				if ( get_option( 'bos4w_subscription_text_display' ) ) {
					$subscription_text = get_option( 'bos4w_subscription_text_display' );
					$subscription_text = esc_html( $subscription_text );
					if ( self::is_valid_sprintf_format( $subscription_text ) ) {
						if ( $use_fixed_price && $product->get_meta( '_bos4w_saved_subs', true ) ) {
							$discount = strip_tags( wc_price( $this->bos4w_max_discount( $all_plans ) ) );
						} else {
							$discount = esc_attr( $this->bos4w_max_discount( $all_plans ) ) . '%';
						}

						$price_html .= '<span class="bos4w-or-subscribe"> - ' . sprintf( $subscription_text, $discount ) . '</span>';
					} else {
						$price_html .= '<span class="bos4w-or-subscribe"> - ' . $subscription_text . '</span>';
					}
				} else {
					/**
					 * Filter bos4w_subscription_text_display
					 *
					 * @param string $the_subscribe_text The subscribe display option text.
					 *
					 * @since 2.0.2
					 */
					$price_html .= '<span class="bos4w-or-subscribe"> ' . apply_filters( 'bos4w_subscription_text_display', $the_subscribe_text ) . '</span>';
				}
			}

			return $price_html;
		}

		/**
		 * Fetch max discount
		 *
		 * @param array $plans Plans array.
		 *
		 * @return mixed
		 */
		public function bos4w_max_discount( $plans ) {
			$discount = array();

			foreach ( $plans as $plan_type => $plan_set ) {
				if ( ! isset( $plan_set['subscription_period'] ) ) {

					foreach ( $plan_set as $single_plan ) {

						if ( isset( $single_plan['subscription_price'] ) && ! empty( $single_plan['subscription_price'] ) ) {
							$discount[] = (float) $single_plan['subscription_price'];
						}
						if ( isset( $single_plan['subscription_discount'] ) && ! empty( $single_plan['subscription_discount'] ) ) {
							$discount[] = (float) $single_plan['subscription_discount'];
						}
					}
				} else {
					if ( isset( $plan_set['subscription_price'] ) ) {
						$discount[] = $plan_set['subscription_price'];
					}
					if ( isset( $plan_set['subscription_discount'] ) ) {
						$discount[] = $plan_set['subscription_discount'];
					}
				}
			}

			return $discount ? max( $discount ) : 0;
		}

		/**
		 * Check if a product has subscription plans
		 *
		 * @param object $product Product object data.
		 *
		 * @return array
		 */
		public function product_has_subscription_plans( $product ) {
			$display_plans = array();

			if ( $product->is_type( 'variable' ) ) {
				$variations = $product->get_children();
				foreach ( $variations as $variation_id ) {
					$variation = wc_get_product( $variation_id );
					$variation_plans = $variation->get_meta( '_bos4w_saved_variation_subs', true );
					if ( $variation_plans ) {
						$display_plans['variation'][ $variation_id ] = $variation_plans;
					}
				}
			}

			$product_plans = $product->get_meta( '_bos4w_saved_subs', true );
			if ( $product_plans ) {
				$display_plans['product'] = $product_plans;
			}

			$global_plans = $this->product_has_global_subscription_plans( $product );
			if ( $global_plans ) {
				$display_plans['global'] = $global_plans;
			}

			return $display_plans;
		}

		/**
		 * Get global discounts
		 *
		 * @param object $product Product Object.
		 *
		 * @return false|mixed|void
		 */
		public function product_has_global_subscription_plans( $product ) {
			$display_plans = get_option( 'bos4w_global_saved_subs' );

			if ( $display_plans ) {
				foreach ( $display_plans as $entry => $plan ) {
					if ( isset( $plan['product_cat'] ) && $plan['product_cat'] > 0 && ! has_term( $plan['product_cat'], 'product_cat', $product->get_id() ) ) {
						unset( $display_plans[ $entry ] );
					}
				}
				$display_plans = array_merge( $display_plans );
			}

			return $display_plans;
		}

		/**
		 * Should the plan be displayed
		 *
		 * @param object $product Product object.
		 *
		 * @return mixed
		 */
		public function bos4w_display_plans( $product ) {
			if ( 'simple' === $product->get_type() || 'variable' === $product->get_type() || 'composite' === $product->get_type() || 'bundle' === $product->get_type() ) {
				return true;
			}

			return false;
		}

		/**
		 * Return product price
		 *
		 * @param WC_Product $product Product object.
		 *
		 * @return float|mixed|string
		 */
		public static function bos4w_get_product_price( $product ) {
			/**
			 * Filter bos_use_regular_price.
			 *
			 * @param bool false
			 *
			 * @since 2.0.2
			 */
			$display_the_price = apply_filters( 'bos_use_regular_price', false );

			$selected_price = ! $display_the_price ? $product->get_price() : $product->get_regular_price();

			if ( wc_tax_enabled() ) {
				if ( 'incl' === get_option( 'woocommerce_tax_display_shop' ) ) {
					$return_price = wc_get_price_including_tax(
						$product,
						array(
							'qty' => 1,
							'price' => $selected_price,
						)
					);
				} elseif ( 'incl' !== get_option( 'woocommerce_tax_display_shop' ) ) {
					$return_price = wc_get_price_excluding_tax(
						$product,
						array(
							'qty' => 1,
							'price' => $selected_price,
						)
					);
				} else {
					if ( ! wc_prices_include_tax() ) {
						$return_price = wc_get_price_excluding_tax(
							$product,
							array(
								'qty' => 1,
								'price' => $selected_price,
							)
						);
					} else {
						$return_price = wc_get_price_including_tax(
							$product,
							array(
								'qty' => 1,
								'price' => $selected_price,
							)
						);
					}
				}
			} else {
				$return_price = $selected_price;
			}

			if ( $product->is_type( 'bundle' ) ) {
				if ( false === $product->contains( 'priced_individually' ) ) {
					if ( ! WC()->cart->display_prices_including_tax() ) {
						$return_price = ! $display_the_price ? $product->get_bundle_price_excluding_tax() : $product->get_bundle_regular_price_excluding_tax();
					} else {
						$return_price = ! $display_the_price ? $product->get_bundle_price_including_tax() : $product->get_bundle_regular_price_including_tax();
					}
				} else {
					$return_price = ! $display_the_price ? $product->get_bundle_price() : $product->get_bundle_regular_price();
				}
			}

			if ( $product->is_type( 'composite' ) ) {
				if ( false === $product->contains( 'priced_individually' ) ) {
					if ( ! WC()->cart->display_prices_including_tax() ) {
						$return_price = ! $display_the_price ? $product->get_composite_price_excluding_tax() : $product->get_composite_regular_price_excluding_tax();
					} else {
						$return_price = ! $display_the_price ? $product->get_composite_price_including_tax() : $product->get_composite_regular_price_including_tax();
					}
				} else {
					$return_price = ! $display_the_price ? $product->get_composite_price() : $product->get_composite_regular_price();
				}
			}

			return $return_price;
		}

		/**
		 * Apply recurring coupon for BOS products
		 *
		 * @param bool   $is_valid False/True value.
		 * @param object $product Product Object Data.
		 * @param object $coupon Coupon Object Data.
		 * @param array  $values Cart Data.
		 *
		 * @return bool|mixed
		 */
		public function bos4w_allow_coupon_on_subscription( $is_valid, $product, $coupon, $values ) {
			$coupon_type = $coupon->get_discount_type();
			if ( $product->is_type( 'variation' ) ) {
				$product_id = $product->get_parent_id();
			} else {
				$product_id = $product->get_id();
			}

			$product_cats = wc_get_product_cat_ids( $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id() );
			$product_ids  = array( $product->get_id(), $product->get_parent_id() );

			// Specific products get the discount.
			if ( count( $coupon->get_product_ids() ) && count( array_intersect( $product_ids, $coupon->get_product_ids() ) ) ) {
				$is_valid = true;
			}

			// Category discounts.
			if ( count( $coupon->get_product_categories() ) && count( array_intersect( $product_cats, $coupon->get_product_categories() ) ) ) {
				$is_valid = true;
			}

			// No product ids - all items discounted.
			if ( ! count( $coupon->get_product_ids() ) && ! count( $coupon->get_product_categories() ) ) {
				$is_valid = true;
			}

			// Specific product IDs excluded from the discount.
			if ( count( $coupon->get_excluded_product_ids() ) && count( array_intersect( $product_ids, $coupon->get_excluded_product_ids() ) ) ) {
				$is_valid = false;
			}

			// Specific categories excluded from the discount.
			if ( count( $coupon->get_excluded_product_categories() ) && count( array_intersect( $product_cats, $coupon->get_excluded_product_categories() ) ) ) {
				$is_valid = false;
			}

			// Sale Items excluded from discount.
			if ( $coupon->get_exclude_sale_items() && $product->is_on_sale() ) {
				$is_valid = false;
			}

			$product_data = wc_get_product( $product_id );

			$sale_countdown_enabled = get_option( 'bos4w_exclude_from_coupons' );

			if ( $sale_countdown_enabled && 'yes' === $sale_countdown_enabled ) {
				$has_bos_subscription = false;

				// Check for product-level subscriptions.
				if ( ! empty( $product_data->get_meta( '_bos4w_saved_subs' ) ) ) {
					$has_bos_subscription = true;
				}

				// Check for variation-level subscriptions (if applicable).
				if ( $product->is_type( 'variation' ) ) {
					$variation_id = $product->get_id();
					if ( ! empty( get_post_meta( $variation_id, '_bos4w_saved_variation_subs', true ) ) ) {
						$has_bos_subscription = true;
					}
				}

				// Check if the product is being purchased as a subscription (using BOS).
				$is_bos_subscription_purchase = isset( $values['bos4w_data'] );

				if ( $has_bos_subscription && $is_bos_subscription_purchase ) {
					$is_valid = false;
				}
			}

			if ( ( 'recurring_percent' === $coupon_type || 'recurring_fee' === $coupon_type ) && $product_data->get_meta( '_bos4w_saved_subs' ) && $is_valid ) {
				$is_valid = true;
			}

			return $is_valid;
		}

		/**
		 * Check if a string is a valid sprintf format.
		 *
		 * @param string $str The string to check.
		 *
		 * @return bool Returns true if the string is a valid sprintf format, false otherwise.
		 */
		public static function is_valid_sprintf_format( $str ) {
			return (bool) preg_match_all( '/%((\d+)\$)?[-\'^#0]*(\d+|\*)?(\.\d+|\.\*)?[%bcdeEufFgGosxX]/', $str );
		}

		/**
		 * Get the discounted prices for a product.
		 *
		 * This method calculates the discounted prices based on the available subscription plans for the product.
		 *
		 * @param object $product The Product object.
		 *
		 * @return bool|mixed Returns an array of discounted prices for each variation if the product is variable,
		 *                    or a single discounted price if the product is not variable.
		 *                    Returns false if there are no subscription plans available for the product.
		 */
		public function get_discounted_prices( $product ) {
			// Check if the product has subscription plans.
			$plans = $this->product_has_subscription_plans( $product );
			if ( ! $plans || empty( $plans ) ) {
				return false;
			}

			/**
			 * A boolean variable that determines whether the price should be displayed.
			 *
			 * When set to true, the system will show the price of the item.
			 * When set to false, the price will be hidden from the display.
			 *
			 * Typical use cases involve conditional displays based on user roles or
			 * specific application states where the price visibility needs to be controlled.
			 *
			 * @var bool
			 *
			 * @since 4.0.0
			 */
			$display_the_price = apply_filters( 'bos_use_regular_price', false );

			$original_price = ! $display_the_price ? $product->get_price() : $product->get_regular_price();

			$discounted_plans = array();

			// Check if the product is variable.
			if ( $product->is_type( 'variable' ) ) {
				if ( isset( $plans['variation'] ) && ! empty( $plans['variation'] ) ) {
					// Loop through each variation and calculate discounted prices.
					foreach ( $plans['variation'] as $variation_id => $variation_plans ) {
						$variation_product = wc_get_product( $variation_id );
						$variation_price = ! $display_the_price ? $variation_product->get_price() : $variation_product->get_regular_price();
						$discounted_plans[ $variation_id ] = $this->calculate_discounted_price( $variation_plans, $variation_price, $variation_product );
					}
				} elseif ( isset( $plans['product'] ) && ! empty( $plans['product'] ) ) {
					// Apply product-level plans to all variations.
					foreach ( $product->get_children() as $variation_id ) {
						$variation_product = wc_get_product( $variation_id );
						$variation_price = ! $display_the_price ? $variation_product->get_price() : $variation_product->get_regular_price();
						$discounted_plans[ $variation_id ] = $this->calculate_discounted_price( $plans['product'], $variation_price, $variation_product );
					}
				} elseif ( isset( $plans['global'] ) && ! empty( $plans['global'] ) ) {
					// Apply global-level plans to all variations.
					foreach ( $product->get_children() as $variation_id ) {
						$variation_product = wc_get_product( $variation_id );
						$variation_price = ! $display_the_price ? $variation_product->get_price() : $variation_product->get_regular_price();
						$discounted_plans[ $variation_id ] = $this->calculate_discounted_price( $plans['global'], $variation_price, $variation_product );
					}
				}
			} else {
				// For simple products, apply the plans directly.
				if ( isset( $plans['product'] ) && ! empty( $plans['product'] ) ) {
					$discounted_plans = $this->calculate_discounted_price( $plans['product'], $original_price, $product );
				} elseif ( isset( $plans['global'] ) && ! empty( $plans['global'] ) ) {
					$discounted_plans = $this->calculate_discounted_price( $plans['global'], $original_price, $product );
				}
			}

			return ! empty( $discounted_plans ) ? $discounted_plans : false;
		}

		/**
		 * Calculate the discounted price for a given product based on the plans and original price.
		 *
		 * @param array  $plans The list of plans to apply to the product.
		 * @param float  $original_price The original price of the product.
		 * @param object $product The product object.
		 *
		 * @return array The list of plans with their respective discounted prices.
		 */
		private function calculate_discounted_price( $plans, $original_price, $product ) {
			$discounted_plans = array();

			$discount_type  = '';
			$discount_value = 0;
			foreach ( $plans as $plan ) {
				$discounted_price = (float) $original_price;

				// Percentage discount.
				if ( isset( $plan['subscription_discount'] ) && $plan['subscription_discount'] > 0 ) {
					$discounted_price = (float) $original_price - ( (float) $original_price * ( (float) $plan['subscription_discount'] / 100 ) );
					$discount_type    = 'percentage_discount';
					$discount_value   = (float) $plan['subscription_discount'];
				}

				// Fixed value discount.
				if ( isset( $plan['subscription_price'] ) && $plan['subscription_price'] > 0 ) {
					$discounted_price = wc_format_decimal( $discounted_price - (float) wc_format_decimal( $plan['subscription_price'] ), wc_get_price_decimals() );
					$discount_type    = 'fixed_price';
					$discount_value   = (float) wc_format_decimal( $plan['subscription_price'] );
				}

				$plan['discounted_price']             = wc_format_decimal( $discounted_price, wc_get_price_decimals() );
				$plan['subscription_period_interval'] = isset( $plan['subscription_period_interval'] ) ? $plan['subscription_period_interval'] : '';
				$plan['subscription_period']          = isset( $plan['subscription_period'] ) ? $plan['subscription_period'] : '';
				$plan['discount_type']                = $discount_type;
				$plan['discount_value']               = $discount_value;

				$discounted_plans[] = $plan;
			}

			return $discounted_plans;
		}

		/**
		 * Retrieve the number of plans or entries from the provided input array.
		 *
		 * @param array $array The input array containing variations, products, or global entries.
		 *
		 * @return array|null An associative array containing the key type, the total count of entries,
		 *                    and variation entry counts, or null if no eligible key is found in the input array.
		 */
		public function get_the_numbe_of_plans( $array ) {
			$keys            = array( 'variation', 'product', 'global' );
			$variation_entry = array();

			foreach ( $keys as $key ) {
				if ( isset( $array[ $key ] ) ) {
					$entry_count = 0;

					if ( isset( $array['variation'] ) && is_array( $array['variation'] ) ) {
						foreach ( $array['variation'] as $variation_id => $entries ) {
							if ( is_array( $entries ) ) {
								$variation_entry[ $variation_id ] = count( $entries );
							} else {
								$variation_entry[ $variation_id ] = 0;
							}
						}
					} elseif ( is_array( $array[ $key ] ) ) {
						foreach ( $array[ $key ] as $sub_array ) {
							if ( is_array( $sub_array ) ) {
								$entry_count += count( $sub_array );
							} else {
								$entry_count ++;
							}
						}
					}

					return array(
						'key'             => $key,
						'count'           => $entry_count,
						'variation_entry' => $variation_entry,
					);
				}
			}

			return null;
		}
	}
}

new BOS4W_Front_End();

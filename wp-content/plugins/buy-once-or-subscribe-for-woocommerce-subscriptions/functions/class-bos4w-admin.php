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

if ( ! class_exists( 'BOS4W_Admin' ) ) {
	/**
	 * Class BOS4W_Admin
	 *
	 * This class handles the administration functionality for the BOS4W plugin.
	 */
	class BOS4W_Admin {
		/**
		 * BOS4W_Admin constructor.
		 */
		public function __construct() {
			add_action( 'admin_enqueue_scripts', array( $this, 'bos4w_admin_scripts' ) );
			add_action( 'woocommerce_product_data_tabs', array( $this, 'bos4w_product_data_tab' ) );
			add_action( 'woocommerce_product_data_panels', array( $this, 'bos4w_product_data_panel' ) );
			add_action( 'woocommerce_admin_process_product_object', array( $this, 'bos4w_save_subscription_data' ), 10, 1 );
			add_action( 'wp_ajax_wpr_add_sub', array( $this, 'wpr_add_subscription_type' ) );
			add_action( 'admin_notices', array( $this, 'bos4w_display_review_message' ) );
			add_action( 'wp_ajax_bos4w_dismiss_notice', array( $this, 'bos4w_do_dismiss' ) );
			add_filter( 'woocommerce_settings_tabs_array', array( $this, 'bos4w_settings_tab' ), 50, 1 );

			if ( defined( 'BOS_IS_PLUGIN' ) && BOS_IS_PLUGIN ) {
				add_filter( 'woocommerce_sections_bos4w_settings_tab', array( $this, 'bos4w_custom_settings' ), 10 );
				add_action( 'woocommerce_settings_tabs_bos4w_settings_tab', array( $this, 'bos4w_settings_tab_content' ) );
				add_action( 'woocommerce_update_options_bos4w_settings_tab', array( $this, 'bos4w_settings_tab_update' ) );
			} else {
				add_action( 'woocommerce_settings_subscription_force', array( $this, 'bos4w_sf_settings_tab_content' ) );
				add_action( 'woocommerce_update_options_subscription_force', array( $this, 'bos4w_settings_tab_update' ) );
			}

			/**
			 * Add BOS for variations
			 */
			add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'bos4w_add_subscription_fields_to_variations' ), 10, 3 );
			add_action( 'woocommerce_save_product_variation', array( $this, 'bos4w_save_variation_subscription_data' ), 10, 2 );
			add_action( 'woocommerce_process_product_meta_variable', array( $this, 'bos4w_save_product_variation_subscription_data' ), 10, 1 );
			add_action( 'wp_ajax_wpr_add_variable_sub', array( $this, 'wpr_add_variable_subscription_type' ) );
		}

		/**
		 * Add subscription fields to variations
		 *
		 * @param int                  $loop The iteration position in the loop.
		 * @param array                $variation_data An array of variation data.
		 * @param WC_Product_Variation $variation The variation object.
		 *
		 * @return void
		 */
		public function bos4w_add_subscription_fields_to_variations( $loop, $variation_data, $variation ) {
			$variation_id = $variation->ID;
			$use_fixed = get_post_meta( $variation_id, 'bos4w_use_variation_fixed_price_' . $variation_id, true );
			$title     = get_post_meta( $variation_id, '_bos4w_subscription_title', true );
			?>
			<div id="bos4w_data_<?php echo absint( $variation_id ); ?>" class="panel woocommerce_options_panel">
				<hr>
				<h2><?php echo esc_html__( 'Subscription plans', 'bos4w' ); ?></h2>
				<?php
				woocommerce_wp_textarea_input(
					array(
						'id'          => '_bos4w_subscription_title_' . $variation_id,
						'label'       => __( 'Title', 'bos4w' ),
						'description' => __( 'Text appears above the available purchase options when at least one frequency has been added below.', 'bos4w' ),
						'placeholder' => __( 'e.g. "Choose frequency"', 'bos4w' ),
						'desc_tip'    => true,
						'value'       => $title ? esc_html( $title ) : '',
					)
				);

				woocommerce_wp_checkbox(
					array(
						'id'            => 'bos4w_use_variation_fixed_price_' . $variation_id,
						'label'         => __( 'Use value discount', 'bos4w' ),
						'description'   => __( 'When checked, you will be able to enter a fixed value discount for each subscription plan.', 'bos4w' ),
						'desc_tip'      => true,
						'wrapper_class' => 'form-row',
						'value'         => $use_fixed ? esc_html( $use_fixed ) : 'no',
					)
				);

				$subscriptions_saved = get_post_meta( $variation_id, '_bos4w_saved_variation_subs', true );
				?>
				<div class="options_group variable_subscriptions_type_<?php echo esc_attr( $variation_id ); ?> wc-metaboxes ui-sortable">
					<?php if ( ! empty( $subscriptions_saved ) ) { ?>
						<?php
						$s = 0;
						foreach ( $subscriptions_saved as $subscription_item ) {
							$subscription_period   = isset( $subscription_item['subscription_period'] ) ? esc_attr( $subscription_item['subscription_period'] ) : '';
							$subscription_interval = isset( $subscription_item['subscription_period_interval'] ) ? esc_attr( $subscription_item['subscription_period_interval'] ) : '';
							$subscription_discount = isset( $subscription_item['subscription_discount'] ) ? esc_attr( $subscription_item['subscription_discount'] ) : '';
							$subscription_price    = isset( $subscription_item['subscription_price'] ) ? esc_attr( $subscription_item['subscription_price'] ) : '';
							?>
							<div class="subscription_type wc-metabox" data-pos="<?php echo isset( $subscription_item['position'] ) ? esc_attr( $subscription_item['position'] ) : ''; ?>">
								<span class="drag-drop" aria-label="<?php echo esc_html__( 'Drag to sort', 'bos4w' ); ?>"><i class="fas fa-grip-vertical"></i></span>
								<div class="sub-actions">
									<span class="this-remove"><?php echo esc_html__( 'Remove', 'bos4w' ); ?></span>
								</div>
								<div class="subscription-selections">
									<div class="subscription-details">
										<p class="form-field _subscription_details_<?php echo esc_attr( $s ); ?>">
											<label for="_subscription_details_<?php echo esc_attr( $s ); ?>"><?php echo esc_html__( 'Interval', 'bos4w' ); ?></label>
										<div class="wrap">
											<label for="bos4w_subscription_interval_<?php echo esc_attr( $variation_id . '_' . $s ); ?>" class="wcs_hidden_label"><?php echo esc_html__( 'Subscription interval', 'bos4w' ); ?></label>
											<select id="bos4w_subscription_interval_<?php echo esc_attr( $variation_id . '_' . $s ); ?>" name="bos4w_saved_variation_subs_<?php echo esc_attr( $variation_id ); ?>[<?php echo esc_attr( $s ); ?>][subscription_period_interval]" class="first">
												<?php foreach ( wcs_get_subscription_period_interval_strings() as $value => $label ) { ?>
													<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $value, $subscription_interval, true ); ?>><?php echo esc_html( $label ); ?></option>
												<?php } ?>
											</select>
											<label for="bos4w_subscription_period_<?php echo esc_attr( $variation_id . '_' . $s ); ?>" class="wcs_hidden_label"><?php echo esc_html__( 'Subscription period', 'bos4w' ); ?></label>
											<select id="bos4w_subscription_period_<?php echo esc_attr( $variation_id . '_' . $s ); ?>" name="bos4w_saved_variation_subs_<?php echo esc_attr( $variation_id ); ?>[<?php echo esc_attr( $s ); ?>][subscription_period]" class="last">
												<?php foreach ( wcs_get_subscription_period_strings() as $value => $label ) { ?>
													<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $value, $subscription_period, true ); ?>><?php echo esc_html( $label ); ?></option>
												<?php } ?>
											</select>
										</div>
										<?php echo wc_help_tip( esc_html__( 'Choose how frequently you would like to bill and ship this item.', 'bos4w' ) ); ?>
										</p>
									</div>
								</div>
								<div class="subscription_pricing_method subscription_pricing_method_inherit">
									<?php
									woocommerce_wp_text_input(
										array(
											'id'            => '_subscription_price_' . esc_attr( $variation_id ),
											'name'          => 'bos4w_saved_variation_subs_' . esc_attr( $variation_id ) . '[' . esc_attr( $s ) . '][subscription_price]',
											'value'         => $subscription_price,
											'wrapper_class' => 'subscription_price_' . esc_attr( $variation_id ),
											'label'         => __( 'Discount Value', 'bos4w' ),
											'description'   => __( 'Enter the discount value for this subscription plan.', 'bos4w' ),
											'desc_tip'      => true,
											'data_type'     => 'decimal',
										)
									);
									woocommerce_wp_text_input(
										array(
											'id'            => '_subscription_discount_' . esc_attr( $variation_id ),
											'name'          => 'bos4w_saved_variation_subs_' . esc_attr( $variation_id ) . '[' . esc_attr( $s ) . '][subscription_discount]',
											'value'         => $subscription_discount,
											'wrapper_class' => 'subscription_price_discount_' . esc_attr( $variation_id ),
											'label'         => __( 'Discount %', 'bos4w' ),
											'description'   => __( 'This discount will be applied on the product price if this frequency is chosen.', 'bos4w' ),
											'desc_tip'      => true,
											'data_type'     => 'decimal',
										)
									);
									?>
								</div>
								<input type="hidden" name="bos4w_saved_variation_subs_<?php echo esc_attr( $variation_id ); ?>[<?php echo esc_attr( $s ); ?>][position]" class="position" value="<?php echo isset( $subscription_item['position'] ) ? esc_attr( $s ) : ''; ?>"/>
							</div>
							<?php
							$s ++;
						}
						?>
					<?php } else { ?>
						<div class="bos4w-no-subscriptions-added">
							<p><?php echo esc_html__( 'No frequency has been defined.', 'bos4w' ); ?></p>
						</div>
					<?php } ?>
				</div>

				<p class="subscription_add_new_wrapper">
					<button type="button" class="button add_new_variation_subscription_button"><?php echo esc_html__( 'Add Frequency', 'bos4w' ); ?></button>
				</p>
				<hr>
			</div>
			<?php
		}

		/**
		 * Save variation subscription data for a specific variation.
		 *
		 * @param int $variation_id The ID of the variation.
		 * @param int $i Index of the variation.
		 *
		 * @return void
		 */
		public function bos4w_save_variation_subscription_data( $variation_id, $i ) {
			$variation = wc_get_product( $variation_id );
			if ( isset( $_REQUEST[ 'bos4w_use_variation_fixed_price_' . $variation_id ] ) ) {
				$variation->update_meta_data( 'bos4w_use_variation_fixed_price_' . $variation_id, 'yes' );
			} else {
				$variation->update_meta_data( 'bos4w_use_variation_fixed_price_' . $variation_id, 'no' );
			}

			$text_prompt = ! empty( $_REQUEST[ '_bos4w_subscription_title_' . $variation_id ] ) ? wp_kses_post( wp_unslash( (string) $_REQUEST[ '_bos4w_subscription_title_' . $variation_id ] ) ) : false;
			if ( false === $text_prompt ) {
				$variation->delete_meta_data( '_bos4w_subscription_title' );
			} else {
				$variation->update_meta_data( '_bos4w_subscription_title', $text_prompt );
			}

			if ( isset( $_REQUEST[ 'bos4w_saved_variation_subs_' . $variation_id ] ) ) {
				$the_subs     = wc_clean( stripslashes_deep( $_REQUEST[ 'bos4w_saved_variation_subs_' . $variation_id ] ) );
				$data_to_save = array();
				$new_index    = 0; // Initialize a new index for re-indexing.

				// Sort the plans based on their position.
				usort(
					$the_subs,
					function ( $a, $b ) {
						return $a['position'] <=> $b['position'];
					}
				);

				foreach ( $the_subs as $key => $sub ) {
					// Check if necessary keys exist before proceeding.
					if ( ! isset( $sub['position'] ) ||
						 ! isset( $sub['subscription_period_interval'] ) ||
						 ! isset( $sub['subscription_period'] ) ) {
						continue; // Skip this entry if any key is missing.
					}

					$entry_key = esc_attr( $sub['subscription_period_interval'] ) . '_' . esc_attr( $sub['subscription_period'] );

					// Save fixed price or discount.
					if ( isset( $_REQUEST[ 'bos4w_use_variation_fixed_price_' . $variation_id ] ) ) {
						if ( isset( $sub['subscription_price'] ) ) {
							$sub['subscription_price'] = wc_format_decimal( $sub['subscription_price'] );
						} else {
							$sub['subscription_price'] = 0;
						}
						$sub['subscription_discount'] = '';
					} else {
						if ( isset( $sub['subscription_discount'] ) ) {
							if ( is_numeric( $sub['subscription_discount'] ) ) {
								$discount = (float) wc_format_decimal( $sub['subscription_discount'] );
								if ( $discount < 0 || $discount > 100 ) {
									$sub['subscription_discount'] = '';
								} else {
									$sub['subscription_discount'] = $discount;
								}
							} else {
								$sub['subscription_discount'] = 0;
							}
						} else {
							$sub['subscription_discount'] = 0;
						}
						$sub['subscription_price'] = '';
					}

					// Re-index the array and update the position.
					$data_to_save[ $new_index ]             = $sub;
					$data_to_save[ $new_index ]['position'] = $new_index;
					$new_index ++;
				}

				if ( ! empty( $data_to_save ) ) {
					$variation->update_meta_data( '_bos4w_saved_variation_subs', $data_to_save );
				} else {
					$variation->delete_meta_data( '_bos4w_saved_variation_subs' );
				}
			} else {
				$variation->delete_meta_data( '_bos4w_saved_variation_subs' );
				$variation->delete_meta_data( 'bos4w_use_variation_fixed_price_' . $variation_id );
			}

			$variation->save();
		}

		/**
		 * Save the variation subscription data.
		 *
		 * @param int $product_id The variation ID.
		 *
		 * @return void
		 */
		public function bos4w_save_product_variation_subscription_data( $product_id ) {
			// Get all variation IDs from the POST data.
			$variation_ids = isset( $_REQUEST['variable_post_id'] ) ? wp_kses_post( wp_unslash( (int) $_REQUEST['variable_post_id'] ) ) : array();

			// Iterate through each variation ID.
			foreach ( $variation_ids as $variation_id ) {
				$variation = wc_get_product( $variation_id );

				if ( isset( $_REQUEST[ 'bos4w_use_variation_fixed_price_' . $variation_id ] ) ) {
					$variation->update_meta_data( 'bos4w_use_variation_fixed_price_' . $variation_id, 'yes' );
				} else {
					$variation->update_meta_data( 'bos4w_use_variation_fixed_price_' . $variation_id, 'no' );
				}

				$text_prompt = ! empty( $_REQUEST[ '_bos4w_subscription_title_' . $variation_id ] ) ? wp_kses_post( wp_unslash( (string) $_REQUEST[ '_bos4w_subscription_title_' . $variation_id ] ) ) : false;
				if ( false === $text_prompt ) {
					$variation->delete_meta_data( '_bos4w_subscription_title' );
				} else {
					$variation->update_meta_data( '_bos4w_subscription_title', $text_prompt );
				}

				if ( isset( $_REQUEST[ 'bos4w_saved_variation_subs_' . $variation_id ] ) ) {
					$the_subs = wc_clean( stripslashes_deep( $_REQUEST[ 'bos4w_saved_variation_subs_' . $variation_id ] ) );

					// Check if any of the submitted plans have valid interval and period.
					$has_valid_plans = false;
					foreach ( $the_subs as $sub ) {
						if ( ! empty( $sub['subscription_period_interval'] ) && ! empty( $sub['subscription_period'] ) ) {
							$has_valid_plans = true;
							break; // No need to continue checking if we found at least one valid plan.
						}
					}

					if ( $has_valid_plans ) {
						$data_to_save = array();
						$new_index    = 0; // Initialize a new index for re-indexing.

						foreach ( $the_subs as $key => $sub ) {

							if ( isset( $sub['subscription_period_interval'] ) && isset( $sub['subscription_period'] ) ) {
								$entry_key = esc_attr( $sub['subscription_period_interval'] ) . '_' . esc_attr( $sub['subscription_period'] );

								// Save fixed price or discount.
								if ( isset( $_REQUEST[ 'bos4w_use_variation_fixed_price_' . $variation_id ] ) ) {
									if ( isset( $sub['subscription_price'] ) ) {
										$sub['subscription_price'] = wc_format_decimal( $sub['subscription_price'] );
									} else {
										$sub['subscription_price'] = 0;
									}
									$sub['subscription_discount'] = '';
								} else {
									if ( isset( $sub['subscription_discount'] ) ) {
										if ( is_numeric( $sub['subscription_discount'] ) ) {
											$discount = (float) wc_format_decimal( $sub['subscription_discount'] );
											if ( $discount < 0 || $discount > 100 ) {
												$sub['subscription_discount'] = '';
											} else {
												$sub['subscription_discount'] = $discount;
											}
										} else {
											$sub['subscription_discount'] = 0;
										}
									} else {
										$sub['subscription_discount'] = 0;
									}
									$sub['subscription_price'] = '';
								}

								// Re-index the array and update the position.
								$data_to_save[ $new_index ]             = $sub;
								$data_to_save[ $new_index ]['position'] = $new_index;
								$new_index ++;
							}
						}

						$variation->update_meta_data( '_bos4w_saved_variation_subs', $data_to_save );
					} else {
						// If no valid plans are found, delete the meta data.
						$variation->delete_meta_data( '_bos4w_saved_variation_subs' );
					}
				} else {
					// If the POST data is not set at all, also delete the meta data.
					$variation->delete_meta_data( '_bos4w_saved_variation_subs' );
					$variation->delete_meta_data( 'bos4w_use_variation_fixed_price_' . $variation_id );
				}

				$variation->save();
			}
		}

		/**
		 * Load scripts
		 */
		public function bos4w_admin_scripts() {
			global $post;
			$plugin_data = get_plugin_data( SFORCE_PLUGIN_FILE );
			wp_enqueue_script(
				'bos4w-admin-js',
				BOS_FUNC_URL . 'assets/js/bos4w-admin.js',
				array(
					'jquery',
					'jquery-ui-datepicker',
					'wc-admin-meta-boxes',
				),
				'1.0.0',
				true
			);
			wp_enqueue_style( 'bos4w-admin', BOS_FUNC_URL . 'assets/css/bos4w-admin.css', array(), $plugin_data['Version'] );
			wp_enqueue_style( 'fontawesome', 'https://use.fontawesome.com/releases/v6.0.0/css/all.css', '', $plugin_data['Version'] );

			$params = array(
				'ajax_url'             => admin_url( 'admin-ajax.php' ),
				'post_id'              => is_object( $post ) ? absint( $post->ID ) : '',
				'bos4w_nonce'          => wp_create_nonce( 'nonce_bos4w' ),
				'subscription_lengths' => function_exists( 'wcs_get_subscription_ranges' ) ? wcs_get_subscription_ranges() : array(),
			);

			wp_localize_script( 'bos4w-admin-js', 'bos4w_admin', $params );
		}

		/**
		 * Display the Product tab
		 *
		 * @param array $tabs Tabs array.
		 *
		 * @return mixed
		 */
		public static function bos4w_product_data_tab( $tabs ) {
			$tabs['bos4w'] = array(
				'label'    => __( 'Subscriptions', 'bos4w' ),
				'target'   => 'bos4w_data',
				'priority' => 100,
				'class'    => array(
					'cart_subscription_options',
					'cart_subscriptions_tab',
					'show_if_simple',
					'show_if_variable',
					'hide_if_grouped',
					'hide_if_external',
					'hide_if_subscription',
					'hide_if_variable-subscription',
				),
			);

			return $tabs;
		}

		/**
		 * Display the Product subscriptions tab content
		 */
		public function bos4w_product_data_panel() {
			global $product_object;

			$subscriptions_saved = $product_object->get_meta( '_bos4w_saved_subs', true );
			$use_fixed_price     = $product_object->get_meta( '_bos4w_use_fixed_price', true );
			?>
			<div id="bos4w_data" class="panel woocommerce_options_panel">
				<?php
				woocommerce_wp_textarea_input(
					array(
						'id'          => '_bos4w_subscription_title',
						'label'       => __( 'Title', 'bos4w' ),
						'description' => __( 'Text appears above the available purchase options when at least one frequency has been added below.', 'bos4w' ),
						'placeholder' => __( 'e.g. "Choose frequency"', 'bos4w' ),
						'desc_tip'    => true,
					)
				);

				woocommerce_wp_checkbox(
					array(
						'id'          => '_bos4w_use_fixed_price',
						'label'       => __( 'Use value discount', 'bos4w' ),
						'description' => __( 'When checked, you will be able to enter a fixed value discount for each subscription plan.', 'bos4w' ),
						'desc_tip'    => true,
						'value'       => $use_fixed_price ? 'yes' : 'no',
					)
				);
				?>
				<hr/>

				<div class="options_group subscriptions_type wc-metaboxes ui-sortable">
					<?php if ( ! empty( $subscriptions_saved ) ) { ?>
						<?php
						$s = 0;
						foreach ( $subscriptions_saved as $subscription_item ) {
							$subscription_period   = isset( $subscription_item['subscription_period'] ) ? esc_attr( $subscription_item['subscription_period'] ) : '';
							$subscription_interval = isset( $subscription_item['subscription_period_interval'] ) ? esc_attr( $subscription_item['subscription_period_interval'] ) : '';
							$subscription_discount = isset( $subscription_item['subscription_discount'] ) ? esc_attr( $subscription_item['subscription_discount'] ) : '';
							$subscription_price    = isset( $subscription_item['subscription_price'] ) ? esc_attr( $subscription_item['subscription_price'] ) : '';
							?>
							<div class="subscription_type wc-metabox" data-pos="<?php echo isset( $subscription_item['position'] ) ? esc_attr( $subscription_item['position'] ) : ''; ?>">
								<span class="drag-drop" aria-label="<?php echo esc_html__( 'Drag to sort', 'bos4w' ); ?>"><i class="fas fa-grip-vertical"></i></span>
								<div class="sub-actions">
									<span class="this-remove"><?php echo esc_html__( 'Remove', 'bos4w' ); ?></span>
								</div>
								<div class="subscription-selections">
									<div class="subscription-details">
										<p class="form-field _subscription_details_<?php echo esc_attr( $s ); ?>">
											<label for="_subscription_details_<?php echo esc_attr( $s ); ?>"><?php echo esc_html__( 'Interval', 'bos4w' ); ?></label>
										<div class="wrap">
											<label for="bos4w_subscription_interval_<?php echo esc_attr( $s ); ?>" class="wcs_hidden_label"><?php echo esc_html__( 'Subscription interval', 'bos4w' ); ?></label>
											<select id="bos4w_subscription_interval_<?php echo esc_attr( $s ); ?>" name="bos4w_saved_subs[<?php echo esc_attr( $s ); ?>][subscription_period_interval]" class="first">
												<?php foreach ( wcs_get_subscription_period_interval_strings() as $value => $label ) { ?>
													<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $value, $subscription_interval, true ); ?>><?php echo esc_html( $label ); ?></option>
												<?php } ?>
											</select>
											<label for="bos4w_subscription_period_<?php echo esc_attr( $s ); ?>" class="wcs_hidden_label"><?php echo esc_html__( 'Subscription period', 'bos4w' ); ?></label>
											<select id="bos4w_subscription_period_<?php echo esc_attr( $s ); ?>" name="bos4w_saved_subs[<?php echo esc_attr( $s ); ?>][subscription_period]" class="last">
												<?php foreach ( wcs_get_subscription_period_strings() as $value => $label ) { ?>
													<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $value, $subscription_period, true ); ?>><?php echo esc_html( $label ); ?></option>
												<?php } ?>
											</select>
										</div>
										<?php echo wc_help_tip( esc_html__( 'Choose how frequently you would like to bill and ship this item.', 'bos4w' ) ); ?>
										</p>
									</div>
								</div>
								<div class="subscription_pricing_method subscription_pricing_method_inherit">
									<?php
									woocommerce_wp_text_input(
										array(
											'id'            => '_subscription_price_' . esc_attr( $s ),
											'name'          => 'bos4w_saved_subs[' . esc_attr( $s ) . '][subscription_price]',
											'value'         => $subscription_price,
											'wrapper_class' => 'subscription_price',
											'label'         => __( 'Discount Value', 'bos4w' ),
											'description'   => __( 'Enter the discount value for this subscription plan.', 'bos4w' ),
											'desc_tip'      => true,
											'data_type'     => 'decimal',
										)
									);
									woocommerce_wp_text_input(
										array(
											'id'            => '_subscription_discount_' . esc_attr( $s ),
											'name'          => 'bos4w_saved_subs[' . esc_attr( $s ) . '][subscription_discount]',
											'value'         => $subscription_discount,
											'wrapper_class' => 'subscription_price_discount',
											'label'         => __( 'Discount %', 'bos4w' ),
											'description'   => __( 'This discount will be applied on the product price if this frequency is chosen.', 'bos4w' ),
											'desc_tip'      => true,
											'data_type'     => 'decimal',
										)
									);
									?>
								</div>
								<input type="hidden" name="bos4w_saved_subs[<?php echo esc_attr( $s ); ?>][position]" class="position" value="<?php echo isset( $subscription_item['position'] ) ? esc_attr( $s ) : ''; ?>"/>
							</div>
							<?php
							$s ++;
						}
						?>
					<?php } else { ?>
						<div class="bos4w-no-subscriptions-added">
							<p><?php echo esc_html__( 'No frequency has been defined.', 'bos4w' ); ?></p>
						</div>
					<?php } ?>
				</div>

				<p class="subscription_add_new_wrapper">
					<button type="button" class="button add_new_subscription_button"><?php echo esc_html__( 'Add Frequency', 'bos4w' ); ?></button>
				</p>
			</div>
			<?php
		}

		/**
		 * Add new line
		 */
		public function wpr_add_subscription_type() {
			check_ajax_referer( 'nonce_bos4w', 'nonce' );

			$entries = isset( $_POST['list'] ) ? absint( $_POST['list'] ) : 0;

			ob_start();
			$output = '';
			if ( $entries >= 0 ) {
				?>
				<div class="subscription_type wc-metabox" data-pos="<?php echo isset( $subscription_item['position'] ) ? esc_attr( $subscription_item['position'] ) : ''; ?>">
					<span class="drag-drop" aria-label="<?php echo esc_html__( 'Drag to sort', 'bos4w' ); ?>"><i class="fas fa-grip-vertical"></i></span>
					<div class="sub-actions">
						<span class="this-remove"><?php echo esc_html__( 'Remove', 'bos4w' ); ?></span>
					</div>
					<?php if ( isset( $_POST['post_id'] ) && empty( $_POST['post_id'] ) ) { ?>
						<div class="subscription-category">
							<div class="subscription-details">
								<p class="form-field _subscription_details_<?php echo esc_attr( $entries ); ?>">
									<label for="_subscription_details_<?php echo esc_attr( $entries ); ?>"><?php echo esc_html__( 'Category', 'bos4w' ); ?></label>
								<div class="wrap">
									<select class="wc-category-search bos4w-wc-enhanced-select" multiple="multiple" style="width: 50%;" id="sspw_select_promoted_products" name="bos4w_saved_subs[<?php echo esc_attr( $entries ); ?>][product_cat][]" data-placeholder="<?php esc_attr_e( 'Search for a Category&hellip;', 'woocommerce-social-proof-fomo' ); ?>" data-action="woocommerce_json_search_products_and_variations">
										<?php
										if ( $subscription_item['product_cat'] ) {
											foreach ( $subscription_item['product_cat'] as $category ) {
												$current_category = $category ? get_term_by( 'slug', $category, 'product_cat' ) : false;
												echo '<option value="' . esc_attr( $category ) . '"' . selected( true, true, false ) . '>' . esc_html( htmlspecialchars( wp_kses_post( (string) $current_category->name ) ) ) . '</option>';
											}
										}
										?>
									</select>
								</div>
								</p>
							</div>
						</div>
					<?php } ?>
					<div class="subscription-selections">
						<div class="subscription-details">
							<p class="form-field _subscription_details_<?php echo esc_attr( $entries ); ?>">
								<label for="_subscription_details_<?php echo esc_attr( $entries ); ?>"><?php echo esc_html__( 'Interval', 'bos4w' ); ?></label>
							<div class="wrap">
								<select id="bos4w_subscription_interval_<?php echo esc_attr( $entries ); ?>" name="bos4w_saved_subs[<?php echo esc_attr( $entries ); ?>][subscription_period_interval]" class="first">
									<?php foreach ( wcs_get_subscription_period_interval_strings() as $value => $label ) { ?>
										<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
									<?php } ?>
								</select>

								<select id="bos4w_subscription_period_<?php echo esc_attr( $entries ); ?>" name="bos4w_saved_subs[<?php echo esc_attr( $entries ); ?>][subscription_period]" class="last">
									<?php foreach ( wcs_get_subscription_period_strings() as $value => $label ) { ?>
										<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
									<?php } ?>
								</select>

							</div>
							<?php echo wc_help_tip( esc_html__( 'Choose how frequently you would like to bill and ship this item.', 'bos4w' ) ); ?>
							</p>
						</div>
					</div>
					<div class="subscription_pricing_method subscription_pricing_method_inherit">
						<?php
						woocommerce_wp_text_input(
							array(
								'id'            => '_subscription_price',
								'name'          => 'bos4w_saved_subs[' . esc_attr( $entries ) . '][subscription_price]',
								'wrapper_class' => 'subscription_price',
								'label'         => __( 'Discount Value', 'bos4w' ),
								'description'   => __( 'Enter the discount value for this subscription plan.', 'bos4w' ),
								'desc_tip'      => true,
								'data_type'     => 'decimal',
							)
						);

						woocommerce_wp_text_input(
							array(
								'id'            => '_subscription_discount',
								'name'          => 'bos4w_saved_subs[' . esc_attr( $entries ) . '][subscription_discount]',
								'wrapper_class' => 'subscription_price_discount',
								'label'         => __( 'Discount %', 'bos4w' ),
								'description'   => __( 'This discount will be applied on the product price if this frequency is chosen.', 'bos4w' ),
								'desc_tip'      => true,
								'data_type'     => 'decimal',
							)
						);
						?>
					</div>
					<input type="hidden" name="bos4w_saved_subs[<?php echo esc_attr( $entries ); ?>][position]" class="position" value="<?php echo esc_attr( $entries ); ?>"/>
				</div>
				<?php
				$output = ob_get_contents();
				ob_end_clean();
			}

			wp_send_json_success(
				array(
					'html' => $output,
				)
			);
		}

		/**
		 * Add new line for variation
		 */
		public function wpr_add_variable_subscription_type() {
			check_ajax_referer( 'nonce_bos4w', 'nonce' );

			$entries      = isset( $_POST['list'] ) ? absint( $_POST['list'] ) : 0;
			$variation_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

			ob_start();
			$output = '';
			if ( $entries >= 0 ) {
				?>
				<div class="subscription_type wc-metabox" data-pos="<?php echo isset( $subscription_item['position'] ) ? esc_attr( $subscription_item['position'] ) : ''; ?>">
					<span class="drag-drop" aria-label="<?php echo esc_html__( 'Drag to sort', 'bos4w' ); ?>"><i class="fas fa-grip-vertical"></i></span>
					<div class="sub-actions">
						<span class="this-remove"><?php echo esc_html__( 'Remove', 'bos4w' ); ?></span>
					</div>
					<?php if ( isset( $_POST['post_id'] ) && empty( $_POST['post_id'] ) ) { ?>
						<div class="subscription-category">
							<div class="subscription-details">
								<p class="form-field _subscription_details_<?php echo esc_attr( $entries ); ?>">
									<label for="_subscription_details_<?php echo esc_attr( $entries ); ?>"><?php echo esc_html__( 'Category', 'bos4w' ); ?></label>
								<div class="wrap">
									<select class="wc-category-search bos4w-wc-enhanced-select" multiple="multiple" style="width: 50%;" id="sspw_select_promoted_products" name="bos4w_saved_subs[<?php echo esc_attr( $entries ); ?>][product_cat][]" data-placeholder="<?php esc_attr_e( 'Search for a Category&hellip;', 'woocommerce-social-proof-fomo' ); ?>" data-action="woocommerce_json_search_products_and_variations">
										<?php
										if ( $subscription_item['product_cat'] ) {
											foreach ( $subscription_item['product_cat'] as $category ) {
												$current_category = $category ? get_term_by( 'slug', $category, 'product_cat' ) : false;
												echo '<option value="' . esc_attr( $category ) . '"' . selected( true, true, false ) . '>' . esc_html( htmlspecialchars( wp_kses_post( (string) $current_category->name ) ) ) . '</option>';
											}
										}
										?>
									</select>
								</div>
								</p>
							</div>
						</div>
					<?php } ?>
					<div class="subscription-selections">
						<div class="subscription-details">
							<p class="form-field _subscription_details_<?php echo esc_attr( $entries ); ?>">
								<label for="_subscription_details_<?php echo esc_attr( $entries ); ?>"><?php echo esc_html__( 'Interval', 'bos4w' ); ?></label>
							<div class="wrap">
								<select id="bos4w_subscription_interval_<?php echo esc_attr( $entries ); ?>" name="bos4w_saved_variation_subs_<?php echo absint( $variation_id ); ?>[<?php echo esc_attr( $entries ); ?>][subscription_period_interval]" class="first">
									<?php foreach ( wcs_get_subscription_period_interval_strings() as $value => $label ) { ?>
										<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
									<?php } ?>
								</select>

								<select id="bos4w_subscription_period_<?php echo esc_attr( $entries ); ?>" name="bos4w_saved_variation_subs_<?php echo absint( $variation_id ); ?>[<?php echo esc_attr( $entries ); ?>][subscription_period]" class="last">
									<?php foreach ( wcs_get_subscription_period_strings() as $value => $label ) { ?>
										<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
									<?php } ?>
								</select>

							</div>
							<?php echo wc_help_tip( esc_html__( 'Choose how frequently you would like to bill and ship this item.', 'bos4w' ) ); ?>
							</p>
						</div>
					</div>
					<div class="subscription_pricing_method subscription_pricing_method_inherit">
						<?php
						woocommerce_wp_text_input(
							array(
								'id'            => '_subscription_price_' . esc_attr( $variation_id ),
								'name'          => 'bos4w_saved_variation_subs_' . esc_attr( $variation_id ) . '[' . esc_attr( $entries ) . '][subscription_price]',
								'wrapper_class' => 'subscription_price',
								'label'         => __( 'Discount Value', 'bos4w' ),
								'description'   => __( 'Enter the discount value for this subscription plan.', 'bos4w' ),
								'desc_tip'      => true,
								'data_type'     => 'decimal',
							)
						);

						woocommerce_wp_text_input(
							array(
								'id'            => '_subscription_discount_' . esc_attr( $variation_id ),
								'name'          => 'bos4w_saved_variation_subs_' . esc_attr( $variation_id ) . '[' . esc_attr( $entries ) . '][subscription_discount]',
								'wrapper_class' => 'subscription_price_discount',
								'label'         => __( 'Discount %', 'bos4w' ),
								'description'   => __( 'This discount will be applied on the product price if this frequency is chosen.', 'bos4w' ),
								'desc_tip'      => true,
								'data_type'     => 'decimal',
							)
						);
						?>
					</div>
					<input type="hidden" name="bos4w_saved_variation_subs_<?php echo absint( $variation_id ); ?>[<?php echo esc_attr( $entries ); ?>][position]" class="position" value="<?php echo esc_attr( $entries ); ?>"/>
				</div>
				<?php
				$output = ob_get_contents();
				ob_end_clean();
			}

			wp_send_json_success(
				array(
					'html' => $output,
				)
			);
		}

		/**
		 * Save data
		 *
		 * @param object $product Product object.
		 */
		public function bos4w_save_subscription_data( $product ) {
			if ( isset( $_REQUEST['bos4w_saved_subs'] ) ) {
				$the_subs        = wc_clean( stripslashes_deep( $_REQUEST['bos4w_saved_subs'] ) );
				$use_fixed_price = isset( $_REQUEST['_bos4w_use_fixed_price'] ) ? 'yes' : 'no';

				$data_to_save = array();
				foreach ( $the_subs as $the_sub ) {

					$entry_key = esc_attr( $the_sub['subscription_period_interval'] ) . '_' . esc_attr( $the_sub['subscription_period'] );

					// Save fixed price or discount.
					if ( 'yes' === $use_fixed_price ) {
						if ( isset( $the_sub['subscription_price'] ) ) {
							$the_sub['subscription_price'] = wc_format_decimal( $the_sub['subscription_price'] );
						} else {
							$the_sub['subscription_price'] = 0;
						}

						$the_sub['subscription_discount'] = '';
					} else {
						if ( isset( $the_sub['subscription_discount'] ) ) {
							if ( is_numeric( $the_sub['subscription_discount'] ) ) {
								$discount = (float) wc_format_decimal( $the_sub['subscription_discount'] );
								if ( $discount < 0 || $discount > 100 ) {
									$the_sub['subscription_discount'] = 0;
								} else {
									$the_sub['subscription_discount'] = $discount;
								}
							} else {
								$the_sub['subscription_discount'] = 0;
							}
						} else {
							$the_sub['subscription_discount'] = 0;
						}

						$the_sub['subscription_price'] = '';
					}

					$data_to_save[ $entry_key ] = $the_sub;
				}

				// Prevent overwriting variation discounts with product-level discounts.
				if ( $product->is_type( 'variable' ) ) {
					$variations = $product->get_children();
					foreach ( $variations as $variation_id ) {
						$variation_subscriptions = get_post_meta( $variation_id, '_bos4w_saved_variation_subs', true );
						if ( ! empty( $variation_subscriptions ) ) {
							unset( $data_to_save[ $variation_id ] );
						}
					}
				}

				if ( ! empty( $data_to_save ) ) {
					$product->update_meta_data( '_bos4w_saved_subs', array_values( $data_to_save ) );
				} else {
					$product->delete_meta_data( '_bos4w_saved_subs' );
				}

				$text_prompt = ! empty( $_REQUEST['_bos4w_subscription_title'] ) ? wp_kses_post( wp_unslash( (string) $_REQUEST['_bos4w_subscription_title'] ) ) : false;
				if ( false === $text_prompt ) {
					$product->delete_meta_data( '_bos4w_subscription_title' );
				} else {
					$product->update_meta_data( '_bos4w_subscription_title', $text_prompt );
				}

				if ( isset( $_REQUEST['_bos4w_use_fixed_price'] ) ) {
					$product->update_meta_data( '_bos4w_use_fixed_price', 'yes' );
				} else {
					$product->delete_meta_data( '_bos4w_use_fixed_price' );
				}
			} else {
				$product->delete_meta_data( '_bos4w_saved_subs' );
				$product->delete_meta_data( '_bos4w_use_fixed_price' );
			}
		}

		/**
		 * Display the Reviews message
		 */
		public function bos4w_display_review_message() {
			$activation_date = get_option( 'bos4w_activation_date' );
			$current_date    = time();
			if ( $activation_date ) {
				$in_7_days  = strtotime( '+7 days', $activation_date );
				$in_30_days = strtotime( '+30 days', $activation_date );
			}

			if ( defined( 'BOS_IS_PLUGIN' ) && BOS_IS_PLUGIN ) {
				$hide_notice = get_option( 'bos4w_notice_dismiss' );

				if ( ! $hide_notice && ( $activation_date && $current_date >= $in_30_days ) ) {
					$head    = esc_html__( 'Enjoying Buy Once or Subscribe?', 'bos4w' );
					$message = esc_html__( 'If you\'re happy with our plugin, please help us grow and spread the word by giving a 5-star rating on the WooCommerce page. If you can\'t find the functionality you\'re looking for we want to know!', 'bos4w' );

					$class = 'notice notice-info is-dismissible bos4w-notice-wrap';

					printf(
						'<div data-dismissible="bos4w-notice-review" class="%1$s"><h2>%2$s</h2><p>%3$s</p><p><a href="%4$s" target="_blank" class="button button-primary bos4w-dismiss-notice"><i class="fa-solid fa-star"></i> <i class="fa-solid fa-star"></i> <i class="fa-solid fa-star"></i> <i class="fa-solid fa-star"></i> <i class="fa-solid fa-star"></i> %5$s</a> &nbsp; <a href="%6$s" target="_blank" class="button bos4w-dismiss-notice">%7$s</a></p></div>',
						esc_attr( $class ),
						esc_html( $head ),
						esc_html( $message ),
						esc_url( 'https://woocommerce.com/products/buy-once-or-subscribe-for-woocommerce-subscriptions/' ),
						esc_html__( 'Rate us', 'bos4w' ),
						esc_url( 'https://woocommerce.com/feature-requests/buy-once-or-subscribe-for-woocommerce-subscriptions/' ),
						esc_html__( 'Submit feature request', 'bos4w' )
					);
				}
			}

			$hide_upsell_notice = get_option( 'bos4w_upsell_notice_dismiss' );

			if ( ! class_exists( 'WPR_Update_Subscription' ) && ! $hide_upsell_notice && ( $activation_date && $current_date >= $in_30_days ) ) {
				$head = esc_html__( 'Enhance your Subscriptions website with these useful plugins', 'bos4w' );

				$message = '<div class="bos4w-message-wrap">';
				$message .= '<div>';
				$message .= '<img src="' . BOS_FUNC_URL . 'assets/images/SF Logo.png" height="50" />';
				$message .= '</div>';
				$message .= '<div>';
				$message .= '<ul>';
				$message .= sprintf( '<li><a href="%s" target="_blank">%s</a> %s</li>', esc_url( 'https://bit.ly/cross-bos-to-ssd' ), esc_html__( 'Self-service Dashboard for WooCommerce Subscriptions', 'bos4w' ), esc_html__( ' - A simple interface on top of WooCommerce Subscriptions that allows your customers to manage their subscriptions by themselves.', 'bos4w' ) );
				$message .= '</ul>';
				$message .= '</div>';
				$message .= '</div>';

				$class = 'notice notice-info is-dismissible bos4w-notice-upsell-wrap';

				printf(
					'<div data-dismissible="bos4w-notice-addons" class="%1$s"><h2>%2$s</h2>%3$s</div>',
					esc_attr( $class ),
					esc_html( $head ),
					wp_kses( $message, $this->bos4w_allowed_notice_tags() )
				);
			}
		}

		/**
		 * Do the notice dismiss
		 */
		public function bos4w_do_dismiss() {
			check_ajax_referer( 'nonce_bos4w', 'nonce' );

			$type = '';
			if ( isset( $_REQUEST['type'] ) ) {
				$type = wc_clean( stripslashes_deep( $_REQUEST['type'] ) );
			}

			if ( 'upsell' === $type ) {
				update_option( 'bos4w_upsell_notice_dismiss', 1 );
			} else {
				update_option( 'bos4w_notice_dismiss', 1 );
			}

		}

		/**
		 * Add Settings tab
		 *
		 * @param array $sections Sections tabs.
		 *
		 * @return mixed
		 */
		public function bos4w_settings_tab( $sections ) {
			if ( defined( 'BOS_IS_PLUGIN' ) && BOS_IS_PLUGIN ) {
				$sections['bos4w_settings_tab'] = __( 'Buy Once or Subscribe', 'bos4w' );
			}

			return $sections;
		}

		/**
		 * Custom settings
		 */
		public function bos4w_custom_settings() {
			global $current_section;

			$tab_id = 'bos4w_settings_tab';

			$sections = array(
				''                    => __( 'General', 'bos4w' ),
				'global_subscription' => __( 'Global Subscription', 'bos4w' ),
			);

			echo '<ul class="subsubsub">';

			$array_keys = array_keys( $sections );

			foreach ( $sections as $id => $label ) {
				echo '<li><a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=' . $tab_id . '&section=' . sanitize_title( $id ) ) ) . '" class="' . ( $current_section == $id ? 'current' : '' ) . '">' . esc_html( $label ) . '</a> ' . ( end( $array_keys ) == $id ? '' : '|' ) . ' </li>';
			}

			echo '</ul><br class="clear" />';
		}

		/**
		 * Add Settings tab content
		 */
		public function bos4w_settings_tab_content() {
			global $current_section;
			if ( 'global_subscription' == $current_section ) {
				$this->bos4w_global_data_panel();
			} else {
				if ( defined( 'BOS_IS_PLUGIN' ) ) {
					?>
					<h2><?php echo esc_html__( 'Get started', 'bos4w' ); ?></h2>
					<iframe width="560" height="315" src="https://www.youtube.com/embed/mHekpxxsLFs" title="YouTube video player" frameborder="0"
							allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
				<?php } ?>
				<?php $this->bos4w_general_settings(); ?>
				<?php
				if ( ! defined( 'BOS_IS_PLUGIN' ) ) {
					$this->bos4w_global_data_panel();
				}
				?>
				<h3>
					<?php
					if ( defined( 'BOS_IS_PLUGIN' ) ) {
						printf(
						/* translators: %1$s documentaion and %2$s hook list */
							wp_kses( 'For more information about our plugin please see the <a href="%1$s" target="_blank">Documentation</a>, including the <a href="%2$s" target="_blank">Hooks and Filters</a>.', $this->bos4w_allowed_notice_tags() ),
							esc_url( 'https://woocommerce.com/document/buy-once-or-subscribe-for-woocommerce-subscriptions/' ),
							esc_url( 'https://woocommerce.com/document/buy-once-or-subscribe-for-woocommerce-subscriptions-developer-hooks-and-filters/' )
						);
					} else {
						printf(
						/* translators: %1$s documentaion and %2$s hook list */
							wp_kses( 'For more information about our plugin please see the <a href="%1$s" target="_blank">Documentation</a>, including the <a href="%2$s" target="_blank">Hooks and Filters</a>.', ssd_allowed_notice_tags() ),
							esc_url( 'https://help.subscriptionforce.com/' ),
							esc_url( 'https://help.subscriptionforce.com/article/16-hooks' )
						);
					}
					?>
				</h3>
				<?php

			}
		}

		/**
		 * Display the settings tab content.
		 *
		 * @return void
		 */
		public function bos4w_sf_settings_tab_content() {
			global $current_section;
			if ( ! defined( 'BOS_IS_PLUGIN' ) && 'bos_settings' == $current_section ) {
				$this->bos4w_settings_tab_content();
			}
		}

		/**
		 * General settings
		 *
		 * @return void
		 */
		public function bos4w_general_settings() {
			woocommerce_admin_fields( self::display_settings() );
		}

		/**
		 * Display the settings
		 *
		 * @return mixed|void
		 */
		public static function display_settings() {
			$settings = array(
				'section_title' => array(
					'name' => __( 'General settings', 'bos4w' ),
					'type' => 'title',
					'desc' => '',
					'id'   => 'settings_tabs_bos4w_settings_tab_section_title',
				),
				array(
					'title'    => __( 'Exclude products bought as subscriptions from coupons', 'bos4w' ),
					'id'       => 'bos4w_exclude_from_coupons',
					'default'  => 'no',
					'type'     => 'checkbox',
					'class'    => 'bos4w-fields',
					'tooltip'  => __( 'When checked, coupon discounts will not be applied to products bought as subscriptions using buy once or subscribe. This will not affect subscription-type products.', 'bos4w' ),
					'desc_tip' => true,
				),
				array(
					'title'    => __( 'Allow plan selection in cart', 'bos4w' ),
					'id'       => 'bos4w_allow_cart_subscription',
					'default'  => 'no',
					'type'     => 'checkbox',
					'class'    => 'bos4w-fields',
					'tooltip'  => __( 'When checked, customers can choose between the one-time purchase and subscription plans in the cart.', 'bos4w' ),
					'desc_tip' => true,
				),
				array(
					'title'       => __( 'Subscribe and save text', 'bos4w' ),
					'type'        => 'text',
					'id'          => 'bos4w_subscription_text_display',
					'class'       => 'bos4w-fields',
					/* translators: Placeholder message */
					'placeholder' => __( 'or subscribe and save up to %1$s', 'bos4w' ),
					/* translators: Placeholder message */
					'desc'        => __( 'Edit the \'Subscribe and save x%\' text displayed next to the price. You can use the following values: %1$s - maximum discount.', 'bos4w' ),
					'desc_tip'    => true,
				),
				array(
					'title'       => __( 'One-time purchase text', 'bos4w' ),
					'type'        => 'text',
					'id'          => 'bos4w_one_time_buy_text',
					'class'       => 'bos4w-fields',
					/* translators: Placeholder message */
					'placeholder' => __( 'One-time purchase option', 'bos4w' ),
					/* translators: Placeholder message */
					'desc'        => __( 'Change the text for the One-time purchase radio option displayed on the product page.', 'bos4w' ),
					'desc_tip'    => true,
				),
				array(
					'title'       => __( 'Subscribe and save up to text', 'bos4w' ),
					'type'        => 'text',
					'id'          => 'bos4w_and_save_up_to_text',
					'class'       => 'bos4w-fields',
					/* translators: Placeholder message */
					'placeholder' => __( 'Subscribe and save up to %1$s', 'bos4w' ),
					/* translators: Placeholder message */
					'desc'        => __( 'Change the text and discount for the radio button for the subscription purchase option displayed on the product page. You can use the following values: %1$s - maximum discount.', 'bos4w' ),
					'desc_tip'    => true,
				),
				array(
					'title'       => __( 'Frequency text', 'bos4w' ),
					'type'        => 'text',
					'id'          => 'bos4w_dropdown_label_text',
					'class'       => 'bos4w-fields',
					/* translators: Placeholder message */
					'placeholder' => __( 'Frequency', 'bos4w' ),
					/* translators: Placeholder message */
					'desc'        => __( 'Change the text for the frequency dropdown label displayed on the product page.', 'bos4w' ),
					'desc_tip'    => true,
				),

				array(
					'title'       => __( 'Interval display text', 'bos4w' ),
					'type'        => 'text',
					'id'          => 'ssd_subscription_plan_display',
					'class'       => 'bos4w-fields',
					/* translators: Placeholder message */
					'placeholder' => __( 'Every %1$s for %2$s %3$s', 'bos4w' ),
					/* translators: Placeholder message */
					'desc'        => __( 'Change the text, interval, and discount display in the subscription dropdown options on the product page. You can use the following values: %1$s - interval, %2$s - discounted price, %3$s - discount.', 'bos4w' ),
					'desc_tip'    => true,
				),

				array(
					'title'       => __( 'Select options text', 'bos4w' ),
					'type'        => 'text',
					'id'          => 'bos4w_add_to_cart_text',
					'class'       => 'bos4w-fields',
					/* translators: Placeholder message */
					'placeholder' => __( 'Select options button text', 'bos4w' ),
					/* translators: Placeholder message */
					'desc'        => __( 'Change text for the "Select options" button displayed for buy once or subscribe products.', 'bos4w' ),
					'desc_tip'    => true,
				),

				'section_end' => array(
					'type' => 'sectionend',
					'id'   => 'settings_tabs_bos4w_settings_tab_section_end',
				),
			);

			/**
			 * Settings filter
			 *
			 * @param array $settings Settings array.
			 *
			 * @since 1.0.0
			 */
			return apply_filters( 'settings_tabs_bos4w_settings', $settings );
		}

		/**
		 * Display the Product subscriptions tab content
		 */
		public function bos4w_global_data_panel() {
			$subscriptions_saved = get_option( 'bos4w_global_saved_subs' );
			$subscriptions_title = get_option( 'bos4w_global_subscription_title' );
			?>
			<h2><?php echo esc_html__( 'Global subscription plans', 'bos4w' ); ?></h2>
			<div id="bos4w_data" class="panel woocommerce_options_panel">
				<?php
				woocommerce_wp_textarea_input(
					array(
						'id'          => '_bos4w_global_subscription_title',
						'label'       => __( 'Title', 'bos4w' ),
						'description' => __( 'Text appears above the available purchase options when at least one frequency has been added below.', 'bos4w' ),
						'placeholder' => __( 'e.g. "Choose frequency"', 'bos4w' ),
						'desc_tip'    => true,
						'value'       => esc_html( $subscriptions_title ),
					)
				);
				?>
				<div class="options_group subscriptions_type wc-metaboxes ui-sortable">
					<?php if ( ! empty( $subscriptions_saved ) ) { ?>
						<?php
						$s = 0;
						foreach ( $subscriptions_saved as $subscription_item ) {
							$subscription_period   = isset( $subscription_item['subscription_period'] ) ? esc_attr( $subscription_item['subscription_period'] ) : '';
							$subscription_interval = isset( $subscription_item['subscription_period_interval'] ) ? esc_attr( $subscription_item['subscription_period_interval'] ) : '';
							$subscription_discount = isset( $subscription_item['subscription_discount'] ) ? esc_attr( $subscription_item['subscription_discount'] ) : '';
							?>
							<div class="subscription_type wc-metabox" data-pos="<?php echo isset( $subscription_item['position'] ) ? esc_attr( $subscription_item['position'] ) : ''; ?>">
								<span class="drag-drop" aria-label="<?php echo esc_html__( 'Drag to sort', 'bos4w' ); ?>"><i class="fas fa-grip-vertical"></i></span>
								<div class="sub-actions">
									<span class="this-remove"><?php echo esc_html__( 'Remove', 'bos4w' ); ?></span>
								</div>
								<div class="subscription-category">
									<div class="subscription-details">
										<p class="form-field _subscription_details_<?php echo esc_attr( $s ); ?>">
											<label for="_subscription_details_<?php echo esc_attr( $s ); ?>"><?php echo esc_html__( 'Cateogy', 'bos4w' ); ?></label>
										<div class="wrap">
											<select class="wc-category-search" multiple="multiple" style="width: 50%;" id="sspw_select_promoted_products" name="bos4w_saved_subs[<?php echo esc_attr( $s ); ?>][product_cat][]" data-placeholder="<?php esc_attr_e( 'Search for a Category&hellip;', 'woocommerce-social-proof-fomo' ); ?>" data-action="woocommerce_json_search_products_and_variations">
												<?php
												if ( $subscription_item['product_cat'] ) {
													foreach ( $subscription_item['product_cat'] as $category ) {
														$current_category = $category ? get_term_by( 'slug', $category, 'product_cat' ) : false;
														echo '<option value="' . esc_attr( $category ) . '"' . selected( true, true, false ) . '>' . esc_html( htmlspecialchars( wp_kses_post( (string) $current_category->name ) ) ) . '</option>';
													}
												}
												?>
											</select>
										</div>
										</p>
									</div>
								</div>
								<div class="subscription-selections">
									<div class="subscription-details">
										<p class="form-field _subscription_details_<?php echo esc_attr( $s ); ?>">
											<label for="_subscription_details_<?php echo esc_attr( $s ); ?>"><?php echo esc_html__( 'Interval', 'bos4w' ); ?></label>
										<div class="wrap">
											<label for="bos4w_subscription_interval_<?php echo esc_attr( $s ); ?>" class="wcs_hidden_label"><?php echo esc_html__( 'Subscription interval', 'bos4w' ); ?></label>
											<select id="bos4w_subscription_interval_<?php echo esc_attr( $s ); ?>" name="bos4w_saved_subs[<?php echo esc_attr( $s ); ?>][subscription_period_interval]" class="first">
												<?php foreach ( wcs_get_subscription_period_interval_strings() as $value => $label ) { ?>
													<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $value, $subscription_interval, true ); ?>><?php echo esc_html( $label ); ?></option>
												<?php } ?>
											</select>
											<label for="bos4w_subscription_period_<?php echo esc_attr( $s ); ?>" class="wcs_hidden_label"><?php echo esc_html__( 'Subscription period', 'bos4w' ); ?></label>
											<select id="bos4w_subscription_period_<?php echo esc_attr( $s ); ?>" name="bos4w_saved_subs[<?php echo esc_attr( $s ); ?>][subscription_period]" class="last">
												<?php foreach ( wcs_get_subscription_period_strings() as $value => $label ) { ?>
													<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $value, $subscription_period, true ); ?>><?php echo esc_html( $label ); ?></option>
												<?php } ?>
											</select>
										</div>
										<?php echo wc_help_tip( esc_html__( 'Choose how frequently you would like to bill and ship this item.', 'bos4w' ) ); ?>
										</p>
									</div>
								</div>
								<div class="subscription_pricing_method subscription_pricing_method_inherit">
									<?php
									woocommerce_wp_text_input(
										array(
											'id'            => '_subscription_discount',
											'name'          => 'bos4w_saved_subs[' . esc_attr( $s ) . '][subscription_discount]',
											'value'         => $subscription_discount,
											'wrapper_class' => 'subscription_price_discount',
											'label'         => __( 'Discount %', 'bos4w' ),
											'description'   => __( 'This discount will be applied on the product price if this frequency is chosen.', 'bos4w' ),
											'desc_tip'      => true,
											'data_type'     => 'decimal',
										)
									);
									?>
								</div>
								<input type="hidden" name="bos4w_saved_subs[<?php echo esc_attr( $s ); ?>][position]" class="position" value="<?php echo isset( $subscription_item['position'] ) ? esc_attr( $s ) : ''; ?>"/>
							</div>
							<?php
							$s ++;
						}
						?>
					<?php } else { ?>
						<div class="bos4w-no-subscriptions-added">
							<p><?php echo esc_html__( 'No frequency has been defined.', 'bos4w' ); ?></p>
						</div>
					<?php } ?>
				</div>

				<p class="subscription_add_new_wrapper">
					<button type="button" class="button add_new_subscription_button"><?php echo esc_html__( 'Add Frequency', 'bos4w' ); ?></button>
				</p>
			</div>
			<?php
		}

		/**
		 * Save data
		 *
		 * @return void
		 */
		public function bos4w_settings_tab_update() {
			if ( isset( $_REQUEST['_bos4w_global_subscription_title'] ) ) {
				update_option( 'bos4w_global_subscription_title', wp_kses_post( wp_unslash( (string) $_REQUEST['_bos4w_global_subscription_title'] ) ) );

				if ( isset( $_REQUEST['bos4w_saved_subs'] ) && ! empty( $_REQUEST['bos4w_saved_subs'] ) ) {
					update_option( 'bos4w_global_saved_subs', wc_clean( stripslashes_deep( $_REQUEST['bos4w_saved_subs'] ) ) );
				} else {
					update_option( 'bos4w_global_saved_subs', '' );
				}
			} else {
				woocommerce_update_options( self::display_settings() );
			}
		}

		/**
		 * Allowed tags for notice
		 *
		 * @return array[][]
		 */
		public function bos4w_allowed_notice_tags() {
			return array(
				'a'   => array(
					'class'  => array(),
					'href'   => array(),
					'rel'    => array(),
					'title'  => array(),
					'target' => array(),
				),
				'div' => array(
					'class' => array(),
					'title' => array(),
					'style' => array(),
				),
				'img' => array(
					'alt'    => array(),
					'class'  => array(),
					'height' => array(),
					'src'    => array(),
					'width'  => array(),
				),
				'li'  => array(
					'class' => array(),
				),
				'ul'  => array(
					'class' => array(),
				),
			);
		}
	}
}

new BOS4W_Admin();

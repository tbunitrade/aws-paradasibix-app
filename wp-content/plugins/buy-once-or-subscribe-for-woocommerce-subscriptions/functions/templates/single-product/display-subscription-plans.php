<?php
/**
 * Display the Dropdown with Subscriptions plans
 *
 * @package Buy Once or Subscribe for WooCommerce Subscriptions
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="bos4w-display-wrap">
	<div class="bos4w-display-plan-text"><?php echo esc_html( $display_text ); ?>:</div>
	<div class="bos4w-display-options">
		<ul>
			<li><label for="bos4w-one-time"><input id="bos4w-one-time" name="bos4w-purchase-type" value="0" type="radio" class="bos4w-buy-type" checked/><?php echo esc_html( $one_time ); ?></label>
			</li>
			<li><label for="bos4w-subscribe-to"><input id="bos4w-subscribe-to" name="bos4w-purchase-type" value="1" type="radio" class="bos4w-buy-type"/><span
							class="bos-display-save-up-to"><?php echo esc_html( $subscribe_and_save ); ?></span></label></li>
		</ul>
	</div>
	<div class="bos4w-display-dropdown">
		<?php if ( $plan_options ) { ?>
			<label for="bos4w-dropdown-plan"><?php echo esc_html( $dropdown_label ); ?></label>
			<?php
			if ( 1 === count( $plan_options ) && 'variation' !== $plan_type ) {
				$product_price   = BOS4W_Front_End::bos4w_get_product_price( $product );
				$use_fixed_price = $product->get_meta( '_bos4w_use_fixed_price' );

				if ( $use_fixed_price ) {
					$bos_type           = 'fixed_price';
					$subscription_price = ! empty( $plan_options[0]['subscription_price'] ) ? floatval( $plan_options[0]['subscription_price'] ) : 0;
					$discounted_price   = wc_format_decimal( $product_price, wc_get_price_decimals() ) - $subscription_price;
				} else {
					$bos_type              = 'percentage_price';
					$subscription_discount = ! empty( $plan_options[0]['subscription_discount'] ) ? floatval( $plan_options[0]['subscription_discount'] ) : 0;
					$discounted_price      = wc_format_decimal( $product_price, wc_get_price_decimals() ) - ( wc_format_decimal( $product_price, wc_get_price_decimals() ) * ( (float) $subscription_discount / 100 ) );
				}

				$selling_price = wc_price( $discounted_price );

				$display_discount = '';
				if ( isset( $plan_options[0]['subscription_discount'] ) && $plan_options[0]['subscription_discount'] > 0 ) {
					$display_discount = sprintf( ' (%s&#37; %s)', esc_attr( $plan_options[0]['subscription_discount'] ), esc_html__( 'off', 'bos4w' ) );
				}
				if ( isset( $plan_options[0]['subscription_price'] ) && $plan_options[0]['subscription_price'] > 0 ) {
					$display_discount = sprintf( ' (%s %s)', esc_attr( strip_tags( wc_price( $plan_options[0]['subscription_price'] ) ) ), esc_html__( 'off', 'bos4w' ) );
				}

				// Safely create the subscription period interval string for simple or global plans.
				$period_interval = isset( $plan_options[0]['subscription_period_interval'] ) && isset( $plan_options[0]['subscription_period'] ) ? wcs_get_subscription_period_strings( $plan_options[0]['subscription_period_interval'], $plan_options[0]['subscription_period'] ) : '';

				$discount_plan = $use_fixed_price && isset( $plan_options[0]['subscription_price'] ) ? esc_attr( $plan_options[0]['subscription_price'] ) : esc_attr( $plan_options[0]['subscription_discount'] );
				$discount_plan = $discount_plan ?? 0;

				/* translators: %s: interval & discount */
				$display_plans = sprintf( esc_html__( 'Every %1$s for %2$s %3$s', 'bos4w' ), wp_kses_data( $period_interval ), wp_kses_data( $selling_price ), wp_kses_data( $display_discount ) );
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
				$output_plan = apply_filters( 'ssd_subscription_plan_display', esc_html( $display_plans ), esc_attr( $period_interval ), esc_attr( $selling_price ), esc_attr( $display_discount ) );

				$display_text_settings = get_option( 'ssd_subscription_plan_display' );
				if ( $display_text_settings ) {
					$output_plan = sprintf(
					/* translators: %s: interval & discount */
						$display_text_settings,
						wp_kses_data( $period_interval ),
						wp_kses_data( $selling_price ),
						wp_kses_data( $display_discount )
					);
				}

				echo esc_html( $output_plan );

				echo sprintf(
					'<input type="hidden" name="convert_to_sub_plan_%d" id="bos4w-dropdown-plan" data-discount="%s" data-price="%s" data-type="%s" value="%s"/>',
					absint( $product_id ),
					esc_attr( $discount_plan ),
					esc_attr( wc_format_decimal( $discounted_price, wc_get_price_decimals() ) ),
					esc_attr( $bos_type ),
					esc_attr( $plan_options[0]['subscription_period_interval'] . '_' . $plan_options[0]['subscription_period'] . '_' . $discount_plan )
				);
			} else {
				?>
				<select id="bos4w-dropdown-plan" name="convert_to_sub_plan_<?php echo absint( $product_id ); ?>">
					<?php
					if ( 'variation' === $plan_type ) {
						foreach ( $plan_options as $variation_id => $plans ) {
							$variation = wc_get_product( $variation_id );
							if ( ! $variation ) {
								continue;
							}

							$product_price   = BOS4W_Front_End::bos4w_get_product_price( $variation );
							$use_fixed_price = $variation->get_meta( '_bos4w_use_fixed_price' );

							foreach ( $plans as $plan ) {
								if ( $use_fixed_price ) {
									$bos_type           = 'fixed_price';
									$subscription_price = ! empty( $plan['subscription_price'] ) ? floatval( $plan['subscription_price'] ) : 0;
									$discounted_price   = wc_format_decimal( $product_price, wc_get_price_decimals() ) - $subscription_price;
								} else {
									$bos_type              = 'percentage_price';
									$subscription_discount = ! empty( $plan['subscription_discount'] ) ? floatval( $plan['subscription_discount'] ) : 0;
									$discounted_price      = wc_format_decimal( $product_price, wc_get_price_decimals() ) - ( wc_format_decimal( $product_price, wc_get_price_decimals() ) * ( (float) $subscription_discount / 100 ) );
								}

								$selling_price = wc_price( $discounted_price );

								$display_discount = '';
								if ( isset( $plan['subscription_discount'] ) && $plan['subscription_discount'] > 0 ) {
									$display_discount = sprintf( ' (%s&#37; %s)', esc_attr( $plan['subscription_discount'] ), esc_html__( 'off', 'bos4w' ) );
								}
								if ( isset( $plan['subscription_price'] ) && $plan['subscription_price'] > 0 ) {
									$display_discount = sprintf( ' (%s %s)', esc_attr( strip_tags( wc_price( $plan['subscription_price'] ) ) ), esc_html__( 'off', 'bos4w' ) );
								}

								// Safely create the subscription period interval string for variations.
								$period_interval = isset( $plan['subscription_period_interval'] ) && isset( $plan['subscription_period'] ) ? wcs_get_subscription_period_strings( $plan['subscription_period_interval'], $plan['subscription_period'] ) : '';

								$discount_plan = $use_fixed_price ? esc_attr( $plan['subscription_price'] ) : esc_attr( $plan['subscription_discount'] );
								$discount_plan = $discount_plan ?? 0;
								?>
								<option data-discount="<?php echo (float) $discount_plan; ?>" data-price="<?php echo (float) wc_format_decimal( $discounted_price, wc_get_price_decimals() ); ?>"
										data-type="<?php echo esc_attr( $bos_type ); ?>"
										value="<?php echo esc_attr( $plan['subscription_period_interval'] ) . '_' . esc_attr( $plan['subscription_period'] ) . '_' . (float) $discount_plan; ?>">
									<?php
									/* translators: %s: interval & discount */
									$display_plans = sprintf( esc_html__( 'Every %1$s for %2$s %3$s', 'bos4w' ), wp_kses_data( $period_interval ), wp_kses_data( $selling_price ), wp_kses_data( $display_discount ) );
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
									$output_plan = apply_filters( 'ssd_subscription_plan_display', esc_html( $display_plans ), esc_attr( $period_interval ), esc_attr( $selling_price ), esc_attr( $display_discount ) );

									$display_text_settings = get_option( 'ssd_subscription_plan_display' );
									if ( $display_text_settings ) {
										$output_plan = sprintf(
										/* translators: %s: interval & discount */
											$display_text_settings,
											wp_kses_data( $period_interval ),
											wp_kses_data( $selling_price ),
											wp_kses_data( $display_discount )
										);
									}

									echo esc_html( $output_plan );
									?>
								</option>
								<?php
							}
						}
					} else {
						foreach ( $plan_options as $plan ) {
							$product_price   = BOS4W_Front_End::bos4w_get_product_price( $product );
							$use_fixed_price = $product->get_meta( '_bos4w_use_fixed_price' );

							if ( $use_fixed_price ) {
								$bos_type           = 'fixed_price';
								$subscription_price = ! empty( $plan['subscription_price'] ) ? floatval( $plan['subscription_price'] ) : 0;
								$discounted_price   = wc_format_decimal( $product_price, wc_get_price_decimals() ) - $subscription_price;
							} else {
								$bos_type              = 'percentage_price';
								$subscription_discount = ! empty( $plan['subscription_discount'] ) ? floatval( $plan['subscription_discount'] ) : 0;
								$discounted_price      = wc_format_decimal( $product_price, wc_get_price_decimals() ) - ( wc_format_decimal( $product_price, wc_get_price_decimals() ) * ( (float) $subscription_discount / 100 ) );
							}

							$selling_price = wc_price( $discounted_price );

							$display_discount = '';
							if ( isset( $plan['subscription_discount'] ) && $plan['subscription_discount'] > 0 ) {
								$display_discount = sprintf( ' (%s&#37; %s)', esc_attr( $plan['subscription_discount'] ), esc_html__( 'off', 'bos4w' ) );
							}
							if ( isset( $plan['subscription_price'] ) && $plan['subscription_price'] > 0 ) {
								$display_discount = sprintf( ' (%s %s)', esc_attr( strip_tags( wc_price( $plan['subscription_price'] ) ) ), esc_html__( 'off', 'bos4w' ) );
							}

							// Safely create the subscription period interval string for simple or global plans.
							$period_interval = isset( $plan['subscription_period_interval'] ) && isset( $plan['subscription_period'] ) ? wcs_get_subscription_period_strings( $plan['subscription_period_interval'], $plan['subscription_period'] ) : '';

							$discount_plan = $use_fixed_price && isset( $plan['subscription_price'] ) ? esc_attr( $plan['subscription_price'] ) : esc_attr( $plan['subscription_discount'] );
							$discount_plan = $discount_plan ?? 0;
							?>
							<option data-discount="<?php echo (float) $discount_plan; ?>" data-price="<?php echo (float) wc_format_decimal( $discounted_price, wc_get_price_decimals() ); ?>"
									data-type="<?php echo esc_attr( $bos_type ); ?>"
									value="<?php echo esc_attr( $plan['subscription_period_interval'] ) . '_' . esc_attr( $plan['subscription_period'] ) . '_' . (float) $discount_plan; ?>">
								<?php
								/* translators: %s: interval & discount */
								$display_plans = sprintf( esc_html__( 'Every %1$s for %2$s %3$s', 'bos4w' ), wp_kses_data( $period_interval ), wp_kses_data( $selling_price ), wp_kses_data( $display_discount ) );
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
								$output_plan = apply_filters( 'ssd_subscription_plan_display', esc_html( $display_plans ), esc_attr( $period_interval ), esc_attr( $selling_price ), esc_attr( $display_discount ) );

								$display_text_settings = get_option( 'ssd_subscription_plan_display' );
								if ( $display_text_settings ) {
									$output_plan = sprintf(
									/* translators: %s: interval & discount */
										$display_text_settings,
										wp_kses_data( $period_interval ),
										wp_kses_data( $selling_price ),
										wp_kses_data( $display_discount )
									);
								}

								echo esc_html( $output_plan );
								?>
							</option>
							<?php
						}
					}
					?>
				</select>
			<?php } ?>
			<span class="bos4w-one-plan-only"></span>
			<input type="hidden" name="bos4w-selected-price" id="bos4w-selected-price"/>
		<?php } ?>
	</div>
</div>

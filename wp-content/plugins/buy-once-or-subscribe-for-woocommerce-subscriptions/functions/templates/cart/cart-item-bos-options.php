<?php
/**
 * Display item product BOS plans.
 *
 * @package Buy Once or Subscribe for WooCommerce Subscriptions
 * @since   5.1.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<ul class="bos4w-select-options">
	<?php
	foreach ( $product_plans as $plan ) {
		if ( empty( $plan ) ) {
			continue;
		}

		$selected = isset( $plan['selected']['selected_subscription'] ) ? $plan['selected']['selected_subscription'] : false;

		if ( isset( $plan['value']['interval'] ) && isset( $plan['value']['period'] ) ) {
			$period_interval = wcs_get_subscription_period_strings( $plan['value']['interval'], $plan['value']['period'] );

			$value = $plan['value']['interval'] . '_' . $plan['value']['period'] . '_' . (float) $plan['value']['discount'];
			?>
			<li class="bos4w-select-option">
				<label>
					<input type="radio" name="bos4w_cart_item[<?php echo esc_attr( wp_unslash( $cart_item_key ) ); ?>]" data-key="<?php echo esc_attr( wp_unslash( $cart_item_key ) ); ?>" data-id="<?php echo absint( $plan['value']['product_id'] ); ?>"
						   data-discount="<?php echo (float) $plan['value']['discount']; ?>" data-price="<?php echo (float) wc_format_decimal( $plan['value']['price'], wc_get_price_decimals() ); ?>"
						   data-type="<?php echo esc_attr( $plan['value']['type'] ); ?>" value="<?php echo esc_attr( $value ); ?>" <?php checked( $selected, $value, true ); ?> />
					<?php echo wp_kses_post( wcs_price_string( BOS4W_Cart_Options::bos4w_display_format_the_frequency( wc_format_decimal( esc_attr( $plan['value']['price'] ), wc_get_price_decimals() ), esc_attr( $plan['value']['period'] ), esc_attr( $plan['value']['interval'] ) ) ) ); ?>
				</label>
			</li>
			<?php
		} else {
			?>
			<li class="bos4w-select-option">
				<label>
					<input type="radio" name="bos4w_cart_item[<?php echo esc_attr( wp_unslash( $cart_item_key ) ); ?>]" data-key="<?php echo esc_attr( wp_unslash( $cart_item_key ) ); ?>" data-id="<?php echo absint( $plan['value']['product_id'] ); ?>"
						   data-discount="" data-price="" data-type="" value="<?php echo esc_attr( $plan['value']['price'] ); ?>" <?php checked( $selected, false, true ); ?> />
					<?php echo wp_kses_post( wc_price( wc_format_decimal( esc_attr( $plan['value']['price'] ), wc_get_price_decimals() ) ) ); ?>
				</label>
			</li>
			<?php
		}
	}
	?>
</ul>




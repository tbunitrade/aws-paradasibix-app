<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CR_Woo_Emails' ) ) :

class CR_Woo_Emails {

	public function __construct() {
		add_filter(
			'woocommerce_email_additional_content_customer_processing_order',
			array( $this, 'shortcodes_in_emails' ),
			10,
			3
		);
		add_filter(
			'woocommerce_email_additional_content_customer_completed_order',
			array( $this, 'shortcodes_in_emails' ),
			10,
			3
		);
	}

	public function shortcodes_in_emails( $additional_content, $object, $email ) {
		if ( empty( $additional_content ) ) {
			return $additional_content;
		}
		if ( empty( $object ) || ! is_a( $object, 'WC_Order' ) ) {
			return $additional_content;
		}
		if ( 'no' !== get_option( 'ivole_verified_reviews', 'no' )  ) {
			// shortcodes are available with the self-hosted setting only initially
			return $additional_content;
		}

		$order_id = $object->get_id();

		$pattern = '/\[cusrev_review_button([^\]]*)\]/';
		return preg_replace_callback(
			$pattern,
			function( $matches ) use ( $order_id ) {

				$atts_string = trim( $matches[1] );
				$atts = shortcode_parse_atts( $atts_string );

				if ( ! is_array( $atts ) ) {
					$atts = array();
				}

				// check if the order is a real one
				if ( ! wc_get_order( $order_id ) ) {
					$order_id = 0;
				}

				return $this->render_review_button_shortcode( $atts, $order_id );
			},
			$additional_content
		);
	}

	public function render_review_button_shortcode( $atts, $order_id = 0 ) {
		$atts = shortcode_atts(
			array(
				'label'	=> __( 'Review', 'customer-reviews-woocommerce' ),
				'bg'		=> '#0073aa',
				'color'		=> '#ffffff',
				'radius'	=> '4px',
			),
			$atts,
			'cusrev_review_button'
		);

		$label = $atts['label'];
		$url = '';

		$link = new CR_Copy_Link( $order_id );
		$review_form = $link->get_review_form( $order_id );
		if ( is_array( $review_form ) && count( $review_form )  > 1 ) {
			if ( 0 !== $review_form[0] ) {
				$label = $review_form[1];
			} else {
				// success
				$url = $review_form[1];
			}
		} else {
			$label = __( 'Error: could not copy a link to an aggregated review form', 'customer-reviews-woocommerce' );
		}

		$bg		= sanitize_hex_color( $atts['bg'] ) ?: '#0073aa';
		$color	= sanitize_hex_color( $atts['color'] ) ?: '#ffffff';
		$radius	= preg_replace( '/[^0-9.%px]/', '', $atts['radius'] );

		$output  = '<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">';
		$output .= '<tr>';
		$output .= '<td align="center">';

		$output .= '<table role="presentation" border="0" cellpadding="0" cellspacing="0">';
		$output .= '<tr>';
		$output .= '<td align="center" bgcolor="' . esc_attr( $bg ) . '" style="border-radius:' . esc_attr( $radius ) . ';">';
		$output .= '<a href="' . esc_url( $url ) . '" target="_blank" ';
		$output .= 'style="display:inline-block;padding:12px 24px;';
		$output .= 'color:' . esc_attr( $color ) . ';';
		$output .= 'text-decoration:none;border-radius:' . esc_attr( $radius ) . ';">';
		$output .= esc_html( $label );
		$output .= '</a>';
		$output .= '</td>';
		$output .= '</tr>';
		$output .= '</table>';

		$output .= '</td>';
		$output .= '</tr>';
		$output .= '</table>';

		return $output;
	}

}

endif;

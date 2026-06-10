<?php
/**
 * @version 10.5.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * For this template, the following variables are available
 *
 * @var $product \WC_Product
 * @var $comment \WP_Comment
 */

?>

<li style="clear:both;">
	<?php
	// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
	?>

	<?php echo get_avatar( $comment->comment_author_email, '32' ); ?>

	<?php echo wc_get_rating_html( (int) get_comment_meta( $comment->comment_ID, 'rating', true ) ); ?>

	<h4 class="meta">
		<a href="<?php echo esc_url( get_comment_link( $comment->comment_ID ) ); ?>">
			<?php
				if ( $product && method_exists( $product, 'get_name' ) ) {
					echo wp_kses_post( $product->get_name() );
				} else {
					echo wp_kses_post( get_the_title( $comment->comment_post_ID ) );
				}
			?>
		</a>
		<?php
		/* translators: %s: review author */
		printf( esc_html__( 'reviewed by %s', 'woocommerce' ), esc_html( get_comment_author( $comment->comment_ID ) ) );
		?>
	</h4>

	<blockquote><?php echo wp_kses_data( $comment->comment_content ); ?></blockquote>

	<?php
	// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
	?>
</li>

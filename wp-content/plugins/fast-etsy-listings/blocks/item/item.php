<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 * Functions to register client-side assets (scripts and stylesheets) for the
 * Gutenberg block.
 *
 * @package fast-etsy-listings
 */

/**
 * Define render callback. 
 *
 */
function fuEtsyItemRenderCallback($atts)
{
  // Attributes
  extract(shortcode_atts(array(
      'item' => '',
      'picwidth' => -1,
  ), $atts));

  $apicall = new fuEtsyItemApiCall($item);
  $apicall->picWidth = intval($picwidth);
  return $apicall->call();
}


/**
 * Registers all block assets so that they can be enqueued through Gutenberg in
 * the corresponding context.
 *
 */
function fu_etsy_item_block_init() 
{
	// Skip block registration if Gutenberg is not enabled/merged.
	if ( ! function_exists( 'register_block_type' ) )
		return;

  register_block_type( 
    __DIR__,
    array(
      'render_callback' => 'fuEtsyItemRenderCallback',
    ) 
  );
}
add_action( 'init', 'fu_etsy_item_block_init' );

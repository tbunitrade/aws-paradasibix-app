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
function fuEtsyShopListingshRenderCallback($atts)
{
  // Attributes
  extract(shortcode_atts(array(
    'title' => '',
    'query' => '',
    'seller' => '',
    'featured' => false,
    'columns' => -1,
    'rows' => -1,
    'picwidth' => -1,
    'sort' => '',
    'slideshow' => '',
    'slides' => -1,
  ), $atts));

  $apicall = new fuEtsySearchApiCall($title);
  $apicall->picWidth = intval($picwidth);
  $apicall->presentation->setTableSize($columns, $rows);
  $apicall->presentation->setSlideShow($slideshow, $slides);
  
  $apicall->setQuery($query);
  $apicall->setSellers($seller);
  $apicall->setFeatured($featured);
  $apicall->setSortOrder($sort);
  return $apicall->call();

}


/**
 * Registers all block assets so that they can be enqueued through Gutenberg in
 * the corresponding context.
 *
 */
function fu_etsy_shoplistings_block_init() {
	// Skip block registration if Gutenberg is not enabled/merged.
	if ( ! function_exists( 'register_block_type' ) )
		return;

  register_block_type( 
    __DIR__,
    array(
      'render_callback' => 'fuEtsyShopListingshRenderCallback',
    ) 
  );  
}
add_action( 'init', 'fu_etsy_shoplistings_block_init' );

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
function fuEtsyFeedbackRenderCallback($atts)
{
  // Attributes
  extract(shortcode_atts(array(
      'seller' => '',
      'accountinfo' => 1,
      'comments' => 1,
      'limit' => -1,
      'columns' => 1,
      'rows' => 10,
      'slideshow' => -1,
      'slides' => -1,
  ), $atts));

  // if we have a a limit, then we're using legacy params, so translate to rows/cols.
  if ($limit >= 0)
  {
    $rows = $limit;
    $columns = 1;
  }
  
  $apicall = new fuEtsyFeedbackApiCall($seller);
  $apicall->presentation->setTableSize($columns, $rows);
  $apicall->presentation->setSlideShow($slideshow, $slides);
  $apicall->showAccountInfo = $accountinfo ? 1 : 0;
  $apicall->showFeedbackComments = $comments ? 1 : 0;
  return $apicall->call();
}


/**
 * Registers all block assets so that they can be enqueued through Gutenberg in
 * the corresponding context.
 *
 */
function fu_etsy_feedback_block_init() 
{
	// Skip block registration if Gutenberg is not enabled/merged.
	if ( ! function_exists( 'register_block_type' ) )
		return;

  register_block_type( 
    __DIR__,
    array(
      'render_callback' => 'fuEtsyFeedbackRenderCallback',
    ) 
  );
}
add_action( 'init', 'fu_etsy_feedback_block_init' );

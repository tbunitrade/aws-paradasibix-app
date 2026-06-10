<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once __DIR__."/apicall_item.php";

//////////////////////////////////////////////////////////////////////////
//
// Handle a single item search.
//
//////////////////////////////////////////////////////////////////////////
class fuEtsyShortcodeListing extends fuShortcodeBase
{
	/**
	 * __construct 
	 * class constructor will set the needed filter and action hooks
	 * 
	 * @param array $args 
	 */
	function __construct($args = array()){
	  $this->shortcode_tag = 'etsy_listing';
	  $this->mce_plugin_url = plugins_url('mce/mce-button-listing.js?v='.FU_ETSY_PLUGIN_VER, __FILE__);
	  parent::__construct();
	}

	/**
   * {@inheritdoc}
	 */
	function shortcode_handler($atts , $content = null){
		// Attributes
    extract(shortcode_atts(array(
        'id' => '',
        'item' => '',
        'picwidth' => -1,
        'arrangement' => -1
    ), $atts));
  
    if (empty($item)) $item = $id;
    $apicall = new fuEtsyItemApiCall($item);
    $apicall->picWidth = intval($picwidth);
    if ($arrangement != -1) $apicall->arrangement = (int)$arrangement;
    return $apicall->call();
	}
}//end class

new fuEtsyShortcodeListing();

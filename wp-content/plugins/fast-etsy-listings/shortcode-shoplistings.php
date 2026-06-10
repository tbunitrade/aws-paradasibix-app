<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once __DIR__."/shortcodes.php";
require_once __DIR__."/apicall_search.php";


//////////////////////////////////////////////////////////////////////////
//
// Handle a search of Shop listings
//
//////////////////////////////////////////////////////////////////////////
class fuEtsyShortcodeShopListings extends fuShortcodeBase
{
	/**
	 * __construct 
	 * class constructor will set the needed filter and action hooks
	 * 
	 * @param array $args 
	 */
	function __construct($args = array()){
	  $this->shortcode_tag = 'etsy_shop_listings';
	  $this->mce_plugin_url = plugins_url('mce/mce-button-shop-listings.js?v='.FU_ETSY_PLUGIN_VER, __FILE__);
	  parent::__construct();
	}

	/**
   * {@inheritdoc}
	 */
	function shortcode_handler($atts , $content = null){
		// Attributes
    extract(shortcode_atts(array(
        'query' => '',
        'seller' => '',
        'featured' => false,
        'columns' => -1,
        'rows' => -1,
        'picwidth' => -1,
        'sort' => '',
        'slideshow' => '',
        'slides' => -1,
        'arrangement' => -1
     ), $atts));
  
    $apicall = new fuEtsySearchApiCall($content);
    $apicall->picWidth = intval($picwidth);
    $apicall->presentation->setTableSize($columns, $rows);
    $apicall->presentation->setSlideShow($slideshow, $slides);
    
    $apicall->setQuery($query);
    $apicall->setSellers($seller);
    $apicall->setFeatured($featured);
    $apicall->setSortOrder($sort);
    if ($arrangement != -1) $apicall->arrangement = (int)$arrangement;    
    return $apicall->call();
	}

}//end class

new fuEtsyShortcodeShopListings();
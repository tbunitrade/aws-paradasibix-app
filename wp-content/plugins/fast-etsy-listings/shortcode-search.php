<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once __DIR__."/shortcodes.php";
require_once __DIR__."/apicall_search.php";


//////////////////////////////////////////////////////////////////////////
//
// Handle a search of items
//
//////////////////////////////////////////////////////////////////////////
class fuEtsyShortcodeSearch extends fuShortcodeBase
{
	/**
	 * __construct 
	 * class constructor will set the needed filter and action hooks
	 * 
	 * @param array $args 
	 */
	function __construct($args = array()){
	  $this->shortcode_tag = 'etsy_search';
	  $this->mce_plugin_url = plugins_url('mce/mce-button-search.js?v='.FU_ETSY_PLUGIN_VER, __FILE__);
	  parent::__construct();
	}

	/**
   * {@inheritdoc}
	 */
	function shortcode_handler($atts , $content = null){
		// Attributes
    extract(shortcode_atts(array(
        'category' => '',
        'query' => '',
        'searchlocation' => '',
        'columns' => -1,
        'rows' => -1,
        'picwidth' => -1,
        'minprice' => -1,
        'maxprice' => -1,
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
    $apicall->setSearchLocation($searchlocation);
    $apicall->setCategories($category);
    $apicall->setSellers("all");
    $apicall->minPrice = $minprice;
    $apicall->maxPrice = $maxprice;
    $apicall->setSortOrder($sort);
    if ($arrangement != -1) $apicall->arrangement = (int)$arrangement;    
    return $apicall->call();
	}

}//end class

new fuEtsyShortcodeSearch();
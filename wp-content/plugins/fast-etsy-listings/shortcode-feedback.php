<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once __DIR__."/apicall_feedback.php";

//////////////////////////////////////////////////////////////////////////
//
// Handle a feedback search.
//
//////////////////////////////////////////////////////////////////////////
class fuEtsyShortcodeFeedback extends fuShortcodeBase
{
	/**
	 * __construct 
	 * class constructor will set the needed filter and action hooks
	 * 
	 * @param array $args 
	 */
	function __construct($args = array()){
	  $this->shortcode_tag = 'etsy_feedback';
	  $this->mce_plugin_url = plugins_url('mce/mce-button-feedback.js?v='.FU_ETSY_PLUGIN_VER, __FILE__);
	  parent::__construct();
	}

	/**
	 * shortcode_handler
	 * @param  array  $atts shortcode attributes
	 * @param  string $content shortcode content
	 * @return string
	 */
	function shortcode_handler($atts , $content = null){
		// Attributes
    extract(shortcode_atts(array(
        'seller' => '',
        'accountinfo' => 1,
        'comments' => 1,
        'limit' => -1,
        'columns' => 1,
        'rows' => 10,
        'slideshow' => '',
        'slides' => -1
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
}//end class

new fuEtsyShortcodeFeedback();

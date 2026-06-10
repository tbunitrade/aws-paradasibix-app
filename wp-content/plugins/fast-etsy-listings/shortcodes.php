<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once __DIR__."/includes/shortcodes.inc.php";

//////////////////////////////////////////////////////////////////////////
// Load shortcode translations.
//
function fu_etsy_add_tinymce_lang( $arr )
{
    $arr[] = WP_PLUGIN_DIR . '/' . basename(__DIR__) . '/shortcode-lang-file.php';
    return $arr;
}
add_filter( 'mce_external_languages', 'fu_etsy_add_tinymce_lang', 10, 1 );

//////////////////////////////////////////////////////////////////////////
// Setup ShortCode MCE ToolBar Buttons
//
class fuEtsyShortcodeMCEToolbar extends fuShortcodeMCEToolbar
{
  /**
   * {@inheritdoc}
   */
  function mce_external_plugins( $plugin_array ) {
    $plugin_array['fast_etsy_listings'] = plugins_url('mce/mce-button-core.js?v='.FU_ETSY_PLUGIN_VER, __FILE__ );
    return $plugin_array;
  }

  /**
   * {@inheritdoc}
   */
  function mce_buttons( $buttons ) {
    array_push( $buttons, 'fast_etsy_listings' );
    return $buttons;
  }

  /**
   * {@inheritdoc}
   */
  function admin_enqueue_scripts()
  {
    wp_register_script( 'fu_etsy_shortcode_script', '', array(), FU_ETSY_PLUGIN_VER, false);
    wp_enqueue_script('fu_etsy_shortcode_script');
    wp_localize_script('fu_etsy_shortcode_script', 'fuEtsyScriptShortcode', array(
      'pluginsUrl' => plugins_url('', __FILE__) . '/../',
      'defCategory' => get_option("fuEtsyDefCategory"),
      'defSeller' => get_option("fuEtsyDefSeller"),
      'defCustomID' => get_option("fuEtsyDefCustomID"),
      'defColumns' => get_option("fuEtsyDefColumns"),
      'defRows' => get_option("fuEtsyDefRows"),
      'picWidthItem' => get_option("fuEtsyPicWidthItem"),
      'picWidthList' => get_option("fuEtsyPicWidthList"),
      'defNumSlides' => get_option("fuEtsyDefNumSlides"),
    ));
  }  
}

new fuEtsyShortcodeMCEToolbar();
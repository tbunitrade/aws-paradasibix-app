<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

//////////////////////////////////////////////////////////////////////////
//
// Class with general utility funcs
//
//////////////////////////////////////////////////////////////////////////


////////////////////////////////////////////////////////////////////////////////////////////////////////

require_once __DIR__."/constants.php";


////////////////////////////////////////////////////////////////////////////////////////////////////////
// Add CSS styles to header.

function fu_etsy_addstyles() 
{
  // load up styles file to add inline
  $styleContents = "";
  $mainStyleFile = WP_PLUGIN_DIR . '/' . basename(__DIR__) . '/styles.css';
  $incStyleFile = WP_PLUGIN_DIR . '/' . basename(__DIR__) . '/includes/styles.inc.css';

  $styleContents .= fuUtils::GetLocalFileContents($mainStyleFile);
  $styleContents .= fuUtils::GetLocalFileContents($incStyleFile);

  // append any programatically derived styles
  $maxTitleLines = (int)get_option('fuEtsyTitleMaxLines', 0);
  if ($maxTitleLines > 0)
  $styleContents .= "\n.fu_etsy_title a {overflow:hidden;text-overflow:ellipsis;display:-webkit-box;-webkit-line-clamp:$maxTitleLines;line-clamp:$maxTitleLines; -webkit-box-orient:vertical;}";

  $maxDescLines = (int)get_option('fuEtsyShortDescMaxLines', 0);
  if ($maxDescLines > 0)
  $styleContents .= "\n.fu_etsy_desc {overflow:hidden;text-overflow:ellipsis;display:-webkit-box;-webkit-line-clamp:$maxDescLines;line-clamp:$maxDescLines; -webkit-box-orient:vertical;}";

  wp_register_style('fu_etsy_style', '', array(), FU_ETSY_PLUGIN_VER);
  wp_enqueue_style( 'fu_etsy_style', '', array(), FU_ETSY_PLUGIN_VER);
  wp_add_inline_style('fu_etsy_style', $styleContents);
}
add_action('wp_print_styles', 'fu_etsy_addstyles');

function fu_etsy_addadminstyles() 
{
  wp_enqueue_style('fu_etsy_style', plugins_url('styles.css', __FILE__ ), array(), FU_ETSY_PLUGIN_VER);
  wp_enqueue_style('fu_inc_style', plugins_url('includes/styles.inc.css', __FILE__ ), array(), FU_ETSY_PLUGIN_VER);
  wp_enqueue_style('fu_etsy_adminstyle', plugins_url('admin_styles.css', __FILE__ ), array(), FU_ETSY_PLUGIN_VER);
  wp_enqueue_style('fu_etsy_catstyle', plugins_url('/cats/cat_styles.css', __FILE__ ), array(), FU_ETSY_PLUGIN_VER);
}
add_action('admin_head', 'fu_etsy_addadminstyles');

////////////////////////////////////////////////////////////////////////////////////////////////////////
// Add JS scripts to header.

function fu_etsy_addscripts() 
{
  $scriptFile = WP_PLUGIN_DIR . '/' . basename(__DIR__) . '/includes/script.inc.js';

  // Ensure jquery is enqueued as a dependency
  if (!wp_script_is('jquery', 'done'))
    wp_enqueue_script( 'jquery' );  

  // Add in some constants from plugin config
  $scriptContents = "/*FEL v" . FU_ETSY_PLUGIN_VER . "*/" . PHP_EOL;
  $scriptContents .= fuUtils::GetBotUserAgentsJS();

  // Slideshow Timer
  $slideshowTimer = intval(get_option('fuEtsySlideshowTimer', FU_DEFSLIDESHOWTIMER)) * 1000;
  if ($slideshowTimer <= 0) $slideshowTimer = FU_DEFSLIDESHOWTIMER * 1000;
  $scriptContents .= "if (typeof window.fu_slideshowtimer === \"undefined\") window.fu_slideshowtimer={$slideshowTimer};" . PHP_EOL;
  $scriptContents .= fuUtils::GetLocalFileContents($scriptFile);

  wp_register_script('fu_etsy_script', '', array('wp-i18n'), FU_ETSY_PLUGIN_VER, false);
  wp_enqueue_script('fu_etsy_script');	
  wp_add_inline_script('fu_etsy_script', $scriptContents, 'after');
}
add_action('wp_enqueue_scripts', 'fu_etsy_addscripts');

function fu_etsy_addadminscripts() 
{
  if (!boolval(get_option('fuEtsyDisableCatChooser', 0)))
  {
    wp_register_script('fu_etsy_cat_script', plugins_url( '/cats/cat_script.js', __FILE__ ),  array('jquery', 'wp-i18n'), FU_ETSY_PLUGIN_VER, false);
    wp_enqueue_script('fu_etsy_cat_script');
    wp_set_script_translations('fu_etsy_cat_script', 'fast-etsy-listings');
  }

  wp_register_script('fu_etsy_admin_script', plugins_url( '/admin_script.js', __FILE__ ),  array('jquery', 'wp-i18n'), FU_ETSY_PLUGIN_VER, false);
  wp_enqueue_script('fu_etsy_admin_script');
  wp_set_script_translations('fu_etsy_admin_script', 'fast-etsy-listings');

  // Main script needed to preview slideshows etc in Block editor
  wp_register_script('fu_etsy_script', plugins_url( '/includes/script.inc.js', __FILE__ ),  array('jquery', 'wp-i18n'), FU_ETSY_PLUGIN_VER . time(), false);
  wp_enqueue_script('fu_etsy_script');
  wp_set_script_translations('fu_etsy_script', 'fast-etsy-listings');
  $slideshowTimer = intval(get_option('fuEtsySlideshowTimer', FU_DEFSLIDESHOWTIMER)) * 1000;
  if ($slideshowTimer <= 0) $slideshowTimer = FU_DEFSLIDESHOWTIMER * 1000;
  wp_add_inline_script('fu_etsy_script', "if (typeof window.fu_slideshowtimer === \"undefined\") window.fu_slideshowtimer={$slideshowTimer};" . PHP_EOL);

  // values from config needed within Block editor JS.
  wp_register_script('fu_etsy_block_script', '', array(), FU_ETSY_PLUGIN_VER, false);    
  wp_enqueue_script('fu_etsy_block_script');
  wp_localize_script('fu_etsy_block_script', 'fuEtsyScriptBlock', array(
		'adminAjaxUrl' => admin_url('admin-ajax.php'),
		'pluginsUrl' => plugins_url('', __FILE__) . '/',
    'defCategory' => get_option("fuEtsyDefCategory"),
    'defSeller' => get_option("fuEtsyDefSeller"),
    'defSearchLocation' => get_option("fuEtsyDefSearchLocation"),
    'defColumns' => get_option("fuEtsyDefColumns"),
    'defRows' => get_option("fuEtsyDefRows"),
    'picWidthItem' => get_option("fuEtsyPicWidthItem"),
    'picWidthList' => get_option("fuEtsyPicWidthList"),
    'defNumSlides' => get_option("fuEtsyDefNumSlides"),
    'searchLocations' => fuEtsy::Countries,
	));
}
add_action('admin_enqueue_scripts', 'fu_etsy_addadminscripts');




////////////////////////////////////////////////////////////////////////////////////////////////////////
// Logic run following plugin upgrade or install

/**
 * This function runs when WordPress completes its upgrade process
 * It iterates through each plugin updated to see if ours is included
 * @param $upgrader_object Array
 * @param $options Array
 */
function fu_etsy_upgrade_completed( $upgrader_object, $options ) 
{
  // The path to our plugin's main file
  $our_plugin = plugin_basename( __FILE__ );

  // If an update has taken place and the updated type is plugins and the plugins element exists
  if( $options['action'] == 'update' && $options['type'] == 'plugin' && isset( $options['plugins'] ) ) 
  {
    // Iterate through the plugins being updated and check if ours is there
    foreach( $options['plugins'] as $plugin ) 
    {
      if( $plugin == $our_plugin ) 
      {
        // Set a transient to record that our plugin has just been updated
        set_transient( 'fu_etsy_updated', 1 );
      }
    }
  }
}
add_action( 'upgrader_process_complete', 'fu_etsy_upgrade_completed', 10, 2 );

/**
 * Show a notice to anyone who has just updated this plugin
 * This notice shouldn't display to anyone who has just installed the plugin for the first time
 */
function fu_etsy_display_update_notice() 
{
  // Check the transient to see if we've just updated the plugin
  if( get_transient( 'fu_etsy_updated' ) ) 
  {
    //echo '<div class="notice notice-success">' . __( 'Thanks for updating', "fast-etsy-listings" ) . '</div>';
    delete_transient( 'fu_etsy_updated' );
  }
}
add_action( 'admin_notices', 'fu_etsy_display_update_notice' );

/**
 * Show a notice to anyone who has just installed the plugin for the first time
 * This notice shouldn't display to anyone who has just updated this plugin
 */
function fu_etsy_display_install_notice() 
{
  // Check the transient to see if we've just activated the plugin
  if( get_transient( 'fu_etsy_activated' ) ) 
  {
    echo '<div class="notice notice-success"><p>' . 
    esc_html__('Thank you for installing Fast Etsy Listings.', "fast-etsy-listings" ) .
          ' <a href="' . esc_url(admin_url( 'options-general.php?page=fast-etsy-listings' )) . '">' .
          esc_html__('Visit the setting page', "fast-etsy-listings" ) .
          '</a> ' .
          esc_html__('to get started and find help.', "fast-etsy-listings" ) . 
          '</p></div>';
    // Delete the transient so we don't keep displaying the activation message
    delete_transient( 'fu_etsy_activated' );
  }
}
add_action( 'admin_notices', 'fu_etsy_display_install_notice' );

/**
 * Run this on activation
 * Set a transient so that we know we've just activated the plugin
 */
function fu_etsy_activate() 
{
  set_transient( 'fu_etsy_activated', 1 );
}
register_activation_hook( __FILE__, 'fu_etsy_activate' );

////////////////////////////////////////////////////////////////////////////////////////////////////////
// Other admin notices.


////////////////////////////////////////////////////////////////////////////////////////////////////////
// Store state of dismissed expired licence notices.

function fu_etsy_handle_admin_notice_dismissal() 
{
  if (!check_ajax_referer('fu_etsy_admin_notice', 'nonce', false)) 
  {
    error_log(FU_ETSY_PLUGIN_TITLE . ": Nonce check failed");
    die;
  }

  // Pick up the notice "type" - passed via jQuery (the "data-notice" attribute on the notice)
  if (($type = sanitize_text_field($_POST['type'])) == "SubExpiry");
    update_option( 'fu_etsyDismissed' . $type, TRUE );

}
add_action( 'wp_ajax_dismissed_notice_handler', 'fu_etsy_handle_admin_notice_dismissal' );



////////////////////////////////////////////////////////////////////////////////////////////////////////
// Handle DB updates during upgrades.

function fu_etsy_init_db_upgrades()
{
  // Perform any DB/Config updates
  $priorVersion = get_option('fuEtsyPluginVer', '0.0');
  if (version_compare($priorVersion, '9.9.9') < 0)
  {
  }

  // Update version num
  update_option('fuEtsyPluginVer', FU_ETSY_PLUGIN_VER);
}
add_action('init', 'fu_etsy_init_db_upgrades');

// Check subscription status.
function fu_etsy_init()
{
  global $g_fuEtsySubInfoApiCall;
  if (is_null($g_fuEtsySubInfoApiCall))
  {
    $g_fuEtsySubInfoApiCall = new fuEtsySubInfoApiCall();
    $g_fuEtsySubInfoApiCall->call(true);
  }
  
}
add_action('init', 'fu_etsy_init');


<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

//////////////////////////////////////////////////////////////////////////
//
// Handle a search of items
//
//////////////////////////////////////////////////////////////////////////
if (!class_exists('fuShortcodeBase'))
{
abstract class fuShortcodeBase
{
  /**
   * $shortcode_tag
   * holds the name of the shortcode tag
   * @var string
   */
  public $shortcode_tag;
  
  /**
   * $mce_plugin_url
   * holds the name mce plugin js
   * @var string
   */
  protected $mce_plugin_url;
  
  /**
   * __construct
   * class constructor will set the needed filter and action hooks
   *
   * @param array $args
   */
  function __construct($args = array()){
    //add shortcode
    add_shortcode( $this->shortcode_tag, array( $this, 'shortcode_handler' ) );
    add_shortcode( 'fu_' . $this->shortcode_tag, array( $this, 'shortcode_handler' ) );
    
    if ( is_admin() ){
      add_action('admin_head', array( $this, 'admin_head') );
    }
  }

  /**
   * shortcode_handler
   * @param  array  $atts shortcode attributes
   * @param  string $content shortcode content
   * @return string
   */
  abstract function shortcode_handler($atts , $content = null);

  /**
   * admin_head
   * calls your functions into the correct filters
   * @return void
   */
  function admin_head() {
    // check user permissions
    if ( !current_user_can( 'edit_posts' ) && !current_user_can( 'edit_pages' ) ) {
      return;
    }

    // check if WYSIWYG is enabled
    if ( 'true' == get_user_option( 'rich_editing' ) ) {
      add_filter( 'mce_external_plugins', array( $this ,'mce_external_plugins' ) );
      add_filter( 'mce_buttons', array($this, 'mce_buttons' ) );
    }
  }

  /**
   * mce_external_plugins
   * Adds our tinymce plugin
   * @param  array $plugin_array
   * @return array
   */
  function mce_external_plugins( $plugin_array ) {
    $plugin_array[$this->shortcode_tag] = $this->mce_plugin_url;
    return $plugin_array;
  }

  /**
   * mce_buttons
   * Adds our tinymce button
   * @param  array $buttons
   * @return array
   */
  function mce_buttons( $buttons ) {
    array_push( $buttons, $this->shortcode_tag );
    return $buttons;
  }
}


//////////////////////////////////////////////////////////////////////////
//
// Setup ShortCode MCE ToolBar Buttons
//
//////////////////////////////////////////////////////////////////////////
abstract class fuShortcodeMCEToolbar
{

  /**
   * __construct
   * class constructor will set the needed filter and action hooks
   *
   * @param array $args
   */
  function __construct($args = array()){
    if ( is_admin() ){
      add_action('admin_head', array( $this, 'admin_head') );
      add_action( 'admin_enqueue_scripts', array($this , 'admin_enqueue_scripts' ) );
    }
  }

  /**
   * admin_head
   * calls your functions into the correct filters
   * @return void
   */
  function admin_head() {
    // check user permissions
    if ( !current_user_can( 'edit_posts' ) && !current_user_can( 'edit_pages' ) ) {
      return;
    }

    // check if WYSIWYG is enabled
    if ( 'true' == get_user_option( 'rich_editing' ) ) {
      add_filter( 'mce_external_plugins', array( $this ,'mce_external_plugins' ) );
      add_filter( 'mce_buttons', array($this, 'mce_buttons' ) );
    }
  }

  /**
   * mce_external_plugins
   * Adds our tinymce plugin
   * @param  array $plugin_array
   * @return array
   */
  abstract function mce_external_plugins( $plugin_array );

  /**
   * mce_buttons
   * Adds our tinymce button
   * @param  array $buttons
   * @return array
   */
  abstract function mce_buttons( $buttons );

  /**
   * admin_enqueue_scripts
   * Used to enqueue custom styles and extra JS params
   * @return void
   */
  abstract function admin_enqueue_scripts();

}
}
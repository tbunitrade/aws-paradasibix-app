<?php

/*
 * Plugin Name: Vertical Timeline Widget for Elementor
 * Description: Vertical Timeline Widget for Elementor Plugin add timeline element to Elementor Page builder.
 * Plugin URI: https://wordpress.org/plugins/3r-elementor-timeline-widget
 * Version:2.7.3
 * Requires at least: 5.2
 * Requires PHP:7.2
 * Author: Cool Plugins
* Author URI:  https://coolplugins.net/?utm_source=vtwe_plugin&utm_medium=inside&utm_campaign=author_page&utm_content=plugins_list
 * License:GPL v2 or later
 * License URI:https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: 3r-elementor-timeline-widget
 * Requires Plugins: elementor
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'TWE_PLUGIN_URL', plugins_url( '/', __FILE__ ) );
define( 'TWE_PLUGIN_PATH', plugin_dir_path(__FILE__));
add_action( 'elementor/preview/enqueue_styles', 'twe_enqueue_style' );
add_action('wp_enqueue_scripts', 'twe_enqueue_style');
add_action( 'elementor/editor/after_enqueue_styles', function() {

    // Load your custom editor CSS
    wp_enqueue_style(
        'twe-editor-styles',
        TWE_PLUGIN_URL . 'assets/css/twe-editor.css',
        [],
          time()
    );
    wp_enqueue_style( 'font-awesome-5-solid', ELEMENTOR_ASSETS_URL . 'lib/font-awesome/css/solid.min.css',array(), 'all' );
    wp_enqueue_style( 'font-awesome-5-fontawesome', ELEMENTOR_ASSETS_URL . 'lib/font-awesome/css/fontawesome.min.css',array(), 'all' );


});
add_action('elementor/editor/after_enqueue_scripts', function() {
    wp_enqueue_script(
        'twae-editor-js',
        TWE_PLUGIN_URL . 'assets/js/twe-editor.js',
        ['jquery'],
        time(),
        true
    );

    wp_localize_script(
        'twae-editor-js',
        'twae_ajax_obj',
        [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('twae_upgrade_notice_nonce'),
        ]
    );
});


function twe_enqueue_style() {
	wp_enqueue_style( 'twe-preview', TWE_PLUGIN_URL . 'assets/css/style.css', array() ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- version intentionally omitted
}


class TweTimelinePlugin {
 
   private static $instance = null;
 
   public static function get_instance() {
      if ( ! self::$instance )
         self::$instance = new self;
      return self::$instance;
   }
 
   public function init(){
      add_action( 'elementor/widgets/widgets_registered', array( $this, 'widgets_registered' ) );
   }
 
   public function widgets_registered() {

      if ( defined('ELEMENTOR_PATH') && class_exists('Elementor\Widget_Base') ) {
         $template_file = plugin_dir_path(__FILE__) . 'timeline-widget.php';
     
         if ( is_readable($template_file) ) {
             require_once $template_file;
         }
     }
   }
}
 
TweTimelinePlugin::get_instance()->init();

add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'twe_add_pro_link' );
function twe_add_pro_link( $links ) {
    $links[] = '<a style="font-weight:bold; color:#852636;" href="https://cooltimeline.com/plugin/elementor-timeline-widget-pro/?utm_source=vtwe_plugin&utm_medium=inside&utm_campaign=get_pro&utm_content=plugins_list#pricing" target="_blank">Get Pro</a>';
    return $links;
}
add_filter( 'plugin_row_meta', 'twe_add_view_demo_row_meta', 10, 2 );
function twe_add_view_demo_row_meta( $links, $file ) {
    if ( $file === plugin_basename( __FILE__ ) ) {
        $demo_link = '<a href="https://cooltimeline.com/elementor-widget/vertical-timeline-widget-for-elementor/?utm_source=vtwe_plugin&utm_medium=inside&utm_campaign=demo&utm_content=plugins_list" target="_blank">View Demo</a>';
        array_splice( $links, count( $links ), 0, $demo_link );
    }
    return $links;
}

/**
 * AJAX: Hide Upgrade Notice
 */
add_action( 'wp_ajax_twae_hide_upgrade_notice_editor', 'twae_hide_upgrade_notice_editor_callback' );

function twae_hide_upgrade_notice_editor_callback() {

    check_ajax_referer( 'twae_upgrade_notice_nonce', 'security' );
      
    update_option( 'twae_hide_upgrade_notice_editor', 'yes' );

    wp_send_json_success([
        'message' => 'Upgrade notice dismissed'
    ]);
}


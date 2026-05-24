<?php
/*
 * Plugin Name: Fast Etsy Listings
 * Plugin URI: http://www.fubaby.com/wordpress-plugins/fast-etsy-listings/
 * Description: Display Etsy Shop listings on your site, using blocks and shortcodes. 
 * Version: 1.2.11
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * Author: Arthur Yarwood
 * Author URI: http://www.fubaby.com
 * Text Domain: fast-etsy-listings
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

/*
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once __DIR__ . "/constants.php";
require_once __DIR__ . "/admin.php";
require_once __DIR__ . "/cats/cat_chooser.php";
require_once __DIR__ . "/utilities.php";
require_once __DIR__ . "/listing.php";
require_once __DIR__ . "/shortcodes.php";
require_once __DIR__ . "/shortcode-feedback.php";
require_once __DIR__ . "/shortcode-listing.php";
require_once __DIR__ . "/shortcode-search.php";
require_once __DIR__ . "/shortcode-shoplistings.php";
require_once __DIR__ . "/blocks/item/item.php";
require_once __DIR__ . "/blocks/search/search.php";
require_once __DIR__ . "/blocks/shoplistings/shoplistings.php";
require_once __DIR__ . "/blocks/feedback/feedback.php";


////////////////////////////////////////////////////////////////////////////////////////////////////////
// Add settings link on WP plugin page

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'fu_etsy_settings_link');
function fu_etsy_settings_link( $actions ) 
{
  $mylinks = array(
    '<a href="' . esc_url(admin_url( 'options-general.php?page=fast-etsy-listings' )) . '">' . esc_html__("Settings", "fast-etsy-listings") .'</a>',
  );
  $actions = array_merge( $actions, $mylinks );
	return $actions;
}

////////////////////////////////////////////////////////////////////////////////////////////////////////
// Add/remove options on init/uninstall.

add_action('init', 'fu_etsy_init_settings');
register_uninstall_hook(__FILE__, 'fu_etsy_uninstall');


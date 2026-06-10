=== Fast Etsy Listings ===
Contributors: Arfa__
Donate link: https://paypal.me/ArthurYarwood
Plugin URI: http://www.fubaby.com/wordpress-plugins/fast-etsy-listings/
Author URI: http://www.fubaby.com
Tags: Etsy, shop, listings, inventory, reviews
Requires at least: 5.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.2.12
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Etsy WordPress Plugin to display live Etsy Listings from your shop or across Etsy. 

== Description ==

Use Fast Etsy Listings to integrate WordPress with Etsy. Quickly display up-to-date Etsy Listings, Shop info and reviews on your WordPress blog. 

Promote items from your Etsy store.

*Fast Etsy Listings is actively maintained to ensure continued support for the latest Etsy API updates, including the latest Etsy API v3.*

* Display single listings or a grid of search results from Etsy
* Show feedback ratings and comments from your Etsy shop
* Slideshow presentation or 'load more' buttons for continuous scrolling
* Simple to use Blocks with live preview to arrange Etsy listings in the WordPress Block Editor
* Easy UI to add Etsy search shortcodes in Classic Editor
* Show items from your shop, other shop, or items specific to a post’s author
* Filter results by category, price range, location etc.
* Items are shown with thumbnail size and aspect ratio of your choice
* Full item details can be shown: price, location, description etc
* Deferred loading and inbuilt caching of listing for faster page loads

&#8594; [More details and documentation can be found at fubaby.com](https://www.fubaby.com/wordpress-plugins/fast-etsy-listings/)

Do also check out the sibling [Fast eBay Listings plugin](https://wordpress.org/plugins/fast-ebay-listings/), a mature, well-maintained offering with similar functionality to integrate your WordPress site with eBay.

== Installation ==

The easiest way to install this plugin is to navigate to the 'Plugins | Add New' page within the Admin Dashboard of your WordPress site. Then simply search for 'Fast Etsy Listings' and install/activate the plugin.

Alternatively, you can:

1. Download this plugin from here
2. Upload the plugin files to the `/wp-content/plugins/fast-etsy-listings` directory
3. Activate the plugin through the 'Plugins' screen in WordPress

Don't forget to 'enable auto-updates' to ensure you get bug fixes and new features automatically deployed.

Use the 'Settings->Fast Etsy Listings' screen to configure the plugin

== Support ==

Fast Etsy Listings is well maintained and is kept up to date with the latest Etsy API changes. You will be supported for many more years.

You can get support, help, or assistance in using Fast Etsy Listings in a number of ways:

1. Visit the [plugin support forum on the WordPress.org site here](https://wordpress.org/support/plugin/fast-etsy-listings/)
2. Contact [me directly via email here](https://www.fubaby.com/contact-me/)

I'll endeavor to follow up as soon as I can and resolve any issues you're having.

== Usage ==

Utilize either easy-to-use Blocks in the Block Editor or visual editors for Classic Editor shortcodes, no need for you to type any shortcodes manually.

The key Blocks / Shortcodes you can add to your pages are:

= Etsy Shop Listings =

Presents listings from your Etsy Shop. You can also narrow down items to display by entering a search query.

The display of the results can be tailored into a grid with dimensions of your choosing and optionally as a slideshow.

If any parameters are left blank, defaults from the plugin setting will be used.

= Etsy Search =

Presents a list of Etsy listings, based on given search criteria. You can narrow down items to display by entering a search query, a category, country and min/max prices.

The display of the results can be tailored into a grid with dimensions of your choosing and optionally as a slideshow.

If any parameters are left blank, defaults from the plugin setting will be used.

= Etsy Single Listing =

Present details of a single Etsy listing. Simply enter the Etsy listing ID of the product to display.

= Shortcodes =

For reference, if you wish to manually enter shortcodes in the Classic Editor text view, you can find the syntax and [details of shortcode parameters supported here](https://www.fubaby.com/wordpress-plugins/fast-etsy-listings/fast-etsy-listings-shortcode-parameter-reference/). 

= Terms & Conditions =

By using this plugin you must abide by the Etsy code of conduct. Including, but not limited to:

* Promote Etsy items alongside unacceptable topics, e.g. sexually explicit material, violence, weapons, illegal goods, and discriminatory or hate-orientated content.

Fast Etsy Listings reserves the right to block usage for non-complying sites.

== Frequently Asked Questions ==

= How do I just show items from my own Etsy Shop? =

The simplest method is to first enter your Etsy shop name in the setting field 'Default Etsy Shop name'. Then use the Fast Etsy Shop Listings block or shortcode which will then show stock you have listed on Etsy.

Alternatively, per block or shortcode you add to your site you can set the Etsy shop to show listings from. 

= Does Fast Etsy Listings display a link to the author's website under results? =

No! 
Fast Etsy Listings *does not* add any external links on your website back to the author's site. This clutters up your website, hurts your search engine ranking and is against WordPress plugin guidelines!
Only links to Etsy items you want on your website are presented and these are all tagged as 'nofollow' to avoid any negative SEO impact. You can optionally choose if Etsy links should open in a new tab/window.

= What types of slideshows are supported? =

Fast Etsy Listings supports several types of slideshow and pagination; manual, auto, 'load more' button, list by category.

Manual: Will present slides that visitors can manually step through using buttons beneath.

Auto: Will present a slideshow that automatically steps through the slides, fading between each every 10 seconds by default. You can change the slideshow speed in the plugin settings.

'Load more' button: Will present a button for visitors to load the next slide/page of results beneath the rest. 

List by Category: Will present a long list of results grouped under eBay category headings.

All slideshows can be configured with as many slides as desired, limited only by the number of items returned from Etsy.

Each slide can be configured with as many rows and columns as desired to produce a grid of items per slide.

Manual and Auto slideshow work best when displaying a grid with 1 or 2 rows of results. The 'Load more' button works best when displaying large numbers of results for a visitor to scroll through (especially mobile users).

= How do I customize the colors etc of listings? =

Within the settings, you choose to inherit your WordPress theme's color scheme (default), black text on white background, or white text on a black background style.

Alternatively, you can override the CSS styles manually. Please get in touch with the visual customization you would like in future releases.

= How do I customize the fields displayed on listings? =

Within the setting you can choose which fields to display for search results on blocks/shortcodes, results shown on widgets and for the single item block/shortcode separately. 

== Screenshots ==

1. Screenshot 1. How to configure Fast Etsy Listings and setup defaults
2. Screenshot 2. Slideshow of Etsy Shop listings
3. Screenshot 3. A single Etsy listing.
4. Screenshot 4. Live preview of Etsy listings in the WordPress Block Editor
5. Screenshot 5. Easy UI to add Etsy listing shortcodes in the WordPress Classic Editor 
6. Screenshot 6. Present your Etsy shop details and reviews on your site

== Changelog ==

= 1.2.12 =
Added support for Rakuten affiliate scheme (premium version only).

= 1.2.11 =
Fixed bug with loading of single listing items: 'Item ID must be an integer' error.

= 1.2.10 =
Fixed transient PHP warning when loading subscription info.

= 1.2.9 =
Fixed bug with sorting by price not working correctly on Search and Shop Listings blocks

= 1.2.8 =
Fixed bug in Rich Snippet structured data.

= 1.2.7 =
Fixed bug with using default seller names for Feedback and Search blocks/shortcodes.
Fixes to translation loading for WordPress 6.7

= 1.2.6 =
Etsy links now presented with shop name subdomain for Share & Save tracking.
Fixed issue with slideshows not display properly on first load of page.

= 1.2.5 =
Added Schema.org Product data for listings.
Added support to monentize links with Etsy Affiliate Window scheme.
Fixed minor display bugs with admin settings pages.
Improved message when old categories can no longer be found.

= 1.2.4 =
Added option to show listings in a randomized sequence.
Fixed bug with live block previews

= 0.2.3 =
Fixed regression issue preventing HTML use in 'no results' message.
Fixed bug with default seller or category from settings not used in live block previews.

= 0.2.2 =
Fixed missing icon on Classic editor toolbar
Fixed issue with only loading featured items.

= 0.2.1 =
Fixed issue with extra slash characters appearing in titles

= 0.2 =
Added Etsy Shop Review Block

= 0.1 =
First official release

== Upgrade Notice ==

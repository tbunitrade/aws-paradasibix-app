<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once __DIR__."/constants.php";
require_once __DIR__."/includes/presentation.inc.php";
require_once __DIR__."/includes/listing.inc.php";


////////////////////////////////////////////////////////////////////////////////////////////////////////
/// Add options needed for plugin

function fu_etsy_init_settings()
{
  add_option('fuEtsyPluginVer', '0.1', '', true); //Version key was introduced
  add_option('fuEtsyDefCategory', ''); // Default category IDs. 
  add_option('fuEtsyDefSeller', ''); // Default seller. 
  add_option('fuEtsyDefSearchLocation', fuEtsy::AnyCountry);
  add_option('fuEtsyDefColumns', 2); // Default columns of search results.
  add_option('fuEtsyDefRows', 1); // Default rows of search results.
  add_option('fuEtsyDefSort', fuEtsySortOrder::CreatedDesc); // Default sort order of search results
  add_option('fuEtsyPicWidthItem', 170); // Default width of pictures for single items.
  add_option('fuEtsyPicWidthList', 170); // Default width of pictures for search listings.
  add_option('fuEtsyPicAspect', fuPictureAspect::Square); // Picture aspect
  add_option('fuEtsyColourStyle', fuColourStyle::Default); // Default colour style
  add_option('fuEtsyTitleMaxLines', 3, '', true); // Max title lines to display.
  add_option('fuEtsyShortDescMaxLines', 20, '', true); // Max short description lines to display.
  add_option('fuEtsyDefArrangementItem', fuArrangement::ImageLeft); // Default arrangement for single items.
  add_option('fuEtsyDefArrangementList', fuArrangement::TitleBelow); // Default arrangement for search listings.
  add_option('fuEtsyDefSlideshowStyle', fuSlideShowStyle::Manual); // Default slideshow style
  add_option('fuEtsyDefDisplayFieldsItem', fuEtsy::DisplayFieldsItemDefault); // Default fields to show for single items
  add_option('fuEtsyDefDisplayFieldsList', fuEtsy::DisplayFieldsListingDefault); // Default fields to show for search listings
  add_option('fuEtsyDefNumSlides', 3); // Default number of slideshow slides
  add_option('fuEtsyEmptySearchMsg', fuEtsyDefaultText::EmptySearch()); // Default empty results message.
  add_option('fuEtsyPriorityListingText', fuEtsyDefaultText::PriorityListing()); // Default default priority listing text
  add_option('fuEtsyLinkText', fuEtsyDefaultText::EtsyLink()); // Default default link text
  add_option('fuEtsyLoadMoreButtonText', fuEtsyDefaultText::LoadMore()); // Default 'load more' button text
  add_option('fuEtsySlideshowTimer', FU_DEFSLIDESHOWTIMER, '', true); // Default slideshow timeout
  add_option('fuEtsyNewWindow', 1); // Whether to open links in new window/tab.
  add_option('fuEtsyDeferredLoading', fuDeferedLoading::NotCached); // Whether to defer loading of listing until page is loaded usign AJAX
  add_option('fuEtsySubscriptionKey', '', '', true); // Key for subscription.
  add_option('fuEtsyDisplayFELLink', 0); // Whether to display a link to Fast Etsy Listings
  add_option('fuEtsyGeoTargetResults', fuGeoTargetting::Never); // Whether to geotarget results based on CloudFlare headers
  add_option('fuEtsyAdvertiserID', '', '', true); // The Affiliate Window Advertiser id
  add_option('fuEtsyAffiliateID', '', '', true); // The Affiliate Window id.
  add_option('fuEtsyAffiliateRefID', '', '', true); // The Affiliate Window click reference id.
  add_option('fuEtsyAdDisclosurePlacement', fuAdDisclosurePlacement::None, '', true); // Where to place ad disclosure
  add_option('fuEtsyAdDisclosureText', fuEtsyDefaultText::AdDisclosure1(), '', true); // What ad disclosure text to display.
}

////////////////////////////////////////////////////////////////////////////////////////////////////////
// Register options.

function fu_etsy_register_settings()
{
  register_setting('fu_etsy_settings_general', 'fuEtsyDefSeller');
  register_setting('fu_etsy_settings_general', 'fuEtsySubscriptionKey');

  register_setting('fu_etsy_settings_defaults', 'fuEtsyDefCategory');
  register_setting('fu_etsy_settings_defaults', 'fuEtsyDefSearchLocation');
  register_setting('fu_etsy_settings_defaults', 'fuEtsyDefColumns');
  register_setting('fu_etsy_settings_defaults', 'fuEtsyDefRows');
  register_setting('fu_etsy_settings_defaults', 'fuEtsyDefSlideshowStyle');
  register_setting('fu_etsy_settings_defaults', 'fuEtsyDefNumSlides');
  register_setting('fu_etsy_settings_defaults', 'fuEtsyDefSort');

  register_setting('fu_etsy_settings_behaviour', 'fuEtsyPicWidthItem');
  register_setting('fu_etsy_settings_behaviour', 'fuEtsyPicWidthList');
  register_setting('fu_etsy_settings_behaviour', 'fuEtsyPicAspect');
  register_setting('fu_etsy_settings_behaviour', 'fuEtsyDefArrangementItem');
  register_setting('fu_etsy_settings_behaviour', 'fuEtsyDefArrangementList');
  register_setting('fu_etsy_settings_behaviour', 'fuEtsyDefDisplayFieldsItem');
  register_setting('fu_etsy_settings_behaviour', 'fuEtsyDefDisplayFieldsList');
  register_setting('fu_etsy_settings_behaviour', 'fuEtsyLinkText');
  register_setting('fu_etsy_settings_behaviour', 'fuEtsyEmptySearchMsg');
  register_setting('fu_etsy_settings_behaviour', 'fuEtsyPriorityListingText');
  register_setting('fu_etsy_settings_behaviour', 'fuEtsyLoadMoreButtonText');
  register_setting('fu_etsy_settings_behaviour', 'fuEtsySlideshowTimer');
  register_setting('fu_etsy_settings_behaviour', 'fuEtsyNewWindow');
  register_setting('fu_etsy_settings_behaviour', 'fuEtsyDeferredLoading');
  register_setting('fu_etsy_settings_behaviour', 'fuEtsyDisplayFELLink');
  register_setting('fu_etsy_settings_behaviour', 'fuEtsyColourStyle');
  register_setting('fu_etsy_settings_behaviour', 'fuEtsyTitleMaxLines');
  register_setting('fu_etsy_settings_behaviour', 'fuEtsyShortDescMaxLines');
  register_setting('fu_etsy_settings_behaviour', 'fuEtsyGeoTargetResults');
  

  register_setting('fu_etsy_settings_affiliate', 'fuEtsyAdvertiserID');
  register_setting('fu_etsy_settings_affiliate', 'fuEtsyAffiliateID');
  register_setting('fu_etsy_settings_affiliate', 'fuEtsyAffiliateRefID');
//  register_setting('fu_etsy_settings_affiliate', 'fuEtsyEnableSmartLinks');
//  register_setting('fu_etsy_settings_affiliate', 'fuEtsySmartLinksCustomID');
  register_setting('fu_etsy_settings_affiliate', 'fuEtsyAdDisclosurePlacement');
  register_setting('fu_etsy_settings_affiliate', 'fuEtsyAdDisclosureText');
}

////////////////////////////////////////////////////////////////////////////////////////////////////////
// Remove options on uninstall.

function fu_etsy_uninstall()
{
  delete_option('fuEtsyPluginVer');
  delete_option('fuEtsyDefCategory');
  delete_option('fuEtsyDefSeller');
  delete_option('fuEtsyDefSearchLocation');
  delete_option('fuEtsyDefColumns');
  delete_option('fuEtsyDefRows');
  delete_option('fuEtsyDefSort');
  delete_option('fuEtsyPicWidthItem');
  delete_option('fuEtsyPicWidthList');
  delete_option('fuEtsyPicAspect');
  delete_option('fuEtsyColourStyle');
  delete_option('fuEtsyTitleMaxLines');
  delete_option('fuEtsyShortDescMaxLines');
  delete_option('fuEtsyDefArrangementItem');
  delete_option('fuEtsyDefArrangementList');
  delete_option('fuEtsyDefDisplayFieldsItem');
  delete_option('fuEtsyDefDisplayFieldsList');
  delete_option('fuEtsyDefSlideshowStyle');
  delete_option('fuEtsyDefNumSlides');
  delete_option('fuEtsyEmptySearchMsg');
  delete_option('fuEtsyPriorityListingText');
  delete_option('fuEtsyLoadMoreButtonText');
  delete_option('fuEtsyLinkText');
  delete_option('fuEtsySlideshowTimer');
  delete_option('fuEtsyNewWindow');
  delete_option('fuEtsyDeferredLoading');
  delete_option('fuEtsySubscriptionKey');
  delete_option('fuEtsyDisplayFELLink');
  delete_option('fuEtsyGeoTargetResults');
  delete_option('fuEtsyAdvertiserID');
  delete_option('fuEtsyAffiliateID');
  delete_option('fuEtsyAffiliateRefID');
  delete_option('fuEtsyAdDisclosurePlacement');
  delete_option('fuEtsyAdDisclosureText');
  
}
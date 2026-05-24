<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once "includes/constants.inc.php";

define("FU_ETSY_PLUGIN_TITLE", "Fast Etsy Listings");
define("FU_ETSY_PLUGIN_VER", "1.2.11");
define("FU_ETSY_PLUGIN_WEBSITE", "https://www.fubaby.com/wordpress-plugins/fast-etsy-listings/");
define("FU_ETSY_CALLCACHEEXPIRY", 360); // minutes (6 hrs)
define("FU_ETSY_CALLCACHEEXPIRYCATEGORIES", 2880); // minutes (2 days)
define("FU_ETSY_CALLCACHEEXPIRYFEEDBACK", 1440); // minutes (1 day)
define("FU_ETSY_CATEGORYMAXDEPTH", 4);
define("FU_ETSY_COUNTRYQUERYPARAM", "etsy_country");

class fuEtsyDefaultText
{
  static public function EmptySearch() { return __('No items found', "fast-etsy-listings"); }
  static public function PriorityListing() { return  __('Featured', "fast-etsy-listings"); }
  static public function EtsyLink() { return "&rarr; " . __('View on Etsy', "fast-etsy-listings"); }
  static public function LoadMore() { return __('Load more', "fast-etsy-listings"); }
  static public function GotoTop() { return __('Go to Top', "fast-etsy-listings"); }
  static public function AdDisclosure1() { return __('Clicking a link to Etsy may result in a referral commission being paid if a purchase is made.', "fast-etsy-listings"); }
  static public function AdDisclosure2() { return __('Clicking a link to Etsy [or other affiliations] may result in a referral commission being paid if a purchase is made.', "fast-etsy-listings"); }
}

class fuEtsy
{
  public const AnyCountry = "Any Country";
  // Countries Etsy operates in
  // https://help.etsy.com/hc/en-us/articles/115015710408-Countries-Eligible-for-Etsy-Payments?segment=selling
  public const Countries = array(
    fuEtsy::AnyCountry,
    "Argentina",
    "Australia",
    "Austria",
    "Belgium",
    "Bulgaria",
    "Canada",
    "Chile",
    "Croatia",
    "Cyprus",
    "Czech Republic",
    "Denmark",
    "Estonia",
    "Finland",
    "France",
    "Germany",
    "Greece",
    "Hong Kong",
    "Hungary",
    "India",
    "Indonesia",
    "Ireland",
    "Israel",
    "Italy",
    "Japan",
    "Latvia",
    "Lithuania",
    "Luxembourg",
    "Malaysia",
    "Malta",
    "Mexico",
    "Morocco",
    "Netherlands",
    "New Zealand",
    "Norway",
    "Peru",
    "Philippines",
    "Poland",
    "Portugal",
    "Romania",
    "Singapore",
    "Slovakia",
    "Slovenia",
    "South Africa",
    "Spain",
    "Sweden",
    "Switzerland",
    "Thailand",
    "Türkiye",
    "Ukraine",
    "United Kingdom",
    "United States",
    "Vietnam",
  );

  // Default display fields for Etsy
  const DisplayFieldsListingDefault = [
    fuDisplayFields::Title, 
    fuDisplayFields::Image, 
    fuDisplayFields::Price, 
    fuDisplayFields::Store, 
    fuDisplayFields::Location, 
    fuDisplayFields::VisitLink
  ];
  const DisplayFieldsItemDefault = [
    fuDisplayFields::Title, 
    fuDisplayFields::Image, 
    fuDisplayFields::Price, 
    fuDisplayFields::Store, 
    fuDisplayFields::Feedback, 
    fuDisplayFields::Location, 
    fuDisplayFields::VisitLink
  ];  
}


//////////////////////////////////////////////////////////////////////////
// Constants specific to plugin

class fuEtsySortOrder 
{
  const CreatedDesc = "CreatedDesc";
  const CreatedAsc = "CreatedAsc";
  const PriceHighest = "PriceHighest";
  const PriceLowest = "PriceLowest";
  const Score = "Score";
  const Random = "Random";

  public static $Labels;
  static public function init() 
  {
    self::$Labels = array(
      self::CreatedDesc => __("Created most recent", "fast-etsy-listings"),
      self::CreatedAsc => __("Created earliest", "fast-etsy-listings"),
      self::PriceHighest => __("Highest Price", "fast-etsy-listings"),
      self::PriceLowest => __("Lowest Price", "fast-etsy-listings"),
      self::Score => __("Score", "fast-etsy-listings"),
      self::Random => __("Randomized", "fast-etsy-listings"),
    );
  }

}

class fuEtsyAWinAdvertisers
{
  const None = "";
  const ROW = "ROW";
  const CA = "CA";
  const UK = "UK";
  const US = "US";
  const EU = "EU";

  public static $Labels;
  static public function init() 
  {
    self::$Labels = array(
      self::None => __("None", "fast-etsy-listings"),
      self::ROW => __("Rest of World (ROW)", "fast-etsy-listings"),
      self::CA => __("Canada", "fast-etsy-listings"),
      self::UK => __("UK", "fast-etsy-listings"),
      self::US => __("US", "fast-etsy-listings"),
      self::EU => __("EU", "fast-etsy-listings"),
    );
  }

}

////////////////////////////////////////////////////////////////////////////////////////////////////////
// Init constants and textdomain has been loaded
function fu_etsy_init_constants()
{
  //////////////////////////////////////////////////////////////////////////
  // Localise included constants.
  fuColourStyle::$Labels = array(
    fuColourStyle::Default => __("Site default", "fast-etsy-listings"),
    fuColourStyle::White => __("Black text on white background", "fast-etsy-listings"),
    fuColourStyle::Black => __("White text on black background", "fast-etsy-listings"),
  );

  fuSlideShowStyle::$Labels = array(
    fuSlideShowStyle::None => __("None", "fast-etsy-listings"),
    fuSlideShowStyle::Manual => __("Manual", "fast-etsy-listings"),
    fuSlideShowStyle::Auto => __("Automatic Slideshow", "fast-etsy-listings"),
    fuSlideShowStyle::LoadMore => __("'Load More' button", "fast-etsy-listings"),
    //fuSlideShowStyle::InfiniteScroll => __("Infinite Scroll", "fast-etsy-listings"),
    fuSlideShowStyle::CategoryGroups => __("List Grouped by Category", "fast-etsy-listings"),
  );

  fuPictureAspect::$Labels = array(
    fuPictureAspect::Freeform => __("Freeform", "fast-etsy-listings"),
    fuPictureAspect::Square => __("Square", "fast-etsy-listings"),
    fuPictureAspect::FourThree => __("4:3", "fast-etsy-listings"),
  );

  fuPictureAspect::$Labels = array(
    fuPictureAspect::Freeform => __("Freeform", "fast-etsy-listings"),
    fuPictureAspect::Square => __("Square", "fast-etsy-listings"),
    fuPictureAspect::FourThree => __("4:3", "fast-etsy-listings"),
  );

  fuSubStates::$Labels = array(
    fuSubStates::Unsubscribed => __("Unsubscribed", "fast-etsy-listings"),
    fuSubStates::Expired => __("Expired", "fast-etsy-listings"),
    fuSubStates::GracePeriod => __("Grace Period", "fast-etsy-listings"),
    fuSubStates::Subscriber => __("Subscriber", "fast-etsy-listings"),
  );

  fuAdDisclosurePlacement::$Labels = array(
    fuAdDisclosurePlacement::None => __("No ad disclosure", "fast-etsy-listings"),
    fuAdDisclosurePlacement::PageTop => __("Top of each page", "fast-etsy-listings"),
    fuAdDisclosurePlacement::Top => __("Top of each block/shortcode/widget", "fast-etsy-listings"),
    fuAdDisclosurePlacement::Bottom => __("Below each block/shortcode/widget", "fast-etsy-listings"),
    //fuAdDisclosurePlacement::PageBottom => __("Bottom of each page", "fast-etsy-listings"),
  );

  fuDeferedLoading::$Labels = array(
    fuDeferedLoading::Always => __("Always", "fast-etsy-listings"),
    fuDeferedLoading::NotCached => __("When not cached", "fast-etsy-listings"),
    fuDeferedLoading::Never => __("Never", "fast-etsy-listings"),
  );

  fuGeoTargetting::$Labels = array(
    fuGeoTargetting::Never => __("Never", "fast-etsy-listings"),
    fuGeoTargetting::NoSeller => __("When no seller filters", "fast-etsy-listings"),
    fuGeoTargetting::Always => __("Always", "fast-etsy-listings"),
  );

  fuItemLocation::$Labels = array(
    fuItemLocation::Nationwide => __("Nationwide", "fast-etsy-listings"),
    fuItemLocation::Internationally => __("Internationally", "fast-etsy-listings")
  );

  fuDisplayFields::$Labels = array(
    fuDisplayFields::Title => __("Title", "fast-etsy-listings"),
    fuDisplayFields::ShortDesc => __("Short Description", "fast-etsy-listings"),
    fuDisplayFields::Image => __("Image", "fast-etsy-listings"),
    fuDisplayFields::Price => __("Price", "fast-etsy-listings"),
    fuDisplayFields::EndTime => __("End Time", "fast-etsy-listings"),
    fuDisplayFields::Feedback => __("Feedback", "fast-etsy-listings"),
    fuDisplayFields::Location => __("Location", "fast-etsy-listings"),
    fuDisplayFields::Store => __("Shop", "fast-etsy-listings"),
    fuDisplayFields::Condition => __("Condition", "fast-etsy-listings"),
    //fuDisplayFields::VisitLink => __("Link to Etsy", "fast-etsy-listings"),
  );  

  fuEtsySortOrder::init();
  fuEtsyAWinAdvertisers::init();
}
add_action('init', 'fu_etsy_init_constants');
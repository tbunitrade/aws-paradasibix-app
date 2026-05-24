<?php # -*- coding: utf-8 -*-
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once __DIR__ . "/constants.php";

$defaultSort = get_option('fuEtsyDefSort', fuEtsySortOrder::CreatedDesc);
if (empty($defaultSort)) $defaultSort = fuEtsySortOrder::CreatedDesc;

$defaultSlideShowStyle = get_option('fuEtsyDefSlideshowStyle', fuSlideShowStyle::None);
if (empty($defaultSlideShowStyle)) $defaultSlideShowStyle = fuSlideShowStyle::None;

$defaultSearchLocation = get_option('fuEtsyDefSearchLocation', fuItemLocation::Internationally);
if (empty($defaultSearchLocation)) $defaultSearchLocation = fuItemLocation::Internationally;

$strings = 'tinyMCE.addI18n( "' . _WP_Editors::$mce_locale . '.fuEtsyStrings", {
    pluginTitle: "' . FU_ETSY_PLUGIN_TITLE . '",
    dblClicktoEdit: "' . esc_js( __( 'Double-click to edit', 'fast-etsy-listings' ) ) . '",
    felShopListings: "' . esc_js( __( 'Fast Etsy Shop Listings', 'fast-etsy-listings' ) ) . '",
    felSearch: "' . esc_js( __( 'Fast Etsy Search', 'fast-etsy-listings' ) ) . '",
    felSingleItem: "' . esc_js( __( 'Fast Etsy Single Listing', 'fast-etsy-listings' ) ) . '",
    felFeedback: "' . esc_js( __( 'Fast Etsy Shop Reviews', 'fast-etsy-listings' ) ) . '",
    itemIdLabel: "' . esc_js( __( 'Listing ID', 'fast-etsy-listings' ) ) . '",
    itemIdToolTip: "' . esc_js( __( 'Enter Etsy Listing ID', 'fast-etsy-listings' ) ) . '",
    searchTabTitle: "' . esc_js( __( 'Search Criteria', 'fast-etsy-listings' ) ) . '",
    presentationTabTitle: "' . esc_js( __( 'Presentation', 'fast-etsy-listings' ) ) . '",
    feedbackCommentsTabTitle: "' . esc_js( __( 'Review Comments', 'fast-etsy-listings' ) ) . '",
    picWidthLabel: "' . esc_js( __( 'Picture Width', 'fast-etsy-listings' ) ) . '",
    feedbackAccountInfoLabel: "' . esc_js( __( 'Show Account Info', 'fast-etsy-listings' ) ) . '",
    feedbackAccountInfoToolTip: "' . esc_js( __( 'Show Etsy Shop info', 'fast-etsy-listings' ) ) . '",
    feedbackRatingsLabel: "' . esc_js( __( 'Show Review Ratings', 'fast-etsy-listings' ) ) . '",
    feedbackRatingsToolTip: "' . esc_js( __( 'Show detailed seller star ratings', 'fast-etsy-listings' ) ) . '",
    feedbackCommentsLabel: "' . esc_js( __( 'Show Review Comments', 'fast-etsy-listings' ) ) . '",
    feedbackCommentsToolTip: "' . esc_js( __( 'Show detailed review comments', 'fast-etsy-listings' ) ) . '",
    feedbackCommentUserDateLabel: "' . esc_js( __( 'Show Review Commenting User/Date', 'fast-etsy-listings' ) ) . '",
    feedbackCommentUserDateToolTip: "' . esc_js( __( 'Show review commenting user and date', 'fast-etsy-listings' ) ) . '",
    feedbackLimitLabel: "' . esc_js( __( 'Max Review Comments', 'fast-etsy-listings' ) ) . '",
    feedbackLimitToolTip: "' . esc_js( __( 'Maximum number of review comments to show', 'fast-etsy-listings' ) ) . '",
    resultsTitleLabel: "' . esc_js( __( 'Results Title', 'fast-etsy-listings' ) ) . '",
    resultsTitleToolTip: "' . esc_js( __( 'Title text displayed above results', 'fast-etsy-listings' ) ) . '",
    queryLabel: "' . esc_js( __( 'Search Query', 'fast-etsy-listings' ) ) . '",
    queryToolTip: "' . esc_js( __( 'Etsy search query keywords', 'fast-etsy-listings' ) ) . '",
    searchLocationLabel: "' . esc_js( __( 'Search Location', 'fast-etsy-listings' ) ) . '",
    searchLocationToolTip: "' . esc_js( __( 'Search for Listings located nationally or internationally', 'fast-etsy-listings' ) ) . '",
    categoryLabel: "' . esc_js( __( 'Category ID', 'fast-etsy-listings' ) ) . '",
    sellerLabel: "' . esc_js( __( 'Etsy Shop Name', 'fast-etsy-listings' ) ) . '",
    sellerToolTip: "' . esc_js( __( 'Name of Etsy shop to show Listings from. Leave blank for default from settings; \'all\' for all; \'author\' for post author', 'fast-etsy-listings' ) ) . '",
    featuredDescLabel: "' . esc_js( __( 'Featured listings only', 'fast-etsy-listings' ) ) . '",
    featuredDescToolTip: "' . esc_js( __( 'Show featured listings only. Overrides any search query.', 'fast-etsy-listings' ) ) . '",
    sellerFeedbackToolTip: "' . esc_js( __( 'Etsy Shop to show reviews for', 'fast-etsy-listings' ) ) . '",
    sortingLabel: "' . esc_js( __( 'Sorting', 'fast-etsy-listings' ) ) . '",
    sortingToolTip: "' . esc_js( __( 'Select what sort order results should be presented in', 'fast-etsy-listings' ) ) . '",
    sortingDefault: "' . fuEtsySortOrder::$Labels[$defaultSort] . '",
    sorting' . fuEtsySortOrder::CreatedDesc . ': "' . fuEtsySortOrder::$Labels[fuEtsySortOrder::CreatedDesc] . '",
    sorting' . fuEtsySortOrder::CreatedAsc . ': "' . fuEtsySortOrder::$Labels[fuEtsySortOrder::CreatedAsc] . '",
    sorting' . fuEtsySortOrder::PriceHighest . ': "' . fuEtsySortOrder::$Labels[fuEtsySortOrder::PriceHighest] . '",
    sorting' . fuEtsySortOrder::PriceLowest . ': "' . fuEtsySortOrder::$Labels[fuEtsySortOrder::PriceLowest] . '",
    sorting' . fuEtsySortOrder::Score . ': "' . fuEtsySortOrder::$Labels[fuEtsySortOrder::Score] . '",
    sorting' . fuEtsySortOrder::Random . ': "' . fuEtsySortOrder::$Labels[fuEtsySortOrder::Random] . '",
    minMaxPriceLabel: "' . esc_js( __( 'Min / Max Price', 'fast-etsy-listings' ) ) . '",
    minMaxPriceToolTip: "' . esc_js( __( 'Leave blank for no filter', 'fast-etsy-listings' ) ) . '",
    resultsColRowsLabel: "' . esc_js( __( 'Results Columns and Rows', 'fast-etsy-listings' ) ) . '",
    slideShowLabel: "' . esc_js( __( 'Slideshow/Pagination Style', 'fast-etsy-listings' ) ) . '",
    slideShowDefault: "' . fuSlideShowStyle::$Labels[$defaultSlideShowStyle] . '",
    slideShow' . fuSlideShowStyle::None . ': "' . fuSlideShowStyle::$Labels[fuSlideShowStyle::None] . '",
    slideShow' . fuSlideShowStyle::Manual . ': "' . fuSlideShowStyle::$Labels[fuSlideShowStyle::Manual] . '",
    slideShow' . fuSlideShowStyle::Auto . ': "' . fuSlideShowStyle::$Labels[fuSlideShowStyle::Auto] . '",
    slideShow' . fuSlideShowStyle::LoadMore . ': "' . fuSlideShowStyle::$Labels[fuSlideShowStyle::LoadMore] . '",
    slideShow' . fuSlideShowStyle::CategoryGroups . ': "' . fuSlideShowStyle::$Labels[fuSlideShowStyle::CategoryGroups] . '",
    slidesLabel: "' . esc_js( __( 'Number of Slides/Pages', 'fast-etsy-listings' ) ) . '",
    useSettingsDefault: "' . esc_js( __( 'Use settings default', 'fast-etsy-listings' ) ) . '",
    blankUseDefaults: "' . esc_js( __( 'Leave blank to use default from settings', 'fast-etsy-listings' ) ) . '",
    invalidQueryCategoryNotBlank: "' . esc_js( __( 'Search query and/or category ID must be specified', 'fast-etsy-listings' ) ) . '",
    invalidMinPriceGreaterThanMax: "' . esc_js( __("Minimum price must be less than maximum price.", "fast-etsy-listings") ) . '",
    invalidMinBidsGreaterThanMax: "' . esc_js( __("Minimum bids must be less than maximum bids.", "fast-etsy-listings") ) . '",
  } );';

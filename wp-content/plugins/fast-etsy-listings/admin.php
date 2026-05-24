<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once __DIR__."/includes/admin.inc.php";

require_once __DIR__."/constants.php";
require_once __DIR__."/listing.php";
require_once __DIR__."/admin_options.php";
require_once __DIR__."/apicall_subinfo.php";


////////////////////////////////////////////////////////////////////////////////////////////////////////
// Admin page user interface

add_action('admin_menu', 'fu_etsy_menu');

function fu_etsy_menu() 
{
  add_options_page(
    FU_ETSY_PLUGIN_TITLE . " " . __("Options", "fast-etsy-listings"), 
    FU_ETSY_PLUGIN_TITLE, 
    'manage_options', 
    "fast-etsy-listings", //__FILE__, 
    'fu_etsy_options');
	add_action( 'admin_init', 'fu_etsy_admin_init' );
}

///////////////////////////////////////////////////////////////////////////////////////

function fu_etsy_admin_init()
{
  global $g_fuEtsySubInfoApiCall;

  // General Setting Section
  // First, we register a section. This is necessary since all future options must belong to one.
  add_settings_section(
      'fu_etsy_settings_section_general',         // ID used to identify this section and with which to register options
      __("General Options", "fast-etsy-listings"),                  // Title to be displayed on the administration page
      function () {},
      'fu_etsy_options_general'   // Page on which to add this section of options
      );

  add_settings_field(
    'fuEtsyDefSeller',
    __('Default Etsy Shop name', "fast-etsy-listings"),
    'fu_inc_definput_callback',
    'fu_etsy_options_general',
    'fu_etsy_settings_section_general',
    array(
        'id' => 'fuEtsyDefSeller',
        'label' => 
          __("Etsy shop owners should enter their Etsy Shop name here to avoid repeating it in every Block or shortcode.", "fast-etsy-listings") 
    )
  );      

  add_settings_field(
    'fuEtsySubscriptionKey',
    FU_ETSY_PLUGIN_TITLE . " " . __("Premium Subscription Key", "fast-etsy-listings"),
    'fu_inc_definput_callback',
    'fu_etsy_options_general',
    'fu_etsy_settings_section_general',
    array(
        'id' => 'fuEtsySubscriptionKey',
        'label' => $g_fuEtsySubInfoApiCall->getResult(),
        'width' => '50%'
    )
    );  

  // Defaults Section
  add_settings_section(
      'fu_etsy_settings_section_defaults',
      __("Default Options", "fast-etsy-listings"),
      function ()
      {
        echo "<p>";
        esc_html_e("Enter default search criteria and options to be used site-wide and avoid repeatedly entering these choices on every Fast Etsy Listings block, shortcode or widget.", "fast-etsy-listings");
        echo "</p>";
      },
      'fu_etsy_options_defaults'
      );
  
  add_settings_field(
      'fuEtsyDefCategory',
      __('Default Category ID', "fast-etsy-listings"),
      'fu_inc_category_callback',
      'fu_etsy_options_defaults',
      'fu_etsy_settings_section_defaults',
      array(
          'id' => 'fuEtsyDefCategory',
          'label' => __('Used for search results when no category is specified. Click box to open Category Chooser.', "fast-etsy-listings"),
          'pattern' => '[0-9]* .*',
          'width' => '50%',
          'class' => 'fu_etsy_category_input'
      )
    );

  add_settings_field(
    'fuEtsyDefSearchLocation',
    __('Default Search Location', "fast-etsy-listings"),
    function ($args) { fu_inc_addformselect_arr($args, fuEtsy::Countries); },
    'fu_etsy_options_defaults',
    'fu_etsy_settings_section_defaults',
    array(
        'id' => 'fuEtsyDefSearchLocation',
        'label' => __('Default location to search for results within.', "fast-etsy-listings")
    )
  );  

  add_settings_field(
    'fuEtsyDefSlideshowStyle',
    __('Default Slideshow / Pagination Style', "fast-etsy-listings"),
    function ($args) { fu_inc_addformselect_dict($args, fuSlideShowStyle::$Labels); },
    'fu_etsy_options_defaults',
    'fu_etsy_settings_section_defaults',
    array(
        'id' => 'fuEtsyDefSlideshowStyle',
        'label' => __('Default slideshow / pagination style: when slideshow="default" on shortcodes.', "fast-etsy-listings")
    )
    );

  add_settings_field(
    'fuEtsyDefNumSlides',
    __('Default Number of Slides', "fast-etsy-listings"),
    'fu_inc_intinput_callback',
    'fu_etsy_options_defaults',
    'fu_etsy_settings_section_defaults',
    array(
        'id' => 'fuEtsyDefNumSlides',
        'label' => __('Default number of slideshow slides.', "fast-etsy-listings")
    )
    );  

  add_settings_field(
    'fuEtsyDefColumns',
    __('Default Results Columns', "fast-etsy-listings"),
    'fu_inc_intinput_callback',
    'fu_etsy_options_defaults',
    'fu_etsy_settings_section_defaults',
    array(
        'id' => 'fuEtsyDefColumns',
        'label' => __('Default number of columns to split search results into, per slide/page.', "fast-etsy-listings")
    )
    );  
  
  add_settings_field(
    'fuEtsyDefRows',
    __('Default Results Rows', "fast-etsy-listings"),
    'fu_inc_intinput_callback',
    'fu_etsy_options_defaults',
    'fu_etsy_settings_section_defaults',
    array(
        'id' => 'fuEtsyDefRows',
        'label' => __('Default number of rows to split search results into, per slide/page.', "fast-etsy-listings")
    )
    ); 

  add_settings_field(
    'fuEtsyDefSort',
    __('Default Results Sorting', "fast-etsy-listings"),
    function ($args) { fu_inc_addformselect_dict($args, fuEtsySortOrder::$Labels); },
    'fu_etsy_options_defaults',
    'fu_etsy_settings_section_defaults',
    array(
        'id' => 'fuEtsyDefSort',
        'label' => __('Default sort order of search results.', "fast-etsy-listings")
    )
    );
      
  ////////////////////////////////////////////////////////////////
  // Cosmetic & Behaviour Section
  add_settings_section(
      'fu_etsy_settings_section_behaviour',
      __("Behavior Options", "fast-etsy-listings"),
      function () {},
      'fu_etsy_options_behaviour'
      );
  
  add_settings_field(
    'fuEtsyLinkText',
    __('Etsy link text', "fast-etsy-listings"),
    'fu_inc_definput_callback',
    'fu_etsy_options_behaviour',
    'fu_etsy_settings_section_behaviour',
    array(
        'id' => 'fuEtsyLinkText',
        'label' => __("Anchor text for links to Etsy. Should contain the word 'Etsy' to avoid misleading links.", "fast-etsy-listings"),
        'width' => '50%'
    )
    );
          
  add_settings_field(
    'fuEtsyEmptySearchMsg',
    __('Empty search results message', "fast-etsy-listings"),
    'fu_inc_definput_callback',
    'fu_etsy_options_behaviour',
    'fu_etsy_settings_section_behaviour',
    array(
        'id' => 'fuEtsyEmptySearchMsg',
        'label' => __('Displayed when an Etsy search returns no results.', "fast-etsy-listings"),
        'width' => '50%'
    )
    );
  add_settings_field(
      'fuEtsyPriorityListingText',
      __('Featured listing text', "fast-etsy-listings"),
      'fu_inc_definput_callback',
      'fu_etsy_options_behaviour',
      'fu_etsy_settings_section_behaviour',
      array(
          'id' => 'fuEtsyPriorityListingText',
          'label' => __('Displayed to highlight featured listings', "fast-etsy-listings"),
          'width' => '50%'
      )
      );
  add_settings_field(
    'fuEtsyLoadMoreButtonText',
    __('\'Load More\' button text', "fast-etsy-listings"),
    'fu_inc_definput_callback',
    'fu_etsy_options_behaviour',
    'fu_etsy_settings_section_behaviour',
    array(
        'id' => 'fuEtsyLoadMoreButtonText',
        'label' => __('Text shown on the \'Load More\' button for continuous scrolling slideshow styles', "fast-etsy-listings"),
        'width' => '50%'
    )
    );

  add_settings_field(
      'fuEtsySlideshowTimer',
      __('Slideshow Timer', "fast-etsy-listings"),
      'fu_inc_intinput_callback',
      'fu_etsy_options_behaviour',
      'fu_etsy_settings_section_behaviour',
      array(
          'id' => 'fuEtsySlideshowTimer',
          'label' => __('Time in seconds for automatic slideshows to step to next slide.', "fast-etsy-listings"),
          'min' => 1,
      )
      ); 
  add_settings_field(
      'fuEtsyNewWindow',
      __('Open in new window', "fast-etsy-listings"),
      'fu_inc_checkbox_callback',
      'fu_etsy_options_behaviour',
      'fu_etsy_settings_section_behaviour',
      array(
          'id' => 'fuEtsyNewWindow',
          'label' => __('If ticked, Etsy links will open in a new browser tab/window.', "fast-etsy-listings"),
      )
      ); 
  add_settings_field(
      'fuEtsyDeferredLoading',
      __('Deferred loading of listings', "fast-etsy-listings"),
      function ($args) { fu_inc_addformselect_dict($args, fuDeferedLoading::$Labels); },
      'fu_etsy_options_behaviour',
      'fu_etsy_settings_section_behaviour',
      array(
          'id' => 'fuEtsyDeferredLoading',
          'label' => __("Defer loading of Etsy listings to improve page load times.", "fast-etsy-listings") . "<br/>" .
                    __("Set to \"Always\" if you use a caching plugin to ensure up-to-date Etsy listings are shown.", "fast-etsy-listings")
      )
      );   
  add_settings_field(
      'fuEtsyDisplayFELLink',
      __("Display a link to plugin author's website", "fast-etsy-listings"),
      'fu_inc_checkbox_callback',
      'fu_etsy_options_behaviour',
      'fu_etsy_settings_section_behaviour',
      array(
          'id' => 'fuEtsyDisplayFELLink',
          'label' => __("Tick to display a \"Powered by Fast Etsy Listings\" link to plugin author's website.", "fast-etsy-listings"),
      )
      ); 
  


  // Cosmetic Section 
  add_settings_section(
    'fu_etsy_settings_section_cosmetic',
    __("Cosmetic Settings", "fast-etsy-listings"),
    function () {},
    'fu_etsy_options_behaviour'
    );  

  add_settings_field(
    'fuEtsyColourStyle',
    __('Color Scheme', "fast-etsy-listings"),
    'fu_etsy_colourstyle_callback',
    'fu_etsy_options_behaviour',
    'fu_etsy_settings_section_cosmetic',
    array(
        'id' => 'fuEtsyColourStyle',
        'label' => __('Color scheme of all Etsy results.', "fast-etsy-listings")
    )
    );

  add_settings_field(
    'fuEtsyPicAspect',
    __('Etsy Picture Aspect Ratio', "fast-etsy-listings"),
    function ($args) { fu_inc_addformselect_dict($args, fuPictureAspect::$Labels); },
    'fu_etsy_options_behaviour',
    'fu_etsy_settings_section_cosmetic',
    array(
        'id' => 'fuEtsyPicAspect',
        'label' => __("Picture aspect ratio for all Etsy images.", "fast-etsy-listings")
    )
    );

  add_settings_field(
    'fuEtsyTitleMaxLines',
    __('Max Etsy Listing Title Lines', "fast-etsy-listings"),
    'fu_inc_intinput_callback',
    'fu_etsy_options_behaviour',
    'fu_etsy_settings_section_cosmetic',
    array(
        'id' => 'fuEtsyTitleMaxLines',
        'label' => __('If set, limit Etsy listing titles to maximum number of lines.', "fast-etsy-listings")
    )
    );      

  add_settings_field(
    'fuEtsyShortDescMaxLines',
    __('Max Etsy Listing Short Description Lines', "fast-etsy-listings"),
    'fu_inc_intinput_callback',
    'fu_etsy_options_behaviour',
    'fu_etsy_settings_section_cosmetic',
    array(
        'id' => 'fuEtsyShortDescMaxLines',
        'label' => __('If set, limit Etsy listing short descriptions to maximum number of lines.', "fast-etsy-listings")
    )
    );      

  // Cosmetic & Behaviour - List Section 
  add_settings_section(
    'fu_etsy_settings_section_cosmetic_list',
    __("Search Listings Display Settings", "fast-etsy-listings"),
    function ()
    {
      echo "<p>";
      esc_html_e("Tailor the display of Etsy items from the Search Blocks and Shortcodes", "fast-etsy-listings");
      echo "</p>";
    },
    'fu_etsy_options_behaviour'
    );

  add_settings_field(
      'fuEtsyDefArrangementList',
      __('Etsy Search Listings Arrangement', "fast-etsy-listings"),
      'fu_etsy_arrangement_callback',
      'fu_etsy_options_behaviour',
      'fu_etsy_settings_section_cosmetic_list',
      array(
          'id' => 'fuEtsyDefArrangementList'
      )
      );

  add_settings_field(
      'fuEtsyDefDisplayFieldsList',
      __('Etsy Search Listing Fields to Display', "fast-etsy-listings"),
      'fu_etsy_displayfields_callback',
      'fu_etsy_options_behaviour',
      'fu_etsy_settings_section_cosmetic_list',
      array(
          'id' => 'fuEtsyDefDisplayFieldsList',
          'label' => __('Default Etsy Fields to Display for search listings blocks & shortcodes', "fast-etsy-listings")
      )
      );

  add_settings_field(
      'fuEtsyPicWidthList',
      __('Default Etsy Search Listings Picture Width', "fast-etsy-listings"),
      'fu_inc_intinput_callback',
      'fu_etsy_options_behaviour',
      'fu_etsy_settings_section_cosmetic_list',
      array(
          'id' => 'fuEtsyPicWidthList',
          'label' => __('Default width of thumbnails for search listings blocks & shortcodes.', "fast-etsy-listings")
      )
      );  


  // Cosmetic & Behaviour - Item Section 
  add_settings_section(
    'fu_etsy_settings_section_cosmetic_item',
    __("Single Item Display Settings", "fast-etsy-listings"),
    function ()
    {
      echo "<p>";
      esc_html_e("Tailor the display of Etsy items from the Single Item Block and Shortcode", "fast-etsy-listings");
      echo "</p>";
    },
    'fu_etsy_options_behaviour'
    );

  add_settings_field(
    'fuEtsyDefArrangementItem',
    __('Etsy Single Item Arrangement', "fast-etsy-listings"),
    'fu_etsy_arrangement_callback',
    'fu_etsy_options_behaviour',
    'fu_etsy_settings_section_cosmetic_item',
    array(
        'id' => 'fuEtsyDefArrangementItem'
    )
    );  

  add_settings_field(
    'fuEtsyDefDisplayFieldsItem',
    __('Etsy Single Item Fields to Display', "fast-etsy-listings"),
    'fu_etsy_displayfields_callback',
    'fu_etsy_options_behaviour',
    'fu_etsy_settings_section_cosmetic_item',
    array(
        'id' => 'fuEtsyDefDisplayFieldsItem',
        'label' => __('Default Etsy Fields to Display for single item blocks & shortcodes', "fast-etsy-listings")
    )
    );
        
  add_settings_field(
      'fuEtsyPicWidthItem',
      __('Default Etsy Item Picture Width', "fast-etsy-listings"),
      'fu_inc_intinput_callback',
      'fu_etsy_options_behaviour',
      'fu_etsy_settings_section_cosmetic_item',
      array(
          'id' => 'fuEtsyPicWidthItem',
          'label' => __('Default width of thumbnails for single item blocks & shortcodes.', "fast-etsy-listings")
      )
      );   

  ////////////////////////////////////////////////////////////////
  // Affiliate Section
  add_settings_section(
    'fu_etsy_settings_section_affiliate',
    __("Affiliate Options [Subscribers Only]", "fast-etsy-listings"),
    function ()
    {
      echo "<p>";
      echo wp_kses_post(__("If you are signed up to the Etsy affiliate scheme at Affiliate Window and wish to monetize your Etsy links, fill in your details here.", "fast-etsy-listings"));
      echo "</p>";
    },
    'fu_etsy_options_affiliate'
    );

  add_settings_field(
      'fuEtsyAdvertiserID',
      __('Affiliate Window Advertister ID', "fast-etsy-listings"),
      function ($args) { fu_inc_addformselect_dict($args, fuEtsyAWinAdvertisers::$Labels); },
      'fu_etsy_options_affiliate',
      'fu_etsy_settings_section_affiliate',
      array(
          'id' => 'fuEtsyAdvertiserID',
          'label' => __('The Affiliate Window Advertiser ID', "fast-etsy-listings"),
          'disabled' => $g_fuEtsySubInfoApiCall->getRespSuccess() && !$g_fuEtsySubInfoApiCall->isSubscriber(),
          )
    );

  add_settings_field(
      'fuEtsyAffiliateID',
      __('Affiliate Window Account ID', "fast-etsy-listings"),
      'fu_inc_definput_callback',
      'fu_etsy_options_affiliate',
      'fu_etsy_settings_section_affiliate',
      array(
          'id' => 'fuEtsyAffiliateID',
          'label' => __('Your Affiliate Window Account ID (usually 6 digits)', "fast-etsy-listings"),
          'readonly' => $g_fuEtsySubInfoApiCall->getRespSuccess() && !$g_fuEtsySubInfoApiCall->isSubscriber(),
          )
    );

  add_settings_field(
      'fuEtsyAffiliateRefID',
      __('Default Affiliate Window Click Reference ID', "fast-etsy-listings"),
      'fu_inc_definput_callback',
      'fu_etsy_options_affiliate',
      'fu_etsy_settings_section_affiliate',
      array(
          'id' => 'fuEtsyAffiliateRefID',
          'label' => __('Default click reference ID to aid reporting in Affiliate Window.', "fast-etsy-listings"),
          'readonly' => $g_fuEtsySubInfoApiCall->getRespSuccess() && !$g_fuEtsySubInfoApiCall->isSubscriber(),
          )
    );         

  // Ad Disclosure section
  add_settings_section(
    'fu_etsy_settings_section_affiliate_addisclosure',
    __("Ad / Affiliate Link Disclosure Settings", "fast-etsy-listings"),
    function ()
    {
      echo "<p>";
      esc_html_e("Use these settings to customize the ad disclosure notice presented and its placement. ", "fast-etsy-listings");
      echo "</p>";
    },
    'fu_etsy_options_affiliate'
    );

  add_settings_field(
    'fuEtsyAdDisclosurePlacement',
    __('Ad Disclosure Placement', "fast-etsy-listings"),
    function ($args) { fu_inc_addformselect_dict($args, fuAdDisclosurePlacement::$Labels); },
    'fu_etsy_options_affiliate',
    'fu_etsy_settings_section_affiliate_addisclosure',
    array(
        'id' => 'fuEtsyAdDisclosurePlacement',
        'label' => __('Choose where to place your ad disclosure notice.', "fast-etsy-listings")
    )
  );

  add_settings_field(
    'fuEtsyAdDisclosureText',
    __('Ad Disclosure Text', "fast-etsy-listings"),
    'fu_inc_definput_callback',
    'fu_etsy_options_affiliate',
    'fu_etsy_settings_section_affiliate_addisclosure',
    array(
        'id' => 'fuEtsyAdDisclosureText',
        'label' => __('Customize your ad disclosure notice text.', "fast-etsy-listings"),
        'width' => '100%',
    )
  );    

  // register our settings
  fu_etsy_register_settings();
}

////////////////////////////////////////////////////////////////////////////////////////////////////////
// Main options page HTML generation
//

DEFINE("FU_ETSY_VALIDADMINTABS", ["general", "defaults", "behaviour", "affiliate"]);
function fu_etsy_options(string $titleSuffix = "")
{
  if (!current_user_can('manage_options'))  {
    wp_die( esc_html__("You do not have sufficient permissions to access this page.", "fast-etsy-listings") );
  }  

  $bannerUrl = plugins_url(basename(__DIR__) . "/images/banner-772x250.jpg");  
  if (!empty($titleSuffix)) $titleSuffix = " - {$titleSuffix}";

  //Get the active tab from the $_GET param, sanitize and validate
  $default_tab = "general";
  $tab = isset($_GET['tab']) ? fu_inc_sanitize_text_in_array($_GET['tab'], FU_ETSY_VALIDADMINTABS, $default_tab) : $default_tab;
  
  ?>
<div class="wrap">
  <h1>Fubaby <?php echo esc_html(FU_ETSY_PLUGIN_TITLE . $titleSuffix); ?></h1>
  <img src="<?php echo esc_url($bannerUrl); ?>" width="772" height="250" border="0">

  <div class="fu_etsy_admin_row">
  <div class="fu_etsy_admin_col1">

      <nav class="nav-tab-wrapper">
        <?php 
        fu_etsy_admin_tab_html($tab, "general", __("General", "fast-etsy-listings"));
        fu_etsy_admin_tab_html($tab, "defaults", __("Default Options", "fast-etsy-listings"));
        fu_etsy_admin_tab_html($tab, "behaviour", __("Cosmetic & Behavior", "fast-etsy-listings"));
        fu_etsy_admin_tab_html($tab, "affiliate", __("Affiliate Options", "fast-etsy-listings"));
        ?>
      </nav>

      <!-- Create the form that will be used to render our options -->  
      <form method="post" action="options.php" id="fu_etsy_admin_form">  
        <?php settings_fields( "fu_etsy_settings_{$tab}" ); ?>  
        <div class="tab-content">
        <?php do_settings_sections( "fu_etsy_options_{$tab}" ); ?>
        </div>           
        <?php submit_button(); ?>  
      </form>  
    </div>
    <div class="fu_etsy_admin_col2">
      <div class="fu_etsy_admin_box">
        <h2><?php esc_html_e("Need help?", "fast-etsy-listings"); ?></h2>
        <p><?php esc_html_e("Check out some of the following resources for help and assistance", "fast-etsy-listings"); ?></p>
        <ul>
        <li><a href="https://wordpress.org/plugins/fast-etsy-listings/#faq" target="_blank"><?php esc_html_e("Frequently Asked Questions", "fast-etsy-listings"); ?></a></li>
        <li><a href="https://wordpress.org/support/plugin/fast-etsy-listings/" target="_blank"><?php esc_html_e("Support forum", "fast-etsy-listings"); ?></a></li>
        <li><a href="<?php echo esc_url(FU_ETSY_PLUGIN_WEBSITE); ?>" target="_blank"><?php esc_html_e("Documentation and support", "fast-etsy-listings"); ?></a></li>
        <li><a href="https://www.fubaby.com/contact-me/" target="_blank"><?php esc_html_e("Contact author directly", "fast-etsy-listings"); ?></a></li>
        <li><a href="https://www.fubaby.com/contact-me/" target="_blank"><?php esc_html_e("Signup to mailing list for news & updates", "fast-etsy-listings"); ?></a></li>
        </ul>
      </div>
      <div class="fu_etsy_admin_box">
        <h2><?php esc_html_e("Liking this plugin?", "fast-etsy-listings"); ?></h2>
        <p><?php esc_html_e("If you like this plugin, why not leave a review. Your feedback will be much appreciated", "fast-etsy-listings"); ?></p>
        <a href="https://wordpress.org/support/plugin/fast-etsy-listings/reviews/" target="_blank"><?php esc_html_e("Reviews on WordPress.org", "fast-etsy-listings"); ?></a>
        <p><span class="fu_etsy_feedback_stars"><span style="width: 80px"></span></span></p>
        <p><?php esc_html_e("If there is something you don't like about this plugin, get in touch and I'll try my best to make things better!", "fast-etsy-listings"); ?></p>
        <a href="https://www.fubaby.com/contact-me/" target="_blank"><?php esc_html_e("Contact author directly", "fast-etsy-listings"); ?></a>
      </div>
      <div class="fu_etsy_admin_box">
        <h2><?php esc_html_e("Useful Links", "fast-etsy-listings"); ?></h2>
        <ul>
        <li><a href="https://www.etsy.com" target="_blank">Etsy.com</a></li>
        </ul>
      </div>   
    </div>
  </div>

  <p><?php
    printf(
      /* translators: %s: name of plugin */
      esc_html__("The term 'Etsy' is a trademark of Etsy, Inc. This %s application uses the Etsy API but is not endorsed or certified by Etsy, Inc.", "fast-etsy-listings"),
      "Fubaby " . esc_html(FU_ETSY_PLUGIN_TITLE));
      ?>
  </p>
</div>
<?php
}

function fu_etsy_admin_tab_html(string $currentTab, string $tabName, string $tabTitle)
{
  $class = $currentTab == $tabName ? "nav-tab-active" : "";
  echo "<a href=\"?page=fast-etsy-listings&tab=".esc_attr($tabName)."\" class=\"nav-tab ".esc_attr($class)."\">".esc_html($tabTitle)."</a>";
}

////////////////////////////////////////////////////////////////////////////////////////////////////////
//  Callbacks
//

function fu_etsy_colourstyle_callback($args)
{
  // create a mock up listing.
  $listingItem = new fuEtsyListing;
  $listingItem->Title = "Title Title Title Title";
  $listingItem->Arrangement = 0;
  $listingItem->ItemId = "";
  $listingItem->Url = "#";
  $listingItem->EndTime = "Tomorrow";
  $listingItem->PriceConv = 100.00;
  $listingItem->Images = array( new fuImage(plugins_url(basename(__DIR__) . "/images/mango.jpg"), 80) );
  $listingItem->Active = true;
  $listingItem->Auction = true;
  $listingItem->setCurrency("USD");
  $listingItem->StoreName = "ACME Co.";

  $html = "";
  $html .= fu_inc_addformlabel($args);
  
  // First loop to display mock up listing per arrangement option.
  $html .= "<div class=\"fu_etsy_results\"><div class=\"fu_etsy_results_row\">";
  foreach (fuColourStyle::$Labels as $key => $value)
  {
    $html .= "<div class=\"fu_etsy_results_cellcommon fu_etsy_results_cell3\">";
    $html .= "<div style=\"padding: 10px 20px 10px 0px;\">";
    $listingHtml = $listingItem->GetListingAsHtml(80, 
        fuEtsy::DisplayFieldsListingDefault,
        $key);
    // JS call on listing links to select correct radio button.
    $inputId = "{$args['id']}_{$value}";
    $listingHtml = str_replace("href=\"#\"", "href=\"#\" onclick=\"" . esc_attr("javascript:document.getElementById(\"{$inputId}\").checked = true;return false;") . "\"", $listingHtml);
    $html .= $listingHtml;
    $html .= "</div></div>";
  }
  // Second loop of radio form controls per arrangement option.
  $html .= "</div><div class=\"fu_etsy_results_row\" style=\"padding: 20px\">";
  foreach (fuColourStyle::$Labels as $key => $value)
  {
    $inputId = "{$args['id']}_{$value}";
    $html .= "<div class=\"fu_etsy_results_cellcommon fu_etsy_results_cell3\" >";
    $html .= "<input type=\"radio\" id=\"".esc_attr($inputId)."\" name=\"".esc_attr($args['id'])."\" value=\"".esc_attr($key)."\"";
    $html .= checked($key, get_option($args['id']), false);
    $html .= "><label for=\"".esc_attr($inputId)."\">".esc_html($value)."</label></div>";
  }
  $html .= "</div></div>";

  echo wp_kses($html, array_merge(fu_inc_kses_admin_ruleset(), array(
    'a' => array(
      'href'     => true,
      'rel'      => true,
      'rev'      => true,
      'name'     => true,
      'target'   => true,
      'onclick'  => true, 
    )
  )));
}

function fu_etsy_arrangement_callback($args)
{
  $options = array(
    __("Picture centered, title below", "fast-etsy-listings") => 0,
    __("Title above, picture centered", "fast-etsy-listings") => fuArrangement::TitleTop,
    __("Picture left, title right", "fast-etsy-listings") => fuArrangement::ImageLeft,
    __("Title above, picture left", "fast-etsy-listings") => fuArrangement::TitleTop | fuArrangement::ImageLeft,
  );

  $thumbs = array(
    'fuEtsyDefArrangementItem' => 'apples.jpg',
    'fuEtsyDefArrangementList' => 'bananas.jpg',
    'fuEtsyDefArrangementWidget' => 'oranges.jpg',
  );
  
  // create a mock up listing.
  $listingItem = new fuEtsyListing;
  $listingItem->Title = "Title Title Title Title";
  $listingItem->ItemId = "";
  $listingItem->Url = "#";
  $listingItem->EndTime = "Tomorrow";
  $listingItem->PriceConv = 100.00;
  $listingItem->Images = array( new fuImage(plugins_url(basename(__DIR__) . "/images/" . $thumbs[$args['id']]), 80) );
  $listingItem->Active = true;
  $listingItem->Auction = true;
  $listingItem->setCurrency("USD");
  $listingItem->StoreName = "ACME Co.";

  $html = "";
  $html .= fu_inc_addformlabel($args);
  
  // First loop to display mock up listing per arrangement option.
  $html .= "<div class=\"fu_etsy_results\"><div class=\"fu_etsy_results_row\">";
  foreach ($options as $key => $value)
  {
    $listingItem->Arrangement = $value;
    $html .= "<div class=\"fu_etsy_results_cellcommon fu_etsy_results_cell4\">";
    $html .= "<div style=\"padding: 10px 20px 10px 0px;\">";
    $listingHtml = $listingItem->GetListingAsHtml(80, fuEtsy::DisplayFieldsListingDefault);
    // JS call on listing links to select correct radio button.
    $inputId = "{$args['id']}_{$value}";
    $listingHtml = str_replace("href=\"#\"", "href=\"#\" onclick=\"" . esc_attr("javascript:document.getElementById(\"{$inputId}\").checked = true;return false;") . "\"", $listingHtml);
    $html .= $listingHtml;
    $html .= "</div></div>";
  }
  // Second loop of radio form controls per arrangement option.
  $html .= "</div><div class=\"fu_etsy_results_row\" style=\"padding: 20px\">";
  foreach ($options as $key => $value)
  {
    $inputId = "{$args['id']}_{$value}";
    $html .= "<div class=\"fu_etsy_results_cellcommon fu_etsy_results_cell4\" >";
    $html .= "<input type=\"radio\" id=\"".esc_attr($inputId)."\" name=\"".esc_attr($args['id'])."\" value=\"".esc_attr($value)."\"";
    $html .= checked($key, get_option($args['id']), false);
    $html .= "><label for=\"".esc_attr($inputId)."\">".esc_html($key)."</label></div>";
  }
  $html .= "</div></div>";

  echo wp_kses($html, array_merge(fu_inc_kses_admin_ruleset(), array(
    'a' => array(
      'href'     => true,
      'rel'      => true,
      'rev'      => true,
      'name'     => true,
      'target'   => true,
      'onclick'  => true, 
    )
  )));
}

function fu_etsy_displayfields_callback($args)
{
  $option = (array)get_option($args['id'], fuEtsy::DisplayFieldsListingDefault);
  
  // Note the ID and the name attribute of the element should match that of the ID in the call to add_settings_field
  $html = "<ul class=\"fu_etsy_admin_checkbox\">";
  foreach (fuDisplayFields::$Labels as $field => $desc)
  {
    $html .= '<li><input type="checkbox" id="'.esc_attr($args['id'].'_'.$field).'" name="'.esc_attr($args['id']).'[]"'. checked(in_array($field, $option), 1, false) . ' value="'.esc_attr($field).'" />';
    $html .= '<label for="'.esc_attr($args['id'].'_'.$field).'">'.esc_html($desc).'</label></li>';
  }
  $html .= "<ul>";

  echo wp_kses($html, fu_inc_kses_admin_ruleset()); 
}


?>
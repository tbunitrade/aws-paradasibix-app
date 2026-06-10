<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once __DIR__."/includes/presentation.inc.php";

//////////////////////////////////////////////////////////////////////////
//
// Handle presentation of apicall results
//
//////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////////////////////////////
// fuEtsyPresentation class
// Manage the presentation of many Etsy items in a table and slideshow.
//
class fuEtsyPresentation extends fuPresentation
{
  public function __construct()
  {
    parent::__construct("fuEtsy", "fu_etsy_");

    $this->loadMoreText = get_option('fuEtsyLoadMoreButtonText', fuEtsyDefaultText::LoadMore());
    $this->gotoTopText = fuEtsyDefaultText::GotoTop();
  }

  ////////////////////////////////////////////////////////////////////////////////////////////////////////
  // Overrides for logic tied to specific plugin config.

  public function GetFELLinkHtml() : string
  {
    if (get_option('fuEtsyDisplayFELLink', '0') != '1')
      return "";

    $result = "<div class=\"{$this->cssPrefix}fel_link\">";
    $result .= __("Powered by ", "fast-etsy-listings");
    $result .= "<a href=\"" . esc_url(FU_ETSY_PLUGIN_WEBSITE) . "\" target=\"_blank\">";
    $result .= "WordPress Etsy Plugin - " . FU_ETSY_PLUGIN_TITLE;
    $result .= "</a></div>";
    return $result;
  }  
    
  public function GetAdDisclosureText($cssClass = 'fu_etsy_results_addisclosure'): string
  {
    $adDisclosureText = get_option('fuEtsyAdDisclosureText', '');
    if (!empty($adDisclosureText))
    {
      return "<div class=\"" . esc_attr($cssClass) . "\" style=\"display: block !important;\">" . esc_html($adDisclosureText) . "</div>";
    }
    return "";
  }
}


 
////////////////////////////////////////////////////////////////////////////////////////////////////////
// fuEtsyUtils
// Static class of helper methods
//
class fuEtsyUtils extends fuUtils
{

}

function fu_etsy_addisclosuretext($content) 
{
  if( is_single())
  {
    $presentation = new fuEtsyPresentation();
    switch (get_option('fuEtsyAdDisclosurePlacement', fuAdDisclosurePlacement::None))
    {
      case fuAdDisclosurePlacement::PageTop:
        $content = $presentation->GetAdDisclosureText('fu_etsy_content_addisclosure') . $content;
        break;
      case fuAdDisclosurePlacement::PageBottom:
        $content = $content . $presentation->GetAdDisclosureText('fu_etsy_content_addisclosure');
        break;
    }
  }
  return $content;
}
add_filter( 'the_content', 'fu_etsy_addisclosuretext', 99 );

<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

//////////////////////////////////////////////////////////////////////////
//
// Class to query the Etsy single Listing item
//
//////////////////////////////////////////////////////////////////////////

require_once __DIR__."/apicall.php";

class fuEtsyItemApiCall extends fuEtsyApiCall
{  
  public $item;
  
  function __construct($item) 
  {
    parent::__construct();
    $this->item = $item;
    $this->arrangement = (int)get_option('fuEystDefArrangementItem', fuArrangement::ImageLeft);
  }

  /**
   * Validate all params, load any defaults for those missing
   * 
   * @param &$message Pass back validation messages.
   * @return bool
   */
  protected function validateArgs(&$message) : bool
  {
    if (!parent::validateArgs($message)) return false;

    if ($this->picWidth <= 0) 
      $this->picWidth = get_option('fuEtsyPicWidthItem');	
    
    $this->item = intval($this->item);
    if ($this->item == 0)
    {
      $message = __("Item ID must be an integer.", "fast-etsy-listings");
      return false;
    }
    
    return true;
  } 

  protected function constructRequest()
  {
    $this->geoTargetGlobalId();

    $queryParams = array("id" => $this->item);
    $this->requestUri = $this->getEndpoint() . "etsy/item?" . http_build_query($queryParams);
    parent::constructRequest();
  }

  protected function createListingGrid($resp)
  {
    $displayFields = is_countable($this->displayFields) && count($this->displayFields) > 0 ? $this->displayFields : get_option('fuEtsyDefDisplayFieldsItem', fuEtsy::DisplayFieldsItemDefault);

    if ($resp != null && isset($resp->data) && count($resp->data) > 0) 
    {
      $this->resultCount = 1;
      $presentation = new fuEtsyPresentation();
      $listingItem = $this->initListing($resp->data[0]);

      $this->result .= "<div class=\"fu_etsy_results_container\">";

      // Ad disclosure text at top of each item.
      $adDisclosurePlacement = get_option('fuEtsyAdDisclosurePlacement', fuAdDisclosurePlacement::None);
      if ($adDisclosurePlacement == fuAdDisclosurePlacement::Top)
        $this->result .= $presentation->GetAdDisclosureText("fu_etsy_results_addisclosure");

      // Get the listing HTML
      $this->result .= $listingItem->GetListingAsHtml($this->picWidth, $displayFields);

      // Ad disclosure text at top of each item.
      if ($adDisclosurePlacement == fuAdDisclosurePlacement::Bottom)
        $this->result .= $presentation->GetAdDisclosureText("fu_etsy_results_addisclosure");

      $this->result .= "</div>"; // fu_etsy_results_container
    }
    else 
    {
      $this->result .= "<p>" . wp_kses_post(get_option('fuEtsyEmptySearchMsg', fuEtsyDefaultText::EmptySearch())) . "</p>";
    }
  }

  protected function initListing($item)
  {
    $listingItem = new fuEtsyListing();
    $listingItem->GlobalId = $this->globalId;
    $listingItem->setCurrency($item->Currency);
    $listingItem->Arrangement = $this->arrangement;
    $listingItem->PicAspect = fuPictureAspect::Freeform;
    $listingItem->parseFromFelObj($item);
  
    return $listingItem;
  }
  
  protected function createDeferredLoadingStub()
  {
    parent::createDeferredLoadingStub();

    $this->deferLoading->setup("fu_etsy_", "fu_etsy_load_getitem", esc_html__("Loading...", "fast-etsy-listings"));

    $this->deferLoading->addData([
      'fu_etsy_picwidth' => $this->picWidth,
      'fu_etsy_arrangement' => $this->arrangement,
      'fu_etsy_item' => $this->item,
    ]);

    $this->result = $this->deferLoading->createStub();
  }
   
}

// Main AJAX entry function
function fu_etsy_load_getitem() 
{
  // Deliberately no nonce check: Read only query of data from front end and will break when page caching plugins are used.
  $apicall = new fuEtsyItemApiCall(sanitize_text_field($_POST['fu_etsy_item']));
  
  if (isset($_POST['fu_etsy_picwidth'])) 
    $apicall->picWidth = intval($_POST['fu_etsy_picwidth']);
    
  if (isset($_POST['fu_etsy_arrangement'])) 
    $apicall->arrangement = intval($_POST['fu_etsy_arrangement']);

  $html = $apicall->call(true);
  echo wp_kses($html, fu_inc_kses_extended_ruleset());

  die();
}

add_action( 'wp_ajax_fu_etsy_load_getitem', 'fu_etsy_load_getitem' );
add_action( 'wp_ajax_nopriv_fu_etsy_load_getitem', 'fu_etsy_load_getitem' );
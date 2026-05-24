<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

//////////////////////////////////////////////////////////////////////////
//
// Class to query the Etsy API
//
//////////////////////////////////////////////////////////////////////////

require_once __DIR__."/apicall.php";
require_once __DIR__."/presentation.php";

class fuEtsySearchApiCall extends fuEtsyApiCall
{
  const LIMIT_MAX = 100;
  const CATEGORIES_MAX = 1;

  public $presentation;

  protected $title = "";
  protected $categoryIds = null;
  protected $query = "";
  protected $sellers = null;
  protected $featured = false;
  protected $sortOrder = "";
  protected $searchLocation = "";

  public $minPrice = -1;
  public $maxPrice = -1;

  function __construct(string $title) 
  {
    parent::__construct();
    $this->presentation = new fuEtsyPresentation();
    $this->arrangement = (int)get_option('fuEtsyDefArrangementList', fuArrangement::TitleBelow);
    $this->title = $title;
  }
  
  ////////////////////////////////////////////////////////////////////////////////////////////////////////
  // Setters
  // These should populate params. Make no assumptions they'll always be run. 
  // These should not load default values from wp_options, Validate() will do that.
  //
  public function setQuery(string $query)
  {
    $this->query = $query;
  }
  
  public function setSearchLocation(string $searchLocation)
  {
    if (in_array($searchLocation, fuEtsy::Countries))
      $this->searchLocation = $searchLocation;
  }

  // Comma separated list of Etsy shops name.
  // Can take special values 'all' or 'author'.
  // Empty is assumed to be no filtering on seller.
  public function setSellers(string $seller)
  {
    switch (strtolower($seller))
    {
      case "all":
        $this->sellers = ["all"];
        break;
      case "author":
        $this->sellers = [get_the_author_meta('etsyshop')];
        break;
      default:  
        if (!empty($seller)) $this->sellers = [$seller];
        break;
    }
  }
  
  public function setSortOrder(string $sortOrder)
  {
    if (empty($sortOrder))
      return;
    
    if (array_key_exists($sortOrder, fuEtsySortOrder::$Labels))
      $this->sortOrder = $sortOrder;  
  }
  
  
  public function setCategories(string $category)
  {
    preg_match('/(?P<id1>\d+)(?P<name> ".*")*[,\s]*(?P<id2>[\d\s]+)*[,\s]*(?P<id3>[\d\s]+)*/', $category, $matches);
    if (isset($matches['id1']))
      $this->categoryIds = array(intval($matches['id1']));
  }
  
  public function setFeatured(bool $value)
  {
    $this->featured = $value;
  }

  ////////////////////////////////////////////////////////////////////////////////////////////////////////
  // Core logic
  //

  /**
   * Validate all params, load any defaults for those missing
   * 
   * @param &$message Pass back validation messages.
   * @return bool
   */  
  protected function validateArgs(&$message) : bool
  {
    if (!parent::validateArgs($message)) return false;

    // Validate Table Presentation
    $this->presentation->validate();

    // Validate Pic Width
    if ($this->picWidth <= 0) 
      $this->picWidth = get_option('fuEtsyPicWidthList', 225);	

    // Validate against limits set by API call
    if ($this->presentation->limit > fuEtsySearchApiCall::LIMIT_MAX) 
      $this->presentation->limit = fuEtsySearchApiCall::LIMIT_MAX;

    // Validate sorting
    if (empty($this->sortOrder)) $this->sortOrder = get_option('fuEtsyDefSort', fuEtsySortOrder::CreatedDesc);

    // Validate categories
    if (is_null($this->categoryIds))
      $this->setCategories(get_option('fuEtsyDefCategory'));
    if (is_null($this->categoryIds))
      $this->categoryIds = array();

    if (is_countable($this->categoryIds) && count($this->categoryIds) > fuEtsySearchApiCall::CATEGORIES_MAX)
    {
      $message = sprintf(
        /* translators: %d: max number of categories */
        __("Too many category IDs, max = %d", "fast-etsy-listings"), 
        fuEtsySearchApiCall::CATEGORIES_MAX);
      return false;
    }

    // Validate min/max prices
    $this->minPrice = floatval($this->minPrice);
    if ($this->minPrice < 0) $this->minPrice = 0;
    $this->maxPrice = floatval($this->maxPrice);
    if ($this->maxPrice < 0) $this->maxPrice = 0;
    if ($this->minPrice > 0 && $this->maxPrice > 0 && $this->minPrice > $this->maxPrice)
    {
      $message = __("Minimum price must be less than maximum price.", "fast-etsy-listings");
      return false;
    }

    // Validate seller - if still null by this point, fallback to default seller.
    if (is_null($this->sellers))
    {
      $seller = get_option('fuEtsyDefSeller');
      $this->sellers = !empty($seller) ? explode(',', $seller) : array();
    }
    
    return true;
  }    


  protected function constructRequest()
  {
    $querySellers = is_countable($this->sellers) && count($this->sellers) > 0 && $this->sellers[0] != "all";

    // Don't geo-target on a retry or on queries by seller, get no results if items aren't available locally.
    if (!$this->retryFetch && 
      (!$querySellers || get_option('fuEtsyGeoTargetResults', fuGeoTargetting::Never) != fuGeoTargetting::NoSeller))
      $this->geoTargetGlobalId();

    // Round limit up to nearest 12 (to a max of 200). More likely to hit cache on future variations
    $querylimit = ceil($this->presentation->limit / 12) * 12;
    $querylimit = $querylimit > fuEtsySearchApiCall::LIMIT_MAX ? fuEtsySearchApiCall::LIMIT_MAX : $querylimit;

    $queryParams = array();

    // There must be either a query or a category. 
    if (!empty($this->query))
    {
      $queryParams["q"] = $this->query;
    }

    if (is_countable($this->categoryIds) && count($this->categoryIds) > 0)
      $queryParams["categoryIds"] = implode(',', $this->categoryIds);

    // Other request params
    if (!empty($this->searchLocation) && $this->searchLocation != fuEtsy::AnyCountry)
      $queryParams["searchLocation"] = $this->searchLocation;
    $queryParams["limit"] = $querylimit;

    if ($this->sortOrder == fuEtsySortOrder::Random)  // Random is not a real Etsy sort order, use CreatedDesc and randomise results we get
      $queryParams["sort"] = fuEtsySortOrder::CreatedDesc;
    else
      $queryParams["sort"] = $this->sortOrder;

    if ($querySellers)
    {
      $queryParams["sellers"] = $this->sellers[0];  // Only support querying one seller right now.
      if ($this->featured) $queryParams["featured"] = 1;
    }
    
    if ($this->minPrice > 0)
      $queryParams["minPrice"] = $this->minPrice;
    if ($this->maxPrice > 0)
      $queryParams["maxPrice"] = $this->maxPrice;
    // Price filters will be based on default globalId, not geotargeted globalId. 
    //if ($this->minPrice > 0 || $this->maxPrice > 0)
    //  $queryParams["priceCurrency"] = fuEtsyGlobal::CurrencyCodes[ get_option('fuEtsyGlobalID') ];

    $this->requestUri = $this->getEndpoint() . "etsy/search?" . http_build_query($queryParams);

    parent::constructRequest();
  }

  protected function countResults($result)
  {
    if (isset($result->data))
    {
      // Count results that we can show, either active, or inactive if configured to show them. 
      $this->resultCount = 0;
      foreach($result->data as $item)
      {
        $listing = $this->initListing($item);
        if ($listing->Active)
          ++$this->resultCount;
      }

      // If results are from cache and contain too few, retry request to get more
      if ($this->resultFromCache && $this->resultCount < $this->presentation->limit && count($result->data) >= $this->presentation->limit)
      {
        $this->traceLog(sprintf(
          /* translators: %d: results, %d active */
          __("Insufficient active results in cache: %1\$d results, %2\$d active.", "fast-etsy-listings"),
          count($result->data),
          $this->resultCount));

        $this->retryFetch = true;
        $this->disableApiCache = true;
      }
      // If geotargeting and no results, fallback to default country 
      else if (!empty($this->globalIdFallback) && $this->globalIdFallback != $this->globalId && $this->resultCount == 0)
      {
        $this->traceLog(sprintf(
          /* translators: %s: geotargeted country, %s default country */
          __("No results from geotargeted %1\$s query, falling back to default: %2\$s.", "fast-etsy-listings"),
          $this->globalId,
          $this->globalIdFallback));

        $this->globalId = $this->globalIdFallback;
        $this->retryFetch = true;
      }      
    }
  }

  protected function createListingGrid($resp)
  {
    $displayFieldsOptionName = 'fuEtsyDefDisplayFieldsList';
    $displayFields = is_countable($this->displayFields) && count($this->displayFields) > 0 ? $this->displayFields : get_option($displayFieldsOptionName, fuEtsy::DisplayFieldsListingDefault);

    if ($resp != null)
    {
  	  $this->result .= $this->presentation->genTableStart($this->title);
  
  	  if (isset($resp->data))
  	  {
        $count = 0;

        // Shuffle results if we random sort order
        if ($this->sortOrder == fuEtsySortOrder::Random)
          shuffle($resp->data);

  		  foreach($resp->data as $item)
  		  {
          $listingItem = $this->initListing($item);
          if (!$listingItem->Active)
            continue;

          if ($this->presentation->slideShow == fuSlideShowStyle::CategoryGroups)
            $this->result .= $this->presentation->genTableItemIntoGroups($listingItem->GetListingAsHtml($this->picWidth, $displayFields), $listingItem->Categories);
          else
            $this->result .= $this->presentation->genTableItem($listingItem->GetListingAsHtml($this->picWidth, $displayFields));

          if (++$count >= $this->presentation->limit)
            break;
  		  }
  	  }

  	  if ($this->resultCount == 0)
  		  $this->result .= "<p>" . wp_kses_post(get_option('fuEtsyEmptySearchMsg', fuEtsyDefaultText::EmptySearch())) . "</p>";
  	  
      $this->result .= $this->presentation->genTableEnd();
    }      
  }

  // Init Listing class from a search result item from API call.
  protected function initListing($item)
  {
    $listingItem = new fuEtsyListing();
    $listingItem->GlobalId = $this->globalId;
    $listingItem->setCurrency($item->Currency);
    $listingItem->Arrangement = $this->arrangement;      
    $listingItem->parseFromFelObj($item);

    return $listingItem;
  }
        

  protected function createDeferredLoadingStub()
  {
    parent::createDeferredLoadingStub();

    $loadingContent = $this->presentation->genTableStart($this->title, "");
    $loadingContent .= esc_html__("Loading...", "fast-etsy-listings");
    $loadingContent .= $this->presentation->genTableEnd();

    $this->deferLoading->setup("fu_etsy_", "fu_etsy_load_search", $loadingContent);

    $this->deferLoading->addData([
      'fu_etsy_picwidth' => $this->picWidth,
      'fu_etsy_arrangement' => $this->arrangement,
      'fu_etsy_title' => $this->title,
      'fu_etsy_query' => $this->query,
      'fu_etsy_searchLocation' => $this->searchLocation,
      'fu_etsy_sort' => $this->sortOrder,
      'fu_etsy_categoryIds' => implode(',', $this->categoryIds),
      'fu_etsy_sellers' => implode(',', $this->sellers),
      'fu_etsy_featured' => $this->featured ? 1 : 0,
      'fu_etsy_minPrice' => $this->minPrice,
      'fu_etsy_maxPrice' => $this->maxPrice,
    ]);
    $this->deferLoading->addData( $this->presentation->deferredLoadingData() );

    $this->result = $this->deferLoading->createStub();
  }
}

// Main AJAX entry function
function fu_etsy_load_search() 
{
  // Deliberately no nonce check: Read only query of data from front end and will break when page caching plugins are used.
  try
  {
    $apicall = new fuEtsySearchApiCall(stripslashes(sanitize_text_field($_POST['fu_etsy_title'])));

    if (isset($_POST['fu_etsy_picwidth'])) 
      $apicall->picWidth = intval($_POST['fu_etsy_picwidth']);

    if (isset($_POST['fu_etsy_arrangement']))
      $apicall->arrangement = intval($_POST['fu_etsy_arrangement']);

    if (isset($_POST['fu_etsy_query']))
      $apicall->setQuery(sanitize_text_field($_POST['fu_etsy_query']));

    if (isset($_POST['fu_etsy_searchLocation'])) 
     $apicall->setSearchLocation(fu_inc_sanitize_text_in_dict_keys($_POST['fu_etsy_searchLocation'], fuItemLocation::$Labels, ""));

    if (isset($_POST['fu_etsy_sort'])) 
      $apicall->setSortOrder(fu_inc_sanitize_text_in_dict_keys($_POST['fu_etsy_sort'], fuEtsySortOrder::$Labels, ""));

    if (isset($_POST['fu_etsy_categoryIds'])) 
      $apicall->setCategories(sanitize_text_field($_POST['fu_etsy_categoryIds']));

    if (isset($_POST['fu_etsy_sellers'])) 
      $apicall->setSellers(sanitize_text_field($_POST['fu_etsy_sellers']));

    if (isset($_POST['fu_etsy_featured'])) 
      $apicall->setFeatured(boolval($_POST['fu_etsy_featured']));

    if (isset($_POST['fu_etsy_minPrice'])) 
      $apicall->minPrice = floatval($_POST['fu_etsy_minPrice']);

    if (isset($_POST['fu_etsy_maxPrice'])) 
      $apicall->maxPrice = floatval($_POST['fu_etsy_maxPrice']);

    $apicall->presentation->loadPresentationFromParams();

    $html = $apicall->call(true);
    echo wp_kses($html, fu_inc_kses_extended_ruleset());
  } 
  catch(TypeError $e)
  {
    echo wp_kses_post($e->getMessage());
  }
  die();
}

add_action( 'wp_ajax_fu_etsy_load_search', 'fu_etsy_load_search' );
add_action( 'wp_ajax_nopriv_fu_etsy_load_search', 'fu_etsy_load_search' );
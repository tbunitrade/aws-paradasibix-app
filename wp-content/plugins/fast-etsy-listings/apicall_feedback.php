<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

//////////////////////////////////////////////////////////////////////////
//
// Class to query an Etsy shop details and reviews
//
//////////////////////////////////////////////////////////////////////////

require_once __DIR__."/apicall.php";
require_once __DIR__."/presentation.php";

class fuEtsyFeedbackApiCall extends fuEtsyApiCall
{  
  public $presentation;

  protected $seller = null;
  public $showAccountInfo = 1;
  public $showFeedbackComments = 1;
  
  function __construct($seller) 
  {
    parent::__construct();
    $this->presentation = new fuEtsyPresentation($this->globalId);
    $this->setSeller($seller);
    $this->callCacheExpiry = FU_ETSY_CALLCACHEEXPIRYFEEDBACK;
  }

  ////////////////////////////////////////////////////////////////////////////////////////////////////////
  // Setters
  // These should populate params. Make no assumptions they'll always be run. 
  // These should not load default values from wp_options, Validate() will do that.
  //
  public function setSeller(string $seller)
  {
    switch (strtolower($seller))
    {
      case "all":
        $this->seller = "";
        break;
      case "author":
        $this->seller = get_the_author_meta('etsyshop');
        break;
      default:  
        if (!empty($seller)) $this->seller = $seller;
        break;
      }
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

    // Validate Table Presentation
    $this->presentation->validate();
 
    // Validate seller, if none set by now, use first seller from defaults.
    if (empty($this->seller))
    {
      $seller = get_option('fuEtsyDefSeller');
      $this->seller = !empty($seller) ? explode(',', $seller)[0] : "";
    }

    if (empty($this->seller))
    {
      $message = __("Seller ID must not be empty.", "fast-etsy-listings");
      return false;
    }

    return true;
  } 

  protected function initListing($item){}

  protected function constructRequest()
  {
    $queryParams = array(
      "id" => $this->seller,
      "accountinfo" => $this->showAccountInfo,
      "limit" => $this->presentation->limit,
    );
    $this->requestUri = $this->getEndpoint() . "etsy/feedback?" . http_build_query($queryParams);
    parent::constructRequest();
  }

  protected function countResults($result)
  {
    if (isset($result->data))
      $this->resultCount = 1;
  }

  protected function createListingGrid($resp)
  {
    $linkText = get_option('fuEtsyLinkText', fuEtsyDefaultText::EtsyLink());
    $linksInNewWindow = get_option('fuEtsyNewWindow', 1) == 1;

    if ($resp != null && isset($resp->data)) 
    {
      $hasStore = $resp->data->hasStore;
      $url = $resp->data->storeUrl;

      // Header row
      $title = __('Feedback', "fast-etsy-listings");
      if ($this->showAccountInfo)
      {
        $title = $resp->data->storeName;
      }
      $this->result .= $this->presentation->genTableStart($title, $url);

      //---------------------------------------------------------
      // Seller feedback summary
      if ($this->showAccountInfo)
      {
        $this->result .= "<div class=\"fu_etsy_feedback_row\"><div class=\"fu_etsy_results_cell fu_etsy_results_cell_padding\" >\r\n";

        $this->result .= "<div class=\"fu_etsy_feedback_summary\">";
        $this->result .= $resp->data->storeDesc;
        $this->result .= "</div>\r\n";

        $this->result .= "<div class=\"fu_etsy_feedback_summary\">";
        $this->result .= str_repeat("&#9733;", (int)$resp->data->fbPcYr);
        $this->result .= "</div>\r\n";

        // Seller membership period
        $registrationDate = strtotime($resp->data->registrationDate);
        $this->result .= "<div class=\"fu_etsy_feedback_summary\">";
        $this->result .= sprintf(
          /* Translators: %1$s: date, %2$s: Country */
          __('Member since: %1$s', "fast-etsy-listings"), 
          date_i18n("F Y", $registrationDate));
        $this->result .= "</div>\r\n";

        //$resp->data->country
        if ($hasStore)
        {
          $this->result .= "<div class=\"fu_etsy_feedback_summary\">";
          $this->result .= fuUtils::GetExtLink(
            $url,
            __("Visit Shop", "fast-etsy-listings") . " " . $resp->data->storeName,
            get_option('fuEtsyNewWindow', 1) == 1,
            "&rarr; " . __("Visit Shop", "fast-etsy-listings"),
            "fu_etsy");    
          $this->result .= "</div>\r\n";
        }

        $this->result .= "</div></div>\r\n";
      }

      //---------------------------------------------------------
      // Feedback Details
      if ($this->showFeedbackComments)
      {
        if ($this->showAccountInfo)
        {
          // Only add sub heading, if no top header with store/username
          $this->addSubHeader(__('Reviews', "fast-etsy-listings"));
        }

        $count = 0;
        foreach ($resp->data->fbDetail as $detail)
        {
          $feedbackItem = "";
          $feedbackItem .= "<div class=\"fu_etsy_results_row\"><div class=\"fu_etsy_results_cell fu_etsy_results_cell_padding\" >\r\n";
          $feedbackItem .= "<div class=\"fu_etsy_feedback_detailleft\">";

          if (isset($detail->itemTitle) && isset($detail->Images))
          {
            $imgHtml = "<img src=\"" . htmlentities(fuUtils::GetSecureUrl($detail->Images[0]->url)) . "\" ";
            $imgHtml .= "alt=\"" . htmlentities($detail->itemTitle) . "\" ";
            $imgHtml .= "title=\"{$linkText}: " . htmlentities($detail->itemTitle) . "\" ";
            $imgHtml .= "width=\"{$detail->Images[0]->size}\" ";
            $imgHtml .= "/>";
            
            $feedbackItem .= fuUtils::GetExtLink($detail->itemUrl, $detail->itemTitle, $linksInNewWindow, $imgHtml, "fu_etsy");
          }
          $feedbackItem .= "</div>";
          $feedbackItem .= "<div class=\"fu_etsy_feedback_detailright\">";
          
          // Star rating and Commenting date
          $commentDate = strtotime($detail->commentTime);
          $feedbackItem .= "<span class=\"fu_etsy_feedback_detail_user\">" . str_repeat("&#9733;", (int)$detail->commentType) . " " . date_i18n("M j, Y", $commentDate) ."</span><br/>";

          // Comment text
          if (!empty($detail->commentText))
            $feedbackItem .= "<span class=\"fu_etsy_feedback_detail_comment\">" . $detail->commentText ."</span>";

          // Item title and link
          if (isset($detail->itemTitle))
          {
            $feedbackItem .= "<br/><span class=\"fu_etsy_feedback_item\">&rarr; "; 
            $feedbackItem .= fuUtils::GetExtLink(
              $detail->itemUrl, 
              $linkText, 
              $linksInNewWindow, 
              $detail->itemTitle, 
              "fu_etsy");
            $feedbackItem .= "</span>";
          }
          $feedbackItem .= "</div></div></div>\r\n";

          $this->result .= $this->presentation->genTableItem($feedbackItem);
          if (++$count >= $this->presentation->limit)
            break;

        }
      }

      $this->result .= $this->presentation->genTableEnd();
    }
    else 
    {
      $this->result .= "<p>" . __('Seller not found', "fast-etsy-listings") . "</p>";
    }
  }

  private function addSubHeader(string $title)
  {
    $this->result .= "<div class=\"fu_etsy_feedback_row\"><div class=\"fu_etsy_results_cell fu_etsy_results_cell_padding fu_etsy_results_cell_paddingtop\" >\r\n";
    $this->result .= "<span class=\"fu_etsy_feedback_header\">$title</span>";
    $this->result .= "</div></div>\r\n";

  }

  private function getRatingIcon($rating)
  {
    return str_repeat("&#9733;", (int)$rating);
  }

  private function getFeedbackStars($rating)
  {
    return '<span class="fu_etsy_feedback_stars"><span style="width: ' . ($rating * 16) . 'px"></span></span>';
  }
  
  // Deferred loading functions
  protected function createDeferredLoadingStub()
  {
    parent::createDeferredLoadingStub();

    $loadingContent = $this->presentation->genTableStart($this->seller, "");
    $loadingContent .= esc_html__("Loading...", "fast-etsy-listings");
    $loadingContent .= $this->presentation->genTableEnd();

    $this->deferLoading->setup("fu_etsy_", "fu_etsy_load_feedback", $loadingContent);

    $this->deferLoading->addData([
      'fu_etsy_seller' => $this->seller,
      'fu_etsy_accinfo' => $this->showAccountInfo ? 1 : 0,
      'fu_etsy_comments' => $this->showFeedbackComments ? 1 : 0,
    ]);
    $this->deferLoading->addData( $this->presentation->deferredLoadingData() );

    $this->result = $this->deferLoading->createStub();
  }
   
}

// Main AJAX entry function
function fu_etsy_load_feedback() 
{
  // Deliberately no nonce check: Read only query of data from front end and will break when page caching plugins are used. 
  $apicall = new fuEtsyFeedbackApiCall(sanitize_text_field($_POST['fu_etsy_seller']));
  $apicall->showAccountInfo = boolval($_POST['fu_etsy_accinfo']);
  $apicall->showFeedbackComments = boolval($_POST['fu_etsy_comments']);
  
  $apicall->presentation->loadPresentationFromParams();

  $html = $apicall->call(true);
  echo wp_kses($html, fu_inc_kses_extended_ruleset());

  die();
}

add_action( 'wp_ajax_fu_etsy_load_feedback', 'fu_etsy_load_feedback' );
add_action( 'wp_ajax_nopriv_fu_etsy_load_feedback', 'fu_etsy_load_feedback' );
<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

//////////////////////////////////////////////////////////////////////////
//
// Class to store a single listing
//
//////////////////////////////////////////////////////////////////////////

require_once __DIR__."/includes/listing.inc.php";


class fuEtsyListing extends fuListing
{
  public function __construct()
  {
    parent::__construct("fuEtsy", "fu_etsy_");

    // Set properties from current text domain
    $this->linkText = get_option('fuEtsyLinkText', fuEtsyDefaultText::EtsyLink());
    $this->linksInNewWindow = get_option('fuEtsyNewWindow', 1) == 1;

    // translators: %s: duration of time left */
    $this->endsInText = __("Ends in %s", "fast-etsy-listings");
    // translators: %s: date sale ended */
    $this->endedText = __("Ended %s", "fast-etsy-listings");  
  }

  protected function GetListingContent(int $picWidth, array $displayFields)
  { 
    $results = "";
    
    // Not top title
    if ( in_array(fuDisplayFields::Title, $displayFields) && ($this->Arrangement & fuArrangement::TitleTop) == 0 )
      $results .= "<h6 class=\"{$this->cssPrefix}title\">" . fuUtils::GetExtLink($this->Url, "{$this->linkText}: {$this->Title}", $this->linksInNewWindow, esc_html($this->Title), $this->extLinkClass) ."</h6>" . PHP_EOL;

    // Condition
    if ( in_array(fuDisplayFields::Condition, $displayFields) && $this->Condition != null)
      $results .= "<div class=\"{$this->cssPrefix}condition\">" . esc_html__("Condition", "fast-etsy-listings") . ": " . esc_html($this->Condition) ."</div>" . PHP_EOL;
          
    // Price
    if ( in_array(fuDisplayFields::Price, $displayFields) )
    {
      $priceStatus = "fu_etsy_priceactive";
      if ( !$this->Active ) 
      {
        $priceStatus = $this->Sold ? "fu_etsy_pricesold" : "fu_etsy_priceunsold";
      }			
      
      $pricePriority = "";
      $priorityText = get_option('fuEtsyPriorityListingText', fuEtsyDefaultText::PriorityListing());
      if ( $this->PriorityListing && !empty($priorityText) )
      {
        $pricePriority = "<span class=\"{$this->cssPrefix}prioritylistingtext\">".esc_html($priorityText)."</span> ";
      }

      $results .= "<div class=\"{$priceStatus}\">";
      $results .=  $pricePriority . $this->GetPrice($this->Currency, $this->PriceConv);
      $results .= "</div>\n";

      if ( $this->Sold )
        $results .= "&nbsp;<div class=\"{$this->cssPrefix}soldtext\">" . esc_html__("Sold", "fast-etsy-listings") . "</div>" . PHP_EOL;
      
      // Fixed Sale/Shop BIN images.
      if ( $this->ListingType == "Classified" )
      {
        $results .= "&nbsp;<div class=\"{$this->cssPrefix}classifiedtext\">" . esc_html__("Classified&nbsp;Ad", "fast-etsy-listings") . "</div>" . PHP_EOL;
      }
    }

    // Auction End Time.
    if ( in_array(fuDisplayFields::EndTime, $displayFields) && $this->EndTime != null)
      $results .= "<div class=\"{$this->cssPrefix}endtime\">" . esc_html($this->GetEndTime()) . "</div>" . PHP_EOL;

    // StoreName
    if ( in_array(fuDisplayFields::Store, $displayFields) && !empty($this->StoreName) )
      $results .= "<div class=\"{$this->cssPrefix}storename\">" . esc_html__("Shop", "fast-etsy-listings") . ": " . esc_html($this->StoreName) . "</div>" . PHP_EOL;    
  
    // Seller feedback.
    if ( in_array(fuDisplayFields::Feedback, $displayFields) && $this->Feedback != null )
      $results .= "<div class=\"{$this->cssPrefix}feedback\">" . esc_html__("Seller Feedback", "fast-etsy-listings") . ": " . str_repeat("&#9733;", $this->FeedbackPercent) . "</div>" . PHP_EOL;    
    
    // Location
    if ( in_array(fuDisplayFields::Location, $displayFields) && !empty($this->Location) )
      $results .= "<div class=\"{$this->cssPrefix}location\">" . esc_html__("Dispatches from", "fast-etsy-listings") . ": " . esc_html($this->Location). "</div>" . PHP_EOL;

    return $results;
  }
}

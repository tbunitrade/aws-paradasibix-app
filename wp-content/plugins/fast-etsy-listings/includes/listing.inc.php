<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once "utilities.inc.php";

//////////////////////////////////////////////////////////////////////////
//
// Class to store a single listing
//
//////////////////////////////////////////////////////////////////////////

if (!class_exists('fuImage'))
{
class fuImage
{
  public function __construct(string $url, int $size)
  {
    $this->url = $url;
    $this->size = $size;
  }

  public $url;
  public $size;
}
}

if (!class_exists('fuListing'))
{
abstract class fuListing
{
  const DebugPrintOuts = FALSE;

  // Tailor per plugin
  protected string $wpOptionPrefix = "fu";
  protected string $cssPrefix = "fu_";
  protected string $extLinkClass = "";
  protected string $linkText = "&rarr; View Item";
  protected bool $linksInNewWindow = true;
  protected string $endsInText = "Ends in %s";
  protected string $endedText = "Ended %s";
  
  // Class Properties.
  public $Arrangement = 0;
  public $PicAspect = fuPictureAspect::Square;
  public $GlobalId = "";
    
  public $ItemId;			//!< Listing ProductID
  public $ListingType; //!< Listing type.
  public $Title;     //!< Listing title
  public $ShortDescription; //!< Listing short description.
  public $Url;       //!< Listing URL
  public $Active;    //!< Listing active or completed.
  public $Images;     //!< array of image objects {url, size}
  
  public $StoreName;    //!< Name of store with listing.
  public $SellerName;   //!< Name of seller with listing.
  
  public $CurrencyCode;      //!< Currency code of listing.
  public $Currency;      //!< Currency of listing.
  public $Price;         //!< Price of listing in above currency
  public $PriceConv;     //!< Price of listing converted to global currency.
  public $PriceBIN;      //!< Buy It Now price of listing.
  public $PriceBINConv;  //!< Buy It Now price converted to global currency.
  public $Sold;          //!< For completed listings, whether item sold or not.
  
  public $Auction;
  public $AuctionBids;
  public $BIN;
  public $BINBestOffer;
  
  public $EndTime;       //!< Listing end time.
  public $Location;      //!< Sellers location.
  public $Categories;    //!< Array of item categories.
  public $Feedback;          //!< Sellers feedback score.
  public $FeedbackPercent;   //!< Percentage of positive feedback.

  public $Condition;     //!< Item condition
  public $PriorityListing = false;  //!< Priority Listing

  
  public function __construct(string $wpOptionPrefix = "fu", string $cssPrefix = "fu_")
  {
    $this->wpOptionPrefix = $wpOptionPrefix;
    $this->cssPrefix = $cssPrefix;
    $this->GlobalId = get_option($this->wpOptionPrefix . 'GlobalID');
    $this->PicAspect = get_option($this->wpOptionPrefix . 'PicAspect', fuPictureAspect::Square);

    $this->Active = TRUE;
    $this->Sold = FALSE;
    $this->AuctionBids= 0;
    $this->BINBestOffer = FALSE;
    $this->Feedback = null;
    $this->FeedbackPercent = 100;
    $this->Images = array();
    $this->Categories = array();
  }
  
  public function parseFromFelObj(object $data)
  {
    $this->ItemId = $data->Id;
    $this->ListingType = $data->ListingType;
    $this->Title = $data->Title;
    $this->ShortDescription = $data->Desc;
    $this->Url = $data->Url;
    $this->Active = $data->Active;
    $this->Images = $data->Images;
    $this->StoreName = $data->StoreName;
    $this->SellerName = $data->SellerName;
    $this->Price = $data->Price;
    $this->PriceConv = $data->PriceConv;
    $this->PriceBIN = $data->PriceBIN;
    $this->PriceBINConv = $data->PriceBINConv;
    $this->Sold = $data->Sold;
    $this->Auction = $data->Auction;
    $this->AuctionBids = $data->AuctionBids;
    $this->BINBestOffer = $data->BIN;
    $this->EndTime = $data->EndTime;
    $this->Location = $data->Location;
    $this->Categories = $data->Categories;
    $this->Feedback = $data->Feedback;
    $this->FeedbackPercent = $data->FeedbackPc;
    $this->Condition = $data->Condition;
    $this->PriorityListing = $data->Priority;

    $dateEnd = !is_null($this->EndTime) ? strtotime($this->EndTime) : false;
    if ($dateEnd !== false)
    {
      $diff = $dateEnd - time();    
      if ($diff < 0)
        $this->Active = false;
    }

    foreach ($this->Categories as $cat)
      $cat->name = str_replace("|", " &#8250; ", $cat->name);
  }

  public function setCurrency(string $currencyCode)
  {
    $this->CurrencyCode = $currencyCode;
    $this->Currency = fuCurrency::GetSymbolEntity($currencyCode);
  }

  protected function GetImage(int $picWidth) : string
  {
  	$picWidth = intval($picWidth);
  	if ( $picWidth <= 0 )
  		$picWidth = get_option("{$this->wpOptionPrefix}PicWidth");
  	
    $picUrl = "";
    foreach ($this->Images as $image)
    {
      if (empty($picUrl) || $image->size >= $picWidth)
        $picUrl = $image->url;
    }

    if (empty($picUrl))
      return "";
          
  	$result = "<img src=\"" . esc_url(fuUtils::GetSecureUrl($picUrl)) . "\" ";
  	$result .= "alt=\"" . esc_attr($this->Title) . "\" ";
  	$result .= "title=\"" . esc_attr("{$this->linkText}: {$this->Title}") . "\" ";
  	$result .= "width=\"" . esc_attr($picWidth) . "\" ";
  	$result .= "/>";
  	
  	return fuUtils::GetExtLink($this->Url, $this->Title, $this->linksInNewWindow, $result, $this->extLinkClass);
  }
  
  protected function GetEndTime()
  {
  	// get time difference in seconds.
  	$dateEnd = strtotime($this->EndTime);
    if ($dateEnd === false)
    {
      return sprintf($this->endsInText, "?");
    }

    $diff = $dateEnd - time();    
    $days = floor($diff / (60*60*24));
    $hours = floor(($diff - ($days*60*60*24)) / (60*60));
    $mins = floor(($diff - ($days*60*60*24) - ($hours*60*60)) / 60);
    
    if ( $diff > 60 )
    {
      $duration = "";
    	if ($days > 0) $duration .= $days . "d ";
    	if ($hours > 0) $duration .= $hours . "h ";
      if ($days == 0 && $mins > 0) $duration .= $mins . "m ";
      return sprintf($this->endsInText, $duration);
    }
    else if ( $diff >= 0 )
      return sprintf($this->endsInText, "{$diff}s ");
    else
      return sprintf($this->endedText, date_i18n("F j, Y, g:i a", $dateEnd));
  }


  protected function GetPrice(string $currency, $price) : string
  {
    $html = "<span>" . esc_html($currency) . "</span>";
    $html .= "<span>" . esc_html($price) . "</span>";
    return $html;
  }

  //! Func to write out a listing. 
  public function GetListingAsHtml(int $picWidth, array $displayFields, string $colourStyle = "")
  { 
    $results = "";

    // Frame
    if (empty($colourStyle)) $colourStyle = get_option($this->wpOptionPrefix . 'ColourStyle', "Default");
    $results .= "<div class=\"{$this->cssPrefix}listing_frame " . fuColourStyle::Classes[$colourStyle] . "\">" . PHP_EOL;
     
    $results .= "<div class=\"{$this->cssPrefix}listing_item\">" . PHP_EOL;
    $results .= $this->GetListingAsJSONLD();
    
    // Top Title
    if ( in_array(fuDisplayFields::Title, $displayFields) && $this->Arrangement & fuArrangement::TitleTop )	
      $results .= "<h6 class=\"{$this->cssPrefix}title\">" .  fuUtils::GetExtLink($this->Url, "{$this->linkText}: {$this->Title}", $this->linksInNewWindow, esc_html($this->Title), $this->extLinkClass) ."</h6>" . PHP_EOL;

    if ( $this->Arrangement & fuArrangement::ImageLeft )
      $results .= $this->GetListingLeftImage($picWidth, $displayFields);
    else
      $results .= $this->GetListingCentreImage($picWidth, $displayFields);

    // Description
    if ( in_array(fuDisplayFields::ShortDesc, $displayFields) && !empty($this->ShortDescription) )		
      $results .= "<div class=\"{$this->cssPrefix}desc\">" . wp_kses_post(str_replace("\n", "<br/>", $this->ShortDescription)) . "</div>" . PHP_EOL;

    // Visit link (now mandatory)
    $results .= "<div class=\"{$this->cssPrefix}visitlink\" style=\"display: block !important;\">" . fuUtils::GetExtLink($this->Url, $this->Title, $this->linksInNewWindow, esc_html($this->linkText), $this->extLinkClass) . "</div>" . PHP_EOL;

    $results .= "</div>" . PHP_EOL; // fu_xxx_listing_item
    $results .= "</div>" . PHP_EOL; // fu_xxx_listing_frame
           
    return $results;
  }	
  
  protected function GetListingCentreImage(int $picWidth, array $displayFields)
  { 
    $results = "<div class=\"{$this->cssPrefix}listing_contentwrapper_centre\">" . PHP_EOL;   
   
    // Image.
    if ( in_array(fuDisplayFields::Image, $displayFields) )
    {
      $imageWidthHeightStyles = $this->GetImageWidthHeightStyles($picWidth);
      $results .= "<div class=\"{$this->cssPrefix}listing_img\" style=\"".esc_attr($imageWidthHeightStyles)."\">" . 
        $this->GetImage($picWidth) . 
        "</div>" . PHP_EOL;
    }
    
    // Content box.
    $results .= "<div class=\"{$this->cssPrefix}listing_content_centre\">";
    
    $results .= $this->GetListingContent($picWidth, $displayFields);

    $results .= "</div>" . PHP_EOL; // fu_xxx_listing_content_centre
    
    $results .= "</div>" . PHP_EOL; // fu_xxx_listing_contentwrapper_centre
    
    return $results;
  }

  protected function GetListingLeftImage(int $picWidth, array $displayFields)
  { 
    $results = "<div class=\"{$this->cssPrefix}listing_contentwrapper_left\">" . PHP_EOL;   
    
    // Image
    if ( in_array(fuDisplayFields::Image, $displayFields) )
    {
      $imageWidthHeightStyles = $this->GetImageWidthHeightStyles($picWidth);
      $results .= "<div class=\"{$this->cssPrefix}listing_img_left\" style=\"".esc_attr($imageWidthHeightStyles)."\">";

      // Needed to float image, title and rightnow logo side-by-side on one line. 
      if ( $this->Arrangement == fuArrangement::ImageLeft)
        $results .= "<h6 class=\"{$this->cssPrefix}title_img\">";

      $results .= $this->GetImage($picWidth);

      if ( $this->Arrangement == fuArrangement::ImageLeft)
        $results .= "</h6>"; 

      $results .= "</div>" . PHP_EOL;
    }

    // Content box.
    $results .= "<div class=\"{$this->cssPrefix}listing_content_left\">" . PHP_EOL;
    
    $results .= $this->GetListingContent($picWidth, $displayFields);

    $results .= "</div>" . PHP_EOL; // fu_xxx_listing_content_left
     
    $results .= "</div>" . PHP_EOL; // fu_xxx_listing_contentwrapper_left

    return $results;
  }

  //! Get image height params based on aspect ratio settings.
  protected function GetImageWidthHeightStyles(int $picWidth) : string
  {
    switch ($this->PicAspect)
    {
      case fuPictureAspect::Freeform:
        return "width:{$picWidth}px;";
      case fuPictureAspect::Square:
        $picHeight = $picWidth;
        return "width:{$picWidth}px;height:{$picHeight}px;overflow:hidden;";
      case fuPictureAspect::FourThree:
        $picHeight = floor($picWidth * 3/4);
        return "width:{$picWidth}px;height:{$picHeight}px;overflow:hidden;";
    }

    return "";
  }

  abstract protected function GetListingContent(int $picWidth, array $displayFields);

  protected function GetListingAsJSONLD() : string
  {
    return '<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Product",
  "description": ' . json_encode($this->ShortDescription) . ',
  "name": ' . json_encode($this->Title) . ',
  "url": ' . json_encode($this->Url) . ',
  "image": ' . json_encode($this->Images[0]->url) . ',
  "offers": {
    "@type": "Offer",
    "availability": "https://schema.org/' . ($this->Active ? "InStock" : "SoldOut") . '",
    "price": ' . json_encode($this->PriceConv) . ',
    "priceCurrency": ' . json_encode($this->CurrencyCode) . '
  }
}
  </script>';
  }
}
}
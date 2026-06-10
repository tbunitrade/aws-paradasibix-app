<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

//////////////////////////////////////////////////////////////////////////
//
// Base class to query the Etsy API
//
//////////////////////////////////////////////////////////////////////////

require_once __DIR__."/constants.php";
require_once __DIR__."/utilities.php";
require_once __DIR__."/includes/apicall.inc.php";

abstract class fuEtsyApiCall extends fuApiCall
{
  protected string $affiliateRefId = '';


  function __construct() 
  {    
    parent::__construct(FU_ETSY_PLUGIN_TITLE, 'fuEtsy');

    $this->globalId = "ETSY";
    if (is_null($this->globalId)) debug_print_backtrace();
  }

  /**
   * @param string $refId - the AWin click reference ID. 
   */
  public function setAffiliateReferenceId(string $refId)
  {
    $this->affiliateRefId = $refId;
  }

  protected function getCallHash(): string
  {
    return "fuEtsy_" . md5($this->requestUri);
  }

  protected function constructRequest()
  { 
    parent::constructRequest();
    
    $this->requestUri .= "&ver=" . FU_ETSY_PLUGIN_VER;

    $advertiserId = trim(get_option('fuEtsyAdvertiserID', ''));
    if (!empty($advertiserId))
      $this->requestUri .= "&affiliateAdvertiserId={$advertiserId}";

    $campaignId = trim(get_option('fuEtsyAffiliateID', ''));
    if (!empty($campaignId))
      $this->requestUri .= "&affiliateCampaignId={$campaignId}";

    $referenceId = trim( !empty($this->affiliateRefId) ? $this->affiliateRefId : get_option('fuEtsyAffiliateRefID', '') );
    if (!empty($referenceId))
      $this->requestUri .= "&affiliateReferenceId=".urlencode($referenceId)."";

  }
}

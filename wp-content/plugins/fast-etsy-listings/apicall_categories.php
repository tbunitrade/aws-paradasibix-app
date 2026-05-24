<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

//////////////////////////////////////////////////////////////////////////
//
// Class to query categories
//
//////////////////////////////////////////////////////////////////////////

require_once __DIR__."/apicall.php";

class fuEtsyCategoriesApiCall extends fuEtsyApiCall
{  
  public $catId;
  public $getChildren = 1;
  public $getSiblings = 0;
  public $getParents = 0;

  function __construct(int $catId = 0) 
  {
    parent::__construct();
    $this->catId = $catId;
    $this->callCacheExpiry = FU_ETSY_CALLCACHEEXPIRYCATEGORIES;
  }

  protected function isUserAgentABot(): bool { return false; }

  protected function constructRequest()
  {
    $queryParams = array(
      "id" => intval($this->catId),
      "getChildren" => intval($this->getChildren),
      "getSiblings" => intval($this->getSiblings),
      "getParents" => intval($this->getParents)
    );

    $this->requestUri = $this->getEndpoint() . "categories?" . http_build_query($queryParams);
    parent::constructRequest();
  }

  protected function createListingGrid($resp)
  {
    if (!is_null($resp) && isset($resp->data))
      $this->result =  $resp->data;
    if (!is_null($resp) && isset($resp->error))
      $this->result =  $resp->error->description;
    return null;
  }

  protected function initListing($item)
  {}

  protected function createDeferredLoadingStub()
  {
    error_log(FU_ETSY_PLUGIN_TITLE . ": fuEtsyCategoriesApiCall.createDeferredLoadingStub() Unsupported function");
  }

  /**
   * Check server response for errors, log if so. 
   * @param object $resp 
   * @return bool True if no errors in server response.
   */
  protected function checkRespError($resp) : bool
  {    
    if ($this->respStatusCode != self::HTTP_STATUS_OK)
    {
      $errMessage = sprintf("Failed to query API: %d / %d - %s",
        $this->respStatusCode,
        !isset($resp->statusCode) ? -1 : $resp->statusCode,
        !isset($resp->error) ? "" : $resp->error->description);
      $this->errorLog($errMessage);
    }

    return true;
  }   
}

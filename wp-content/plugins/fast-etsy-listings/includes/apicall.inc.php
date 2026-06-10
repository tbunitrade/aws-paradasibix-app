<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

//////////////////////////////////////////////////////////////////////////
//
// Base class for all FEL API calls.
//
//////////////////////////////////////////////////////////////////////////

require_once __DIR__."/constants.inc.php";
require_once __DIR__."/deferred-loading.inc.php";

if (!class_exists('fuApiCall'))
{
abstract class fuApiCall
{
  private const FEL_ENDPOINT = "https://felp.fubaby.com/";
  private const FEL_CACHEEXPIRY = 360; // in minutes, thus 6hrs

  public const HTTP_STATUS_OK = 200;
  public const HTTP_STATUS_BADREQUEST = 400;
  public const HTTP_STATUS_UNAUTHORISED = 401;
  public const HTTP_STATUS_FORBIDDEN = 403;
  public const HTTP_STATUS_NOTFOUND = 404;

  // Tailor per plugin
  protected string $wpOptionPrefix = "fu";
  protected string $pluginTitle = "Fast XXX";

  protected string $globalId = "";
  protected string $globalIdFallback = "";  // original globalId to use as fallback when geo-targetting yields zero results.
  protected bool $retryFetch = false;
  protected int $callCacheExpiry = self::FEL_CACHEEXPIRY;
  protected string $requestUri = "";
  protected bool $resultFromCache = false;
  protected $result = ""; // string or object
  protected int $resultCount = 0;
  protected int $respStatusCode = 0;
  
  protected fuDeferredLoading $deferLoading;
  protected bool $disableApiCache = false;
  protected bool $enableTraceLog = false;

  // Core properties to adjust presentation.
  public int $picWidth = 0;
  public int $arrangement = 0;
  public $displayFields = [];
  
  function __construct(string $pluginTitle = "Fast XXX", string $wpOptionPrefix = "fu") 
  {
    $this->pluginTitle = $pluginTitle;
    $this->wpOptionPrefix = $wpOptionPrefix;

    $this->deferLoading = new fuDeferredLoading($pluginTitle, $wpOptionPrefix);

    // Disable API caching, either from settings or overridden in query request. Sanitized to a bool val.
    if (isset($_REQUEST['fuDisableAPICache']))
      $this->disableApiCache = boolval($_REQUEST['fuDisableAPICache']);
    else
      $this->disableApiCache = boolval(get_option("{$this->wpOptionPrefix}DisableAPICache", false));

    // Enable trace logging, either from settings or overridden in query request. Sanitized to a bool val.
    if (isset($_REQUEST['fuTraceLogging']))
      $this->enableTraceLog = boolval($_REQUEST['fuTraceLogging']);
    else
      $this->enableTraceLog = boolval(get_option("{$this->wpOptionPrefix}TraceLogging", false));
  }

  protected function initListing($item)
  {}

  /**
   * Renders server response into HTML listing grid.clear
   * @param object $resp
   */
  abstract protected function createListingGrid($resp);

  /**
   * Renders stub HTML/JS to trigger deferred loading of results on page load via AJAX.
   */
  protected function createDeferredLoadingStub()
  {
    $this->deferLoading->addData([
      'fuDisableAPICache' => $this->disableApiCache ? 1 : 0,
      'fuTraceLogging' => $this->enableTraceLog ? 1 : 0,
    ]);
  }
  
  public function forceDeferredLoading()
  {
    $this->deferLoading->setMode(fuDeferedLoading::Always);
  }

  public function disableDeferredLoading()
  {
    $this->deferLoading->setMode(fuDeferedLoading::Never);
  }

  // Utility funcs.
  protected function traceLog(string $msg)
  {
    if ($this->enableTraceLog)
      $this->result .= "<!-- {$this->pluginTitle} Trace Log: {$msg} -->"  . PHP_EOL;
  }

  protected function errorLog(string $msg)
  {
    error_log("{$this->pluginTitle}: {$msg}");
  }

  protected function errorAndDisplayLog(string $msg)
  {
    $this->errorLog($msg);
    $this->result .= "<p class=\"fu_error\">" . esc_html($this->pluginTitle) . ": " . esc_html($msg) . "</p>" . PHP_EOL;
  }
  
  protected function getEndpoint() : string
  {
    $endpointOverride = get_option("{$this->wpOptionPrefix}FelServerOverride");
    return empty($endpointOverride) ? fuApiCall::FEL_ENDPOINT : $endpointOverride;
  }

  protected function getRequestUri() : string { return $this->requestUri; }
  public function getResult() { return $this->result; }
  public function getResultCount() : int { return $this->resultCount; }
  public function getRetryFetch() : bool { return $this->retryFetch; }
  public function getRespSuccess() : bool { return $this->respStatusCode == self::HTTP_STATUS_OK; }

  public function setCachingOptions(int $callCacheExpiry)
  {
    $this->callCacheExpiry = $callCacheExpiry;
  }

  protected function getCallHash(): string
  {
    return "fu_" . md5($this->requestUri);
  }

  /**
   * Dump settings to JS fields to pass into a deferred loading stub
   */
  public function dumpSettingsToParams() : string
  {
    return '    
    fuDeferredLoading : "'.esc_js($this->deferLoading).'",
    fuDisableAPICache : '.esc_js($this->disableApiCache ? 1 : 0).',
    fuTraceLogging : '.esc_js($this->enableTraceLog ? 1 : 0).',';
  }

  /**
   * Main entry function 
   * @param bool $fromAjax Whether the call is from AJAX and thus should not return a deferred loading stub.
   * @return string The HTML of listings to render to page.
   */
  public function call(bool $fromAjax = false) 
  {
    $message = "";
    if (!$this->validateArgs($message))
    {
      $this->errorAndDisplayLog($message);
      return $this->result;
    }

    if (empty($this->requestUri))
    {
      $this->constructRequest();
      $this->traceLog(sprintf("Request URI: %s", $this->requestUri));
    }

    // Avoid a call for bot useragents, fallback to deferred loading
    if (!$this->isUserAgentABot())
    {
      // Fetch the request, either now or defer until after page load
      if ($fromAjax ||
        $this->deferLoading->mode() == fuDeferedLoading::Never ||
        ($this->deferLoading->mode() == fuDeferedLoading::NotCached && $this->isRequestCached()))
      {
        $resp = $this->performFetch();
        if ($this->checkRespError($resp))
          $this->createListingGrid($resp);
        return $this->result;
      }
    }

    // Fallback to deferred loading
    $this->createDeferredLoadingStub();
    return $this->result;  
  }

  /**
   * Validate all arguments passed in. Derived classes will validate further specific arguments.
   * @param string &$message - pass by ref, message with validation failure details.
   * @return bool - false if validation fails.
   */
  protected function validateArgs(string &$message) : bool
  {  
    $this->picWidth = intval($this->picWidth);
    return true;
  }

  /** 
   * Geo-target results to visitor's country. Requires CloudFlare GeoTargeting.
   * Upto derived classes to implement.
   */
  protected function geoTargetGlobalId()
  {}

  /**
   * Constructs the request query params.
   */
  protected function constructRequest()
  { 
    $siteHostname = wp_parse_url(get_site_url(), PHP_URL_HOST);
    $this->requestUri .= "&hostname={$siteHostname}";

    $subscriptionKey = trim(get_option("{$this->wpOptionPrefix}SubscriptionKey", ""));
    if (!empty($subscriptionKey))
      $this->requestUri .= "&subkey={$subscriptionKey}";

    $globalId = str_replace("-", "_", $this->globalId);	
    $this->requestUri .= "&globalId={$globalId}";
  }

  /**
   * Fetches the request, with the option to fallback and retry the fetch if necessary.
   * Used when Geo-targeting to fallback to default globalId.
   * @return object Server response decoded as an object
   */
  protected function performFetch()
  {
    $resp = $this->fetch();

    // If no results and we have a fallback GlobalId, try fetching again.
    if ($this->retryFetch)
    {
      $this->traceLog("Retrying Fetch.");
      $this->constructRequest();
      $this->traceLog(sprintf("Request URI: %s", $this->requestUri));

      $resp = $this->fetch();
    }

    return $resp;
  }

  /**
   * Checks if the current user agent is a bot
   * @return bool
   */
  protected function isUserAgentABot(): bool
  {
    if (is_user_logged_in()) return false;
    if (!array_key_exists('HTTP_USER_AGENT', $_SERVER) || empty($_SERVER['HTTP_USER_AGENT'])) return true;

    foreach (FU_BOT_USERAGENTS as $botUserAgent)
    {
      if (strpos($_SERVER['HTTP_USER_AGENT'], $botUserAgent) !== false)
        return true;
    }
    return false;
  }

  /**
   * Checks if the given request is cached as a transient.
   * @return bool
   */
  protected function isRequestCached(): bool
  {
    $callHash = $this->getCallHash();
    $resp = get_transient($callHash);
    $this->traceLog(sprintf("Request cached: %s", $resp !== false ? "True" : "False"));
    return $resp !== false;
  }

  /**
   * Fetch server response, from transient cache or server.
   * @return object Server response decoded as an object
   */
  protected function fetch()
  {
    // Retrieve cached transient
    $resp = null;
    $callHash = $this->getCallHash();
    $respRaw = get_transient($callHash);
    if ($respRaw !== false && !$this->disableApiCache) 
    {
      $this->traceLog("Retrieved pre-cached transient.");
      $resp = $this->decodeResponse($respRaw);
      if (isset($resp->statusCode)) $this->respStatusCode = (int)$resp->statusCode;
      $this->resultFromCache = true;
    }
    else
    {
      // No cached transient, so go query remotely.
      $timeStart = microtime(true);
      $remoteGetResp = wp_remote_get($this->requestUri);
      $this->respStatusCode = (int)wp_remote_retrieve_response_code($remoteGetResp);
      $respRaw = wp_remote_retrieve_body($remoteGetResp);

      if ($respRaw == null)
      {
        $this->errorLog("Failed to query API.");
        return null;
      }

      $resp = $this->decodeResponse($respRaw);
      if ($this->respStatusCode == self::HTTP_STATUS_OK)
      {
        // Only save transient on successful fetch
        set_transient($callHash, $respRaw, $this->callCacheExpiry * MINUTE_IN_SECONDS);
      }

      $timeEnd = microtime(true);
      $this->traceLog(sprintf("API call took: %d secs.", $timeEnd - $timeStart));
    }

    $this->countResults($resp);
    return $resp;
  }

  /**
   * Counts the results in the API response data
   */
  protected function countResults($resp)
  {
    if (isset($resp->data))
      $this->resultCount = count($resp->data);
  }

  /**
   * Decode server response from json to object.
   * @param object $respRaw
   * @return object Server response decoded as an object
   */
  protected function decodeResponse($respRaw)
  {
    $resp = json_decode($respRaw);
    if (json_last_error() !== JSON_ERROR_NONE)
    {
      $this->errorLog("Failed to decode API response.");
      return null;
    }
    return $resp;
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
      $this->errorAndDisplayLog($errMessage);
    }

    return $this->respStatusCode == self::HTTP_STATUS_OK;
  }  
}
}
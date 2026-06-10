<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

//////////////////////////////////////////////////////////////////////////
//
// Class to query sub info
//
//////////////////////////////////////////////////////////////////////////

require_once __DIR__."/apicall.php";

class fuEtsySubInfoApiCall extends fuEtsyApiCall
{ 
  protected $adminNotice = "";
  protected $isSubscriber = false;


  function __construct() 
  {
    parent::__construct();
  }

  public function getAdminNotice() : string { return $this->adminNotice; }

  public function isSubscriber() : bool { return (bool)$this->isSubscriber; }

  protected function isUserAgentABot(): bool { return false; }


  protected function constructRequest()
  {
    $this->requestUri = $this->getEndpoint() . "etsy/subinfo?id=1";
    parent::constructRequest();
    $this->callCacheExpiry = 1440; // 1 day
  }

  // Override fetch, don't bother querying subinfo if subkey is empty
  protected function fetch()
  {
    $subkey = get_option('fuEtsySubscriptionKey');
    if (!empty($subkey))
    {
      return parent::fetch();
    }
    $this->respStatusCode = self::HTTP_STATUS_OK;
    return null;
  }

  protected function createListingGrid($resp)
  {
    $subInfo = !is_null($resp) && isset($resp->sub) ? $resp->sub : null;

    $this->result .= "<p>";
    $this->result .= sprintf(
      /* translators: %s: status */
      esc_html__("Subscription Status: %s", "fast-etsy-listings"),
      fuSubStates::$Labels[ $subInfo != null ? $subInfo->state : fuSubStates::Unsubscribed ]);

    if ($subInfo == null || $subInfo->state == fuSubStates::Unsubscribed)
    {
      $subscriptionKey = get_option('fuEtsySubscriptionKey');
      if (!empty($subscriptionKey))
        $this->result .= " - <span style=\"color: red;\">" . esc_html__("Subscription Key / Domain not recognized", "fast-etsy-listings") . "</span>";

      $this->result .= "<br/><a href=\"" . esc_url(FU_ETSY_PLUGIN_WEBSITE) . "fast-etsy-listings-premium-subscription/\" target=\"_blank\">";
      $this->result .= esc_html__("Click here for more information on premium subscription benefits and pricing.", "fast-etsy-listings");
      $this->result .= "</a>";
      $this->result .= "</p>";
    }
    else
    {
      $expiry = date_i18n("F j, Y", strtotime($subInfo->expiry));
      if ($subInfo->daysLeft >= 0)
      {
        $this->result .= " - ";
        $this->result .= sprintf(
          /* translators: %s: expiry date */
          esc_html__("expires: %s", "fast-etsy-listings"),
          $expiry);
      }
      else
      {
        $this->result .= " - <span style=\"color: red;\">";
        $this->result .= sprintf(
          /* translators: %s: expired date */
          esc_html__("expired: %s", "fast-etsy-listings"),
          $expiry);
        $this->result .= "</span>";

        $this->adminNotice .= FU_ETSY_PLUGIN_TITLE . " - ";
        $this->adminNotice .= sprintf(
          /* translators: %s: expired date */
          __(" Your subscription expired on: %s", "fast-etsy-listings"),
          $expiry) . ". ";
          $this->adminNotice .= __("Please renew to continue using Premium features.", "fast-etsy-listings");
      }
      $this->result .= "</p>";

      // Display all domains associated.
      if (isset($subInfo->allDomains))
      {
        $this->result .= "<p><strong>" . esc_html__("All domains linked to subscription", "fast-etsy-listings") . "</strong></p>";
        $this->result .= "<ol>";
        foreach ($subInfo->allDomains as $domain)
        {
          $this->result .= "<li>" . esc_html($domain) . "</li>";
        }
        $this->result .= "</ol>";
      }

      if ($subInfo != null)
        $this->isSubscriber = $subInfo->state == fuSubStates::Subscriber || $subInfo->state == fuSubStates::GracePeriod;
    } 
  }

  protected function createDeferredLoadingStub()
  {
    error_log(FU_ETSY_PLUGIN_TITLE . ": fuEtsySubInfoApiCall.createDeferredLoadingStub() Unsupported function");
  }
}

////////////////////////////////////////////////////////////////////////////////////////////////////////
// Global Subscriber Info object  
$g_fuEtsySubInfoApiCall = null;


////////////////////////////////////////////////////////////////////////////////////////////////////////
// Show a notice to anyone with an expired license.

function fu_etsy_display_sub_expiry_notice() 
{
  global $g_fuEtsySubInfoApiCall;
  $adminNotice = $g_fuEtsySubInfoApiCall->getAdminNotice();

  // Check if expired and if notice has been dismissed...
  if ( !empty($adminNotice) && !get_option('fu_EtsyDismissedSubExpiry', FALSE ) ) 
  { 
    // Added the class "notice-fu-subexpired" so jQuery pick it up and pass via AJAX,
    // and added "data-notice" attribute in order to track multiple / different notices
    // multiple dismissible notice states
    echo '<div class="notice notice-error notice-fu-admin is-dismissible" ';
    echo 'data-notice="SubExpiry" data-nonce="'.wp_create_nonce("fu_etsy_admin_notice").'">';
    echo '<p>' . esc_html($adminNotice) . '</p>';
    echo '</div>';
  }
}
add_action( 'admin_notices', 'fu_etsy_display_sub_expiry_notice' );

<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

//////////////////////////////////////////////////////////////////////////
//
// Base class for all FEL API calls.
//
//////////////////////////////////////////////////////////////////////////

require_once __DIR__."/constants.inc.php";
require_once __DIR__."/utilities.inc.php";

if (!class_exists('fuDeferredLoading'))
{
  class fuDeferredLoading
  {
    // Static counter for all deferred loading stubs.
    protected static $id = 0;

    protected string $mode = fuDeferedLoading::NotCached;
    protected string $stubId;
    protected string $action;
    protected string $loadingContent;
    protected array $data = [];

    // Tailor per plugin
    protected string $wpOptionPrefix = "fu";
    protected string $pluginTitle = "Fast XXX";

    function __construct(string $pluginTitle = "Fast XXX", string $wpOptionPrefix = "fu") 
    {
      $this->pluginTitle = $pluginTitle;
      $this->wpOptionPrefix = $wpOptionPrefix;
  
      // Sanitise and validate request params.
      // Enable deferred loading, either from settings or overridden in query request.
      $this->mode = fu_inc_sanitize_text_in_dict_keys(
        isset($_REQUEST['fuDeferredLoading']) ? 
          sanitize_text_field($_REQUEST['fuDeferredLoading']) : 
          get_option("{$this->wpOptionPrefix}DeferredLoading", fuDeferedLoading::NotCached), 
        fuDeferedLoading::$Labels, 
        fuDeferedLoading::NotCached);
    }

    public function setMode(string $mode)
    {
      switch ($mode)
      {
        case fuDeferedLoading::Always:
        case fuDeferedLoading::NotCached:
        case fuDeferedLoading::Never:
          $this->mode = $mode;
          break;
        }
    }

    public function mode() : string { return $this->mode; }

    /**
     * Setup the deferred loading stub
     * @param string $stubIdPrefix - stubId prefix to uniquely identify block to replace by AJAX call
     * @param string $action - AJAX action
     * @param string $loadingContent - HTML loading content, shown momentarily until replaced by AJAX result.
     */
    public function setup(string $stubIdPrefix, string $action, string $loadingContent)
    {
      $this->stubId = $stubIdPrefix . self::$id++;
      $this->action = $action;
      $this->loadingContent = $loadingContent;
    }

    /**
     * @param array $data - dictionary of ajax data key/value pairs.
     */
    public function addData(array $data)
    {
      $this->data = array_merge($this->data, $data);
    }

    /**
     * @return string - HTML deferred loading stub
     */
    public function createStub() : string
    {
      $result = '<div id="'.$this->stubId.'">'.$this->loadingContent.'</div>
<script>
<!--
var content_'.$this->stubId.' = null;
var waitForDepends_'.$this->stubId.' = setInterval(function () {
  if (typeof jQuery !== "undefined") {
  if (window.fu_is_bot_useragent !== undefined) {
    clearInterval(waitForDepends_'.$this->stubId.');
    if (typeof jQuery.ajax == "undefined") {
      console.log("'.$this->pluginTitle.': jQuery.ajax missing. Are you using jQuery.Slim that doesn\'t include ajax? ");
      return;
    }
    if (fu_is_bot_useragent) return;
    content_'.$this->stubId.' = jQuery("#'.$this->stubId.'");
    jQuery(document).ready(jQuery.ajax({
      type : "post",
      url : "'.admin_url('admin-ajax.php').'",
      data : {
        action : "'.$this->action.'",' . PHP_EOL;

      foreach ($this->data as $dataKey => $dataValue)
      {
        $result .= $dataKey . ' : ' . wp_json_encode($dataValue) . ',' . PHP_EOL;
      }

      $result .= '
      },
      success : function( response ) {
        content_'.$this->stubId.'.html( response );
      },
      error: function(e) {
        console.log("'.$this->pluginTitle.': deferred loading error: " + e);
      }
    }));
  }}
}, 10);
//-->
</script>' . PHP_EOL;

      return $result;
    }

  }
}
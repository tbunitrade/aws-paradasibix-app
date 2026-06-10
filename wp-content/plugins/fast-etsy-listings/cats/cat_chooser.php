<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once __DIR__."/../constants.php";
require_once __DIR__."/../apicall_categories.php";


////////////////////////////////////////////////////////////////////////////////////////////////////////
// Add category choose UI to footer.
function fu_etsy_admincatchooser() 
{
  echo '<div id="fu_etsy_cat_popup_overlay">
  <div id="fu_etsy_cat_popup_content">
  <h2>' . esc_html(FU_ETSY_PLUGIN_TITLE) . esc_html__(" - Etsy Category Chooser", "fast-etsy-listings") . '</h2>
  <button id="fu_etsy_cat_popup_cancel" class="fu_etsy_cat_popup_button">X</button>';

  for ($i=1; $i<=FU_ETSY_CATEGORYMAXDEPTH; ++$i)
  {
    echo '<div class="fu_etsy_cat_popup_catlist_container">';
    echo '<select class="fu_etsy_cat_popup_catlist" id="fu_etsy_cat_list_l'.esc_attr($i).'" name="cat_list_l'.esc_attr($i).'" cat_depth="'.esc_attr($i).'" size="12"></select>';
    echo '<div class="fu_etsy_cat_popup_catlist_loading" id="fu_etsy_cat_list_loading_l'.esc_attr($i).'">' . esc_html__("Loading...", "fast-etsy-listings") . '</div>';
    echo '</div>';
  }

  echo '
  <div id="fu_etsy_cat_popup_chosenrow">
    <button id="fu_etsy_cat_popup_clearchosen" class="fu_etsy_cat_popup_button">' . esc_html__("Clear selection/Use default", "fast-etsy-listings") . '</button>
    <input type="text" id="fu_etsy_cat_popup_chosen" name="fu_etsy_cat_popup_chosen" readonly>
    <input type="hidden" id="fu_etsy_cat_popup_chosencatid" name="fu_etsy_cat_popup_chosencatid">
    <button id="fu_etsy_cat_popup_close" class="fu_etsy_cat_popup_button">' . esc_html__("Use category", "fast-etsy-listings") . '</button>
  </div>
  </div></div>
  <script>
  var fu_etsy_adminurl = "' . esc_js(admin_url('admin-ajax.php')) . '";
  var fu_etsy_catnonce = "' . esc_js(wp_create_nonce("fu_cat_chooser")) . '";
  </script>
  ';
}
add_action('admin_footer', 'fu_etsy_admincatchooser');

// AJAX entry function for category chooser
function fu_etsy_load_categories() 
{
  if (!check_ajax_referer('fu_cat_chooser', 'nonce', false)) 
  {
    error_log('Nonce check failed');
    die;
  }

  $apicall = new fuEtsyCategoriesApiCall(intval($_POST['fu_etsy_catid']));
  $apicall->getChildren = isset($_POST['fu_etsy_getChildren']) ? intval($_POST['fu_etsy_getChildren']) : 0;
  $apicall->getSiblings = isset($_POST['fu_etsy_getSiblings']) ? intval($_POST['fu_etsy_getSiblings']) : 0;
  $apicall->getParents = isset($_POST['fu_etsy_getParents']) ? intval($_POST['fu_etsy_getParents']) : 0;
  $jsonResp = $apicall->call(true);

  if (!is_null($jsonResp))
    wp_send_json($jsonResp);
  else
    wp_send_json(
      sprintf(
        /* translators: %d: category ID */
        __("%d - Category ID query failed! Possibly invalid or out of date.", "fast-etsy-listings"),
        intval($_POST['fu_etsy_catid'])));
}

add_action( 'wp_ajax_fu_etsy_load_categories', 'fu_etsy_load_categories' );
add_action( 'wp_ajax_nopriv_fu_etsy_load_categories', 'fu_etsy_load_categories' );
jQuery(document).ready(function($) {
  var $fu_etsy_category_input = null;
  const { __, _x, _n, _nx } = wp.i18n;

  function clearLists(listIdx) {
    for (var i = listIdx; ; ++i) {
      var cat_list = jQuery("#fu_etsy_cat_list_l" + i);
      if (cat_list.length == 0) break;
      cat_list.empty();
    }
  }

  function loadList(listIdx, parentCat = 0) {
    clearLists(listIdx);
    
    var $cat_list = jQuery("#fu_etsy_cat_list_l" + listIdx);
    var $cat_list_loading = jQuery("#fu_etsy_cat_list_loading_l" + listIdx);
    jQuery.ajax({
      type : "post",
      url : fu_etsy_adminurl,
      data : {
        action : "fu_etsy_load_categories",
        fu_etsy_catid : parseInt(parentCat),
        fu_etsy_getChildren : 1,
        fu_etsy_getSiblings: 0,
        fu_etsy_getParents: 0,
        nonce : fu_etsy_catnonce,
      },
      beforeSend: function() {
        $($cat_list_loading).addClass("active");
      },
      success : function( response ) {
        response.forEach(cat => {
          $cat_list.append(new Option(cat.name + (cat.childCount > 0 ? " ->" : ""), cat.id));
        });
        $($cat_list_loading).removeClass("active");
      },
      error: function(e) {
        console.log(__("deferred loading error:", "fast-etsy-listings") + e);
      }
    });
  }

  function loadListForCat(catId) {
    const { groups: { id1, name, id2, id3 } } = /(?<id1>\d+)(?<name> ".*")*[,\s]*(?<id2>[\d\s]+)*[,\s]*(?<id3>[\d\s]+)*/.exec(catId);
    clearLists(1);

    jQuery.ajax({
      type : "post",
      url : fu_etsy_adminurl,
      data : {
        action : "fu_etsy_load_categories",
        fu_etsy_catid : parseInt(id1),
        fu_etsy_getChildren : 1,
        fu_etsy_getSiblings: 1,
        fu_etsy_getParents: 1,
        nonce : fu_etsy_catnonce,
      },
      beforeSend: function() {
        $('[id^=fu_etsy_cat_list_loading_l]').addClass("active");
      },
      success : function( response ) {
        if (typeof response == "string") {
          $("#fu_etsy_cat_popup_chosen").val(response);
          loadList(1);
        }
        else {
          response.forEach(cat => {
            jQuery("#fu_etsy_cat_list_l" + cat.depth).append(new Option(cat.name + (cat.childCount > 0 ? " ->" : ""), cat.id, cat.selected, cat.selected));
          });
        }
        $('[id^=fu_etsy_cat_list_loading_l]').removeClass("active");
      },
      error: function(e) {
        console.log(__("deferred loading error:", "fast-etsy-listings") + e);
      }
    });
  }

  // Open the category chooser popup
  $("body").on("click", ".fu_etsy_category_input, .mce-fu_etsy_category_input", function() {
    if ($fu_etsy_category_input != null) return;
    $("#fu_etsy_cat_popup_overlay, #fu_etsy_cat_popup_content").addClass("active");
    $fu_etsy_category_input = this;
    catId = $fu_etsy_category_input.value;
    $("#fu_etsy_cat_popup_chosen").val(catId);
    if (catId != null && catId.length > 0)
      loadListForCat(catId);
    else
      loadList(1);
  });

  // Category selected
  $('[id^=fu_etsy_cat_list_l]').on('click', function() {
    idx = parseInt($(this).attr('cat_depth'));
    catName = $("option:selected", this).text();
    catId = this.value;

    if (catId.length > 0) {
      if (catName.endsWith(" ->")) {
        if (catId != $("#fu_etsy_cat_popup_chosencatid").val())
          loadList(idx+1, catId);
        catName = catName.slice(0, -3);
      }
      else {
        clearLists(idx+1);
      }
   
      $("#fu_etsy_cat_popup_chosen").val(catId + ' "' + catName + '"');
      $("#fu_etsy_cat_popup_chosencatid").val(catId);
    }
  });

  // Blank the chosen category
  $("#fu_etsy_cat_popup_clearchosen").on("click", function() {
    $("#fu_etsy_cat_popup_chosen").val("");
    $("#fu_etsy_cat_popup_chosencatid").val("");
  });  
  
  // Close the category chooser popup and pass back value to original input element.
  $("#fu_etsy_cat_popup_close").on("click", function() {
    $("#fu_etsy_cat_popup_overlay, #fu_etsy_cat_popup_content").removeClass("active");

    var nativeInputValueSetter = Object.getOwnPropertyDescriptor(window.HTMLInputElement.prototype, "value").set;
    nativeInputValueSetter.call($fu_etsy_category_input, $("#fu_etsy_cat_popup_chosen").val());
    var ev2 = new Event('input', { bubbles: true});
    $fu_etsy_category_input.dispatchEvent(ev2);
    $fu_etsy_category_input = null;
  });

  // Cancel the category chooser popup
  $("#fu_etsy_cat_popup_cancel").on("click", function() {
    $("#fu_etsy_cat_popup_overlay, #fu_etsy_cat_popup_content").removeClass("active");
    $fu_etsy_category_input = null;
  });

});

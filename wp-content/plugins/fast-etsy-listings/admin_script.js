// Map of Block clientIds to Slide Indices. Necessary when mulitple slideshows are in Block editor preview.
var fuSlideIndices = [];

var fuIsSubmitting = false
jQuery(document).ready(function () {
  const { __, _x, _n, _nx } = wp.i18n;
  jQuery('#fu_etsy_admin_form').on("submit", function(){ fuIsSubmitting = true; });
  jQuery('#fu_etsy_admin_form').data('initial-state', jQuery('#fu_etsy_admin_form').serialize());
  jQuery(window).on('beforeunload', function() {
    if (!fuIsSubmitting && jQuery('#fu_etsy_admin_form').length != 0 && jQuery('#fu_etsy_admin_form').serialize() != jQuery('#fu_etsy_admin_form').data('initial-state')){
      return __("You have unsaved changes which will not be saved.", "fast-etsy-listings");
    }
  });
})

function fuConvertArrToObj(data, key, val) {
  var output = [];
  for (i = 0; i < data.length; i++) {
      obj = {};
      obj[key] = data[i];
      obj[val] = data[i];
      output.push(obj);
  }
  return output;
}

// shorthand no-conflict safe document-ready function
jQuery(function($) {
  // Hook into the "notice-fu-subexpired" class we added to the notice, so
  // Only listen to YOUR notices being dismissed
  $( document ).on( 'click', '.notice-fu-admin .notice-dismiss', function () {
      // Read the "data-notice" information to track which notice
      // is being dismissed and send it via AJAX
      var notice = $(this).closest( '.notice-fu-admin' );
      // Make an AJAX call
      $.ajax( ajaxurl,
        {
          type: 'POST',
          data: {
            action: 'dismissed_notice_handler',
            type: notice.data('notice'),
            nonce: notice.data('nonce'),
          }
        } );
    } );
});

// debound method used for Block previews, prevents repeated AJAX calls in quick succession whilst typing in search criteria
function fuDebounce(func, wait, immediate) {
  var timeout;
  return (...args) => {
      var context = this, args = arguments;
      var later = () => {
          timeout = null;
          if (!immediate) func.apply(context, args);
      };
      var callNow = immediate && !timeout;
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
      if (callNow) func.apply(context, args);
  };
};
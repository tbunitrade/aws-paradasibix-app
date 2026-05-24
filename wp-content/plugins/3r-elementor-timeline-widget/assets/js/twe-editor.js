jQuery(document).on('click', '.twae_hide_upgrade_notice_editor', function () {
     var $notice = jQuery(this).closest('.twae-upgrade-pro-notice'); 

    jQuery.ajax({
        
        url: twae_ajax_obj.ajax_url,
        type: 'POST',
        data: {
            action: 'twae_hide_upgrade_notice_editor',
            security: twae_ajax_obj.nonce
        },
       
        
        success: function (response) {
             $notice.slideUp(300, function () {
                $notice.remove(); 
            });
        }
    });
});


jQuery(document).on('focus', 'select[data-setting="twe_layout"]', function () {

    const $select = jQuery(this);

    const allowedValues = ['centered', 'one-sided'];

    if (!allowedValues.includes($select.val())) {
        $select.val('centered');
    }

    $select.find('option').each(function () {
        const val = jQuery(this).val();
        jQuery(this).prop('disabled', !allowedValues.includes(val));
    });
});




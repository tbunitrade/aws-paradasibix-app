(function() {
	tinymce.PluginManager.add('fast_etsy_listings', function( editor, url ) {

		//add button
		editor.addButton('fast_etsy_listings', {
			type: 'menubutton',			
      image: url + '/images/etsyicon.svg',
			tooltip: editor.getLang('fuEtsyStrings.pluginTitle'),
			menu: [
        {
          text: editor.getLang('fuEtsyStrings.felShopListings'),
            onclick: function() {
              editor.execCommand('etsy_shop_listings_popup','',{
            });
          }	
        },
        {
          text: editor.getLang('fuEtsyStrings.felSearch'),
            onclick: function() {
              editor.execCommand('etsy_search_popup','',{
            });
          }	
        },
        {
          text: editor.getLang('fuEtsyStrings.felSingleItem'),
              onclick: function() {
            editor.execCommand('etsy_listings_popup','',{
            });
          }	
        },
        {
          text: editor.getLang('fuEtsyStrings.felFeedback'),
            onclick: function() {
              editor.execCommand('etsy_feedback_popup','',{
            });
          }	
        },
			]			
		});

	});
})();

(function() {
	tinymce.PluginManager.add('etsy_listing', function( editor, url ) {
		var sh_tag = 'etsy_listing';

		//helper functions 
		function getAttr(s, n) {
			r = new RegExp(n + '=\"([^\"]*)\"', 'g').exec(s);
			if (!r) r = new RegExp(n + '=(.+?)([\\s$])', 'g').exec(s);
			return r ?  window.decodeURIComponent(r[1]) : '';
		};

		function replaceShortcodes( content ) {
			return content.replace( /\[(fu_etsy_listing|etsy_listing)([^\]]*)\]/g, function(all,tag,attr) {
        var placeholder = url + '/images/etsylistingpanel.jpg';
        attr = window.encodeURIComponent( attr );
  
        html = '<img src="' + placeholder + '" class="mceItem wp-' + sh_tag + '" ';
        html += 'title="' + editor.getLang("fuEtsyStrings.dblClicktoEdit") + '" ';
        html += 'data-sh-attr="' + attr + '" data-mce-resize="false" data-mce-placeholder="1" />';
        return html;
			});
		}

		function restoreShortcodes( content ) {
			return content.replace( /(?:<p(?: [^>]+)?>)*(<img [^>]+>)(?:<\/p>)*/g, function( match, image ) {
				if (!getAttr( image, 'class' ).includes('wp-' + sh_tag)) return match;
				var data = getAttr( image, 'data-sh-attr' );

				if ( data ) {
					return '<p>[' + sh_tag + data + ']</p>';
				}
				return match;
			});
		}

		//add popup
		editor.addCommand('etsy_listing_popup', function(ui, v) {
			//setup defaults
			var item = v.item ? v.item : '';
			var picwidth = v.picwidth ? v.picwidth : '';
			
			editor.windowManager.open( {
				title: editor.getLang('fuEtsyStrings.felSingleItem'),
				body: [		 
          {
            type: 'textbox',
            name: 'item',
            label: editor.getLang('fuEtsyStrings.itemIdLabel'),
            value: item,
            tooltip: editor.getLang('fuEtsyStrings.itemIdToolTip'),
            minWidth: 300,
            required: true,
          },
          {
            type: 'textbox',
            subtype: 'number',
            name: 'picwidth',
            label: editor.getLang('fuEtsyStrings.picWidthLabel'),
            value: picwidth,
            placeholder: fuEtsyScriptShortcode.picWidthItem,
            tooltip: editor.getLang('fuEtsyStrings.blankUseDefaults'),
            maxWidth: 100,
            maxLength: 4,
          },					   				
				],
				onsubmit: function( e ) {
					var shortcode_str = '[' + sh_tag;
					// check for item
					if (typeof e.data.item != 'undefined' && e.data.item.length)
						shortcode_str += ' item="' + e.data.item + '"';
					// check for picwidth
					if (typeof e.data.picwidth != 'undefined' && e.data.picwidth.length)
						shortcode_str += ' picwidth="' + e.data.picwidth + '"';

					//add panel content
					shortcode_str += ']';
					//insert shortcode to tinymce
					editor.insertContent( shortcode_str);
				}
			});
	  });

		//replace from shortcode to an image placeholder
		editor.on('BeforeSetcontent', function(event){ 
			event.content = replaceShortcodes( event.content );
		});

		//replace from image placeholder to shortcode
		editor.on('GetContent', function(event){
			event.content = restoreShortcodes(event.content);
		});

		//open popup on placeholder double click
		editor.on('DblClick',function(e) {
			if ( e.target.nodeName == 'IMG' && e.target.className.indexOf('wp-' + sh_tag) > -1 ) {
				var data = e.target.attributes['data-sh-attr'].value;
				data = window.decodeURIComponent(data);
				//console.log(title);
				editor.execCommand('etsy_listing_popup','',{
					item : getAttr(data,'item'),
					picwidth : getAttr(data,'picwidth'),
				});
			}
		});
	});
})();
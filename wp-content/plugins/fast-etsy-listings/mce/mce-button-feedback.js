
(function() {
	tinymce.PluginManager.add('etsy_feedback', function( editor, url ) {
		var sh_tag = 'etsy_feedback';

		//helper functions 
		function getAttr(s, n) {
			r = new RegExp(n + '=\"([^\"]*)\"', 'g').exec(s);
			if (!r) r = new RegExp(n + '=(.+?)([\\s$])', 'g').exec(s);
			return r ?  window.decodeURIComponent(r[1]) : '';
		};

		function replaceShortcodes( content ) {
			return content.replace( /\[(fu_etsy_feedback|etsy_feedback)([^\]]*)\]/g, function(all,tag,attr) {
        var placeholder = url + '/images/etsyfeedbackpanel.jpg';
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
		editor.addCommand('etsy_feedback_popup', function(ui, v) {
			//setup defaults
			var seller = v.seller ? v.seller : '';
			var accountinfo = v.accountinfo ? v.accountinfo : 1;
			var comments = v.comments ? v.comments : 1;
			var commentuserdate = v.commentuserdate ? v.commentuserdate : 1;
      var defaultRows = v.limit ? v.limit : 10;
      var rows = v.rows ? v.rows : defaultRows;
			var columns = v.columns ? v.columns : 1;
			var slideshow = v.slideshow ? v.slideshow : '';
			var slides = v.slides ? v.slides : '';
			
			editor.windowManager.open( {
				title: editor.getLang('fuEtsyStrings.felFeedback'),
				body: [
          {
            //title: 'TabPanels',
            type: 'tabpanel',
            items: [
              // Search Criteria Tab Panel
              {
                title: editor.getLang('fuEtsyStrings.searchTabTitle'),
                type: 'form',
                items: [
                  {
                    type: 'textbox',
                    name: 'seller',
                    label: editor.getLang('fuEtsyStrings.sellerLabel'),
                    value: seller,
                    placeholder: fuEtsyScriptShortcode.etsyDefSeller,
                    tooltip: editor.getLang('fuEtsyStrings.sellerFeedbackToolTip')
                  },
               ]
              },
              // Presentation Tab Panel
              {
                title: editor.getLang('fuEtsyStrings.presentationTabTitle'),
                type: 'form',
                items: [
                  {
                    type: 'checkbox',
                    name: 'accountinfo',
                    checked: accountinfo == 1,
                    label: editor.getLang('fuEtsyStrings.feedbackAccountInfoLabel'),
                    tooltip: editor.getLang('fuEtsyStrings.feedbackAccountInfoToolTip'),
                    maxWidth: 100,
                    maxLength: 10,
                  },
                  {
                    type: 'checkbox',
                    name: 'comments',
                    checked: comments == 1,
                    label: editor.getLang('fuEtsyStrings.feedbackCommentsLabel'),
                    tooltip: editor.getLang('fuEtsyStrings.feedbackCommentsToolTip'),
                    maxWidth: 100,
                    maxLength: 10,
                  },
                ]
              },
              // Feedback Comments Tab Panel
              {
                title: editor.getLang('fuEtsyStrings.feedbackCommentsTabTitle'),
                type: 'form',
                items: [
                  {
                    layout: 'grid',
                    name: 'align',
                    label: editor.getLang('fuEtsyStrings.resultsColRowsLabel'),
                    type: 'form',
                    columns: '2',
                    spacingH: 10,
                    spacingV: 0,
                    padding: 0,
                    items: [
                      {
                        type: 'textbox',
                        subtype: 'number',
                        name: 'columns',
                        value: columns,
                        placeholder: fuEtsyScriptShortcode.etsyDefColumns,
                        tooltip: editor.getLang('fuEtsyStrings.blankUseDefaults'),
                        maxWidth: 100,
                        maxLength: 2,
                      },	
                      {
                        type: 'textbox',
                        subtype: 'number',
                        name: 'rows',
                        value: rows,
                        placeholder: fuEtsyScriptShortcode.etsyDefRows,
                        tooltip: editor.getLang('fuEtsyStrings.blankUseDefaults'),
                        maxWidth: 100,
                        maxLength: 2,
                      },	
                    ]
                  },	
                  {
                    type: 'listbox',
                    name: 'slideshow',
                    label: editor.getLang('fuEtsyStrings.slideShowLabel'),
                    value: slideshow,
                      'values': [						           
                            {text: editor.getLang('fuEtsyStrings.useSettingsDefault') + ' (' + editor.getLang('fuEtsyStrings.slideShowDefault') + ')', value: ''},
                            {text: editor.getLang('fuEtsyStrings.slideShowNone'), value: 'None'},
                            {text: editor.getLang('fuEtsyStrings.slideShowManual'), value: 'Manual'},
                            {text: editor.getLang('fuEtsyStrings.slideShowAuto'), value: 'Auto'},
                            {text: editor.getLang('fuEtsyStrings.slideShowLoadMore'), value: 'LoadMore'},
                            //{text: editor.getLang('fuEtsyStrings.slideShowInfiniteScroll'), value: 'InfiniteScroll'},
                          ],
                  },	
                  {
                    type: 'textbox',
                    subtype: 'number',
                    name: 'slides',
                    label: editor.getLang('fuEtsyStrings.slidesLabel'),
                    value: slides,
                    placeholder: fuEtsyScriptShortcode.etsyDefNumSlides,
                    tooltip: editor.getLang('fuEtsyStrings.blankUseDefaults'),
                    maxWidth: 100,
                    maxLength: 4,
                  },	
                ]
              },
            ]
          }     
				],
				onsubmit: function( e ) {
					var shortcode_str = '[' + sh_tag;
					// check for seller
					if (typeof e.data.seller != 'undefined' && e.data.seller.length)
						shortcode_str += ' seller="' + e.data.seller + '"';

          shortcode_str += ' accountinfo="' + (typeof e.data.accountinfo != 'undefined' && e.data.accountinfo ? '1' : '0') + '"';
          shortcode_str += ' comments="' + (typeof e.data.comments != 'undefined' && e.data.comments ? '1' : '0') + '"';

					// check for limit
					if (typeof e.data.limit != 'undefined' && e.data.limit.length)
						shortcode_str += ' limit="' + e.data.limit + '"';

          // check for rows/columns
          if (typeof e.data.rows != 'undefined' && e.data.rows.length)
            shortcode_str += ' rows="' + e.data.rows + '"';
          if (typeof e.data.columns != 'undefined' && e.data.columns.length)
            shortcode_str += ' columns="' + e.data.columns + '"';
          // check for slides
          if (typeof e.data.slideshow != 'undefined' && e.data.slideshow.length)
            shortcode_str += ' slideshow="'+e.data.slideshow+'"';
          if (typeof e.data.slides != 'undefined' && e.data.slides.length)
            shortcode_str += ' slides="' + e.data.slides + '"';

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
			var cls  = e.target.className.indexOf('wp-' + sh_tag);
			if ( e.target.nodeName == 'IMG' && e.target.className.indexOf('wp-' + sh_tag) > -1 ) {
				var data = e.target.attributes['data-sh-attr'].value;
        data = window.decodeURIComponent(data);
				//console.log(title);
				editor.execCommand('etsy_feedback_popup','',{
					seller : getAttr(data,'seller'),
					accountinfo : getAttr(data,'accountinfo'),
					comments : getAttr(data,'comments'),
          commentuserdate : getAttr(data,'commentuserdate'),
					limit : getAttr(data,'limit'),
          rows : getAttr(data,'rows'),
					columns : getAttr(data,'columns'),
					slideshow : getAttr(data,'slideshow'),
					slides : getAttr(data,'slides'),
				});
			}
		});
	});
})();
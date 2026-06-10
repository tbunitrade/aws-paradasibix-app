(function() {
	tinymce.PluginManager.add('etsy_shop_listings', function( editor, url ) {
		var sh_tag = 'etsy_shop_listings';

		//helper functions 
		function getAttr(s, n) {
			r = new RegExp(n + '=\"([^\"]*)\"', 'g').exec(s);
			if (!r) r = new RegExp(n + '=(.+?)([\\s$])', 'g').exec(s);
			return r ?  window.decodeURIComponent(r[1]) : '';
		};

		function replaceShortcodes( content ) {
			return content.replace( /\[(fu_etsy_shop_listings|etsy_shop_listings)([^\]]*)\]([^\]]*)\[\/(fu_etsy_shop_listings|etsy_shop_listings)\]/g, function( all,tag,attr,con ) {
        var placeholder = url + '/images/etsyshoplistingspanel.jpg';
        attr = window.encodeURIComponent( attr );
        con = window.encodeURIComponent( con );
        var html = '<img src="' + placeholder + '" class="mceItem wp-' + sh_tag + '" ';
        html += 'title="' + editor.getLang("fuEtsyStrings.dblClicktoEdit") + '" ';
        html += 'data-sh-attr="' + attr + '" data-sh-content="' + con + '" data-mce-resize="false" data-mce-placeholder="1" />';
        return html;
      });
		}

		function restoreShortcodes( content ) {
			return content.replace( /(?:<p(?: [^>]+)?>)*(<img [^>]+>)(?:<\/p>)*/g, function( match, image ) {
				if (!getAttr( image, 'class' ).includes('wp-' + sh_tag)) return match;
				var data = getAttr( image, 'data-sh-attr' );
				var con = getAttr( image, 'data-sh-content' );

				if ( data ) {
					return '<p>[' + sh_tag + data + ']' + con + '[/'+sh_tag+']</p>';
				}
				return match;
			});
		}

    function addHtmlSpecialChars(str) {
      return str.replace(/&/g, "&amp;").replace(/>/g, "&gt;").replace(/</g, "&lt;").replace(/"/g, "&quot;");
    }

    function removeHtmlSpecialChars(str) {
      return str.replace(/&amp;/g, "&").replace(/&gt;/g, ">").replace(/&lt;/g, "<&lt;>").replace(/&quot;/g, "\"");
    }

    function validateForm(data) {
      return "";
    }

		//add popup
		editor.addCommand('etsy_shop_listings_popup', function(ui, v) {
			//setup defaults
			var content = v.content ? v.content : '';
			var query = v.query ? removeHtmlSpecialChars(v.query) : '';
			var seller = v.seller ? removeHtmlSpecialChars(v.seller) : '';
      var featured = v.featured == 'true';
			var sort = v.sort ? v.sort : '';
			var rows = v.rows ? v.rows : '';
			var columns = v.columns ? v.columns : '';
			var slideshow = v.slideshow ? v.slideshow : '';
			var slides = v.slides ? v.slides : '';
			var picwidth = v.picwidth ? v.picwidth : '';
			
			editor.windowManager.open( {
				title: editor.getLang('fuEtsyStrings.felShopListings'),
        //bodyType: 'tabpanel',
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
                    name: 'content',
                    label: editor.getLang('fuEtsyStrings.resultsTitleLabel'),
                    value: content,
                    tooltip: editor.getLang('fuEtsyStrings.resultsTitleToolTip'),
                  },				       
                  {
                    type: 'textbox',
                    name: 'seller',
                    label: editor.getLang('fuEtsyStrings.sellerLabel'),
                    value: seller,
                    placeholder: fuEtsyScriptShortcode.defSeller,
                    tooltip: editor.getLang('fuEtsyStrings.sellerToolTip')
                  },                  
                  {
                    type: 'checkbox',
                    name: 'featured',
                    label: editor.getLang('fuEtsyStrings.featuredDescLabel'),
                    checked: featured,
                    tooltip: editor.getLang('fuEtsyStrings.featuredDescToolTip'),
                  },
                  {
                    type: 'textbox',
                    name: 'query',
                    label: editor.getLang('fuEtsyStrings.queryLabel'),
                    value: query,
                    tooltip: editor.getLang('fuEtsyStrings.queryToolTip'),
                    minWidth: 300,
                  },
               ]
              },
              // Presentation Tab Panel
              {
                title: editor.getLang('fuEtsyStrings.presentationTabTitle'),
                type: 'form',
                items: [
                  {
                    type: 'listbox',
                    name: 'sort',
                    label: editor.getLang('fuEtsyStrings.sortingLabel'),
                    value: sort,
                    'values': [						           
                      {text: editor.getLang('fuEtsyStrings.useSettingsDefault') + ' (' + editor.getLang('fuEtsyStrings.sortingDefault') + ')', value: ''},
                      {text: editor.getLang('fuEtsyStrings.sortingCreatedDesc'), value: 'CreatedDesc'},
                      {text: editor.getLang('fuEtsyStrings.sortingCreatedAsc'), value: 'CreatedAsc'},
                      {text: editor.getLang('fuEtsyStrings.sortingPriceHighest'), value: 'PriceHighest'},
                      {text: editor.getLang('fuEtsyStrings.sortingPriceLowest'), value: 'PriceLowest'},
                      {text: editor.getLang('fuEtsyStrings.sortingScore'), value: 'Score'},
                      {text: editor.getLang('fuEtsyStrings.sortingRandom'), value: 'Random'}
                    ],
                    tooltip: editor.getLang('fuEtsyStrings.sortingToolTip')
                  },              
                  {
                    type: 'textbox',
                    subtype: 'number',
                    name: 'picwidth',
                    label: editor.getLang('fuEtsyStrings.picWidthLabel'),
                    value: picwidth,
                    placeholder: fuEtsyScriptShortcode.picWidthList,
                    tooltip: editor.getLang('fuEtsyStrings.blankUseDefaults'),
                    maxWidth: 100,
                    maxLength: 4,
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
                            {text: editor.getLang('extrastrings.slideShowCategoryGroups'), value: 'CategoryGroups'},
                          ],
                  },	
                  {
                    type: 'textbox',
                    subtype: 'number',
                    name: 'slides',
                    label: editor.getLang('fuEtsyStrings.slidesLabel'),
                    value: slides,
                    placeholder: fuEtsyScriptShortcode.defNumSlides,
                    tooltip: editor.getLang('fuEtsyStrings.blankUseDefaults'),
                    maxWidth: 100,
                    maxLength: 4,
                  },
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
                        placeholder: fuEtsyScriptShortcode.defColumns,
                        tooltip: editor.getLang('fuEtsyStrings.blankUseDefaults'),
                        maxWidth: 100,
                        maxLength: 2,
                      },	
                      {
                        type: 'textbox',
                        subtype: 'number',
                        name: 'rows',
                        value: rows,
                        placeholder: fuEtsyScriptShortcode.defRows,
                        tooltip: editor.getLang('fuEtsyStrings.blankUseDefaults'),
                        maxWidth: 100,
                        maxLength: 2,
                      },	
                    ]
                  },	
                ]
              },
            ]
          }
        ],
        onsubmit: function( e ) {

          //check if code valid
          var validationError = validateForm(e.data);    
          if (validationError.length > 0) {
            //if code invalid, display error message and keep text editor window open
            tinyMCE.activeEditor.windowManager.alert(validationError);
            return false;
          }

          var shortcode_str = '[' + sh_tag;
          // check for query
          if (typeof e.data.query != 'undefined' && e.data.query.length)
            shortcode_str += ' query="' + addHtmlSpecialChars(e.data.query) + '"';
          // check for seller
          if (typeof e.data.seller != 'undefined' && e.data.seller.length)
            shortcode_str += ' seller="' + addHtmlSpecialChars(e.data.seller) + '"';
          // check for featured flag
          if (typeof e.data.featured != 'undefined' && e.data.featured)
            shortcode_str += ' featured="true"';
          // add sort
          if (typeof e.data.sort != 'undefined' && e.data.sort.length)
            shortcode_str += ' sort="'+e.data.sort+'"';
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
          // check for picwidth
          if (typeof e.data.picwidth != 'undefined' && e.data.picwidth.length)
            shortcode_str += ' picwidth="' + e.data.picwidth + '"';

          //add panel content
          shortcode_str += ']' + e.data.content + '[/' + sh_tag + ']';
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
				var content = e.target.attributes['data-sh-content'].value;
				data = window.decodeURIComponent(data);
				content = window.decodeURIComponent(content);

				editor.execCommand('etsy_shop_listings_popup','',{
					query : getAttr(data,'query'),
					seller : getAttr(data,'seller'),
          featured : getAttr(data, 'featured'),
					sort : getAttr(data,'sort'),
					rows : getAttr(data,'rows'),
					columns : getAttr(data,'columns'),
					slideshow : getAttr(data,'slideshow'),
					slides : getAttr(data,'slides'),
					picwidth : getAttr(data,'picwidth'),
					content: content
				});
			}
		});
	});
})();
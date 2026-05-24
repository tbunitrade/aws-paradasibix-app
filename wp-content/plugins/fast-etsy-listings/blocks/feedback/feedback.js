( function( wp ) {
	var el = wp.element.createElement;
	var __ = wp.i18n.__;
  var useEffect = wp.element.useEffect;
  var useCallback = wp.element.useCallback;
  var useRef = wp.element.useRef;
  var xhr = [];

	/**
	 * Import stuff for the Inspector Controls 
	 */
	var InspectorControls = wp.blockEditor.InspectorControls;
	var CheckboxControl = wp.components.CheckboxControl;
  var PanelBody = wp.components.PanelBody;
  var SelectControl = wp.components.SelectControl;
  var TextControl = wp.components.TextControl;
  
	/**
	 * Every block starts by registering a new block type definition.
	 */
	wp.blocks.registerBlockType( 'fast-etsy-listings/feedback', {

		/**
		 * The edit function describes the structure of your block in the context of the editor.
		 * This represents what the editor will render when the block is used.
		 *
		 * @param {Object} [props] Properties passed from the editor.
		 * @return {Element}       Element to render.
		 */
		edit: function( props ) {
      const lazyEffectDebounceTime = 500;
      const etsyPlaceholderImgSrc = fuEtsyScriptBlock.pluginsUrl + '/blocks/images/etsyfeedbackpanel.jpg'
      const etsyPlaceholderImg = '<img src="' + etsyPlaceholderImgSrc + '" width="700" height="260">';
			var attributes = props.attributes;
			
			/* Event handlers */
      function onAccountInfoCheckChange(value) {
        props.setAttributes( {accountinfo: (value ? 1 : 0)} );
      }
      function onCommentsCheckChange(value) {
        props.setAttributes( {comments: (value ? 1 : 0)} );
        if (value == 0) props.setAttributes( {commentuserdate: 0} );
      }

      /* useEffect AJAX call to get preview. useCallback for debounce. useRef's to maintain context. */
      if (!(props.clientId in fuSlideIndices)) fuSlideIndices[props.clientId] = Object.keys(fuSlideIndices).length
      const refAttributes = useRef(attributes);
      const refClientId = useRef(props.clientId);

      useEffect(() => {
        if ((props.attributes.seller != null && props.attributes.seller != '') ||
            fuEtsyScriptBlock.defSeller != '')
        {
          refAttributes.current = attributes;
          refClientId.current = props.clientId;
          loadPreview(attributes);
        }
        else
        {
          // No preview to load, show placeholder.
          jQuery("#fu_etsy_"+refClientId.current).html(etsyPlaceholderImg);
        }
      }, [attributes]);
    
      const loadPreview = useCallback(
        fuDebounce(attr => {
          if(xhr[refClientId.current] && xhr[refClientId.current].readyState != 4){
            xhr[refClientId.current].abort();
          }
          xhr[refClientId.current] = jQuery.ajax({
            type : "post",
            url : fuScriptBlock.adminAjaxUrl,
            data : {
                action : "fu_etsy_load_search",
                action : "fu_etsy_load_feedback",
                fu_etsy_seller : refAttributes.current.seller ?? 1,
                fu_etsy_accinfo : refAttributes.current.accountinfo ?? 1,
                fu_etsy_comments : refAttributes.current.comments ?? 1,
                fu_cols : refAttributes.current.columns ?? '',
                fu_rows : refAttributes.current.rows ?? '',
                fu_slideshow : refAttributes.current.slideshow ?? '',
                fu_slides : refAttributes.current.slides ?? '',
                fu_slideId : fuSlideIndices[refClientId.current],
                fu_etsy_customId : refAttributes.current.customid ,
            },
            beforeSend : function() {
              fuStopSlideShow(fuSlideIndices[refClientId.current]);
              jQuery("#fu_etsy_"+refClientId.current).html( 
                etsyPlaceholderImg +
                '<div class="fu_etsy_loadingtextoverlay">' + __( 'Loading...', 'fast-etsy-listings' ) + '</div>'
              );
            },
            success : function( response ) {
              jQuery("#fu_etsy_"+refClientId.current).html(response);
              console.log("Fast Etsy Listings: deferred loading success");
            },
            error: function(e) {
              jQuery("#fu_etsy_"+refClientId.current).html("Fast Etsy Listings: deferred loading error: " + e );
              console.log("Fast Etsy Listings: deferred loading error: " + e);
            }
          });
        }, lazyEffectDebounceTime),
        []
      );
      
			/* Return the edit content */
			return [
					!! props.isSelected && el(InspectorControls, { key: 'inspector' },
						el( 'div', { className: 'components-block-description' }),
						
						el(PanelBody, 
								{ 
									title: __( 'Search Criteria', 'fast-etsy-listings' ),
									initialOpen: true
								},
								el(TextControl,
									{
										type: 'text',
										label: __( 'Etsy Shop Name', 'fast-etsy-listings' ),
										placeholder: fuEtsyScriptBlock.defSeller,
										value: props.attributes.seller,
										onChange: function( value ) {
										    props.setAttributes( { seller: value } );
										},
                    help: __( 'Etsy Shop to show feedback for', 'fast-etsy-listings' ),
									}
								),
							),												
						el(PanelBody, 
							{ 
								title: __( 'Presentation', 'fast-etsy-listings' ),
								initialOpen: false 
							},
              el(CheckboxControl,
								{
									label: __( 'Show Account Info', 'fast-etsy-listings' ),
									checked: props.attributes.accountinfo != null ? props.attributes.accountinfo : 1,
									onChange: onAccountInfoCheckChange.bind(1),
                  className: 'fu_etsy_checkbox_editor',
								}
							),	
              el(CheckboxControl,
								{
									label: __( 'Show Review Comments', 'fast-etsy-listings' ),
									checked: props.attributes.comments != null ? props.attributes.comments : 1,
									onChange: onCommentsCheckChange.bind(1),
                  className: 'fu_etsy_checkbox_editor',
								}
							),	
						),
            el(PanelBody, 
              { 
                title: __( 'Review Comments', 'fast-etsy-listings' ),
                initialOpen: false 
              },
              el(SelectControl,
                {
                  type: 'text',
                  label: __( 'Slideshow/Pagination Style', 'fast-etsy-listings' ),
                  value: props.attributes.slideshow,
                  onChange: function ( value ) {
                    props.setAttributes( { slideshow: value } );
                  },
                  options: [						           
                      {label: __( 'Settings Default', 'fast-etsy-listings'), value: ''},
                      {label: __( 'None', 'fast-etsy-listings'), value: 'None'},
                      {label: __( 'Manual', 'fast-etsy-listings'), value: 'Manual'},
                      {label: __( 'Auto', 'fast-etsy-listings'), value: 'Auto'},
                      {label: __( '\'Load More\' button', 'fast-etsy-listings'), value: 'LoadMore'},
                    ],
                }
              ),			
              el(TextControl,
                {
                  type: 'number',
                  label: __( 'Number of Slides/Pages', 'fast-etsy-listings' ),
                  placeholder: fuEtsyScriptBlock.defNumSlides,
                  value: props.attributes.slides,
                  onChange: function ( value ) {
                    props.setAttributes( { slides: value } );
                  },
                }
              ),
              el(TextControl,
                {
                  type: 'number',
                  label: __( 'Result Columns', 'fast-etsy-listings' ),
                  placeholder: fuEtsyScriptBlock.defColumns,
                  value: props.attributes.columns,
                  onChange: function ( value ) {
                    props.setAttributes( { columns: value } );
                  },
                }
              ),									
              el(TextControl,
                {
                  type: 'number',
                  label: __( 'Results Rows', 'fast-etsy-listings' ),
                  placeholder: fuEtsyScriptBlock.defRows,
                  value: props.attributes.rows,
                  onChange: function ( value ) {
                    props.setAttributes( { rows: value } );
                  },
                }
              ),
            ),              
					),		

          // Placeholder element to be replaced with preview.
          el( 'div', 
            { key: 'fuSearchBlockStub', id: "fu_etsy_"+refClientId.current, className: 'fu_etsy_disablelinks' },
            el( 'img', 
              { key: 'fuSearchBlockStubImg', src: etsyPlaceholderImgSrc, width: 701, height: 126 },
            )
          )
        ];
		},

		/**
		 * The save function defines the way in which the different attributes should be combined
		 * into the final markup, which is then serialized by Gutenberg into `post_content`.
		 *
		 * @return {Element}       Element to render.
		 */
		save: function( props ) {
			// Rendering in PHP.
			return null;
		}
	} );
} )(
	window.wp
);

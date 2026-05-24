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
	var PlainText = wp.blockEditor.PlainText;
	var PanelBody = wp.components.PanelBody;
	var SelectControl = wp.components.SelectControl;
	var TextControl = wp.components.TextControl;
	var CheckboxControl = wp.components.CheckboxControl;
	
	/**
	 * Every block starts by registering a new block type definition.
	 * @see https://wordpress.org/gutenberg/handbook/block-api/
	 */
	wp.blocks.registerBlockType( 'fast-etsy-listings/shoplistings', {

		/**
		 * The edit function describes the structure of your block in the context of the editor.
		 * This represents what the editor will render when the block is used.
		 *
		 * @param {Object} [props] Properties passed from the editor.
		 * @return {Element}       Element to render.
		 */
		edit: function( props ) {
      const lazyEffectDebounceTime = 500;
      const placeholderImgSrc = fuEtsyScriptBlock.pluginsUrl + '/blocks/images/etsysearchpanel.jpg'
      const placeholderImg = '<img src="' + placeholderImgSrc + '" width="700" height="162">';
			var attributes = props.attributes;
			
			/* Event handlers */

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
          jQuery("#fu_etsy_"+refClientId.current).html(placeholderImg);
        }
      }, [attributes]);
    
      const loadPreview = useCallback(
        fuDebounce(attr => {
          if(xhr[refClientId.current] && xhr[refClientId.current].readyState != 4){
            xhr[refClientId.current].abort();
          }
          xhr[refClientId.current] = jQuery.ajax({
            type : "post",
            url : fuEtsyScriptBlock.adminAjaxUrl,
            data : {
                action : "fu_etsy_load_search",
                fu_etsy_widget : 0,
                fu_etsy_title : refAttributes.current.title ?? '',
                fu_etsy_query : refAttributes.current.query ?? '',
                fu_etsy_sort : refAttributes.current.sort ?? '',
                fu_etsy_sellers : refAttributes.current.seller ?? fuEtsyScriptBlock.defSeller,
                fu_etsy_featured : refAttributes.current.featured ? "1" : "0",
                fu_etsy_picwidth : refAttributes.current.picwidth,
                fu_cols : refAttributes.current.columns ?? '',
                fu_rows : refAttributes.current.rows ?? '',
                fu_slideshow : refAttributes.current.slideshow ?? '',
                fu_slides : refAttributes.current.slides ?? '',
                fu_slideId : fuSlideIndices[refClientId.current],
            },
            beforeSend : function() {
              fuStopSlideShow(fuSlideIndices[refClientId.current]);
              jQuery("#fu_etsy_"+refClientId.current).html( 
                placeholderImg +
                '<div class="fu_etsy_loadingtextoverlay">' + __( 'Loading...', 'fast-etsy-listings' ) + '</div>'
              );
            },
            success : function( response ) {
              var respHtmlObject = jQuery(response);
              respHtmlObject.find(".fu_etsy_results_title").remove();
              jQuery("#fu_etsy_"+refClientId.current).html(respHtmlObject);
              jQuery("#fu_etsy_"+refClientId.current).find(".fu_etsy_results").addClass("fu_etsy_disablelinks"); // disable links to avoid accidently clicks
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
									onChange: function ( value ) {
										props.setAttributes( { seller: value } );
									},
                  help: __( 'Name of Etsy shop to show Listings from. Leave blank for default from settings; \'all\' for all; \'author\' for post author', 'fast-etsy-listings' ),
								}
							),
							el(CheckboxControl,
                {
									label: __( 'Featured listings only', 'fast-etsy-listings' ),
									checked: props.attributes.featured,
                  onChange: function ( value ) {
                    props.setAttributes( { featured: value } );
                  },
                  help:  __( 'Show featured listings only. Overrides any search query.', 'fast-etsy-listings' ),
                }
              ),
							el(TextControl,
                {
                  type: 'text',
                  label: __( 'Search Query', 'fast-etsy-listings' ),
                  value: props.attributes.query,
                  onChange: function ( value ) {
                    props.setAttributes( { query: value } );
                  },
                  help: __( 'Etsy search query keywords', 'fast-etsy-listings' ),
                }
              ),
						),
			  		el(PanelBody, 
						{ 
							title: __( 'Presentation', 'fast-etsy-listings' ),
							initialOpen: false 
						},
            el(SelectControl,
              {
                type: 'text',
                label: __( 'Sorting', 'fast-etsy-listings' ),
                value: props.attributes.sort,
                onChange: function ( value ) {
                  props.setAttributes( { sort: value } );
                },
                options: [						           
                  {label: __( 'Settings Default', 'fast-etsy-listings' ), value: ''},
                  {label: __( 'Created most recent', 'fast-etsy-listings' ), value: 'CreatedDesc'},
                  {label: __( 'Created earliest', 'fast-etsy-listings' ), value: 'CreatedAsc'},
                  {label: __( 'Highest Price', 'fast-etsy-listings' ), value: 'PriceHighest'},
                  {label: __( 'Lowest Price', 'fast-etsy-listings' ), value: 'PriceLowest'},
                  {label: __( 'Score', 'fast-etsy-listings' ), value: 'Score'},
                  {label: __( 'Randomized', 'fast-etsy-listings' ), value: 'Random'}
                ],
                help: __( 'Select what sort order results should be presented in', 'fast-etsy-listings' ),
              }
            ),
            el(TextControl,
							{
								type: 'number',
								label: __( 'Picture Width', 'fast-etsy-listings' ),
                placeholder: fuEtsyScriptBlock.picWidthList,
								value: props.attributes.picwidth,
								onChange: function ( value ) {
									props.setAttributes( { picwidth: value } );
								},
							}
						),
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
										{label: __( 'List Grouped by Category', 'fast-etsy-listings'), value: 'CategoryGroups'},
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

          // Element within main editor pane
					el( 'div', 
						{ key: 'fuSearchBlockContainer', className: 'fu_etsy_results_container' },
						el( 'div', 
							{ key: 'fuSearchBlockTitle', className: 'fu_etsy_results_title' },
							el( 'a',
								{ key: 'fuSearchBlockTitleControl', href: '#' },										
								el( PlainText, 
									{
										className: 'fu_etsy_results_title_editor',
										placeholder: __( 'Results Title' ),
										value: props.attributes.title,
										onChange: function( value ) {
										    props.setAttributes( { title: value } );
										  }										  
									}
								)
							)			
						),
            // Placeholder element to be replaced with preview.
            el( 'div', 
              { key: 'fuSearchBlockStub', id: "fu_etsy_"+refClientId.current },
              el( 'img', 
                { key: 'fuSearchBlockStubImg', src: placeholderImgSrc, width: 700, height: 162 },
              )
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

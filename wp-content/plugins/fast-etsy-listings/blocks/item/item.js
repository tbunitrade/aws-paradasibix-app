(  function ( blocks, i18n, element, blockEditor ) {
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
	var PanelBody = wp.components.PanelBody;
	var TextControl = wp.components.TextControl;
	
	/**
	 * Every block starts by registering a new block type definition.
	 */
	wp.blocks.registerBlockType( 'fast-etsy-listings/item', {
	
		/**
		 * The edit function describes the structure of your block in the context of the editor.
		 * This represents what the editor will render when the block is used.
		 *
		 * @param {Object} [props] Properties passed from the editor.
		 * @return {Element}       Element to render.
		 */
		edit: function( props ) {
      const lazyEffectDebounceTime = 500;
      const placeholderImgSrc = fuEtsyScriptBlock.pluginsUrl + '/blocks/images/etsylistingpanel.jpg'
      const placeholderImg = '<img src="' + placeholderImgSrc + '" width="700" height="138">';
			var attributes = props.attributes;
			
			/* Event handlers */

      /* useEffect AJAX call to get preview. useCallback for debounce. useRef's to maintain context. */
      if (!(props.clientId in fuSlideIndices)) fuSlideIndices[props.clientId] = Object.keys(fuSlideIndices).length
      const refAttributes = useRef(attributes);
      const refClientId = useRef(props.clientId);

      useEffect(() => {
        if ((props.attributes.item != null && props.attributes.item != ''))
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
                action : "fu_etsy_load_getitem",
                fu_etsy_item : refAttributes.current.item,
                fu_etsy_picwidth : refAttributes.current.picwidth,
            },
            beforeSend : function() {
              fuStopSlideShow(fuSlideIndices[refClientId.current]);
              jQuery("#fu_etsy_"+refClientId.current).html( 
                placeholderImg +
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
										label: __( 'Listing ID', 'fast-etsy-listings' ),
										value: props.attributes.item,
										onChange: function( value ) {
										    props.setAttributes( { item: value } );
										},
                    help: __( 'Enter Etsy Listing ID', 'fast-etsy-listings' ),
									}
								),
							),												
						el(PanelBody, 
							{ 
								title: __( 'Presentation', 'fast-etsy-listings' ),
								initialOpen: false 
							},
							el(TextControl,
								{
									type: 'number',
									label: __( 'Picture Width', 'fast-etsy-listings' ),
                  placeholder: fuEtsyScriptBlock.picWidthItem,
									value: props.attributes.picwidth,
									onChange: function ( value ) {
										props.setAttributes( { picwidth: value } );
									},
								}
							)
						),
					),		

          // Placeholder element to be replaced with preview.
          el( 'div', 
            { key: 'fuSearchBlockStub', id: "fu_etsy_"+refClientId.current, className: 'fu_etsy_disablelinks' },
            el( 'img', 
              { key: 'fuSearchBlockStubImg', src: placeholderImgSrc, width: 700, height: 138 },
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

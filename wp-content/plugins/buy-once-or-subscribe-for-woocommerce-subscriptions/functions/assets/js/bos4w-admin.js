/**
 * Admin JS
 *
 * @package Buy Once or Subscribe for WooCommerce Subscriptions
 * @since   1.0.0
 */

jQuery(
    function ($) {
        let bos4w_sub_wrap = $('#bos4w_data');
        let bos4w_list_wrapper = bos4w_sub_wrap.find('.subscriptions_type');
        let bos4w_sub_type = bos4w_sub_wrap.find('.subscription_type');
        let bos4w_sub_count = bos4w_sub_wrap.find('.subscription_type').length;
        let bos4w_no_sub = bos4w_sub_wrap.find('.bos4w-no-subscriptions-added');
        let bos4w_notice = $('.bos4w-notice-wrap');
        let bos4w_upsell_notice = $('.bos4w-notice-upsell-wrap');
        let product_select_option = $('select#product-type');

        $.fn.init_the_help_tips = function () {
            $(this).find('.woocommerce-help-tip').tipTip(
                {
                    'attribute': 'data-tip',
                    'fadeIn': 50,
                    'fadeOut': 50,
                    'delay': 200
                }
            );
        };

        show_hide_tab(product_select_option.val());

        product_select_option.on(
            'change', function () {
                var select_val = $(this).val();

                show_hide_tab(select_val);
            }
        );

        bos4w_sub_wrap.on(
            'click',
            '.add_new_subscription_button',
            function (e) {
                e.preventDefault();
                bos4w_sub_count++;

                $.ajax(
                    {
                        url: bos4w_admin.ajax_url,
                        type: 'post',
                        data: {
                            action: 'wpr_add_sub',
                            nonce: bos4w_admin.bos4w_nonce,
                            post_id: bos4w_admin.post_id,
                            list: bos4w_sub_count
                        },
                        complete: function () {

                        },
                        success: function (response) {
                            bos4w_no_sub.hide();
                            bos4w_list_wrapper.append(response.data.html);
                            togglePriceDiscountFields();
                            bos4w_list_wrapper.find('.subscription_type').last().init_the_help_tips();
                            $(document.body).trigger('wc-enhanced-select-init');
                        },
                        error: function (XMLHttpRequest, textStatus, errorThrown) {
                            if (XMLHttpRequest.status == 0) {
                                console.error('Network connectivity error.');
                            } else {
                                console.error(XMLHttpRequest.responseText);
                            }
                        }
                    }
                );
            }
        );

        bos4w_sub_wrap.on(
            'click',
            '.this-remove',
            function (e) {
                e.preventDefault();
                $(this).closest('.subscription_type').remove();
            }
        );

        bos4w_sub_wrap.sortable(
            {
                items: '.subscription_type',
                cursor: 'move',
                axis: 'y',
                handle: 'span.drag-drop',
                scrollSensitivity: 40,
                forcePlaceholderSize: true,
                helper: 'clone',
                opacity: 0.65,
                placeholder: 'wc-metabox-sortable-placeholder',
                start: function (event, ui) {
                    ui.item.css('background-color', '#f6f6f6');
                },
                stop: function (event, ui) {
                    ui.item.removeAttr('style');
                    update_ordering();
                }
            }
        );

        function update_ordering() {
            bos4w_sub_type.each(
                function (index, el) {
                    $('.position', el).val(parseInt($(el).index('.subscriptions_type .subscription_type'), 10));
                    $(el).attr('data-pos', index);
                }
            );
        }

        function show_hide_tab(select_val) {
            if ('grouped' === select_val || 'external' === select_val || 'subscription' === select_val || 'variable-subscription' === select_val) {
                $('.hide_if_subscription').hide();
                $('.hide_if_variable-subscription').hide();
            }
        }

        // Dismiss notice.
        bos4w_notice.on(
            'click',
            'button.notice-dismiss',
            function () {
                bos4w_dismiss_notice('notice');
            }
        );

        // Register dismiss!
        function bos4w_dismiss_notice(type) {
            $.ajax(
                {
                    url: bos4w_admin.ajax_url,
                    type: 'post',
                    data: {
                        action: 'bos4w_dismiss_notice',
                        nonce: bos4w_admin.bos4w_nonce,
                        type: type,
                    },
                    complete: function () {

                    },
                    success: function (state) {
                        location.reload();
                    },
                    error: function (XMLHttpRequest, textStatus, errorThrown) {
                        if (XMLHttpRequest.status == 0) {
                            console.error('Network connectivity error.');
                        } else {
                            console.error(XMLHttpRequest.responseText);
                        }
                    }
                }
            );
        }

        bos4w_upsell_notice.on(
            'click',
            'button.notice-dismiss',
            function () {
                bos4w_dismiss_notice('upsell');
            }
        );

        function togglePriceDiscountFields() {
            if ($('#_bos4w_use_fixed_price').is(':checked')) {
                $('.subscription_price_discount').closest('.form-field').hide();
                $('.subscription_price').closest('.form-field').show();
            } else {
                $('.subscription_price_discount').closest('.form-field').show();
                $('.subscription_price').closest('.form-field').hide();
            }
        }

        $('#_bos4w_use_fixed_price').change(togglePriceDiscountFields);
        togglePriceDiscountFields();

        // Variation specific code
        $(document).on('click', '.add_new_variation_subscription_button', function (e) {
            e.preventDefault();
            let variation = $(this).closest('.woocommerce_variation');
            let variation_id = variation.find('.variable_post_id').val();
            update_ordering_variation(variation);
            let last_index = Math.max(...variation.find('.variable_subscriptions_type_' + variation_id + ' .subscription_type').map(function() {
                return parseInt($(this).attr('data-pos'), 10) || 0;
            }).get());

            let new_index = last_index + 1;

            $.ajax({
                url: bos4w_admin.ajax_url,
                type: 'post',
                data: {
                    action: 'wpr_add_variable_sub',
                    nonce: bos4w_admin.bos4w_nonce,
                    post_id: variation_id,
                    list: new_index
                },
                success: function (response) {
                    variation.find('.variable_subscriptions_type_' + variation_id).append(response.data.html);
                    variation.find('.variable_subscriptions_type_' + variation_id).last().init_the_help_tips();
                    $(document.body).trigger('wc-enhanced-select-init');
                    togglePriceDiscountFieldsForVariation(variation_id);
                },
                error: function (XMLHttpRequest, textStatus, errorThrown) {
                    if (XMLHttpRequest.status == 0) {
                        console.error('Network connectivity error.');
                    } else {
                        console.error(XMLHttpRequest.responseText);
                    }
                }
            });
        });

        $(document).on('click', '.this-remove', function (e) {
            e.preventDefault();
            let variation = $(this).closest('.woocommerce_variation');
            let variation_id = variation.find('.variable_post_id').val();
            let subscriptionTypeContainer = $(this).closest('.variable_subscriptions_type_' + variation_id);

            // Get the index of the subscription type being removed
            let removedIndex = $(this).closest('.subscription_type').index();

            $(this).closest('.subscription_type').remove();

            // Re-index the remaining subscription plan input fields
            subscriptionTypeContainer.find('.subscription_type').each(function(index) {
                // Only re-index if the index is greater than or equal to the removed index
                if (index >= removedIndex) {
                    $(this).find('input, select').each(function() {
                        let originalName = $(this).attr('name');
                        let newName = originalName.replace(/\[\d+\]/g, '[' + index + ']');
                        $(this).attr('name', newName);
                    });
                }
            });

            variation.addClass('variation-needs-update');
            $('button.cancel-variation-changes, button.save-variation-changes').prop('disabled', false);

            $('#variable_product_options').trigger('woocommerce_variations_input_changed');

        });

        $(document).on('sortstart', '.variable_subscriptions_type_', function (event, ui) {
            ui.item.css('background-color', '#f6f6f6');
        });

        $(document).on('sortstop', '.variable_subscriptions_type_', function (event, ui) {
            ui.item.removeAttr('style');
            let variation = $(this).closest('.woocommerce_variation');
            update_ordering_variation(variation);
        });

        function update_ordering_variation(variation) {
            variation.find('.variable_subscriptions_type_').each(function (index, el) {
                $('.position', el).val(parseInt($(el).index('.subscriptions_type .subscription_type'), 10));
                $(el).attr('data-pos', index);
            });
        }

        function initVariations() {
            // Log the number of variations found
            const variations = $('#variable_product_options').find('.woocommerce_variation');

            variations.each(function () {
                let variation = $(this);
                let variation_id = variation.find('.variable_post_id').val();

                // Check if subscriptions_type exists within the variation
                let subscriptionsTypeContainers = variation.find('.options_group.variable_subscriptions_type_' + variation_id);

                if (subscriptionsTypeContainers.length === 0) {
                    return; // Skip to the next variation if no subscriptions_type containers are found
                }

                subscriptionsTypeContainers.each(function () {
                    let subscriptionsType = $(this);

                    subscriptionsType.sortable({
                        items: '.subscription_type',
                        cursor: 'move',
                        axis: 'y',
                        handle: 'span.drag-drop',
                        scrollSensitivity: 40,
                        forcePlaceholderSize: true,
                        helper: 'clone',
                        opacity: 0.65,
                        placeholder: 'wc-metabox-sortable-placeholder',
                        start: function (event, ui) {
                            console.log("Sortable start");
                            ui.item.css("background-color", "#f6f6f6");
                        },
                        stop: function (event, ui) {
                            console.log("Sortable stop");
                            ui.item.removeAttr("style");
                            var variation = ui.item.closest('.woocommerce_variation');
                            console.log('Calling update_ordering_variation for variation:', variation);
                            update_ordering_variation(variation);
                        }
                    });
                });
            });

            // Call togglePriceDiscountFields for each variation
            const variationsForToggle = $('.woocommerce_variations').find('.woocommerce_variation');

            variationsForToggle.each(function () {
                let variation_id = $(this).find('.variable_post_id').val();
                togglePriceDiscountFieldsForVariation(variation_id);
            });
        }

        function update_ordering_variation(variation) {
            const subscriptionsTypeContainers = variation.find('div[class*="variable_subscriptions_type_"]');
            subscriptionsTypeContainers.each(function () {
                const subscriptionTypes = $(this).find('.subscription_type');

                subscriptionTypes.each(function (index, el) {
                    $(el).find('.position').val(index);
                    $(el).attr('data-pos', index);
                });
            });

            variation.addClass('variation-needs-update');
            $('button.cancel-variation-changes, button.save-variation-changes').prop('disabled', false);

            $('#variable_product_options').trigger(
                'woocommerce_variations_input_changed'
            );
        }

        function togglePriceDiscountFieldsForVariation(variation_id) {
            let $useFixedPriceCheckbox = $('#bos4w_use_variation_fixed_price_' + variation_id);
            let $variationBOS = $('.variable_subscriptions_type_' + variation_id);

            if ($useFixedPriceCheckbox.is(':checked')) {
                $variationBOS.find('._subscription_discount_' + variation_id + '_field').closest('.form-field').hide();
                $variationBOS.find('._subscription_price_' + variation_id + '_field').closest('.form-field').show();
            } else {
                $variationBOS.find('._subscription_discount_' + variation_id + '_field').closest('.form-field').show();
                $variationBOS.find('._subscription_price_' + variation_id + '_field').closest('.form-field').hide();
            }
        }

        $(document).on('change', 'input[id^="bos4w_use_variation_fixed_price_"]', function () {
            let variation_id = $(this).attr('id').replace('bos4w_use_variation_fixed_price_', '');
            togglePriceDiscountFieldsForVariation(variation_id);
        });

        // Defer the initialization until WooCommerce has fully loaded the variations
        $(document.body).on('woocommerce_variations_loaded', function () {
            setTimeout(initVariations, 500);
        });

        // Also call initVariations on document ready to catch any variations already present
        $(document).ready(function () {
            setTimeout(initVariations, 500);
        });

        $( 'li.variations_tab a' ).on( 'click', function (event, variation) {
            const variations = $('#variable_product_options').find('.woocommerce_variation');

            variations.each(function () {
                let variation = $(this);
                let variation_id = variation.find('.variable_post_id').val();
                $('#bos4w_data_' + variation_id).show();
            });

        } );
    }
);

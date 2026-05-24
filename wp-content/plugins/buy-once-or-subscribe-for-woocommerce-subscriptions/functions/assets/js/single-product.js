/**
 * Single product JS
 *
 * @package Buy Once or Subscribe for WooCommerce Subscriptions
 * @since 1.0.0
 */

jQuery(
    function ($) {
        let bos4w_plan_select = $('.bos4w-buy-type');
        let bos4w_button_text = $('.single_add_to_cart_button');
        let bos4w_variable_product = $('form.variations_form');
        let bos4w_display_dropdown_wrap = $('.bos4w-display-wrap');
        let bos4w_display_dropdown = $('#bos4w-dropdown-plan');
        let bos4w_select_options = $('.bos4w-select-options');
        let bos4w_block_white = {message: null, overlayCSS: {background: '#fff', opacity: 0.6}};

        if (wpr_bos4w_js.bos4w_is_product) {
            // bos4w_button_text.html( wpr_bos4w_js.bos4w_buy_now );
        }

        if (bos4w_variable_product.length > 0) {
            bos4w_display_dropdown_wrap.hide();
        }

        if ($('form.cart').hasClass('bundle_form')) {
            jQuery(document.body).on(
                'woocommerce-bundled-item-totals-changed',
                function (event, bundle) {
                    setTimeout(function () {
                        let bundle_price_html = bundle.get_bundle().get_price_html(),
                            bundle_price_inner_html = $(bundle_price_html).html();

                        let the_price = bundle_price_inner_html.match(/\d+/g);
                        let bundle_price = the_price[0] + '.' + the_price[1];

                        if (the_price[2] && the_price[3]) {
                            bundle_price = the_price[2] + '.' + the_price[3];
                        }

                        bundle_price = parseFloat(bundle_price);

                        bos4w_display_dropdown.find('option').each(function (index, element) {
                            let text = element.text;
                            let current_price = element.dataset.price;
                            let discount_amount = element.dataset.discount;
                            let data_type = element.dataset.type;
                            let bmult = 0;

                            if ('fixed_price' === data_type) {
                                bmult = discount_amount;
                            } else {
                                let dec = (discount_amount / 100).toFixed(2);
                                bmult = bundle_price * dec;
                            }

                            let new_price = bundle_price - bmult;

                            element.dataset.price = new_price.toFixed(2);
                            new_price = new_price.toFixed(2);

                            if (',' === wpr_bos4w_js.decimal_separator) {
                                current_price = current_price.replace('.', ',');
                                new_price = new_price.replace('.', ',');
                            }

                            text = text.replace(addZeroes(current_price), addZeroes(new_price));
                            $(this).text(text);
                        });

                        $('#bos4w-selected-price').val($("#bos4w-dropdown-plan option:selected").attr("data-price"));

                    }, 500);
                }
            );
        } else if ($('form.cart').hasClass('composite_form')) {
            $('.composite_data').on('wc-composite-initializing', function (event, composite) {
                composite.actions.add_action('component_totals_changed', function () {

                    let composite_price_html = composite.composite_price_view.get_price_html(),
                        composite_price_inner_html = $(composite_price_html).html();

                    let the_price = composite_price_inner_html.match(/\d+/g);
                    let composite_price = the_price[0] + '.' + the_price[1];

                    if (the_price[2] && the_price[3]) {
                        composite_price = the_price[2] + '.' + the_price[3];
                    }

                    composite_price = parseFloat(composite_price);

                    bos4w_display_dropdown.find('option').each(function (index, element) {
                        let text = element.text;
                        let current_price = element.dataset.price;
                        let discount_amount = element.dataset.discount;
                        let data_type = element.dataset.type;
                        let mult = 0;

                        if ('fixed_price' === data_type) {
                            mult = discount_amount;
                        } else {
                            let dec = (discount_amount / 100).toFixed(2);
                            mult = composite_price * dec;
                        }

                        let new_price = composite_price - mult;

                        element.dataset.price = new_price.toFixed(2);
                        new_price = new_price.toFixed(2);

                        if (',' === wpr_bos4w_js.decimal_separator) {
                            current_price = current_price.replace('.', ',');
                            new_price = new_price.replace('.', ',');
                        }

                        text = text.replace(addZeroes(current_price), addZeroes(new_price));
                        $(this).text(text);
                    });

                    setTimeout(function () {
                        $('#bos4w-selected-price').val($("#bos4w-dropdown-plan option:selected").attr("data-price"));
                    }, 500);
                }, 51, this);
            });
        } else {
            $('.single_variation_wrap').on(
                'show_variation',
                function (event, variation) {
                    updateSubscriptionPlans(variation);
                }
            );
        }

        $(document).on('change', '[name^=bos4w_cart_item]', function () {
            let bos4w_cart_item = $(this).val();
            let bos4w_cart_item_discount = $(this).data('discount');
            let bos4w_cart_item_price = $(this).data('price');
            let bos4w_cart_item_type = $(this).data('type');
            let bos4w_cart_item_key = $(this).data('key');

            if (bos4w_cart_item) {

                $.ajax(
                    {
                        url: wpr_bos4w_js.ajax_url,
                        type: 'post',
                        data: {
                            action: 'bos4w_update_cart_item',
                            nonce: wpr_bos4w_js.nonce,
                            bos4w_cart_item: bos4w_cart_item,
                            bos4w_cart_item_discount: bos4w_cart_item_discount,
                            bos4w_cart_item_price: bos4w_cart_item_price,
                            bos4w_cart_item_type: bos4w_cart_item_type,
                            bos4w_cart_item_key: bos4w_cart_item_key
                        },
                        complete: function () {

                        },
                        success: function (response) {
                            if (response.success) {
                                $(document.body).trigger('wc_fragment_refresh');
                                $(document.body).trigger('updated_cart_totals');

                                if ($('form.woocommerce-cart-form').length > 0) {
                                    const updateButton = $('form.woocommerce-cart-form').find('[name="update_cart"]');

                                    updateButton.prop('disabled', false);
                                    updateButton.removeAttr('aria-disabled');
                                }
                            } else {
                                console.error('Error:', response.data);
                            }
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
        });

        bos4w_display_dropdown.on('change', function () {
            $('#bos4w-selected-price').val($(this).find(":selected").attr("data-price"));
        });

        bos4w_plan_select.on(
            'click',
            function () {
                let bos4w_select = $(this).val();
                if ('1' === bos4w_select) {
                    $('.bos4w-display-dropdown').show('slow');
                } else {
                    $('.bos4w-display-dropdown').hide('slow');
                }

                if (wpr_bos4w_js.bos4w_is_product) {
                    if ('1' === bos4w_select) {
                        bos4w_button_text.html(wpr_bos4w_js.bos4w_subscribe);
                    } else {
                        bos4w_button_text.html(wpr_bos4w_js.bos4w_buy_now);
                    }
                }
            }
        );

        function updateSubscriptionPlans(variation) {
            let variationTitle = variation.bos4w_subscription_title || wpr_bos4w_js.bos4w_display_text;
            let discountText = variation.bos4w_discount_text || '';

            if (discountText) {
                $('.bos-display-save-up-to').html(discountText);
            }

            // Update the display title
            $('.bos4w-display-plan-text').text(variationTitle);

            // Update the dropdown with the plans for the selected variation
            bos4w_display_dropdown.empty();

            let plans = variation.bos4w_discounted_price;

            if (plans) {
                bos4w_display_dropdown_wrap.show();
                if ( 1 === plans.length ) {
                    $('#bos4w-dropdown-plan').hide();
                    $('.bos4w-one-plan-only').show();
                } else {
                    $('#bos4w-dropdown-plan').show();
                    $('.bos4w-one-plan-only').hide();
                }

                $.each(plans, function (index, plan) {
                    let cleanDiscountedPrice = $(plan.discounted_price).text();
                    let bos_type = plan.subscription_price ? 'fixed_price' : 'percentage_price';
                    let display_discount = '';
                    if (plan.display_discount && plan.display_discount.trim() !== '') {
                        display_discount = ` (${plan.display_discount})`;
                    }

                    let optionText = htmlspecialchars_decode(plan.display);
                    let optionValue = `${plan.subscription_period_interval}_${plan.subscription_period}_${bos_type === 'fixed_price' ? plan.subscription_price : plan.subscription_discount}`;
                    let optionData = {
                        'value': optionValue,
                        'data-discount': bos_type === 'fixed_price' ? plan.subscription_price : plan.subscription_discount,
                        'data-price': plan.float_discounted,
                        'data-type': bos_type
                    };
                    bos4w_display_dropdown.append($('<option>', optionData).text(optionText));
                    $('.bos4w-one-plan-only').text(optionText);
                });
            } else {
                bos4w_display_dropdown_wrap.hide();
            }

            setTimeout(function () {
                $('#bos4w-selected-price').val($("#bos4w-dropdown-plan option:selected").attr("data-price"));
            }, 500);
        }

        function addZeroes(num) {
            let value, res;

            num.toString();

            if (',' === wpr_bos4w_js.decimal_separator) {
                res = num.split(",");
            } else {
                res = num.split(".");
            }

            value = Number(num);
            if (isNaN(Number(num))) {
                value = num;
            }

            if (res.length === 1 || res[1].length < 3) {
                if (undefined === res[1]) {
                    value = res[0] + '.00';
                } else {
                    value = res[0] + '.' + res[1];
                }

                value = Number(value).toFixed(2);
            }

            if (',' === wpr_bos4w_js.decimal_separator) {
                value = String(value).replace('.', ',');
            }

            return value;
        }

        function htmlspecialchars_decode(str) {
            return $('<div>').html(str).text();
        }
    }
);

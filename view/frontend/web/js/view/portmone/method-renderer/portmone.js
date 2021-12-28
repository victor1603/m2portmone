define(
    [
        'Magento_Payment/js/view/payment/cc-form',
        'jquery',
        'mage/url',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Payment/js/model/credit-card-validation/validator'
    ],
    function (Component, $, url) {
        'use strict';

        return Component.extend({
            redirectAfterPlaceOrder: false,
            defaults: {
                template: 'CodeCustom_Portmone/portmone/checkout'
            },
            getCode: function() {
                return 'portmone_payment';
            },
            isActive: function() {
                return true;
            },
            afterPlaceOrder: function () {
                $.post(url.build('portmone/checkout/payment'), {
                    'random_string': this._generateRandomString(30)
                }).done(function(data) {
                    if (!data.status) {
                        return
                    }

                    if (data.status && data.redirect) {
                        window.location = data.redirect;
                    }
                });
            },
            _generateRandomString: function(length) {
                if (!length) {
                    length = 10;
                }
                var text = '';
                var possible = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
                for (var i = 0; i < length; ++i) {
                    text += possible.charAt(Math.floor(Math.random() * possible.length));
                }
                return text;
            }
        });
    }
);

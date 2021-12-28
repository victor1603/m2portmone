define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'portmone_payment',
                component: 'CodeCustom_Portmone/js/view/portmone/method-renderer/portmone'
            }
        );

        return Component.extend({});
    }
);

/**
 * Copyright © 2018 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
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
                type: 'interkassa_payment',
                component: 'Magento_InterkassaPayment/js/view/payment/method-renderer/interkassa_payment'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);

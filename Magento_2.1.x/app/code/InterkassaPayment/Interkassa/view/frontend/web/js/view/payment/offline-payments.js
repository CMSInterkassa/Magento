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
                type: 'interkassa',
                component: 'InterkassaPayment_Interkassa/js/view/payment/method-renderer/interkassa-method'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);
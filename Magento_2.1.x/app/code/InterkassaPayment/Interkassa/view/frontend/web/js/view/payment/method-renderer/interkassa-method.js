define(
    [
        'ko',
        'Magento_Checkout/js/view/payment/default'
    ],
    function (ko, Component) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'InterkassaPayment_Interkassa/payment/interkassa'
            },
            /**
             * Get value of instruction field.
             * @returns {String}
             */
            getInstructions: function () {
                console.log("try to find instructions:"+this.item.method);
                return window.checkoutConfig.payment.instructions[this.item.method];
            }
        });
    }
);

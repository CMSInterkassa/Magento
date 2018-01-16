/**
 * Copyright © 2018 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/url-builder',
        'mage/url'
    ],
    function (
        $,
        Component,
        urlBuilder,
        url
    ){
        'use strict';

        return Component.extend({
            redirectAfterPlaceOrder: false,
            defaults: {
                template: 'Magento_InterkassaPayment/payment/form',
                transactionResult: ''
            },

            initObservable: function () {

                this._super()
                    .observe([
                        'transactionResult'
                    ]);
                return this;
            },

            getCode: function() {
                return 'interkassa_payment';
            },

            getData: function() {
                return {
                    'method': this.item.method,
                    'additional_data': null
                };
            },

            afterPlaceOrder: function () {
                console.log('Redirect Interkassa')
                //console.log(url.build('interkassa/checkout/index'))
                window.location.replace(url.build('interkassa/checkout/index'));
            }
        });
    }
);
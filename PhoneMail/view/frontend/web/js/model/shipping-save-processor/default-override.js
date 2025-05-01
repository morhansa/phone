/**
 * PhoneMail Module - Shipping Save Processor Override
 * Fix saving shipping information issues
 */
define([
    'jquery',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/resource-url-manager',
    'mage/storage',
    'Magento_Checkout/js/model/payment-service',
    'Magento_Checkout/js/model/payment/method-converter',
    'Magento_Checkout/js/model/error-processor',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/action/select-billing-address',
    'Magento_Checkout/js/model/shipping-save-processor/default'
], function (
    $,
    quote,
    resourceUrlManager,
    storage,
    paymentService,
    methodConverter,
    errorProcessor,
    fullScreenLoader,
    selectBillingAddressAction,
    originalProcessor
) {
    'use strict';
    
    return {
        saveShippingInformation: function () {
            console.log('PhoneMail: Override saveShippingInformation running');
            
            // Fix missing email in shipping address
            var shippingAddress = quote.shippingAddress();
            if (shippingAddress) {
                var telephone = shippingAddress.telephone;
                
                if (telephone && (!shippingAddress.email || shippingAddress.email.indexOf('example.com') > -1)) {
                    console.log('PhoneMail: Fixing missing email in shipping address');
                    
                    var cleanPhone = telephone.replace(/\D/g, '');
                    if (cleanPhone.length >= 6) {
                        var domain = window.location.hostname.replace('www.', '');
                        var generatedEmail = cleanPhone + '@' + domain;
                        
                        // Set email in quote
                        shippingAddress.email = generatedEmail;
                        quote.guestEmail = generatedEmail;
                        
                        console.log('PhoneMail: Generated email', generatedEmail);
                    }
                }
            }
            
            // Try to use original processor
            try {
                return originalProcessor.saveShippingInformation();
            } catch (e) {
                console.error('PhoneMail: Error in original saveShippingInformation', e);
                
                // If error occurs, implement our own version
                var payload;
                
                if (!quote.billingAddress() && quote.shippingAddress().canUseForBilling()) {
                    selectBillingAddressAction(quote.shippingAddress());
                }
                
                payload = {
                    addressInformation: {
                        shipping_address: quote.shippingAddress(),
                        billing_address: quote.billingAddress(),
                        shipping_method_code: quote.shippingMethod().method_code,
                        shipping_carrier_code: quote.shippingMethod().carrier_code
                    }
                };
                
                fullScreenLoader.startLoader();
                
                return storage.post(
                    resourceUrlManager.getUrlForSetShippingInformation(quote),
                    JSON.stringify(payload)
                ).done(
                    function (response) {
                        quote.setTotals(response.totals);
                        paymentService.setPaymentMethods(methodConverter(response.payment_methods));
                        fullScreenLoader.stopLoader();
                    }
                ).fail(
                    function (response) {
                        errorProcessor.process(response);
                        fullScreenLoader.stopLoader();
                    }
                );
            }
        }
    };
});
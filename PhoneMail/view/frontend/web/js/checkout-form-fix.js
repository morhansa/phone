/**
 * PhoneMail Module - Checkout Form Fix
 * Fixes validation issues in checkout
 */
define([
    'jquery',
    'Magento_Checkout/js/model/quote',
    'domReady!'
], function($, quote) {
    'use strict';
    
    return function() {
        $(function() {
            console.log('PhoneMail Checkout Form Fix: Initialized');
            
            // Function to update quote with phone and email
            function updateQuoteWithPhone(phoneNumber) {
                if (!phoneNumber) return;
                
                // Clean phone number and validate
                var cleanPhone = phoneNumber.replace(/\D/g, '');
                if (cleanPhone.length < 6) return;
                
                // Generate email from phone
                var domain = window.location.hostname.replace('www.', '');
                var email = cleanPhone + '@' + domain;
                
                // Update quote
                quote.guestEmail = email;
                
                // Update shipping address
                var shippingAddress = quote.shippingAddress() || {};
                shippingAddress.email = email;
                shippingAddress.telephone = phoneNumber;
                quote.shippingAddress(shippingAddress);
                
                console.log('PhoneMail: Updated quote with phone ' + phoneNumber);
            }
            
            // Intercept form submission to ensure email is set
            $(document).on('click', '.button.action.continue.primary', function() {
                setTimeout(function() {
                    // Find telephone fields
                    var phoneFields = $('input[name="telephone"], input[name$=".telephone"]');
                    if (phoneFields.length) {
                        var phoneValue = phoneFields.first().val();
                        if (phoneValue) {
                            updateQuoteWithPhone(phoneValue);
                        }
                    }
                }, 0);
            });
            
            // Monitor telephone fields
            $(document).on('change', 'input[name="telephone"], input[name$=".telephone"]', function() {
                updateQuoteWithPhone($(this).val());
            });
        });
    };
});
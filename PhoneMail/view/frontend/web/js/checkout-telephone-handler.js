/**
 * PhoneMail Module
 *
 * @category  PhoneMail
 * @package   PhoneMail\view\frontend\web\js
 * @author    MagoArab
 * @copyright Copyright (c) 2025
 */
define([
    'jquery',
    'mage/translate',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/checkout-data',
    'domReady!'
], function ($, $t, quote, checkoutData) {
    'use strict';
    
    return function (config) {
        // Add phone validation method if it doesn't exist
        if (!$.validator.methods['validate-phone-number']) {
            $.validator.addMethod(
                'validate-phone-number',
                function (value) {
                    // Check if empty
                    if ($.isEmpty(value)) {
                        return false;
                    }
                    
                    // Clean the phone number (remove non-numeric characters)
                    var cleanedNumber = value.replace(/\D/g, '');
                    
                    // Check length - minimum 6 digits, maximum 15 digits (international standard)
                    if (cleanedNumber.length < 6 || cleanedNumber.length > 15) {
                        return false;
                    }
                    
                    return true;
                },
                $t('Please enter a valid phone number (minimum 6 digits).')
            );
        }
        
        // Process the telephone field and generate email
        function processPhoneField(telephoneField) {
            if (!telephoneField || !telephoneField.length) return;
            
            // Add our explanatory note after the field
            if (telephoneField.next('.phonemail-note').length === 0) {
                telephoneField.after('<div class="phonemail-note">' + 
                    $t('Your email address will be generated automatically from your phone number') + 
                    '</div>');
            }
            
            // Monitor changes to the telephone field
            telephoneField.on('change keyup blur', function() {
                var value = $(this).val();
                if (!value) return;
                
                // Clean phone number and check length
                var cleanPhone = value.replace(/\D/g, '');
                if (cleanPhone.length < 6) return;
                
                // Generate email
                var domain = window.location.hostname.replace('www.', '');
                var email = cleanPhone + '@' + domain;
                
                // Update quote
                updateQuoteWithPhoneAndEmail(value, email);
                
                // Copy phone value to other telephone fields
                $('input[name$="telephone"]').not(this).val(value);
            });
        }
        
        // Update quote with phone and email
        function updateQuoteWithPhoneAndEmail(telephone, email) {
            // Update shipping address
            var shippingAddress = quote.shippingAddress();
            if (shippingAddress) {
                shippingAddress.telephone = telephone;
                shippingAddress.email = email;
                quote.shippingAddress(shippingAddress);
            }
            
            // Update guest email
            quote.guestEmail = email;
            checkoutData.setValidatedEmailValue(email);
            
            // Also store in billing address if available
            var billingAddress = quote.billingAddress();
            if (billingAddress) {
                billingAddress.telephone = telephone;
                billingAddress.email = email;
                quote.billingAddress(billingAddress);
            }
            
            // Store in checkout data for persistence
            checkoutData.setInputFieldEmailValue(email);
            checkoutData.setNewCustomerEmail(email);
            
            console.log('PhoneMail: Updated quote with phone: ' + telephone + ' and email: ' + email);
        }
        
        // Initialize when DOM is ready
        $(function() {
            // Process shipping address telephone field
            var telephoneFields = $('input[name="shippingAddress.telephone"]');
            if (telephoneFields.length) {
                processPhoneField(telephoneFields);
                console.log('PhoneMail: Processed shipping address telephone field');
            }
            
            // Check periodically for new telephone fields (for when DOM updates)
            var intervalId = setInterval(function() {
                var newTelephoneFields = $('input[name="shippingAddress.telephone"]');
                if (newTelephoneFields.length && !newTelephoneFields.data('phonemail-processed')) {
                    processPhoneField(newTelephoneFields);
                    newTelephoneFields.data('phonemail-processed', true);
                    console.log('PhoneMail: Processed new telephone field');
                }
                
                // Limit how long we keep checking
                if (window.phonemailCheckCount === undefined) {
                    window.phonemailCheckCount = 1;
                } else {
                    window.phonemailCheckCount++;
                    if (window.phonemailCheckCount > 10) {
                        clearInterval(intervalId);
                    }
                }
            }, 1000);
        });
    };
});
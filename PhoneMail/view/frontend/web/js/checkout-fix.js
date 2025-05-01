/**
 * PhoneMail Module - Checkout Fix
 *
 * @category  PhoneMail
 * @package   PhoneMail\view\frontend\web\js
 * @author    MagoArab
 * @copyright Copyright (c) 2025
 */
define([
    'jquery',
    'ko',
    'underscore',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/checkout-data',
    'Magento_Checkout/js/model/step-navigator',
    'domReady!'
], function($, ko, _, quote, checkoutData, stepNavigator) {
    'use strict';
    
    return function(config) {
        var phoneCheckCount = 0;
        var VALID_PHONE_MIN_LENGTH = config.minLength || 6;
        var VALID_PHONE_MAX_LENGTH = config.maxLength || 15;
        var MAX_PHONE_CHECK = 20;
        
        // Run init once DOM is ready
        $(function() {
            console.log('PhoneMail: Checkout fix initialized');
            
            // Generate email from phone
            function generateEmail(phone) {
                if (!phone) return '';
                
                var cleanPhone = phone.replace(/\D/g, '');
                if (cleanPhone.length < VALID_PHONE_MIN_LENGTH || cleanPhone.length > VALID_PHONE_MAX_LENGTH) return '';
                
                var domain = window.location.hostname.replace('www.', '');
                return cleanPhone + '@' + domain;
            }
            
            // Update checkout data with phone and generated email
            function updateCheckoutData(phone) {
                if (!phone) return false;
                
                var cleanPhone = phone.replace(/\D/g, '');
                if (cleanPhone.length < VALID_PHONE_MIN_LENGTH || cleanPhone.length > VALID_PHONE_MAX_LENGTH) return false;
                
                var email = generateEmail(phone);
                if (!email) return false;
                
                console.log('PhoneMail: Updating checkout data with phone ' + phone + ' and email ' + email);
                
                // Update quote email
                quote.guestEmail = email;
                
                // Update shipping address
                var shippingAddress = quote.shippingAddress() || {};
                shippingAddress.telephone = phone;
                shippingAddress.email = email;
                quote.shippingAddress(shippingAddress);
                
                // Update billing address if available
                var billingAddress = quote.billingAddress();
                if (billingAddress) {
                    billingAddress.telephone = phone;
                    billingAddress.email = email;
                    quote.billingAddress(billingAddress);
                }
                
                // Save to checkout data
                checkoutData.setInputFieldEmailValue(email);
                checkoutData.setValidatedEmailValue(email);
                
                // Also update any email input fields in the page
                $('input[type="email"]').val(email);
                
                return true;
            }
            
            // Add note to telephone field
            function addNotesToTelephoneFields() {
                $('input[name$="telephone"], input[name="telephone"]').each(function() {
                    var field = $(this);
                    var fieldContainer = field.closest('.field');
                    
                    // Skip if already processed
                    if (fieldContainer.data('phonemail-processed')) {
                        return;
                    }
                    
                    // Add the note
                    if (fieldContainer.find('.phonemail-note').length === 0) {
                        fieldContainer.append(
                            '<div class="phonemail-note">' +
                            'Your email address will be generated automatically from your phone number' +
                            '</div>'
                        );
                    }
                    
                    // Mark as processed
                    fieldContainer.data('phonemail-processed', true);
                    
                    // Add change handler
                    field.off('change.phonemail').on('change.phonemail', function() {
                        updateCheckoutData($(this).val());
                    });
                });
            }
            
            // Fix email validation
            function fixEmailValidation() {
                // Override email validation to always pass
                if ($.validator && $.validator.methods && $.validator.methods['validate-email']) {
                    var originalEmailValidator = $.validator.methods['validate-email'];
                    
                    $.validator.methods['validate-email'] = function(value, element) {
                        // If the field is empty, let the required validator handle it
                        if (!value) {
                            return true;
                        }
                        
                        // If it's our generated email, always pass
                        if (value.indexOf('@' + window.location.hostname.replace('www.', '')) !== -1) {
                            return true;
                        }
                        
                        // Otherwise, use the original validator
                        return originalEmailValidator.call(this, value, element);
                    };
                    
                    console.log('PhoneMail: Fixed email validation');
                }
            }
            
            // Initialize the fix
            function initFix() {
                // Add notes to telephone fields
                addNotesToTelephoneFields();
                
                // Fix email validation
                fixEmailValidation();
                
                // Find all telephone fields and add handlers
                var telephoneFields = $('input[name$="telephone"], input[name="telephone"]');
                telephoneFields.each(function() {
                    var field = $(this);
                    
                    // Skip if already processed
                    if (field.data('phonemail-handler-added')) {
                        return;
                    }
                    
                    // Add blur handler
                    field.on('blur', function() {
                        updateCheckoutData($(this).val());
                    });
                    
                    // Mark as processed
                    field.data('phonemail-handler-added', true);
                });
                
                // Pre-fill email fields if telephone is already filled
                if (telephoneFields.length) {
                    var phoneValue = telephoneFields.first().val();
                    if (phoneValue) {
                        updateCheckoutData(phoneValue);
                    }
                }
            }
            
            // Run initialization
            initFix();
            
            // Also run after a delay to catch dynamically loaded elements
            setTimeout(initFix, 1000);
            setTimeout(initFix, 3000);
            
            // Set up an interval to periodically check for new elements
            var fixInterval = setInterval(function() {
                phoneCheckCount++;
                
                // Run the fix
                initFix();
                
                // Stop checking after MAX_PHONE_CHECK times
                if (phoneCheckCount >= MAX_PHONE_CHECK) {
                    clearInterval(fixInterval);
                    console.log('PhoneMail: Stopped checking for new elements');
                }
            }, 2000);
            
            // Also run when shipping method changes
            $(document).on('click', '.checkout-shipping-method input[type="radio"]', function() {
                setTimeout(initFix, 500);
            });
            
            // Run when shipping address changes
            $(document).on('click', '.action-select-shipping-item, .action-edit-address', function() {
                setTimeout(initFix, 500);
            });
        });
    };
});
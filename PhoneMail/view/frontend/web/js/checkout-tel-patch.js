/**
 * PhoneMail Module - Checkout Telephone Patch
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
    'Magento_Checkout/js/model/shipping-save-processor',
    'domReady!'
], function($, ko, _, quote, checkoutData, stepNavigator, shippingSaveProcessor) {
    'use strict';
    
    return function(config) {
        var phoneCheckCount = 0;
        var VALID_PHONE_MIN_LENGTH = 6;
        var MAX_PHONE_CHECK = 20;
        
        // Run init once DOM is ready
        $(function() {
            console.log('PhoneMail: Tel patch initialized');
            
            // Generate email from phone
            function generateEmail(phone) {
                if (!phone) return '';
                
                var cleanPhone = phone.replace(/\D/g, '');
                if (cleanPhone.length < VALID_PHONE_MIN_LENGTH) return '';
                
                var domain = window.location.hostname.replace('www.', '');
                return cleanPhone + '@' + domain;
            }
            
            // Update checkout data with phone and generated email
            function updateCheckoutData(phone) {
                if (!phone || phone.length < VALID_PHONE_MIN_LENGTH) return false;
                
                // Generate email from phone
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
            
            // Move telephone field to the top of the form
            function moveTelephoneFieldToTop() {
                var shippingForm = $('.form-shipping-address fieldset.field');
                var telephoneField = shippingForm.filter('.field.telephone');
                
                if (telephoneField.length && shippingForm.length > 1) {
                    // Move telephone field to top
                    telephoneField.prependTo(telephoneField.parent());
                    console.log('PhoneMail: Moved telephone field to top');
                }
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
                        
                        // Copy to other phone fields
                        var phoneValue = $(this).val();
                        $('input[name$="telephone"], input[name="telephone"]').not(this).val(phoneValue);
                    });
                });
            }
            
            // Find and prepare the continue button
            function prepareContinueButton() {
                var continueBtn = $('.button.action.continue.primary');
                
                if (continueBtn.length && !continueBtn.data('phonemail-processed')) {
                    console.log('PhoneMail: Preparing continue button');
                    
                    // Remove any existing click handlers (important!)
                    continueBtn.prop('onclick', null).off('click');
                    
// Add our custom handler - but differently
continueBtn.on('click', function(e) {
    // DO NOT prevent default immediately
    // We'll check if we need to prevent default after validation
                        
                        // Find the telephone field
                        var telephoneFields = $('input[name$="telephone"], input[name="telephone"]');
                        var telephoneField = telephoneFields.first();
                        
                        if (!telephoneField.length) {
                            console.log('PhoneMail: No telephone field found');
                            return false;
                        }
                        
                        // Validate form fields
                        var isValid = true;
                        var requiredFields = $('.form-shipping-address').find('.field.required :input, .required :input');
                        
                        requiredFields.each(function() {
                            var field = $(this);
                            
                            // Skip hidden fields
                            if (field.is(':hidden')) {
                                return true;
                            }
                            
                            var fieldValue = field.val();
                            if (!fieldValue || fieldValue.trim() === '') {
                                isValid = false;
                                
                                // Add error styling
                                field.addClass('mage-error');
                                
                                // Add error message if not already present
                                var errorMsgId = field.attr('id') + '-error';
                                if ($('#' + errorMsgId).length === 0) {
                                    field.after(
                                        '<div id="' + errorMsgId + '" class="mage-error">' +
                                        'This is a required field.' +
                                        '</div>'
                                    );
                                }
                            } else {
                                // Remove error styling and message
                                field.removeClass('mage-error');
                                $('#' + field.attr('id') + '-error').remove();
                            }
                        });
                        
                        // Special validation for telephone
                        var phoneValue = telephoneField.val();
                        if (!phoneValue || phoneValue.replace(/\D/g, '').length < VALID_PHONE_MIN_LENGTH) {
                            isValid = false;
                            
                            // Add error styling
                            telephoneField.addClass('mage-error');
                            
                            // Add error message
                            var phoneErrorId = telephoneField.attr('id') + '-error';
                            if ($('#' + phoneErrorId).length === 0) {
                                telephoneField.after(
                                    '<div id="' + phoneErrorId + '" class="mage-error">' +
                                    'Please enter a valid phone number (minimum ' + VALID_PHONE_MIN_LENGTH + ' digits).' +
                                    '</div>'
                                );
                            }
                        } else {
                            // Valid phone - update checkout data
                            updateCheckoutData(phoneValue);
                        }
                        
if (!isValid) {
    console.log('PhoneMail: Validation failed');
    e.preventDefault();
    e.stopPropagation();
    return false;
}
                        
                        // If validation passed, proceed to next step
                        console.log('PhoneMail: Validation passed, proceeding to next step');
                        
                        // Save shipping information and proceed
                        try {
                            // Use shipping-save-processor
                            shippingSaveProcessor.saveShippingInformation();
                            return true;
                        } catch (error) {
                            console.error('PhoneMail: Error saving shipping information', error);
                            
                            // Fallback - try next step directly
                            if (stepNavigator.getActiveItemIndex() < stepNavigator.steps.length - 1) {
                                stepNavigator.navigateTo(stepNavigator.getActiveItemIndex() + 1);
                            }
                            return true;
                        }
                    });
                    
                    continueBtn.data('phonemail-processed', true);
                }
            }
            
            // Initialize and run periodically
            function initialize() {
                // Process telephone fields
                addNotesToTelephoneFields();
                
                // Move telephone field to top
                moveTelephoneFieldToTop();
                
                // Prepare continue button
                prepareContinueButton();
                
                // Pre-fill email fields
                var telephoneFields = $('input[name$="telephone"], input[name="telephone"]');
                if (telephoneFields.length) {
                    var phoneValue = telephoneFields.first().val();
                    if (phoneValue) {
                        updateCheckoutData(phoneValue);
                    }
                }
                
                // Increment check count
                phoneCheckCount++;
                
                // Stop checking after MAX_PHONE_CHECK iterations
                return phoneCheckCount < MAX_PHONE_CHECK;
            }
            
            // Initial run
            initialize();
            
            // Set up periodic checks
            var interval = setInterval(function() {
                var shouldContinue = initialize();
                if (!shouldContinue) {
                    clearInterval(interval);
                }
            }, 1000);
        });
    };
});
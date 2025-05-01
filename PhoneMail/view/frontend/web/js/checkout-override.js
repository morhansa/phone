/**
 * PhoneMail Module - Checkout Override
 * This file addresses validation issues in checkout
 */
define([
    'jquery',
    'mage/url',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/url-builder',
    'Magento_Customer/js/model/customer',
    'Magento_Checkout/js/model/step-navigator',
    'domReady!'
], function($, url, quote, urlBuilder, customer, stepNavigator) {
    'use strict';
    
    return function(config) {
        $(function() {
            console.log('PhoneMail Checkout Override: Initialized');
            
            // Constants
            var PHONE_MIN_LENGTH = 6;
            var REQUIRED_CLASSES = '.required-entry, .required';
            var CONTINUE_BTN_SELECTOR = '.button.action.continue.primary';
            
            // Setup the checkout patch
            function setupCheckoutPatch() {
                // Override the original action button to bypass form validation issues
                overrideContinueButton();
                
                // Add phone note
                addPhoneNote();
                
                // Move telephone field to top
                movePhoneFieldToTop();
            }
            
            // Function to add note to phone field
            function addPhoneNote() {
                var telephoneFields = $('input[name="telephone"], input[name$=".telephone"]');
                
                telephoneFields.each(function() {
                    var field = $(this);
                    var fieldContainer = field.closest('.field');
                    
                    // Add note if it doesn't exist
                    if (fieldContainer.find('.phonemail-note').length === 0) {
                        fieldContainer.append(
                            '<div class="phonemail-note" style="color:#6d6d6d;font-size:12px;font-style:italic;margin-top:5px;">' +
                            'Your email address will be generated automatically from your phone number' +
                            '</div>'
                        );
                    }
                    
                    // Add change handler
                    field.off('change.phonemail').on('change.phonemail', function() {
                        // Update other telephone fields
                        var val = $(this).val();
                        telephoneFields.not(this).val(val);
                        
                        // Update email
                        updateEmail(val);
                    });
                });
            }
            
            // Move telephone field to top
            function movePhoneFieldToTop() {
                var fieldset = $('.form-shipping-address fieldset, .shipping-address-form fieldset');
                var phoneField = $('.field.telephone', fieldset);
                
                if (phoneField.length && fieldset.length) {
                    // Move to top
                    phoneField.prependTo(fieldset);
                    
                    // Add styling for prominence
                    phoneField.css({
                        'order': '-1000',
                        'margin-bottom': '20px',
                        'padding-bottom': '15px',
                        'border-bottom': '1px solid #ccc'
                    });
                }
            }
            
            // Generate email from phone
            function generateEmail(phone) {
                if (!phone) return '';
                
                var cleanPhone = phone.replace(/\D/g, '');
                if (cleanPhone.length < PHONE_MIN_LENGTH) return '';
                
                var domain = window.location.hostname.replace('www.', '');
                return cleanPhone + '@' + domain;
            }
            
            // Update email from phone
            function updateEmail(phone) {
                var email = generateEmail(phone);
                if (!email) return;
                
                // Update quote
                quote.guestEmail = email;
                
                // Update shipping address
                var shippingAddress = quote.shippingAddress();
                if (shippingAddress) {
                    shippingAddress.telephone = phone;
                    shippingAddress.email = email;
                    quote.shippingAddress(shippingAddress);
                }
                
                console.log('PhoneMail: Updated email to ' + email);
            }
            
            // Override continue button
            function overrideContinueButton() {
                $(document).on('click', CONTINUE_BTN_SELECTOR, function(e) {
                    // Stop default action
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    
                    console.log('PhoneMail: Continue button clicked');
                    
                    // Validate required fields
                    var isValid = validateRequiredFields();
                    
                    if (isValid) {
                        // Get phone number
                        var phone = $('input[name="telephone"], input[name$=".telephone"]').first().val();
                        updateEmail(phone);
                        
                        // Proceed to next step manually
                        proceedToNextStep();
                    }
                    
                    return false;
                });
            }
            
            // Validate required fields
            function validateRequiredFields() {
                var isValid = true;
                var requiredFields = $('.form-shipping-address').find(REQUIRED_CLASSES).filter(':visible');
                
                requiredFields.each(function() {
                    var field = $(this);
                    
                    // Skip if field is not visible
                    if (!field.is(':visible')) return true;
                    
                    // Check if field has value
                    if (!field.val() || field.val().trim() === '') {
                        isValid = false;
                        showFieldError(field, 'This is a required field.');
                    } else {
                        clearFieldError(field);
                    }
                });
                
                // Special validation for phone
                var phoneField = $('input[name="telephone"], input[name$=".telephone"]').first();
                if (phoneField.length) {
                    var phoneValue = phoneField.val();
                    var cleanPhone = phoneValue ? phoneValue.replace(/\D/g, '') : '';
                    
                    if (!cleanPhone || cleanPhone.length < PHONE_MIN_LENGTH) {
                        isValid = false;
                        showFieldError(phoneField, 'Please enter a valid phone number (minimum ' + PHONE_MIN_LENGTH + ' digits).');
                    }
                }
                
                return isValid;
            }
            
            // Show field error
            function showFieldError(field, message) {
                // Add error class
                field.addClass('mage-error');
                
                // Add error message
                var errorId = field.attr('id') ? field.attr('id') + '-error' : 'field-error-' + Math.floor(Math.random() * 1000);
                if ($('#' + errorId).length === 0) {
                    field.after('<div id="' + errorId + '" class="mage-error" style="color:#e02b27;">' + message + '</div>');
                }
            }
            
            // Clear field error
            function clearFieldError(field) {
                field.removeClass('mage-error');
                var errorId = field.attr('id') ? field.attr('id') + '-error' : null;
                if (errorId) {
                    $('#' + errorId).remove();
                } else {
                    field.next('.mage-error').remove();
                }
            }
            
            // Manually proceed to the next step
            function proceedToNextStep() {
                console.log('PhoneMail: Proceeding to next step');
                
                try {
                    // First try to save using proper checkout functions if available
                    if (typeof window.checkoutConfig !== 'undefined' && 
                        typeof require !== 'undefined') {
                        
                        require([
                            'Magento_Checkout/js/model/shipping-save-processor'
                        ], function(shippingSaveProcessor) {
                            try {
                                shippingSaveProcessor.saveShippingInformation();
                            } catch (e) {
                                console.error('PhoneMail: Error with shipping processor:', e);
                                fallbackProceed();
                            }
                        });
                    } else {
                        fallbackProceed();
                    }
                } catch (e) {
                    console.error('PhoneMail: Error proceeding:', e);
                    fallbackProceed();
                }
            }
            
            // Fallback method to proceed
            function fallbackProceed() {
                var activeStep = $('.step-content.active');
                var nextStepId = activeStep.attr('data-role') === 'shipping' ? 'payment' : null;
                
                if (nextStepId) {
                    // Hide current step
                    activeStep.removeClass('active');
                    
                    // Show next step
                    $('#' + nextStepId).addClass('active');
                    
                    // Update navigation
                    $('.opc-progress-bar-item.current').removeClass('current').addClass('complete');
                    $('.opc-progress-bar-item[data-step="' + nextStepId + '"]').addClass('current');
                    
                    console.log('PhoneMail: Completed fallback navigation to', nextStepId);
                } else {
                    // Last resort - just click the real button
                    console.log('PhoneMail: Using last resort navigation');
                    setTimeout(function() {
                        // Click the original button
                        $(CONTINUE_BTN_SELECTOR).last().trigger('mousedown').trigger('mouseup').trigger('click');
                    }, 100);
                }
            }
            
            // Execute setup and monitor for DOM changes
            setupCheckoutPatch();
            
            // Re-run setup periodically to catch newly loaded elements
            setInterval(function() {
                setupCheckoutPatch();
            }, 2000);
        });
    };
});
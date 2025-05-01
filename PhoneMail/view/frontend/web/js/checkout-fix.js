/**
 * PhoneMail Module - Checkout Fix
 * Simple approach to fix checkout issues
 */
define([
    'jquery',
    'Magento_Checkout/js/model/quote',
    'domReady!'
], function($, quote) {
    'use strict';
    
    return function() {
        // Wait for DOM to be fully loaded
        $(function() {
            console.log('PhoneMail Checkout Fix: Initialized');
            
            // Add fix for the Next button
            $(document).on('click', '.button.action.continue.primary', function(event) {
    // Use setTimeout to ensure our code runs after validation
    setTimeout(function() {
                var telephoneField = $('input[name="telephone"], input[name$=".telephone"]').first();
                
                if (telephoneField.length) {
                    var phone = telephoneField.val();
                    
                    if (phone && phone.replace(/\D/g, '').length >= 6) {
                        // Generate email
                        var domain = window.location.hostname.replace('www.', '');
                        var email = phone.replace(/\D/g, '') + '@' + domain;
                        
                        // Set email
                        quote.guestEmail = email;
                        
                        // Update shipping address
                        var shippingAddress = quote.shippingAddress() || {};
                        shippingAddress.email = email;
                        quote.shippingAddress(shippingAddress);
                        
                        // Continue to next step via direct navigation
                        try {
                            // Get step container
                            var stepContent = $('.step-content.active');
                            var nextId = '';
                            
                            if (stepContent.attr('id') === 'shipping') {
                                nextId = 'payment';
                            } else if (stepContent.attr('id') === 'shipping-method') {
                                nextId = 'payment';
                            }
                            
                            if (nextId) {
                                // Hide current step
                                stepContent.removeClass('active');
                                
                                // Show next step
                                $('#' + nextId).addClass('active');
                                
                                // Update progress
                                $('.opc-progress-bar-item.current').removeClass('current');
                                $('.opc-progress-bar-item[data-step="' + nextId + '"]').addClass('current');
                                
                                console.log('PhoneMail: Navigated to ' + nextId);
                                
                                // Prevent default action
                                event.preventDefault();
                                event.stopPropagation();
                                return false;
                            }
                        } catch (e) {
                            console.error('PhoneMail: Navigation error', e);
                        }
                    } else {
                        // Show validation error
                        telephoneField.addClass('mage-error');
                        var errorMsg = 'Please enter a valid phone number';
                        
                        if ($('#telephone-error').length === 0) {
                            telephoneField.after('<div id="telephone-error" class="mage-error">' + errorMsg + '</div>');
                        }
                        
                        event.preventDefault();
                        event.stopPropagation();
                        return false; }, 0);
                    }
                }
            });
            
            // Add phone notes
            function addPhoneNotes() {
                $('input[name="telephone"], input[name$=".telephone"]').each(function() {
                    var field = $(this);
                    var fieldContainer = field.closest('.field');
                    
                    if (fieldContainer.find('.phonemail-note').length === 0) {
                        fieldContainer.append(
                            '<div class="phonemail-note" style="color:#6d6d6d;font-size:12px;font-style:italic;margin-top:5px;">' +
                            'Your email address will be generated automatically from your phone number' +
                            '</div>'
                        );
                    }
                });
            }
            
            // Initial setup
            addPhoneNotes();
            
            // Monitor for new fields
            setInterval(addPhoneNotes, 2000);
        });
    };
});
/**
 * PhoneMail Module - Phone Register Validation
 * Validates if phone number is already registered
 */
define([
    'jquery',
    'mage/url',
    'mage/translate',
    'domReady!'
], function($, urlBuilder, $t) {
    'use strict';
    
    return function(config) {
        $(function() {
            var PHONE_SELECTOR = '#telephone, input[name="telephone"]';
            var REGISTER_FORM = '#form-validate';
            
            // Function to check if phone exists
            function checkPhoneExists(phone, callback) {
                var cleanPhone = phone.replace(/\D/g, '');
                
                // AJAX check to the controller
                $.ajax({
                    url: urlBuilder.build('phonemail/phone/check'),
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        phone: cleanPhone
                    },
                    success: function(response) {
                        callback(response.exists, response.message);
                    },
                    error: function() {
                        callback(false, '');
                    }
                });
            }
            
            // Handle registration form
            $(REGISTER_FORM).on('submit', function(e) {
                var phoneField = $(PHONE_SELECTOR);
                
                if (phoneField.length) {
                    var phoneValue = phoneField.val();
                    
                    if (phoneValue) {
                        // Prevent immediate submission
                        e.preventDefault();
                        
                        // Check if phone exists
                        checkPhoneExists(phoneValue, function(exists, message) {
                            if (exists) {
                                // Show error message
                                phoneField.addClass('mage-error');
                                
                                if ($('#phone-exists-error').length === 0) {
                                    phoneField.after(
                                        '<div id="phone-exists-error" class="mage-error" style="color:#e02b27;">' +
                                        message +
                                        ' <a href="' + urlBuilder.build('customer/account/login') + '">' +
                                        $t('Click here to login') +
                                        '</a>.</div>'
                                    );
                                }
                                
                                // Don't submit the form
                                return false;
                            } else {
                                // Remove error if exists
                                phoneField.removeClass('mage-error');
                                $('#phone-exists-error').remove();
                                
                                // Continue with form submission
                                $(REGISTER_FORM).unbind('submit').submit();
                            }
                        });
                        
                        return false;
                    }
                }
                
                // Let the form submit normally if no phone field or value
                return true;
            });
        });
    };
});
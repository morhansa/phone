define([
    'jquery',
    'mage/translate'
], function ($, $t) {
    'use strict';

    return function (validator) {
        // Add phone validation
        validator.addRule(
            'validate-phone-exists',
            function (value) {
                var isValid = true;
                
                // Skip empty values
                if (!value) {
                    return true;
                }
                
                // Clean phone number
                var cleanPhone = value.replace(/\D/g, '');
                
                // Check if phone exists
                $.ajax({
                    url: '/phonemail/ajax/checkphone',
                    type: 'POST',
                    data: {
                        phone: cleanPhone
                    },
                    dataType: 'json',
                    async: false,
                    success: function (response) {
                        if (response.exists) {
                            isValid = false;
                            
                            // Show message with login and forgot password links
                            var message = $t('This phone number is already registered. ') +
                                '<a href="' + response.loginUrl + '">' + $t('Sign in') + '</a> ' +
                                $t('or') + ' ' +
                                '<a href="' + response.forgotUrl + '">' + $t('recover your password') + '</a>.';
                            
                            // Add message to page
                            var messageContainer = $('#phone-exists-message');
                            if (messageContainer.length === 0) {
                                messageContainer = $('<div id="phone-exists-message" class="message-error error message"></div>');
                                $('#telephone').after(messageContainer);
                            }
                            
                            messageContainer.html(message);
                        } else {
                            // Remove message if exists
                            $('#phone-exists-message').remove();
                        }
                    }
                });
                
                return isValid;
            },
            $t('This phone number is already registered.')
        );
        
        return validator;
    };
});
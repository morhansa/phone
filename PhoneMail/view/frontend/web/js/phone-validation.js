define([
    'jquery',
    'jquery/validate',
    'mage/translate'
], function($) {
    'use strict';

    return function() {
        $.validator.addMethod(
            'validate-phone-number',
            function(value) {
                // Check if empty
                if ($.isEmpty(value)) {
                    return false;
                }
                
                // Check length - minimum 6 digits, maximum 15 digits (international standard)
                var cleanedNumber = value.replace(/\D/g, '');
                if (cleanedNumber.length < 6 || cleanedNumber.length > 15) {
                    return false;
                }

                return true;
            },
            $.mage.__('Please enter a valid phone number (minimum 6 digits).')
        );
    };
});
define([
    'jquery',
    'mage/translate',
    'Magento_Ui/js/lib/validation/utils',
    'mage/validation'
], function ($, $t, utils) {
    'use strict';
    
    return function (config) {
        var minLength = config.minLength || 6;
        var maxLength = config.maxLength || 15;
        
        $.validator.addMethod(
            'validate-phone-number',
            function (value, element) {
                if (value === null || value.length === 0) {
                    return true;
                }
                
                // Remove spaces and other formatting characters for length check
                var cleanPhone = value.replace(/[^0-9+]/g, '');
                
                // Check length
                if (cleanPhone.length < minLength || cleanPhone.length > maxLength) {
                    return false;
                }
                
                return true;
            },
            $.mage.__('Please enter a valid phone number (between %1 and %2 digits).').replace('%1', minLength).replace('%2', maxLength)
        );
    };
});
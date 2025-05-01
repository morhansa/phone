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
    'domReady!'
], function ($) {
    'use strict';
    
    return function (config) {
        // Wait for DOM to be fully loaded
        $(function() {
            // Find all telephone fields
            var mainPhoneField = $('#customer-phone');
            
            // Function to copy phone value to other fields
            function copyPhoneValue() {
                var phoneValue = $(this).val();
                
                if (phoneValue && phoneValue.length > 0) {
                    // Find all other telephone inputs and update them
                    $('input[name$="telephone"]').not(this).val(phoneValue);
                    console.log('PhoneMail: Copied phone value to other fields');
                }
            }
            
            // Attach event handlers to all telephone fields
            $(document).on('change', 'input[type="tel"], input[name$="telephone"]', copyPhoneValue);
            
            // Periodically check for shipping phone field
            var checkInterval = setInterval(function() {
                var shippingPhone = $('input[name="shippingAddress.telephone"]');
                
                if (shippingPhone.length && mainPhoneField.length) {
                    // If main phone has value, copy to shipping
                    if (mainPhoneField.val()) {
                        shippingPhone.val(mainPhoneField.val());
                    }
                    // If shipping has value but main doesn't, copy from shipping
                    else if (shippingPhone.val() && !mainPhoneField.val()) {
                        mainPhoneField.val(shippingPhone.val());
                    }
                    
                    // Clear interval after a while to avoid performance issues
                    if (window.phoneMailIntervalCount === undefined) {
                        window.phoneMailIntervalCount = 1;
                    } else {
                        window.phoneMailIntervalCount++;
                        if (window.phoneMailIntervalCount > 10) {
                            clearInterval(checkInterval);
                        }
                    }
                }
            }, 1000);
        });
    };
});
/**
 * MagoArab PhoneMail Module
 *
 * @category  MagoArab
 * @package   MagoArab_PhoneMail
 * @author    MagoArab
 * @copyright Copyright (c) 2025
 */
define([
    'jquery',
    'mage/translate'
], function ($, $t) {
    'use strict';
    
    return function (config) {
        var whatsappConfigPanel = {
            whatsappEnabled: config.whatsappEnabledFieldId,
            apiUrl: config.apiUrlFieldId,
            apiKey: config.apiKeyFieldId,
            templateOrder: config.templateOrderFieldId,
            templateShipping: config.templateShippingFieldId
        };
        
        /**
         * Initialize the WhatsApp configuration
         */
        function init() {
            // Toggle WhatsApp settings visibility based on enabled status
            $(document).on('change', '#' + whatsappConfigPanel.whatsappEnabled, function() {
                toggleWhatsAppFields($(this).val() === '1');
            });
            
            // Validate API URL when changed
            $(document).on('change', '#' + whatsappConfigPanel.apiUrl, function() {
                validateApiUrl($(this).val());
            });
            
            // Initialize field states
            toggleWhatsAppFields($('#' + whatsappConfigPanel.whatsappEnabled).val() === '1');
        }
        
        /**
         * Toggle WhatsApp API fields visibility
         * 
         * @param {boolean} isEnabled
         */
        function toggleWhatsAppFields(isEnabled) {
            var fields = [
                whatsappConfigPanel.apiUrl,
                whatsappConfigPanel.apiKey,
                whatsappConfigPanel.templateOrder,
                whatsappConfigPanel.templateShipping
            ];
            
            fields.forEach(function(fieldId) {
                var field = $('#row_' + fieldId);
                if (isEnabled) {
                    field.show();
                } else {
                    field.hide();
                }
            });
        }
        
        /**
         * Validate WhatsApp API URL
         * 
         * @param {string} url
         */
        function validateApiUrl(url) {
            var urlField = $('#' + whatsappConfigPanel.apiUrl);
            
            if (!url) {
                return;
            }
            
            // Simple URL validation
            var urlPattern = /^(https?:\/\/)?([\da-z.-]+)\.([a-z.]{2,6})([\/\w .-]*)*\/?$/;
            if (!urlPattern.test(url)) {
                urlField.addClass('validation-failed');
                urlField.after('<div class="validation-advice">' + $t('Please enter a valid URL') + '</div>');
            } else {
                urlField.removeClass('validation-failed');
                urlField.next('.validation-advice').remove();
            }
        }
        
        // Initialize the component
        init();
    };
});
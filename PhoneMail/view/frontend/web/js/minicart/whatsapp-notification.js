/**
 * MagoArab PhoneMail Module
 *
 * @category  MagoArab
 * @package   MagoArab_PhoneMail
 * @author    MagoArab
 * @copyright Copyright (c) 2025
 */
define([
    'uiComponent',
    'ko',
    'Magento_Customer/js/customer-data'
], function (Component, ko, customerData) {
    'use strict';

    return Component.extend({
        whatsappEnabled: ko.observable(false),
        
        /**
         * @inheritdoc
         */
        initialize: function () {
            this._super();
            
            // Get WhatsApp configuration from localStorage
            var config = customerData.get('phonemail-config');
            
            // Subscribe to changes in configuration
            this.whatsappEnabled(config().whatsapp_enabled || false);
            
            config.subscribe(function (updatedConfig) {
                this.whatsappEnabled(updatedConfig.whatsapp_enabled || false);
            }, this);
            
            return this;
        }
    });
});
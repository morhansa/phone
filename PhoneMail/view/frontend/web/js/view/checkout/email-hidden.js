/**
 * PhoneMail Module
 *
 * @category  PhoneMail
 * @package   PhoneMail\view\frontend\web\js\view\checkout
 * @author    MagoArab
 * @copyright Copyright (c) 2025
 */
define([
    'jquery',
    'ko',
    'uiComponent',
    'Magento_Customer/js/model/customer',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/checkout-data',
    'mage/translate'
], function ($, ko, Component, customer, quote, checkoutData, $t) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'MagoArab_PhoneMail/checkout/email-hidden',
            isVisible: ko.observable(false)
        },

        initialize: function () {
            this._super();
            
            console.log('PhoneMail: Email-hidden component initialized');
            
            var self = this;
            
            // Initialize email from telephone if possible
            this.initEmailFromTelephone();
            
            // Watch for changes to telephone fields
            setTimeout(function() {
                self.setupTelephoneWatcher();
            }, 1000);
            
            return this;
        },
        
        initEmailFromTelephone: function() {
            // Get shipping address
            var shippingAddress = quote.shippingAddress();
            if (shippingAddress && shippingAddress.telephone) {
                // Generate email
                var email = this.generateEmailFromPhone(shippingAddress.telephone);
                
                if (email) {
                    // Update quote
                    quote.guestEmail = email;
                    
                    // Update shipping address
                    shippingAddress.email = email;
                    quote.shippingAddress(shippingAddress);
                    
                    console.log('PhoneMail: Initialized email from existing telephone: ' + email);
                }
            }
        },
        
        setupTelephoneWatcher: function() {
            var self = this;
            
            // Watch for changes to telephone fields
            $(document).on('change keyup', 'input[name="telephone"], input[name$=".telephone"]', function() {
                var phone = $(this).val();
                if (phone) {
                    self.updateEmailFromPhone(phone);
                }
            });
            
            console.log('PhoneMail: Telephone watcher setup');
        },
        
        generateEmailFromPhone: function(phone) {
            if (!phone) return '';
            
            var cleanPhone = phone.replace(/\D/g, '');
            if (cleanPhone.length < 6) return '';
            
            var domain = window.location.hostname.replace('www.', '');
            return cleanPhone + '@' + domain;
        },
        
        updateEmailFromPhone: function(phone) {
            var email = this.generateEmailFromPhone(phone);
            if (!email) return;
            
            console.log('PhoneMail: Updating email to ' + email);
            
            // Set it in the quote
            quote.guestEmail = email;
            
            // Update shipping address
            var shippingAddress = quote.shippingAddress();
            if (shippingAddress) {
                shippingAddress.email = email;
                quote.shippingAddress(shippingAddress);
            }
            
            // Also update checkout data
            checkoutData.setInputFieldEmailValue(email);
            checkoutData.setValidatedEmailValue(email);
        }
    });
});
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
    'mage/translate',
    'jquery/ui',
    'mage/validation'
], function ($, $t) {
    'use strict';

    $.widget('magoarab.phonemailWidget', {
        options: {
            emailSelector: '#email',
            phoneSelector: '#telephone',
            emailParentSelector: '.field.email',
            phoneParentSelector: '.field.telephone',
            requiredPhoneClass: 'required',
            visibleClass: 'visible'
        },

        /**
         * Widget initialization
         * @private
         */
        _create: function () {
            this._bindEvents();
            this._initializeFields();
        },

        /**
         * Bind event handlers
         * @private
         */
        _bindEvents: function () {
            var self = this;

            // Handle phone number input changes
            $(this.options.phoneSelector).on('change keyup', function () {
                self._updateEmail($(this).val());
            });

            // Make phone field required
            $(this.options.phoneParentSelector).addClass(this.options.requiredPhoneClass);
            $(this.options.phoneSelector).attr('required', true);

            // Hide email field
            $(this.options.emailParentSelector).hide();
        },

        /**
         * Initialize field values and validations
         * @private
         */
        _initializeFields: function () {
            var self = this;
            
            // Set default email value if phone has a value
            var phoneValue = $(this.options.phoneSelector).val();
            if (phoneValue) {
                this._updateEmail(phoneValue);
            }

            // Add custom validation for phone field
            $.validator.addMethod(
                'validate-phone-number',
                function (value) {
                    // Check if empty
                    if ($.isEmpty(value)) {
                        return false;
                    }
                    
                    // Clean the phone number
                    var cleanedNumber = value.replace(/\D/g, '');
                    
                    // Check length - minimum 6 digits, maximum 15 digits (international standard)
                    if (cleanedNumber.length < 6 || cleanedNumber.length > 15) {
                        return false;
                    }
                    
                    return true;
                },
                $t('Please enter a valid phone number (minimum 6 digits).')
            );

            // Add validation rules to phone field
            $(this.options.phoneSelector).attr('data-validate', '{"required":true, "validate-phone-number":true}');
        },

        /**
         * Update email field based on phone number
         * @param {String} phoneNumber 
         * @private
         */
        _updateEmail: function (phoneNumber) {
            if (!phoneNumber) {
                return;
            }

            // Clean the phone number (remove non-numeric characters)
            var cleanPhone = phoneNumber.replace(/\D/g, '');
            
            if (cleanPhone.length > 0) {
                // Get domain from current URL
                var domain = window.location.hostname.replace('www.', '');
                
                // Generate email address
                var email = cleanPhone + '@' + domain;
                
                // Set the email field value
                $(this.options.emailSelector).val(email);
            }
        }
    });

    return $.magoarab.phonemailWidget;
});
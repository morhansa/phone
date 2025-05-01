/**
 * PhoneMail Module - Shipping Mixin
 * Overrides validateShippingInformation to prevent errors
 */
define([
    'jquery',
    'mage/utils/wrapper'
], function($, wrapper) {
    'use strict';
    
    return function(target) {
        target.validateShippingInformation = wrapper.wrap(
            target.validateShippingInformation,
            function(originalMethod) {
                try {
                    console.log('PhoneMail: Patched validateShippingInformation running');
                    
                    // Fix any elements missing form property
                    var shippingForm = $('#co-shipping-form');
                    if (shippingForm.length) {
                        shippingForm.find('input, select, textarea').each(function() {
                            if (this.form === undefined) {
                                this.form = shippingForm[0];
                            }
                        });
                    }
                    
                    // Call original method
                    return originalMethod();
                } catch (e) {
                    console.error('PhoneMail: Error in validateShippingInformation', e);
                    
                    // Validate manually if error occurs
                    var isValid = true;
                    var telephoneElem = $('#co-shipping-form [name="telephone"]');
                    
                    if (telephoneElem.length && !telephoneElem.val()) {
                        isValid = false;
                        telephoneElem.addClass('mage-error');
                        if ($('#telephone-error').length === 0) {
                            telephoneElem.after('<div id="telephone-error" class="mage-error">This is a required field.</div>');
                        }
                    } else {
                        telephoneElem.removeClass('mage-error');
                        $('#telephone-error').remove();
                    }
                    
                    return isValid;
                }
            }
        );
        
        return target;
    };
});
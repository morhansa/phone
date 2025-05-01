/**
 * PhoneMail Module - Direct validator fix
 * Fixes the "Cannot read properties of undefined (reading 'form')" error
 */
define([
    'jquery',
    'domReady!'
], function($) {
    'use strict';
    
    return function() {
        $(function() {
            console.log('PhoneMail: Validation form fix running');
            
            // Store original jQuery.fn.valid function
            var originalValid = $.fn.valid;
            
            // Override jQuery.fn.valid to fix the form property issue
            $.fn.valid = function() {
                // Check if we have elements and if the first element has a form property
                if (this.length === 0 || (this[0] && this[0].form === undefined)) {
                    console.warn('PhoneMail: Element missing form property, returning true');
                    return true;
                }
                
                try {
                    // Try original method
                    return originalValid.apply(this, arguments);
                } catch (e) {
                    console.error('PhoneMail: Error in valid() function', e);
                    // Return true to prevent checkout from breaking
                    return true;
                }
            };
            
            // Also fix validateElement
            var originalValidateElement = $.validator && $.validator.prototype.element;
            if (originalValidateElement) {
                $.validator.prototype.element = function(element) {
                    try {
                        return originalValidateElement.apply(this, arguments);
                    } catch (e) {
                        console.error('PhoneMail: Error validating element', e);
                        return true;
                    }
                };
            }
            
            // Custom fix for the checkout page
            $(document).on('click', '.action.primary.continue', function(e) {
                // Find if we have any inputs that are missing form property
                $('input, select, textarea').each(function() {
                    if (this.form === undefined && !$(this).attr('data-form-fixed')) {
                        // Fix missing form property by setting it to the closest form
                        var closestForm = $(this).closest('form')[0];
                        if (closestForm) {
                            this.form = closestForm;
                            $(this).attr('data-form-fixed', 'true');
                            console.log('PhoneMail: Fixed missing form property for element', this);
                        }
                    }
                });
            });
        });
    };
});
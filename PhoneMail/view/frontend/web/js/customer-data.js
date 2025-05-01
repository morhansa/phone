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
    'Magento_Customer/js/customer-data'
], function ($, customerData) {
    'use strict';

    /**
     * Initialize PhoneMail customer data section
     */
    function init() {
        var phonemailConfig = customerData.get('phonemail-config');
        
        // If config isn't already loaded, reload it
        if (!phonemailConfig().initialized) {
            customerData.reload(['phonemail-config'], true);
        }
    }

    return function (config) {
        init();
    };
});
define([
    'jquery',
    'mage/utils/wrapper',
    'Magento_Checkout/js/model/quote',
    'Magento_Customer/js/model/customer',
    'Magento_Checkout/js/model/url-builder',
    'Magento_Checkout/js/model/error-processor',
    'Magento_Ui/js/model/messageList',
    'uiRegistry'
], function ($, wrapper, quote, customer, urlBuilder, errorProcessor, messageList, registry) {
    'use strict';

    return function (shippingSaveProcessor) {
        return wrapper.wrap(shippingSaveProcessor, function (originalAction, addressData) {
            // التحقق من وجود البيانات قبل محاولة الوصول إليها
            if (quote && quote.shippingAddress && quote.shippingAddress()) {
                // الحصول على رقم الهاتف من حقل الهاتف في نموذج الشحن
                var telephoneField = registry.get('checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.telephone');
                
                if (telephoneField && telephoneField.value()) {
                    var telephone = telephoneField.value();
                    
                    // إضافة رقم الهاتف إلى بيانات العنوان
                    if (addressData && addressData.addressInformation && addressData.addressInformation.shipping_address) {
                        addressData.addressInformation.shipping_address.telephone = telephone;
                    }
                    
                    // إضافة رقم الهاتف إلى عنوان الفواتير إذا كان متطابقاً مع عنوان الشحن
                    if (addressData && addressData.addressInformation && addressData.addressInformation.billing_address) {
                        addressData.addressInformation.billing_address.telephone = telephone;
                    }
                }
            }
            
            return originalAction(addressData);
        });
    };
});
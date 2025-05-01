/**
 * PhoneMail Module - Telephone Filler
 *
 * @category  PhoneMail
 * @package   PhoneMail\view\frontend\web\js
 * @author    MagoArab
 * @copyright Copyright (c) 2025
 */
define([
    'jquery',
    'Magento_Customer/js/customer-data',
    'Magento_Checkout/js/model/quote',
    'domReady!'
], function($, customerData, quote) {
    'use strict';
    
    return function(config) {
        // استخراج رقم الهاتف من البريد الإلكتروني
        function extractPhoneFromEmail(email) {
            if (!email || email.indexOf('@') === -1) {
                return '';
            }
            
            var parts = email.split('@');
            return parts[0];
        }
        
        // ملء حقل الهاتف في نموذج الدفع
        function fillTelephoneField() {
            var telephone = '';
            
            // محاولة الحصول على رقم الهاتف من بيانات العميل إذا كان مسجلاً
            if (window.isCustomerLoggedIn) {
                var customer = customerData.get('customer')();
                if (customer && customer.email) {
                    telephone = extractPhoneFromEmail(customer.email);
                }
            }
            
            // إذا لم نجد رقم هاتف، نحاول الحصول عليه من بيانات الاقتباس
            if (!telephone && quote.guestEmail) {
                telephone = extractPhoneFromEmail(quote.guestEmail);
            }
            
            // ملء جميع حقول الهاتف في الصفحة
            if (telephone) {
                console.log('PhoneMail: Filling telephone fields with: ' + telephone);
                $('input[name$="telephone"], input[name="telephone"]').val(telephone);
            }
        }
        
        // تنفيذ الوظيفة عند تحميل الصفحة
        $(function() {
            fillTelephoneField();
            
            // تنفيذ الوظيفة أيضاً بعد تأخير لضمان تحميل جميع العناصر
            setTimeout(fillTelephoneField, 1000);
            setTimeout(fillTelephoneField, 3000);
            
            // تنفيذ الوظيفة عند تغيير طريقة الشحن
            $(document).on('click', '.checkout-shipping-method input[type="radio"]', function() {
                setTimeout(fillTelephoneField, 500);
            });
            
            // تنفيذ الوظيفة عند تغيير عنوان الشحن
            $(document).on('click', '.action-select-shipping-item, .action-edit-address', function() {
                setTimeout(fillTelephoneField, 500);
            });
        });
    };
});
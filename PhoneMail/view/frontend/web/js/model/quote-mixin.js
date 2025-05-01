define([
    'mage/utils/wrapper',
    'jquery'
], function (wrapper, $) {
    'use strict';

    return function (quote) {
        // التحقق من وجود البيانات قبل محاولة الوصول إليها
        var originalGetData = quote.getData;
        
        quote.getData = wrapper.wrap(originalGetData, function (originalMethod) {
            try {
                return originalMethod();
            } catch (e) {
                console.warn('PhoneMail: Error in quote.getData', e);
                // إرجاع كائن فارغ لتجنب الأخطاء
                return {
                    totals: {},
                    items: []
                };
            }
        });
        
        return quote;
    };
});
var config = {
    config: {
        mixins: {
            'mage/validation': {
                'MagoArab_PhoneMail/js/phone-validation': true
            },
            'Magento_Checkout/js/view/shipping': {
                'MagoArab_PhoneMail/js/mixin/shipping-mixin': true
            }
        }
    },
    deps: [
        'MagoArab_PhoneMail/js/checkout-fix',
        'MagoArab_PhoneMail/js/checkout-form-fix',
        'MagoArab_PhoneMail/js/checkout-validator-fix',
        'MagoArab_PhoneMail/js/validation-form-fix'
    ],
    map: {
        '*': {
            'Magento_Checkout/js/model/shipping-save-processor/default': 'MagoArab_PhoneMail/js/model/shipping-save-processor/default-override'
        }
    }
};
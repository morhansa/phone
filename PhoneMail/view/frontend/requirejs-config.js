var config = {
    map: {
        '*': {
            'Magento_Checkout/js/model/shipping-save-processor/default': 'MagoArab_PhoneMail/js/model/shipping-save-processor/default-override'
        }
    },
    config: {
        mixins: {
            'Magento_Checkout/js/model/quote': {
                'MagoArab_PhoneMail/js/model/quote-mixin': true
            }
        }
    }
};
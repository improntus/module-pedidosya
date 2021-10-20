/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'uiComponent',
    'Magento_Checkout/js/model/shipping-rates-validator',
    'Magento_Checkout/js/model/shipping-rates-validation-rules',
    '../../model/shipping-rates-validator/pedidosya',
    '../../model/shipping-rates-validation-rules/pedidosya'
], function (
    Component,
    defaultShippingRatesValidator,
    defaultShippingRatesValidationRules,
    pedidosyaShippingRatesValidator,
    pedidosyaShippingRatesValidationRules
) {
    'use strict';

    defaultShippingRatesValidator.registerValidator('pedidosya', pedidosyaShippingRatesValidator);
    defaultShippingRatesValidationRules.registerRules('pedidosya', pedidosyaShippingRatesValidationRules);

    return Component;
});

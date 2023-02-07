/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageplaza.com license sliderConfig is
 * available through the world-wide-web at this URL:
 * https://www.mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Mageplaza
 * @package     Mageplaza_Sitemap
 * @copyright   Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

define([
    'jquery',
    'Magento_Ui/js/form/element/single-checkbox'
], function ($, Component) {
    'use strict';

    return Component.extend({
        defaults: {
            useConfig: false,
            listens: {
                'useConfig': 'toggleElement'
            }
        },

        /**
         * @inheritdoc
         */
        initObservable: function () {
            var self, check;

            this._super().observe('useConfig');

            self = this;
            check = setInterval(function () {
                var excludeSitemap = $("select[name=\"mp_exclude_sitemap\"]");
                if (excludeSitemap.length) {
                    self.disableField(excludeSitemap);
                    clearInterval(check);
                }
            }, 100);

            return this;
        },

        /**
         * Disable field
         *
         * @param field
         */
        disableField: function (field) {
            var self = this;
            field.prop('disabled', !self.useConfig());
        },

        /**
         * Toggle element
         */
        toggleElement: function () {
            var self = this,
                excludeSitemap = $("select[name=\"mp_exclude_sitemap\"]");
            self.disableField(excludeSitemap);
        }
    });
});


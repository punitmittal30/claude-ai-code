define([
    'Magento_Ui/js/grid/columns/thumbnail',
    'jquery',
    'mage/template',
    'text!Pratech_Banners/template/grid/cells/preview.html',
    'Magento_Ui/js/modal/modal',
    'mage/translate'
], function (Thumbnail, $, mageTemplate, previewTemplate) {
    'use strict';

    return Thumbnail.extend({
        defaults: {
            bodyTmpl: 'Pratech_Banners/grid/cells/html',
            fieldClass: {
                'data-grid-thumbnail-cell': true
            }
        },

        /**
         * Get content data per row
         * @param {Object} row
         * @returns {String}
         */
        getContent: function (row) {
            return row[this.index];
        },

        /**
         * Check banner type per row
         *
         * @param {Object} row
         * @returns {boolean}
         */
        getType: function (row) {
            return row[this.index + '_type'] === '1';
        },

        /**
         * Build preview.
         *
         * @param {Object} row
         */
        preview: function (row) {
            var modalHtml = mageTemplate(
                previewTemplate,
                {
                    src: this.getSrc(row), alt: this.getAlt(row), link: this.getLink(row), type: this.getType(row),
                    linkText: $.mage.__('Go to Details Page')
                }
                ),
                previewPopup = $('<div/>').html(modalHtml);

            previewPopup.modal({
                innerScroll: true,
                modalClass: '_image-box',
                buttons: []
            }).trigger('openModal');

            if (this.getType(row)) {
                $('.content-preview-block').html($.parseHTML(this.getContent(row)));
            }
        }
    });
});

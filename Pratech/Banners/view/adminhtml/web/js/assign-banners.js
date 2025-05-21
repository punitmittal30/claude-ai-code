/**
 * global $, $H
 */
define([
    'mage/adminhtml/grid'
], function () {
    'use strict';

    return function (config) {
        var selectedBanners = config.selectedBanners,
            sliderBanner = $H(selectedBanners),
            gridJsObject = window[config.gridJsObjectName],
            tabIndex = 1000;

        /**
         * Show selected banners when edit form in associated slide grid
         */
        $('pratech_slider_banner').value = Object.toJSON(sliderBanner);

        /**
         * Register Banner Slide
         *
         * @param {Object} grid
         * @param {Object} element
         * @param {Boolean} checked
         */
        function registerSliderBanner(grid, element, checked) {
            if (checked) {
                sliderBanner.set(element.value, element.value);
            } else {
                sliderBanner.unset(element.value);
            }
            $('pratech_slider_banner').value = Object.toJSON(sliderBanner);

            grid.reloadParams = {
                'selected_banners[]': sliderBanner.keys()
            };
        }

        /**
         * Click on banner row
         *
         * @param {Object} grid
         * @param {String} event
         */
        function sliderBannerRowClick(grid, event) {
            var trElement = Event.findElement(event, 'tr'),
                isInput = Event.element(event).tagName === 'INPUT',
                checked = false,
                checkbox = null;

            if (trElement) {
                checkbox = Element.getElementsBySelector(trElement, 'input');

                if (checkbox[0]) {
                    checked = isInput ? checkbox[0].checked : !checkbox[0].checked;
                    gridJsObject.setCheckboxChecked(checkbox[0], checked);
                }
            }
        }

        /**
         * Change banner position
         *
         * @param {String} event
         */
        function selectionChange(event) {
            var element = Event.element(event);

            if (element && element.checked) {
                sliderBanner.set(element.value, element.value);
                $('pratech_slider_banner').value = Object.toJSON(sliderBanner);
            }
        }

        /**
         * Initialize slider banner row
         *
         * @param {Object} grid
         * @param {String} row
         */
        function sliderBannerRowInit(grid, row) {
            var checkbox = $(row).getElementsByClassName('checkbox')[0];
            if (checkbox) {
                Event.observe(checkbox, 'keyup', selectionChange);
            }
        }

        gridJsObject.rowClickCallback = sliderBannerRowClick;
        gridJsObject.initRowCallback = sliderBannerRowInit;
        gridJsObject.checkboxCheckCallback = registerSliderBanner;

        if (gridJsObject.rows) {
            gridJsObject.rows.each(function (row) {
                sliderBannerRowInit(gridJsObject, row);
            });
        }
    };
});

/**
 * global $, $H
 */
define([
    'mage/adminhtml/grid'
], function () {
    'use strict';

    return function (config) {
        var selectedVideos = config.selectedVideos,
            sliderVideo = $H(selectedVideos),
            gridJsObject = window[config.gridJsObjectName],
            tabIndex = 1000;

        /**
         * Show selected videos when edit form in associated slide grid
         */
        $('video_slider_mapping').value = Object.toJSON(sliderVideo);

        /**
         * Register Video Slide
         *
         * @param {Object} grid
         * @param {Object} element
         * @param {Boolean} checked
         */
        function registerSliderVideo(grid, element, checked) {
            if (checked) {
                sliderVideo.set(element.value, element.value);
            } else {
                sliderVideo.unset(element.value);
            }
            $('video_slider_mapping').value = Object.toJSON(sliderVideo);

            grid.reloadParams = {
                'selected_videos[]': sliderVideo.keys()
            };
        }

        /**
         * Click on video row
         *
         * @param {Object} grid
         * @param {String} event
         */
        function sliderVideoRowClick(grid, event) {
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
         * Change video position
         *
         * @param {String} event
         */
        function selectionChange(event) {
            var element = Event.element(event);

            if (element && element.checked) {
                sliderVideo.set(element.value, element.value);
                $('video_slider_mapping').value = Object.toJSON(sliderVideo);
            }
        }

        /**
         * Initialize slider video row
         *
         * @param {Object} grid
         * @param {String} row
         */
        function sliderVideoRowInit(grid, row) {
            var checkbox = $(row).getElementsByClassName('checkbox')[0];
            if (checkbox) {
                Event.observe(checkbox, 'keyup', selectionChange);
            }
        }

        gridJsObject.rowClickCallback = sliderVideoRowClick;
        gridJsObject.initRowCallback = sliderVideoRowInit;
        gridJsObject.checkboxCheckCallback = registerSliderVideo;

        if (gridJsObject.rows) {
            gridJsObject.rows.each(function (row) {
                sliderVideoRowInit(gridJsObject, row);
            });
        }
    };
});

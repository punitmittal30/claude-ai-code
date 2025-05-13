/**
 * Pratech_Search
 *
 * @category  JS
 * @package   Pratech\Search
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 */

define(
    [
    'mage/adminhtml/grid'
    ], function () {
        'use strict';

        return function (config) {
            var selectedProducts = config.selectedProducts,
            searchTermProducts = $H(selectedProducts),
            gridJsObject = window[config.gridJsObjectName];

            $('in_searchterm_products').value = searchTermProducts.keys();

            /**
             * Register Search Term Product
             *
             * @param {Object} grid
             * @param {Object} element
             * @param {Boolean} checked
             */
            function registerSearchTermProduct(grid, element, checked)
            {
                if (checked) {
                    searchTermProducts.set(element.value, '');
                } else {
                    searchTermProducts.unset(element.value);
                }
                $('in_searchterm_products').value = searchTermProducts.keys();
                grid.reloadParams = {
                    'selected_products[]': searchTermProducts.keys()
                };
            }

            /**
             * Click on product row
             *
             * @param {Object} grid
             * @param {String} event
             */
            function searchTermProductRowClick(grid, event)
            {
                var trElement = Event.findElement(event, 'tr'),
                eventElement = Event.element(event),
                isInputCheckbox = eventElement.tagName === 'INPUT' && eventElement.type === 'checkbox',
                checked = false,
                checkbox = null;

                if (eventElement.tagName === 'LABEL'
                    && trElement.querySelector('#' + eventElement.htmlFor)
                    && trElement.querySelector('#' + eventElement.htmlFor).type === 'checkbox'
                ) {
                    event.stopPropagation();
                    trElement.querySelector('#' + eventElement.htmlFor).trigger('click');

                    return;
                }

                if (trElement) {
                    checkbox = Element.getElementsBySelector(trElement, 'input');

                    if (checkbox[0]) {
                        checked = isInputCheckbox ? checkbox[0].checked : !checkbox[0].checked;
                        gridJsObject.setCheckboxChecked(checkbox[0], checked);
                    }
                }
            }

            /**
             * Initialize search term product row
             *
             * @param {Object} grid
             * @param {String} row
             */
            function searchTermProductRowInit(grid, row)
            {
                var checkbox = $(row).getElementsByClassName('checkbox')[0];

            }

            gridJsObject.rowClickCallback = searchTermProductRowClick;
            gridJsObject.initRowCallback = searchTermProductRowInit;
            gridJsObject.checkboxCheckCallback = registerSearchTermProduct;

            if (gridJsObject.rows) {
                gridJsObject.rows.each(
                    function (row) {
                        searchTermProductRowInit(gridJsObject, row);
                    }
                );
            }
        };
    }
);

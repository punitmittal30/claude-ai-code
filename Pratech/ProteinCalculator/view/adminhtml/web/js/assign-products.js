/**
 * Pratech_ProteinCalculator
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\ProteinCalculator
 * @author    Himmat Singh <himmat.singh@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/
define([
    'mage/adminhtml/grid'
], function () {
    'use strict';

    return function (config) {
        var selectedProducts = config.selectedProducts,
            gridJsObject = window[config.gridJsObjectName],
            categoryProducts = new Map(),
            tabIndex = 1000;

        // Preprocess selectedProducts to handle malformed data
        for (let key in selectedProducts) {
            if (selectedProducts.hasOwnProperty(key)) {
                if (typeof key === 'string' && key.includes(':')) {
                    try {
                        let parsedKey = JSON.parse(key);
                        for (let innerKey in parsedKey) {
                            if (parsedKey.hasOwnProperty(innerKey)) {
                                categoryProducts.set(innerKey, '');
                            }
                        }
                    } catch (e) {
                        console.error('Error parsing key:', key, e);
                    }
                } else {
                    categoryProducts.set(key, selectedProducts[key]);
                }
            }
        }

        // Initialize product_id value with current selected products
        $('product_id').value = JSON.stringify(Object.fromEntries(categoryProducts));

        /**
         * Register Category Product
         *
         * @param {Object} grid
         * @param {Object} element
         * @param {Boolean} checked
         */
        function registerCategoryProduct(grid, element, checked) {
            if (checked) {
                if (element.positionElement) {
                    element.positionElement.disabled = false;
                    categoryProducts.set(element.value, element.positionElement.value);
                } else {
                    categoryProducts.set(element.value, '');
                }
            } else {
                if (element.positionElement) {
                    element.positionElement.disabled = true;
                }
                categoryProducts.delete(element.value);
            }
            $('product_id').value = JSON.stringify(Object.fromEntries(categoryProducts));
            grid.reloadParams = {
                'selected_products[]': Array.from(categoryProducts.keys())
            };
        }

        /**
         * Click on product row
         *
         * @param {Object} grid
         * @param {String} event
         */
        function categoryProductRowClick(grid, event) {
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
         * Change product position
         *
         * @param {String} event
         */
        function positionChange(event) {
            var element = Event.element(event);

            if (element && element.checkboxElement && element.checkboxElement.checked) {
                categoryProducts.set(element.checkboxElement.value, element.value);
                $('product_id').value = JSON.stringify(Object.fromEntries(categoryProducts));
            }
        }

        /**
         * Initialize category product row
         *
         * @param {Object} grid
         * @param {String} row
         */
        function categoryProductRowInit(grid, row) {
            var checkbox = $(row).getElementsByClassName('checkbox')[0],
                position = $(row).getElementsByClassName('input-text')[0];

            if (checkbox && position) {
                checkbox.positionElement = position;
                position.checkboxElement = checkbox;
                position.disabled = !checkbox.checked;
                position.tabIndex = tabIndex++;
                Event.observe(position, 'keyup', positionChange);
            }
        }

        gridJsObject.rowClickCallback = categoryProductRowClick;
        gridJsObject.initRowCallback = categoryProductRowInit;
        gridJsObject.checkboxCheckCallback = registerCategoryProduct;

        if (gridJsObject.rows) {
            gridJsObject.rows.each(function (row) {
                categoryProductRowInit(gridJsObject, row);
            });
        }
    };
});
define([
    'mage/adminhtml/grid'
], function () {
    'use strict';

    return function (config) {
        var selectedProducts = config.selectedProducts,
            linkedProducts = $H(selectedProducts),
            gridJsObject = window[config.gridJsObjectName],
            tabIndex = 1000;

        $('linked_configurable_products').value = Object.toJSON(linkedProducts);

        /**
         * Register Linked Product
         *
         * @param {Object} grid
         * @param {Object} element
         * @param {Boolean} checked
         */
        function registerLinkedProduct(grid, element, checked) {
            if (checked) {
                if (element.positionElement) {
                    element.positionElement.disabled = false;
                    linkedProducts.set(element.value, element.positionElement.value);
                } else {
                    linkedProducts.set(element.value, element.value);
                }
            } else {
                if (element.positionElement) {
                    element.positionElement.disabled = true;
                }
                linkedProducts.unset(element.value);
            }
            $('linked_configurable_products').value = Object.toJSON(linkedProducts);
            grid.reloadParams = {
                'selected_products[]': linkedProducts.keys()
            };
        }

        /**
         * Row click event
         *
         * @param {Object} grid
         * @param {Event} event
         */
        function linkedProductRowClick(grid, event) {
            var trElement = Event.findElement(event, 'tr'),
                eventElement = Event.element(event),
                isInputCheckbox = eventElement.tagName === 'INPUT' && eventElement.type === 'checkbox',
                isInputPosition = grid.targetElement &&
                    grid.targetElement.tagName === 'INPUT' &&
                    grid.targetElement.name === 'position',
                checked = false,
                checkbox = null;

            if (eventElement.tagName === 'LABEL' &&
                trElement.querySelector('#' + eventElement.htmlFor) &&
                trElement.querySelector('#' + eventElement.htmlFor).type === 'checkbox'
            ) {
                event.stopPropagation();
                trElement.querySelector('#' + eventElement.htmlFor).trigger('click');
                return;
            }

            if (trElement && !isInputPosition) {
                checkbox = Element.getElementsBySelector(trElement, 'input');

                if (checkbox[0]) {
                    checked = isInputCheckbox ? checkbox[0].checked : !checkbox[0].checked;
                    gridJsObject.setCheckboxChecked(checkbox[0], checked);
                }
            }
        }

        /**
         * Position change event
         *
         * @param {Event} event
         */
        function positionChange(event) {
            var element = Event.element(event);

            if (element && element.checkboxElement && element.checkboxElement.checked) {
                linkedProducts.set(element.checkboxElement.value, element.value);
                $('linked_configurable_products').value = Object.toJSON(linkedProducts);
            }
        }

        /**
         * Initialize row
         *
         * @param {Object} grid
         * @param {Element} row
         */
        function linkedProductRowInit(grid, row) {
            var checkbox = $(row).getElementsByClassName('checkbox')[0],
                position = $(row).getElementsByClassName('input-text')[0];

            if (checkbox) {
                if (position) {
                    checkbox.positionElement = position;
                    position.checkboxElement = checkbox;
                    position.disabled = !checkbox.checked;
                    position.tabIndex = tabIndex++;
                    Event.observe(position, 'keyup', positionChange);
                }
            }
        }

        gridJsObject.rowClickCallback = linkedProductRowClick;
        gridJsObject.initRowCallback = linkedProductRowInit;
        gridJsObject.checkboxCheckCallback = registerLinkedProduct;

        if (gridJsObject.rows) {
            gridJsObject.rows.each(function (row) {
                linkedProductRowInit(gridJsObject, row);
            });
        }
    };
});
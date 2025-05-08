/**
 * global $, $H
 */
define([
    'mage/adminhtml/grid'
], function () {
    'use strict';

    return function (config) {
        var selectedRules = config.selectedRules,
            rulesData = $H(selectedRules),
            gridJsObject = window[config.gridJsObjectName];

        $('stackable_rule_ids').value = Object.toJSON(rulesData);

        function registerRule(grid, element, checked) {
            if (checked) {
                rulesData.set(element.value, element.value);
            } else {
                rulesData.unset(element.value);
            }

            $('stackable_rule_ids').value = Object.toJSON(rulesData);

            grid.reloadParams = {
                'selected_rules[]': rulesData.keys()
            };
        }

        function ruleRowClick(grid, event) {
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
         * @param {String} event
         */
        function selectionChange(event) {
            var element = Event.element(event);

            if (element && element.checked) {
                rulesData.set(element.value, element.value);
                $('stackable_rule_ids').value = Object.toJSON(rulesData);
            }
        }

        /**
         * @param {Object} grid
         * @param {String} row
         */
        function ruleRowInit(grid, row) {
            var checkbox = $(row).getElementsByClassName('checkbox')[0];
            if (checkbox) {
                Event.observe(checkbox, 'keyup', selectionChange);
            }
        }

        gridJsObject.rowClickCallback = ruleRowClick;
        gridJsObject.initRowCallback = ruleRowInit;
        gridJsObject.checkboxCheckCallback = registerRule;

        if (gridJsObject.rows) {
            gridJsObject.rows.each(function (row) {
                ruleRowInit(gridJsObject, row);
            });
        }
    };
});

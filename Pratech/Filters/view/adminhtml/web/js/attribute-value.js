define(
    [
    'jquery',
    'uiRegistry',
    'Magento_Ui/js/form/element/multiselect',
    ], function ($, registry, Multiselect) {
        'use strict';

        return Multiselect.extend(
            {
                initialize: function () {
                    this._super();

                    var attributeTypeField = registry.get(this.parentName + '.' + 'attribute_type');
                    attributeTypeField.on('value', this.updateAttributeValues.bind(this));

                    // Set initial values if they exist
                    this.setInitialAttributeValues(attributeTypeField.value());

                    return this;
                },

                setInitialAttributeValues: function (attributeType) {
                    var self = this;

                    if (attributeType) {
                        // Use the initial data passed to the form
                        var initialValues = this.value();
                        if (initialValues.length) {
                            this.updateAttributeValues(attributeType, initialValues);
                        } else {
                            this.updateAttributeValues(attributeType);
                        }
                    }
                },

                updateAttributeValues: function (attributeType, initialValues) {
                    var self = this;

                    if (attributeType != null) {
                        $.ajax(
                            {
                                url: window.ATTRVAL_CONTROLLER_URL,
                                showLoader: true,
                                data: { attributeCode: attributeType },
                                type: 'GET',
                                dataType: 'json',
                                contentType: 'application/json',
                                success: function (data) {
                                    // Clear existing options
                                    self.clear();

                                    // Add new options
                                    if (data) {
                                        self.setOptions(data);
                                        if (initialValues) {
                                            self.value(initialValues);
                                        }
                                    }
                                },
                                error: function () {
                                    console.error('Failed to fetch attribute values.');
                                }
                            }
                        );
                    }
                }
            }
        );
    }
);

define(
    [
        'ko',
        'uiElement',
        'underscore',
        'jquery',
        'Magento_Ui/js/modal/alert',
        'mage/translate'
    ], function (ko, Element, _, $, message) {
        'use strict';

        return Element.extend(
            {
                defaults: {
                    template: 'Pratech_Return/vin-return-number/view',
                    vinReturnNumber: ko.observable(null),
                    requestId: null,
                    addAllowed: true,
                    saveUrl: null,
                    removeUrl: null,
                    indexedCarriers: {}
                },
                initialize: function () {
                    this._super();

                    return this;
                },
                initObservable: function () {
                    this._super().observe(
                        [
                            'value',
                            'requestId'
                        ]
                    );

                    return this;
                },
                addReturnNumber: function () {
                    let returnNum = this.value();
                    $.ajax(
                        {
                            url: this.saveUrl,
                            data: {'number': returnNum, 'request_id': this.requestId()},
                            method: 'post',
                            global: false,
                            dataType: 'json',
                            success: function (data) {
                                if (!_.isUndefined(data.success)) {
                                    this.vinReturnNumber(returnNum.toString());
                                    this.value("");
                                }
                            }.bind(this)
                        }
                    );

                    return this;
                },
                removeReturnNumber: function (id) {
                    console.log('remove ----', id);
                    $.ajax(
                        {
                            url: this.removeUrl,
                            data: {'id': id},
                            method: 'post',
                            global: false,
                            dataType: 'json',
                            success: function (data) {
                                if (!_.isUndefined(data.success)) {
                                    this.vinReturnNumber(null);
                                }
                            }.bind(this)
                        }
                    );

                    return this;
                }
            }
        );
    }
);

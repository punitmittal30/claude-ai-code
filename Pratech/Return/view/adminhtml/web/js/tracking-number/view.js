define(
    [
        'uiElement',
        'underscore',
        'jquery',
        'Magento_Ui/js/modal/alert',
        'mage/translate'
    ], function (Element, _, $, message) {
        'use strict';

        return Element.extend(
            {
                defaults: {
                    template: 'Pratech_Return/tracking-number/view',
                    trackingNumbers: [],
                    requestId: null,
                    carriers: null,
                    carrier: null,
                    addAllowed: true,
                    saveUrl: null,
                    removeUrl: null,
                    indexedCarriers: {}
                },
                initialize: function () {
                    this._super();

                    _.each(
                        this.carriers, function (val) {
                            this.indexedCarriers[val.code] = val.label;
                        }.bind(this)
                    );

                    return this;
                },
                initObservable: function () {
                    this._super().observe(
                        [
                            'value',
                            'carrier',
                            'trackingNumbers',
                            'requestId'
                        ]
                    );

                    return this;
                },
                initLinks: function () {
                    this._super();
                    return this;
                },
                addTracking: function () {
                    $.ajax(
                        {
                            url: this.saveUrl,
                            data: {'code': this.carrier(), 'number': this.value(), 'request_id': this.requestId()},
                            method: 'post',
                            global: false,
                            dataType: 'json',
                            success: function (data) {
                                if (!_.isUndefined(data.success)) {
                                    var numbers = this.trackingNumbers();
                                    //TODO validate
                                    numbers.push(
                                        {
                                            'id': data.id,
                                            'code': this.carrier(),
                                            'number': this.value(),
                                            'customer': 1
                                        }
                                    );
                                    this.trackingNumbers(numbers);
                                    this.carrier("");
                                    this.value("");
                                }
                            }.bind(this)
                        }
                    );

                    return this;
                },
                removeTracking: function (id) {
                    this.trackingNumbers(
                        _.reject(
                            this.trackingNumbers(), function (tracking) {
                                return tracking.id == id;
                            }
                        )
                    );
                    $.ajax(
                        {
                            url: this.removeUrl,
                            data: {'id': id},
                            method: 'post',
                            global: false,
                            dataType: 'json'
                        }
                    );

                    return this;
                }
            }
        );
    }
);

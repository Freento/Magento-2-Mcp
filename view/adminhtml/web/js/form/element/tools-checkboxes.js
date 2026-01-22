define([
    'underscore',
    'Magento_Ui/js/lib/view/utils/async',
    'uiElement'
], function (_, async, Element) {
    'use strict';

    return Element.extend({
        defaults: {
            template: 'Freento_McpServer/form/element/tools-checkboxes',
            groupedOptions: [],
            options: [],
            value: [],
            visible: true,
            label: '',
            provider: '',
            _dataLoaded: false
        },

        initialize: function () {
            this._super();
            this.loadData();
            return this;
        },

        loadData: function() {
            var self = this;
            require(['uiRegistry'], function(registry) {
                registry.get(self.provider, function(provider) {
                    var data = provider.get('data');
                    if (data && data.tools && data.tools.length) {
                        self._dataLoaded = true;
                        self.value(data.tools);
                    }
                    self._provider = provider;
                });
            });
        },

        initObservable: function () {
            this._super();
            this.observe(['groupedOptions', 'value', 'visible']);
            this.groupOptions();

            // Subscribe to value changes to update provider
            var self = this;
            this.value.subscribe(function(newValue) {
                self.onValueChange();
            });

            return this;
        },

        groupOptions: function () {
            var grouped = {},
                result = [];

            _.each(this.options, function (option) {
                var module = option.module || 'Other';
                if (!grouped[module]) {
                    grouped[module] = [];
                }
                grouped[module].push(option);
            });

            _.each(Object.keys(grouped).sort(), function (module) {
                result.push({
                    module: module,
                    tools: grouped[module]
                });
            });

            this.groupedOptions(result);
        },

        onValueChange: function () {
            if (this._provider && this._dataLoaded) {
                this._provider.set('data.tools', this.value());
            }
        },

        isChecked: function (toolValue) {
            return _.contains(this.value() || [], toolValue);
        },

        toggleValue: function (toolValue) {
            var values = (this.value() || []).slice();
            var idx = values.indexOf(toolValue);

            if (idx === -1) {
                values.push(toolValue);
            } else {
                values.splice(idx, 1);
            }

            this.value(values);
            this.onValueChange();
        },

        toggleModule: function (module) {
            var self = this,
                values = (this.value() || []).slice(),
                group = _.find(this.groupedOptions(), function(g) { return g.module === module; }),
                toolValues = _.pluck(group.tools, 'value'),
                allChecked = _.every(toolValues, function(v) { return self.isChecked(v); });

            if (allChecked) {
                values = _.difference(values, toolValues);
            } else {
                values = _.union(values, toolValues);
            }

            this.value(values);
            this.onValueChange();
        },

        isModuleChecked: function (module) {
            var self = this,
                group = _.find(this.groupedOptions(), function(g) { return g.module === module; });

            if (!group) return false;

            return _.every(group.tools, function(tool) {
                return self.isChecked(tool.value);
            });
        },

        isModuleFullyChecked: function (module, tools) {
            var values = this.value() || [];
            return _.every(tools, function(tool) {
                return values.indexOf(tool.value) !== -1;
            });
        }
    });
});

define([
    'Magento_Ui/js/grid/columns/actions',
    'jquery',
    'Magento_Ui/js/modal/alert',
    'Magento_Ui/js/modal/confirm',
    'Magento_Ui/js/modal/modal',
    'mage/translate'
], function (Actions, $, alert, confirm, modal, $t) {
    'use strict';

    return Actions.extend({
        defaults: {
            generateTokenUrl: '',
            assignRoleUrl: '',
            removeAccessUrl: '',
            roles: []
        },

        generateToken: function (params) {
            var self = this;

            // Magento passes params as single array argument: [userId, username, hasToken]
            var userId = params[0];
            var username = params[1];
            var hasToken = params[2];

            var message = hasToken
                ? $t('This will invalidate the existing token. Continue?')
                : $t('Generate new MCP token for user "%1"?').replace('%1', username);

            confirm({
                title: $t('Generate MCP Token'),
                content: message,
                actions: {
                    confirm: function () {
                        $.ajax({
                            url: self.generateTokenUrl,
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                admin_user_id: userId,
                                form_key: FORM_KEY
                            },
                            success: function (response) {
                                if (response.success) {
                                    alert({
                                        title: $t('MCP Token Generated'),
                                        content: '<p><strong>' + response.message + '</strong></p>' +
                                            '<p style="word-break: break-all; background: #f5f5f5; padding: 10px; margin: 10px 0; font-family: monospace;">' +
                                            response.token + '</p>' +
                                            '<p style="color: #e22626;"><strong>' + response.notice + '</strong></p>',
                                        actions: {
                                            always: function () {
                                                location.reload();
                                            }
                                        }
                                    });
                                } else {
                                    alert({
                                        title: $t('Error'),
                                        content: response.message
                                    });
                                }
                            },
                            error: function () {
                                alert({
                                    title: $t('Error'),
                                    content: $t('An error occurred while generating the token.')
                                });
                            }
                        });
                    }
                }
            });
        },

        assignRole: function (params) {
            var self = this;

            // Magento passes params as single array argument: [userId, username, currentRoleId]
            var userId = params[0];
            var username = params[1];
            var currentRoleId = params[2];

            // Build role options HTML
            var optionsHtml = '<option value="">' + $t('— None —') + '</option>';
            if (this.roles && this.roles.length) {
                this.roles.forEach(function (role) {
                    var selected = (role.value == currentRoleId) ? ' selected' : '';
                    optionsHtml += '<option value="' + role.value + '"' + selected + '>' + role.label + '</option>';
                });
            }

            var content = '<div>' +
                '<p>' + $t('Select MCP ACL Role for user "%1":').replace('%1', username) + '</p>' +
                '<select id="mcp-role-select" class="admin__control-select" style="width: 100%; margin-top: 10px;">' +
                optionsHtml +
                '</select></div>';

            var modalElement = $('<div/>').html(content);

            modal({
                title: $t('Assign MCP ACL Role'),
                modalClass: 'mcp-assign-role-modal',
                buttons: [{
                    text: $t('Cancel'),
                    class: 'action-secondary',
                    click: function () {
                        this.closeModal();
                    }
                }, {
                    text: $t('Save'),
                    class: 'action-primary',
                    click: function () {
                        var roleId = modalElement.find('#mcp-role-select').val();
                        var modalInstance = this;

                        $.ajax({
                            url: self.assignRoleUrl,
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                admin_user_id: userId,
                                role_id: roleId,
                                form_key: FORM_KEY
                            },
                            success: function (response) {
                                modalInstance.closeModal();
                                if (response.success) {
                                    location.reload();
                                } else {
                                    alert({
                                        title: $t('Error'),
                                        content: response.message
                                    });
                                }
                            },
                            error: function () {
                                modalInstance.closeModal();
                                alert({
                                    title: $t('Error'),
                                    content: $t('An error occurred while assigning the role.')
                                });
                            }
                        });
                    }
                }]
            }, modalElement);

            modalElement.modal('openModal');
        },

        removeAccess: function (params) {
            var self = this;

            // Magento passes params as single array argument: [userId, username]
            var userId = params[0];
            var username = params[1];

            confirm({
                title: $t('Remove MCP Access'),
                content: '<p>' + $t('This will:') + '</p>' +
                    '<ul><li>' + $t('Delete the user\'s MCP token') + '</li>' +
                    '<li>' + $t('Unassign their ACL role') + '</li></ul>' +
                    '<p><strong>' + $t('User "%1" will lose all MCP API access.').replace('%1', username) + '</strong></p>',
                actions: {
                    confirm: function () {
                        $.ajax({
                            url: self.removeAccessUrl,
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                admin_user_id: userId,
                                form_key: FORM_KEY
                            },
                            success: function (response) {
                                if (response.success) {
                                    location.reload();
                                } else {
                                    alert({
                                        title: $t('Error'),
                                        content: response.message
                                    });
                                }
                            },
                            error: function () {
                                alert({
                                    title: $t('Error'),
                                    content: $t('An error occurred while removing access.')
                                });
                            }
                        });
                    }
                }
            });
        }
    });
});

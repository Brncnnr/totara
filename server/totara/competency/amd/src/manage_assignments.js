/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_competency
 */

define(['core/str', 'core/modal_factory', 'core/modal_events', 'totara_competency/basket_list', 'core/ajax', 'core/notification',
'core/templates'],
function(str, ModalFactory, ModalEvents, ListBase, ajax, notification, templates) {

    /**
     * Class constructor for manage Assignment.
     *
     * @class
     * @constructor
     */
    function Assignment() {
        if (!(this instanceof Assignment)) {
            return new Assignment();
        }
        this.actionModals = {};
    }

    Assignment.prototype = new ListBase();

    /**
     * Set custom listeners for assignment list
     *
     */
    Assignment.prototype.customBubbledEventsListener = function() {
        var that = this;

        // Bulk events from Selection basket
        this.listParent.addEventListener(this.basketManager.getEventListener() + ':customUpdate', function(e) {
            var action = '',
                actionKey = e.detail.action,
                template = '';

            switch (actionKey) {
                case 'bulkActivate':
                    action = 'activate';
                    break;
                case 'bulkDelete':
                    action = 'delete';
                    break;
                case 'bulkArchive':
                    action = 'archive';
                    template = 'totara_competency/archive_bulk_modal';
                    break;
            }

            var modalType = action + '_bulk';

            var modalEvent = function(e) {
                var extraData = that.getExtraDataForArchive(e);
                that.triggerBulkActionAndUpdatePage(action, extraData);
            };

            that.showConfirmationModal(modalType, modalEvent, template);
        });
    };

    /**
     * Add custom list actions
     *
     */
    Assignment.prototype.customListEvents = function() {
        var list = this.list,
            that = this;

        /**
         * When an action icon has been clicked
         *
         * @param {event} e
         */
        list.onCustomAction = function(e) {
            var action = null,
                actionKey = e.detail.key,
                id = e.detail.val,
                extra = e.detail.extra,
                modalType = null,
                template = '';

            switch (actionKey) {
                case 'activateClicked':
                    action = modalType = 'activate';
                    if (extra.user_group_type === 'user') {
                        modalType = modalType + '_individual';
                    }
                    break;
                case 'deleteClicked':
                    action = modalType = 'delete';
                    if (extra.status == 2) {
                        modalType = 'delete_archived';
                    } else {
                        modalType = 'delete_draft';
                    }
                    if (extra.user_group_type === 'user') {
                        modalType = modalType + '_individual';
                    }
                    break;
                case 'archiveClicked':
                    action = modalType = 'archive';
                    template = 'totara_competency/archive_modal';
                    if (extra.user_group_type !== 'user') {
                        template = 'totara_competency/archive_modal_group';
                        modalType = modalType + '_group';
                    }
                    break;
            }

            var modalEvent = function(e) {
                var extraData = that.getExtraDataForArchive(e);
                that.triggerActionAndUpdatePage(action, id, extraData);
            };

            that.showConfirmationModal(modalType, modalEvent, template);
        };
    };

    /**
     * Display modal for action and then trigger it
     *
     * @param {string} modalType
     * @param {Function} modalEvent
     * @param {string} template
     */
    Assignment.prototype.showConfirmationModal = function(modalType, modalEvent, template) {
        var body = null,
            that = this;

        var stringKeys = [
            {key: 'action_' + modalType + '_modal_header', component: 'totara_competency'}
        ];

        if (that.loader) {
            that.loader.show();
        }

        // First entry in strings must be the title of the modal
        // if template is passed the body will be a template promise
        if (template !== '') {
            body = templates.render(template, []);
        } else {
            stringKeys.push({key: 'action_' + modalType + '_modal', component: 'totara_competency'});
        }

        str.get_strings(stringKeys).then(function(strings) {
            var modalTitle = strings[0];
            var modalBody = body !== null ? body : strings[1];

            // Make sure we reuse a modal instance per action
            if (!that.actionModals[modalType]) {
                that.actionModals[modalType] = null;
            }
            var activeModal = that.actionModals[modalType];
            if (activeModal) {
                that.displayModal(activeModal, modalEvent);
            } else {
                ModalFactory.create({
                    body: modalBody,
                    title: modalTitle,
                    type: ModalFactory.types.CONFIRM
                }).done(function(modal) {
                    that.actionModals[modalType] = modal;
                    that.displayModal(modal, modalEvent);
                });
            }
        });
    };

    /**
     * Display the modal (making sure the event is properly registered.
     *
     * @param {Modal} modal
     * @param {Function} eventFunction
     */
    Assignment.prototype.displayModal = function(modal, eventFunction) {
        var root = modal.getRoot(),
            that = this;

        // Make sure a previous listener is removed
        root.off(ModalEvents.yes);
        root.on(ModalEvents.yes, eventFunction);

        // Uncheck checkbox if it's in the modal
        root.on(ModalEvents.shown, function(e) {
            var checkbox = e.target.querySelector('[data-tw-assigncomp-archive-modal-confirm]');
            if (checkbox) {
                checkbox.checked = false;
            }

            if (that.loader) {
                that.loader.hide();
            }
        });

        modal.show();
    };

    /**
     * Get extra checkbox value for archiving action
     *
     * @param {Event} e
     * @return {Object}
     */
    Assignment.prototype.getExtraDataForArchive = function(e) {
        var checkbox = e.target.querySelector('[data-tw-assigncomp-archive-modal-confirm]');
        var checked = checkbox ? checkbox.checked : false;
        return {continue_tracking: checked};
    };

    /**
     * Trigger action and then update the list. In case of a delete action
     * the item is removed from the list and the basket is refreshed
     *
     * @param {string} action (activate, archive, delete)
     * @param {string} id
     * @param {Object} extraData
     */
    Assignment.prototype.triggerActionAndUpdatePage = function(action, id, extraData) {
        if (typeof extraData === 'undefined') {
            extraData = {};
        }

        var that = this;

        that.loader.show();
        // Call action first before reloading the list and the basket
        // to make sure the action is done.
        ajax.getData(this.triggerAction(action, null, id, extraData)).then(function(data) {
            // Show confirmation
            that.showActionConfirmation(action, data.results);

            if (action === 'delete') {
                // Should only remove item from list not refresh the whole list
                that.list.removeItemFromList([id]);
                that.loader.show();

                // Delete should remove the id from the basket as well
                that.basketManager.queueRemove(id).updateAndRender().then(function() {
                    that.loader.hide();
                });
            } else {
                that.list.update();
            }
        });
    };

    /**
     * Trigger the action and then reload the list and the basket
     *
     * @param {string} action
     * @param {Object} extraData
     */
    Assignment.prototype.triggerBulkActionAndUpdatePage = function(action, extraData) {
        if (typeof extraData === 'undefined') {
            extraData = {};
        }

        var that = this;

        that.loader.show();
        // Call action first before reloading the list and the basket
        // to make sure the action is done.
        that.basketManager.load().then(function(selectedItems) {
            ajax.getData(that.triggerAction(action, that.basketManager.getBasketKey(), null, extraData)).then(function(data) {
                // Show confirmation
                that.showBulkActionConfirmation(action, data.results, selectedItems);

                // empty selected items
                that.loader.show();
                that.basketManager.deleteAndRender().then(function() {
                    // update list
                    that.updatePage([that.list.getUpdateRequestArgs()]);
                });
            });
        });
    };

    /**
     * Show confirmation of action once done
     *
     * @param {string} action
     * @param {Array} data
     */
    Assignment.prototype.showActionConfirmation = function(action, data) {
        var params = {affected: data.length},
            type = data.length > 0 ? "success" : "error";

        this.showNotification(type, 'action_confirm_' + action + '_' + type, 'totara_competency', params);
    };

    /**
     * Show confirmation of action once done
     *
     * @param {string} action
     * @param {Array} data
     * @param {Array} selectedItems
     */
    Assignment.prototype.showBulkActionConfirmation = function(action, data, selectedItems) {
        var skipped = selectedItems.length - data.length;
        var params = {affected: data.length, expected: selectedItems.length, skipped: skipped},
            type = data.length > 0 ? "success" : "warning";

        var messageKey = 'action_confirm_' + action + '_bulk';
        if (skipped > 0) {
            messageKey = messageKey + '_skipped';
        }

        this.showNotification(type, messageKey, 'totara_competency', params);
    };

    /**
     * Show notification
     *
     * @param {String} type success, warning, error, etc/
     * @param {String} messageStringKey used for get_string
     * @param {String} messageComponent used for get_string
     * @param {Object} messageParams used for get_string, optional
     */
    Assignment.prototype.showNotification = function(type, messageStringKey, messageComponent, messageParams) {
        if (typeof messageParams === 'undefined') {
            messageParams = {};
        }
        // Clear old notifications.
        notification.clearNotifications();

        str.get_string(messageStringKey, messageComponent, messageParams).done(function(message) {
            notification.addNotification({
                message: message,
                type: type
            });

            // Scroll to top to make sure that the notification is visible
            window.scrollTo(0, 0);
        }).fail(notification.exception);
    };

    /**
     * Return the webservice request args for triggering an action like 'delete', 'archive', etc.
     *
     * @param {string} action
     * @param {string} basket
     * @param {integer} id
     * @param {Object} extraData
     * @return {Object}
     */
    Assignment.prototype.triggerAction = function(action, basket, id, extraData) {
        if (typeof extraData === 'undefined') {
            extraData = {};
        }

        return {
            args: {
                'action': action,
                'basket': basket,
                'id': id,
                'extra': extraData
            },
            callback: [],
            methodname: 'totara_competency_assignment_action'
        };
    };

    /**
     * Defines actions
     *
     * @param {Object} row
     * @return {Object[]}
     */
    Assignment.prototype.actionsCallback = function(row) {
        var icons = [],
            status = parseInt(row.status);

        if (status !== 1) {
            icons.push({
                name: 'delete',
                icon: 'delete',
                eventKey: 'deleteClicked',
                disabled: false,
                hidden: false
            });
        }
        if (status === 1) {
            icons.push({
                name: 'archive',
                icon: 'archive',
                eventKey: 'archiveClicked',
                disabled: false,
                hidden: false
            });
        }
        if (status === 0) {
            icons.push({
                name: 'activate',
                icon: 'activate',
                eventKey: 'activateClicked',
                disabled: false,
                hidden: false
            });
        }
        return icons;
    };

    /**
     * Defines icons to be used by the actions
     *
     * @return {Object[]}
     */
    Assignment.prototype.iconsCallback = function() {
        return [{
            key: 'delete',
            name: 'remove',
            string: 'action_delete',
            component: 'totara_competency',
            classes: 'tw-list__hover_warning'
        },
        {
            key: 'activate',
            name: 'activate',
            string: 'action_activate',
            component: 'totara_competency',
        },
        {
            key: 'archive',
            name: 'archive',
            string: 'action_archive',
            component: 'totara_competency',
        }];
    };

    /**
     * Function for extending initializer
     *
     */
    Assignment.prototype.initExtend = function() {
        this.customListEvents();
        this.customBubbledEventsListener();
    };

    /**
     * List mapping properties
     *
     * @param {Object} wgt widget instance
     * @returns {JSON} mapping structure
     */
    var listMapping = function(wgt) {
        return {
            actions: wgt.actionsCallback,
            cols: [
                {
                    dataPath: 'competency_name',
                    headerString: {
                        component: 'totara_competency',
                        key: 'header_competency',
                    },
                },
                {
                    dataPath: 'assignment_type_name',
                    headerString: {
                        component: 'totara_competency',
                        key: 'header_assignment_type',
                    },
                },
                {
                    dataPath: 'user_group_name',
                    headerString: {
                        component: 'totara_competency',
                        key: 'assigned_type_detail',
                    },
                },
                {
                    dataPath: 'status_name',
                    headerString: {
                        component: 'totara_competency',
                        key: 'header_status',
                    },
                    size: 'sm'
                },
                {
                    columnTemplate: {
                        template: 'totara_competency/warning_message',
                    },
                    dataPath: 'pathway_warning_message',
                    headerString: {
                        component: 'totara_competency',
                        key: 'header_pathway_warning_message',
                    },
                }
            ],
            extraRowData: [
                {
                    key: 'status',
                    dataPath: 'status'
                },
                {
                    key: 'user_group_type',
                    dataPath: 'user_group_type'
                }
            ],
            hasHierarchy: false,
            icons: wgt.iconsCallback
        };
    };

    /**
     * initialisation method
     *
     * @param {node} parent
     * @returns {Object} promise
     */
    var init = function(parent) {
        return new Promise(function(resolve) {
            var wgt = new Assignment();

            var data = {
                basketKey: 'totara_competency_manage_assignment',
                basketType: 'session',
                list: {
                    map: listMapping(wgt),
                    service: 'totara_competency_assignment_index'
                },
                parent: parent
            };

            wgt.init(data).then(function() {
                resolve(wgt);
            });
        });
    };

    return {
        init: init
    };
});
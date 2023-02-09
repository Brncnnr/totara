/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package totara_competency
 */
define(['core/str', 'core/modal_factory', 'core/modal_events', 'totara_competency/basket_list', 'core/ajax', 'core/notification',
'core/templates', 'core/webapi'],
function(str, ModalFactory, ModalEvents, ListBase, ajax, notification, templates, webapi) {

    var NEW_PROFICIENCY_VALUE_RADIOS = '[name=editProficiencyValue]';
    var NEW_PROFICIENCY_VALUE_RADIOS_CHECKED = '[name=editProficiencyValue]:checked';

    /**
     * Class constructor for EditProficiencyValue.
     *
     * @class
     * @constructor
     */
    function EditProficiencyValue() {
        if (!(this instanceof EditProficiencyValue)) {
            return new EditProficiencyValue();
        }

        this.scaleCache = {};

        // We need to always remember the framework id locally, because if "View selected"
        // is clicked it is removed as a filter, but we require it for the scale value query.
        // We also use it for detecting framework filter change and to revert unconfirmed framework changes.
        this.frameworkId = null;
    }

    EditProficiencyValue.prototype = new ListBase();

    /**
     * Set custom listeners for assignment list
     *
     */
    EditProficiencyValue.prototype.customBubbledEventsListener = function() {
        var that = this;

        // Bulk events from Selection basket
        this.listParent.addEventListener(this.basketManager.getEventListener() + ':customUpdate', function() {
            that.showModal(that.createValueSelectionModal()).then(function (result) {
                if (!result.confirmed) {
                    return;
                }

                that.loader.show();

                var checked = result.modal.getRoot().find(NEW_PROFICIENCY_VALUE_RADIOS_CHECKED);
                var newValue = checked.val();
                var scaleValueId = newValue === 'remove' ? null : newValue;
                var successMessagePromise;

                if (that.list.selectedItems.length === 1) {
                    successMessagePromise  = str.get_string(
                        'edit_proficiency_value_by_assignment_updated_single', 'totara_competency');
                } else {
                    successMessagePromise  = str.get_string(
                        'edit_proficiency_value_by_assignment_updated', 'totara_competency', that.list.selectedItems.length);
                }

                var updatePromise = that.updateMinProficiencyValues(scaleValueId);

                Promise.all([successMessagePromise, updatePromise]).then(function (results) {
                    that.showNotification(results[0]);
                    that.resetListAndBasket();
                }).catch(notification.exception);
            });
        });
    };

    /**
     * Make a request to update the proficiency value for the currently selected assignments.
     *
     * @param {string|null} scaleValueId
     * @return {Promise<void>}
     */
    EditProficiencyValue.prototype.updateMinProficiencyValues = function(scaleValueId) {
        return webapi.call({
            operationName: 'totara_competency_update_min_proficiency_override_for_assignments',
            variables: {
                input: {
                    assignment_ids: this.list.selectedItems,
                    scale_value_id: scaleValueId,
                },
            },
        });
    };

    /**
     * Show success notification.
     *
     * @param {String} message
     */
    EditProficiencyValue.prototype.showNotification = function(message) {
        notification.clearNotifications();

        notification.addNotification({
            message: message,
            type: 'success'
        });

        window.scrollTo(0, 0); // Make sure that the notification is visible.
    };

    /**
     * Reset and re-render the list and basket.
     * Filters are not touched, due to filters.clearPrimaryTree and filters.clearFilters being overridden in initExtend.
     *
     * @see EditProficiencyValue.initExtend
     */
    EditProficiencyValue.prototype.resetListAndBasket = function() {
        var that = this;
        this.basketManager.deleteAndRender().then(function() {
            that.updatePage([that.list.getUpdateRequestArgs()]);
        });
    };

    /**
     * Create modal to choose a new proficiency value, this is split out so we can pre-fetch/cache all the network requests.
     */
    EditProficiencyValue.prototype.createValueSelectionModal = function() {
        var titlePromise = str.get_string(
            'edit_proficiency_bulk_modal_title',
            'totara_competency',
            this.list.selectedItems.length
        );

        var scalePromise = this.loadCompetencyScale();

        // Preload the modal backdrop template.
        var modalBackdropTemplatePromise = templates.render('core/modal_backdrop', null);

        var that = this;
        return Promise.all([titlePromise, scalePromise, modalBackdropTemplatePromise]).then(function(results) {
            var title = results[0];
            var scale = results[1];

            var body = templates.render(
                'totara_competency/edit_proficiency_value_modal',
                that.getModalTemplateData(scale)
            );

            return ModalFactory.create({
                body: body,
                title: title,
                type: ModalFactory.types.SAVE_CANCEL
            }).then(function (modal) {
                modal.disableSave();

                modal.getRoot().find(NEW_PROFICIENCY_VALUE_RADIOS)
                    .off()
                    .change(function () {
                        modal.enableSave();
                    });

                return modal;
            });
        });
    };

    /**
     * Display modal a modal, from a create promise
     *
     * @param {Promise} createPromise
     */
    EditProficiencyValue.prototype.showModal = function(createPromise) {
        if (this.loader) {
            this.loader.show();
        }

        var that = this;
        return createPromise.then(function(modal) {
            return that.displayModal(modal);
        });
    };

    /**
     * Create modal to choose a new proficiency value, this is split out so we can pre-fetch/cache all the network requests.
     */
    EditProficiencyValue.prototype.createFrameworkChangeConfirmModal = function() {
        var stringsPromise = str.get_strings([
            {
                key: 'edit_proficiency_change_framework_warning',
                component: 'totara_competency',
            },
            {
                key: 'filter_select_competency_framework',
                component: 'totara_competency',
            },
            {
                key: 'confirm',
                component: 'moodle',
            },
            {
                key: 'cancel',
                component: 'moodle',
            },
        ]);

        // Preload the modal backdrop template.
        var modalBackdropTemplatePromise = templates.render('core/modal_backdrop', null);

        // var that = this;
        return Promise.all([stringsPromise, modalBackdropTemplatePromise]).then(function(results) {
            var strings = results[0];

            return ModalFactory.create(
                {
                    body: strings[0],
                    title: strings[1],
                    type: ModalFactory.types.CONFIRM,
                },
                undefined,
                {
                    yesstr: strings[2],
                    nostr: strings[3],
                }
            );
        });
    };



    /**
     * Load the competency scale for the currently selected framework.
     * @return {Promise}
     */
    EditProficiencyValue.prototype.loadCompetencyScale = function() {
        var frameworkId = this.frameworkId;

        if (this.scaleCache[frameworkId]) {
            var scale = this.scaleCache[frameworkId];
            return Promise.resolve(scale);
        }

        var that = this;
        return webapi.call({
            operationName: 'totara_competency_scale',
            variables: {
                framework_id: frameworkId,
            },
        }).then(function (response) {
            var scale = response['totara_competency_scale'];

            if (!scale) {
                throw new Error('No scale found for framework id ' + frameworkId);
            }

            that.scaleCache[frameworkId] = scale;
            return scale;
        });
    };

    /**
     * Display modal to choose a new proficiency value.
     * @param {Object} scale
     */
    EditProficiencyValue.prototype.getModalTemplateData = function(scale) {
        var minProficiencyFound = false;

        // Sort by sortorder accenting to find the default/minimum proficiency value.
        // Then reverse for display.
        var values = scale.values.sort(function (a, b) {
            return b.sortorder - b.sortorder;
        }).map(function (value) {
            var templateValue = {
                id: value.id,
                name: value.name,
                isDefaultProficiency: false,
            };

            if (!minProficiencyFound && value.proficient) {
                templateValue.isDefaultProficiency = true;
                minProficiencyFound = true;
            }

            return templateValue;
        }).reverse();

        return { values: values };
    };

    /**
     * Display the modal (making sure the event is properly registered.
     *
     * @param {Modal} modal
     * @return {Promise}
     */
    EditProficiencyValue.prototype.displayModal = function(modal) {
        var root = modal.getRoot(),
            that = this;

        // Uncheck checkbox if it's in the modal
        root.on(ModalEvents.shown, function() {
            if (that.loader) {
                that.loader.hide();
            }
        });

        modal.show();

        return new Promise(function(resolve) {
            // Make sure a previous listener is removed
            var confirmedResult = { confirmed: true, modal: modal };
            root.off(ModalEvents.yes);
            root.on(ModalEvents.yes, resolve.bind(modal, confirmedResult));

            root.off(ModalEvents.save);
            root.on(ModalEvents.save, resolve.bind(modal, confirmedResult));

            var unconfirmedResult = { confirmed: false, modal: modal };
            root.off(ModalEvents.no);
            root.on(ModalEvents.no, resolve.bind(modal, unconfirmedResult));

            root.off(ModalEvents.cancel);
            root.on(ModalEvents.cancel, resolve.bind(modal, unconfirmedResult));

            root.off(ModalEvents.hidden);
            root.on(ModalEvents.hidden, resolve.bind(modal, unconfirmedResult));
        });
    };

    /**
     * Function for extending initializer
     *
     */
    EditProficiencyValue.prototype.initExtend = function() {
        this.customBubbledEventsListener();

        // We must always have a primary filter (framework) set.
        this.selectors.clearPrimaryTree = function () {};

        // Never clear the framework filter, there always must be a framework selected.
        this.filters.clearFilters = function () {
            this.filters = { framework: this.getFilter('framework') };
        };

        var that = this;
        var originalOnFiltersUpdate = this.filters.onFiltersUpdate.bind(this.filters);

        this.filters.onFiltersUpdate = function() {
            // Potentially revert the change of framework, based on user confirm modal selection.
            var frameworkFilterValue = this.getFilter('framework');
            if (this.frameworkId !== frameworkFilterValue) {
                if (that.shouldShowConfirmFrameworkModal(frameworkFilterValue)) {
                    that.showConfirmFrameworkModal(frameworkFilterValue);
                } else {
                    that.frameworkId = frameworkFilterValue;
                }
            }

            originalOnFiltersUpdate();
        };
    };

    /**
     * @param {string|undefined} frameworkFilterValue
     * @return {boolean}
     */
    EditProficiencyValue.prototype.shouldShowConfirmFrameworkModal = function(frameworkFilterValue) {
        if (!frameworkFilterValue) {
            return false;
        }

        if (this.frameworkId === frameworkFilterValue) {
            return false;
        }

        return this.list.selectedItems.length > 0;
    };

    /**
     * @param {string|undefined} frameworkFilterValue
     * @return {Promise}
     */
    EditProficiencyValue.prototype.showConfirmFrameworkModal = function(frameworkFilterValue) {
        var that = this;
        return this.showModal(that.createFrameworkChangeConfirmModal()).then(function (result) {
            if (result.confirmed) {
                that.frameworkId = frameworkFilterValue;
                that.loadCompetencyScale(); // Prefetch competency scale for edit action modal.
                that.basketManager.deleteAndRender();
            } else {
                // Revert the new selection on cancel/close.
                that.filters.setFilter('framework', that.frameworkId);
                that.selectors.setPrimaryTreeValue(that.frameworkId);
                that.list.update();
            }
        });
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
                    dataPath: 'min_proficiency_value_name',
                    headerString: {
                        component: 'totara_competency',
                        key: 'min_required_proficiency_value',
                    },
                },
                {
                    dataPath: 'has_default_proficiency_value_override_yes_no',
                    headerString: {
                        component: 'totara_competency',
                        key: 'default_proficiency_value_override_header',
                    },
                    size: 'xs',
                },
            ],
            extraRowData: [
                {
                    key: 'user_group_type',
                    dataPath: 'user_group_type'
                }
            ],
            hasHierarchy: false,
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
            var wgt = new EditProficiencyValue();

            var data = {
                basketKey: 'totara_competency_edit_proficiency_value',
                basketType: 'simple',
                list: {
                    map: listMapping(wgt),
                    service: 'totara_competency_assignment_index'
                },
                parent: parent
            };

            wgt.init(data).then(function() {
                // Pre-fetch all the lang strings and templates for the modals.
                wgt.createValueSelectionModal();
                wgt.createFrameworkChangeConfirmModal();

                resolve(wgt);
            });
        });
    };

    return {
        init: init
    };
});
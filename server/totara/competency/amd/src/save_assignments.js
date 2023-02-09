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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
 * @package totara_competency
 */

define(['core/str', 'core/templates', 'totara_competency/modal_list', 'totara_competency/list_framework_hierarchy_events',
'core/ajax', 'core/notification', 'totara_competency/loader_manager', 'core/modal_factory', 'totara_competency/session_basket'],
function(str, TemplatesManager, ModalList, HierarchyEvents, ajax, notification, Loader, ModalFactory, SessionBasket) {

    /**
    * Class constructor for the save assignments.
    *
    * @class
    * @constructor
    */
    function SaveAssignments() {
        if (!(this instanceof SaveAssignments)) {
            return new SaveAssignments();
        }

        this.basketKeys = {
            audiences: null,
            competencies: null,
            organisations: null,
            positions: null,
            users: null
        };
        this.baskets = {
            audiences: null,
            competencies: null,
            organisations: null,
            positions: null,
            users: null
        };
        this.counts = null;
        this.competenciesCount = null;
        this.disabledBtnClass = 'tw-assignComp__btn_disabled';
        this.disabledViewerClass = 'tw-assignCompSave__disabled';
        this.hideClass = 'tw-assignCompSave__hidden';
        this.manageAssignmentsUrl = null;
        this.modalsData = {
            audiences: '',
            organisations: '',
            positions: '',
            users: ''
        };
        this.modalLists = {};
        this.removingElement = false;
        this.selectedModal = null;
        this.services = {
            updateBasket: 'totara_core_basket_update',
            createAssignments: 'totara_competency_assignment_create_from_baskets',
            users: 'totara_competency_user_index',
            audiences: 'totara_competency_cohort_index',
            organisations: 'hierarchy_organisation_index',
            positions: 'hierarchy_position_index'
        };
        this.strings = {};
        this.widget = '';
        this.saveConfirmationModal = null;
    }

    /**
    * Listen for propagated events
    */
    SaveAssignments.prototype.propagatedEvents = function() {
        var selectTree = this.widget.querySelector('[data-tw-assignCompSave-tree]'),
            that = this;

        this.widget.addEventListener('click', function(e) {
            if (e.target.closest('[data-tw-assignCompSave-remove-selected-group]')) {
                e.preventDefault();
                var row = e.target.closest('[data-tw-assignCompSave-selected-row-id]');
                if (row) {
                    that.removeSelectedGroup(row);
                }

            // Browse individuals modal
            } else if (e.target.closest('[data-tw-assignCompSave-browse-individuals]')) {
                e.preventDefault();
                if (e.target.closest('.' + that.disabledViewerClass)) {
                    return;
                }
                that.modalLists.individual.show();

            // Page save btn
            } else if (e.target.closest('[data-tw-assignCompSave-save]')) {
                e.preventDefault();
                that.showSaveModal(e);
            }
        });

        if (selectTree) {
            selectTree.addEventListener('totara_core/select_tree:add', function(e) {
                that.selectedModal = e.detail.val;
            });

            selectTree.addEventListener('totara_core/select_tree:changed', function() {
                var modalList = that.modalLists[that.selectedModal];
                modalList.show();
            });
        }
    };

    /**
    * Show confirmation modal on create assignments button
    */
    SaveAssignments.prototype.showSaveModal = function() {
        var count = this.getTotalCount(),
            that = this;

        if (count < 1) {
            return;
        }

        str.get_string('save_modal_body', 'totara_competency', count * this.competenciesCount).then(function(fetchedString) {
            var templateData = {'count_string': fetchedString};

            if (that.saveConfirmationModal) {
                TemplatesManager.render('totara_competency/save_modal', templateData).then(function(result) {
                    that.saveConfirmationModal.setBody(result);
                    that.saveConfirmationModal.show();
                });
            } else {
                ModalFactory.create({
                    body: TemplatesManager.render('totara_competency/save_modal', templateData),
                    title: that.strings.confirmationHeader,
                    type: ModalFactory.types.CONFIRM
                }).done(function(modal) {
                    that.saveConfirmationModal = modal;
                    var root = modal.getRoot();

                    root.on('modal-confirm:yes', function(e) {
                        var activateCheckbox = e.target.querySelector('[data-tw-assigncomp-save-modal-checkbox]').checked;
                        that.createAssignments(activateCheckbox);
                    });

                    root.on('modal:shown', function(e) {
                        var checkbox = e.target.querySelector('[data-tw-assigncomp-save-modal-checkbox]');
                        if (checkbox) {
                            // Uncheck checkbox if it's in the modal
                            checkbox.checked = false;
                        }
                    });
                    modal.show();
                });
            }

        });
    };

    /**
     * Add loading display
     */
    SaveAssignments.prototype.initVariables = function() {
        var nodeData = this.widget.dataset;

        this.manageAssignmentsUrl = this.widget.querySelector('[data-tw-assignCompSave-backBtn]').getAttribute('href');

        this.competenciesCount = parseInt(nodeData.competenciesCount);

        this.basketKeys = {
            audiences: nodeData.basketAudiences,
            competencies: nodeData.basketCompetencies,
            organisations: nodeData.basketOrganisations,
            positions: nodeData.basketPositions,
            users: nodeData.basketUsers
        };

        this.baskets = {
            audiences: new SessionBasket(this.basketKeys.audiences),
            competencies: new SessionBasket(this.basketKeys.competencies),
            organisations: new SessionBasket(this.basketKeys.organisations),
            positions: new SessionBasket(this.basketKeys.positions),
            users: new SessionBasket(this.basketKeys.users)
        };

        this.counts = {
            audiences: parseInt(nodeData.basketAudiencesCount),
            organisations: parseInt(nodeData.basketOrganisationsCount),
            positions: parseInt(nodeData.basketPositionsCount),
            users: parseInt(nodeData.basketUsersCount)
        };
    };

    /**
    * Disable individuals viewer
    */
    SaveAssignments.prototype.disableOverviewActions = function() {
        var buttonNode = this.widget.querySelector('[data-tw-assignCompSave-save]'),
            viewerNode = this.widget.querySelector('[data-tw-assignCompSave-browse-individuals]');
        buttonNode.classList.add(this.disabledBtnClass);
        viewerNode.classList.add(this.disabledViewerClass);
        buttonNode.setAttribute('aria-disabled', true);
        viewerNode.setAttribute('aria-disabled', true);
    };

    /**
    * Enable individuals viewer
    */
    SaveAssignments.prototype.enableOverviewActions = function() {
        var buttonNode = this.widget.querySelector('[data-tw-assignCompSave-save]'),
            viewerNode = this.widget.querySelector('[data-tw-assignCompSave-browse-individuals]');
        buttonNode.classList.remove(this.disabledBtnClass);
        viewerNode.classList.remove(this.disabledViewerClass);
        buttonNode.setAttribute('aria-disabled', false);
        viewerNode.setAttribute('aria-disabled', false);
    };

    /**
    * Calculate the total count of items across all types and return it
    * @returns {int} count
    */
    SaveAssignments.prototype.getTotalCount = function() {
        var count = 0;
        for (var item in this.counts) {
            if (this.counts.hasOwnProperty(item)) {
                count += parseInt(this.counts[item]);
            }
        }
        return count;
    };

    /**
    * Create assignments
    * @param {bool} activate, activiate items on creation
    */
    SaveAssignments.prototype.createAssignments = function(activate) {
        var that = this;

        ajax.getData({
            args: {
                basket: that.basketKeys.competencies,
                status: activate ? 1 : 0,
                usergroups: {
                    cohort: that.basketKeys.audiences,
                    organisation: that.basketKeys.organisations,
                    position: that.basketKeys.positions,
                    user: that.basketKeys.users,
                },
            },
            methodname: that.services.createAssignments,
        }).then(function(data) {
            if (data.results && data.results.length === 0) {
                // An error occurred, reload the page
                window.location.reload();
            } else {
                window.location.replace(that.manageAssignmentsUrl);
            }
        }).catch(function() {
            window.location.reload();
        });
    };

    /**
    * Set item count for type
    * @param {string} type (user, audience ...)
    * @param {array} items, selected items
    */
    SaveAssignments.prototype.setTypeCount = function(type, items) {
        if (items) {
            this.counts[type] = items.length ? items.length : 0;
        }
    };

    /**
    * Update total count and toggle count based actions
    */
    SaveAssignments.prototype.updateTotalCount = function() {
        var count = this.getTotalCount(),
            noItemsNode = this.widget.querySelector('[data-tw-assignCompSave-selected-empty]'),
            totalCountNode = this.widget.querySelector('[data-tw-assignCompSave-selected-group-count]');

        totalCountNode.innerHTML = count;

        if (count < 1) {
            noItemsNode.classList.remove(this.hideClass);
            this.disableOverviewActions();
        } else {
            noItemsNode.classList.add(this.hideClass);
            this.enableOverviewActions();
        }
    };

    /**
    * Reset modals select tree
    */
    SaveAssignments.prototype.resetTreeList = function() {
        var selectTree = this.widget.querySelector('[data-tw-assignCompSave-tree] [data-tw-selectorgroup]');
        selectTree.setAttribute('data-tw-selectorgroup-clear', true);
    };

    /**
    * Render Items
    *
    * @param {string} type (user, audience ...)
    * @param {object} items
    * @return {promise}
    */
    SaveAssignments.prototype.renderItems = function(type, items) {
        var container = this.widget.querySelector('[data-tw-assignCompSave-selected-group="' + type + '"]'),
            that = this;

        return new Promise(function(resolve) {
            if (!container) {
                resolve();
                return;
            }

            TemplatesManager.render('totara_competency/save_selected_user_group_body', {
                basket: that.basketKeys[type],
                items: items,
                title: that.strings[type],
                type: type,
            }).then(function(html) {
                container.innerHTML = html;
                that.setTypeCount(type, items);
                that.updateTotalCount();
                resolve();
            });
        });
    };

    /**
    * Remove type group when empty
    * @param {node} row
    */
    SaveAssignments.prototype.removeSelectedGroup = function(row) {
        if (this.removingElement) {
            return;
        }
        this.removingElement = true;

        var id = row.getAttribute('data-tw-assignCompSave-selected-row-id'),
            list = row.closest('[data-tw-assignCompSave-selected-group]'),
            listItems,
            that = this,
            type = row.getAttribute('data-tw-assignCompSave-selected-row-type');

        this.baskets[type].remove(id).then(function() {
            row.remove();
            listItems = list.querySelectorAll('[data-tw-assignCompSave-selected-row-id]');
            that.setTypeCount(type, listItems);
            that.updateTotalCount();

            if (!listItems.length) {
                list.firstElementChild.classList.add(that.hideClass);
            }

            that.removingElement = false;
        }).catch(function() {
            that.removingElement = false;
        });
    };

    /**
    * Returns config date for constructing user modal list
    * @return {Promise} data
    */
    SaveAssignments.prototype.getUserModalConfig = function() {
        var that = this;

        return new Promise(function(resolve) {
            var data = {
                externalBasket: that.baskets.users,
                key: 'users',
                list: {
                    map: {
                        cols: [{
                            dataPath: 'display_name',
                            headerString: {
                                key: 'fullnameuser',
                                value: ''
                            },
                        }],
                    },
                    service: 'totara_competency_user_index',
                },
                onClosed: function() {
                    that.resetTreeList();
                },
                onSaved: function(modal, items, selectionData) {
                    that.renderItems('users', selectionData);
                },
                primarySearch: {
                    filterKey: 'text',
                    placeholderString: [{
                        component: 'totara_core',
                        key: 'search'
                    }]
                },
                title: [{
                    component: 'totara_core',
                    key: 'selectuserplural'
                }]
            };
            resolve(data);
        });
    };

    /**
    * Returns config date for constructing audience modal list
    * @return {Promise} data
    */
    SaveAssignments.prototype.getAudienceModalConfig = function() {
        var that = this;

        return new Promise(function(resolve) {
            var data = {
                externalBasket: that.baskets.audiences,
                key: 'audiences',
                list: {
                    map: {
                        cols: [{
                            dataPath: 'display_name',
                            headerString: {
                                component: 'totara_cohort',
                                key: 'name'
                            },
                        }],
                    },
                    service: that.services.audiences
                },
                onClosed: function() {
                    that.resetTreeList();
                },
                onSaved: function(modal, items, selectionData) {
                    that.renderItems('audiences', selectionData);
                },
                primarySearch: {
                    filterKey: 'text',
                    placeholderString: [{
                        component: 'totara_core',
                        key: 'search'
                    }]
                },
                title: [{
                    component: 'totara_cohort',
                    key: 'selectcohorts'
                }]
            };
            resolve(data);
        });
    };

    /**
    * Returns config date for constructing organisation modal list
    * @return {Promise} data
    */
    SaveAssignments.prototype.getOrganisationModalConfig = function() {
        var that = this;

        return new Promise(function(resolve, reject) {
            HierarchyEvents.init().then(function(eventData) {
                var options = {
                    externalBasket: that.baskets.organisations,
                    key: 'organisations',
                    crumbtrail: {
                        service: 'hierarchy_organisation_show',
                        stringList: [
                            {
                                component: 'totara_hierarchy',
                                key: 'hierarchy_list:organisation:all',
                            },
                            {
                                component: 'totara_hierarchy',
                                key: 'hierarchy_list:organisation:all_in_framework',
                            }
                        ]
                    },
                    events: eventData,
                    expandable: {
                        args: {include: {crumbs: 1}},
                        service: 'hierarchy_organisation_show',
                        template: 'totara_competency/hierarchy_expanded',
                    },
                    levelToggle: true,
                    list: {
                        map: {
                            cols: [{
                                dataPath: 'fullname',
                                expandedViewTrigger: true,
                                headerString: {
                                    component: 'totara_hierarchy',
                                    key: 'organisation',
                                },
                            }],
                            extraRowData: [{
                                key: 'framework',
                                dataPath: 'frameworkid'
                            }],
                            hasExpandedView: true,
                            hasHierarchy: true,
                        },
                        service: 'hierarchy_organisation_index',
                    },
                    onClosed: function() {
                        that.resetTreeList();
                    },
                    onSaved: function(modal, items, selectionData) {
                        that.renderItems('organisations', selectionData);
                    },
                    primaryDropDown: {
                        filterKey: 'framework',
                        placeholderString: [{
                            component: 'totara_hierarchy',
                            key: 'allframeworks'
                        }],
                        service: 'hierarchy_organisation_framework_index',
                        serviceArgs: {
                            direction: 'asc',
                            filters: [],
                            order: 'sortorder',
                            page: 0
                        },
                        serviceLabelKey: 'fullname'
                    },
                    primarySearch: {
                        filterKey: 'text',
                        placeholderString: [{
                            component: 'totara_core',
                            key: 'search'
                        }]
                    },
                    title: [{
                        component: 'totara_hierarchy',
                        key: 'hierarchy_list:organisation:select',
                    }]
                };
                resolve(options);
            }).catch(function() {
                reject();
            });
        });
    };

    /**
    * Returns config date for constructing position modal list
    * @return {Promise} data
    */
    SaveAssignments.prototype.getPositionModalConfig = function() {
        var that = this;

        return new Promise(function(resolve, reject) {
            HierarchyEvents.init().then(function(eventData) {
                var options = {
                    externalBasket: that.baskets.positions,
                    key: 'positions',
                    crumbtrail: {
                        service: 'hierarchy_position_show',
                        stringList: [
                            {
                                component: 'totara_hierarchy',
                                key: 'hierarchy_list:position:all',
                            },
                            {
                                component: 'totara_hierarchy',
                                key: 'hierarchy_list:position:all_in_framework',
                            }
                        ]
                    },
                    events: eventData,
                    expandable: {
                        args: {include: {crumbs: 1}},
                        service: 'hierarchy_position_show',
                        template: 'totara_competency/hierarchy_expanded',
                    },
                    levelToggle: true,
                    list: {
                        map: {
                            cols: [{
                                dataPath: 'fullname',
                                expandedViewTrigger: true,
                                headerString: {
                                    component: 'totara_hierarchy',
                                    key: 'position',
                                },
                            }],
                            extraRowData: [{
                                key: 'framework',
                                dataPath: 'frameworkid'
                            }],
                            hasExpandedView: true,
                            hasHierarchy: true,
                        },
                        service: 'hierarchy_position_index',
                    },
                    onClosed: function() {
                        that.resetTreeList();
                    },
                    onSaved: function(modal, items, selectionData) {
                        that.renderItems('positions', selectionData);
                    },
                    primaryDropDown: {
                        filterKey: 'framework',
                        placeholderString: [{
                            component: 'totara_hierarchy',
                            key: 'allframeworks'
                        }],
                        service: 'hierarchy_position_framework_index',
                        serviceArgs: {
                            direction: 'asc',
                            filters: [],
                            order: 'sortorder',
                            page: 0
                        },
                        serviceLabelKey: 'fullname'
                    },
                    primarySearch: {
                        filterKey: 'text',
                        placeholderString: [{
                            component: 'totara_core',
                            key: 'search'
                        }]
                    },
                    title: [{
                        component: 'totara_hierarchy',
                        key: 'hierarchy_list:position:select',
                    }]
                };
                resolve(options);
            }).catch(function() {
                reject();
            });
        });
    };

    /**
    * Returns config date for constructing individuals modal list viewer
    * @return {Promise} data
    */
    SaveAssignments.prototype.getIndividualsModalConfig = function() {
        var that = this;
        return new Promise(function(resolve) {
            var options = {
                key: 'individual',
                list: {
                    map: {
                        cols: [{
                            dataPath: 'full_name',
                            headerString: {
                                component: 'core',
                                key: 'fullnameuser',
                            }
                        },
                        {
                            columnTemplate: {
                                template: 'totara_competency/save_user_group_names',
                            },
                            dataPath: 'user_group_names',
                            headerString: {
                                component: 'totara_competency',
                                key: 'sort_user_group_name',
                            }
                        }],
                    },
                    service: 'totara_competency_expand_user_groups_index',
                    serviceArgs: {
                        baskets: {
                            cohort: that.basketKeys.audiences,
                            organisation: that.basketKeys.organisations,
                            position: that.basketKeys.positions,
                            user: that.basketKeys.users,
                        }
                    },
                },
                primarySearch: {
                    filterKey: 'name',
                    placeholderString: [{
                        component: 'totara_core',
                        key: 'search'
                    }]
                },
                title: [{
                    component: 'totara_competency',
                    key: 'browse_selected_user_groups'
                }]
            };
            resolve(options);
        });
    };

    /**
    * Set parent node
    * @param {node} parent
    */
    SaveAssignments.prototype.setParent = function(parent) {
        this.widget = parent;
    };

    /**
    * Set data for each modal type
    * @return {promise}
    */
    SaveAssignments.prototype.getModalData = function() {
        var that = this;

        return new Promise(function(resolve, reject) {
            Promise.all([
                that.getAudienceModalConfig(),
                that.getOrganisationModalConfig(),
                that.getPositionModalConfig(),
                that.getUserModalConfig(),
                that.getIndividualsModalConfig()
            ]).then(function(data) {
                that.modalsData.audiences = data[0];
                that.modalsData.organisations = data[1];
                that.modalsData.positions = data[2];
                that.modalsData.users = data[3];
                that.modalsData.individuals = data[4];
                resolve();
            }).catch(function() {
                reject();
            });
        });
    };

    /**
    * Get required strings for page
    * @return {promise}
    */
    SaveAssignments.prototype.getPageStrings = function() {
        var stringList = [],
            that = this;

        stringList.push({
            'component': 'totara_cohort',
            'key': 'cohorts'
        },
        {
            'component': 'totara_core',
            'key': 'individualplural'
        },
        {
            'component': 'totara_hierarchy',
            'key': 'organisationplural'
        },
        {
            'component': 'totara_hierarchy',
            'key': 'positionplural'
        },
        {
            'component': 'totara_competency',
            'key': 'save_modal_header',
        });

        return new Promise(function(resolve) {
            str.get_strings(stringList).then(function(fetchedStrings) {
                that.strings.audiences = fetchedStrings[0];
                that.strings.users = fetchedStrings[1];
                that.strings.organisations = fetchedStrings[2];
                that.strings.positions = fetchedStrings[3];
                that.strings.selectedGroups = fetchedStrings[4];
                that.strings.confirmationHeader = fetchedStrings[5];
                resolve();
            });
        });
    };

    /**
    * Create modals
    */
    SaveAssignments.prototype.createModals = function() {
        var that = this;

        var modalsPromises = [
            ModalList.adder(that.modalsData.audiences),
            ModalList.adder(that.modalsData.organisations),
            ModalList.adder(that.modalsData.positions),
            ModalList.adder(that.modalsData.users),
            ModalList.viewer(that.modalsData.individuals),
        ];

        Promise.all(modalsPromises).then(function(modals) {
            var a, name, modal;

            for (a = 0; a < modals.length; a++) {
                modal = modals[a];
                name = modal.getKey();
                that.modalLists[name] = modal;
            }

            // Enable select tree
            var selectTree = that.widget.querySelector('[data-tw-assignCompSave-tree] [data-tw-selectorgroup]');
            selectTree.setAttribute('data-tw-selecttree-disabled', 'activate');

            if (that.getTotalCount() > 0) {
                that.enableOverviewActions();
            }

            that.loader.hide();

        }).catch(function(e) {
            notification.exception({
                fileName: 'save_assignments.js',
                message: e[0] + ' modal: ' + e[1],
                name: 'Error adding modal'
            });
        });
    };

    /**
    * initialisation method
    * @param {node} parent
    * @returns {Promise}
    */
    var init = function(parent) {
        return new Promise(function(resolve) {
            var wgt = new SaveAssignments();
            wgt.setParent(parent);
            wgt.initVariables();
            wgt.loader = Loader.init(parent);
            wgt.loader.show();
            wgt.propagatedEvents();
            resolve(wgt);
            wgt.getPageStrings().then(function() {
                wgt.getModalData().then(function() {
                    wgt.createModals();
                });
            });
        });
    };

    return {
        init: init
    };
});

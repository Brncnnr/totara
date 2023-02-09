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
 * @author Marco Song <marco.song@totaralearning.com>
 * @package criteria_othercompetency
 */

define(['core/templates', 'core/notification', 'core/ajax', 'totara_competency/modal_list', 'totara_competency/loader_manager',
'totara_competency/list_framework_hierarchy_events'],
function(templates, notification, ajax, ModalList, Loader, HierarchyEvents) {

    /**
     * Class constructor for CriterionOtherCompetency.
     *
     * @class
     * @constructor
     */
    function CriterionOtherCompetency() {
        if (!(this instanceof CriterionOtherCompetency)) {
            return new CriterionOtherCompetency();
        }

        this.widget = ''; // Parent widget
        this.loader = null; // Loading overlay manager

        /**
         * Criterion data.
         * This object should only contain the data to be sent on the save api endpoints.
         * The variable names MUST correlate to the save endpoint parameters
         */
        this.criterion = {
            id: 0,
            type: 'othercompetency',
            itemids: [],
            aggregation: {
                method: 1,
                reqitems: 1
            },
            singleuse: false,
            expandable: true
        };

        // Saving items from the basket - therefore not stored in criterion
        this.criterionKey = ''; // Unique key to use in bubbled events
        this.competencies = []; // other competencies to complete
        this.competencyAdder = null; // Initialized Adder for other competencies
        this.pwCompetencyId = 0; // Id of the competency this criterion belongs to

        this.endpoints = {
            detail: 'criteria_othercompetency_get_detail',
            competencies: 'totara_competency_competency_index',
        };

        this.domClasses = {
            hidden: 'tw-editAchievementPaths--hidden',
        };

        this.filename = 'othercompetency.js';
    }

    CriterionOtherCompetency.prototype = {
        /**
         * Add event listeners for CriterionOtherCompetency
         */
        events: function() {
            var that = this;

            this.widget.addEventListener('click', function(e) {
                if (!e.target) {
                    return;
                }

                // Add competencies link clicked
                if (e.target.closest('[data-tw-criterionOtherCompetency-addCompetencies]')) {
                    e.preventDefault();
                    that.addCompetencies();

                    // Item remove link clicked
                } else if (e.target.closest('[data-tw-competency-item-remove]')) {
                    e.preventDefault();

                    var competencyNode = e.target.closest('[data-tw-competency-item-value]'),
                        competencyId;

                    if (!competencyNode) {
                        return;
                    }

                    competencyId = competencyNode.getAttribute('data-tw-competency-item-value');
                    that.removeCompetency(competencyId);
                }
            });

            this.widget.addEventListener('change', function(e) {
                if (!e.target) {
                    return;
                }

                // Aggregation method changed
                if (e.target.closest('[data-tw-criterionOtherCompetency-aggregationMethod-changed]')) {
                    e.preventDefault();

                    var newMethod = e.target.closest('[data-tw-criterionOtherCompetency-aggregationMethod-changed]').value;

                    if (that.criterion.aggregation.method != newMethod) {
                        that.setAggregationMethod(newMethod);

                        that.triggerEvent('update', {criterion: that.criterion});
                        that.triggerEvent('dirty', {});
                    }

                    // Aggregation count changed
                } else if (e.target.closest('[data-tw-criterionOtherCompetency-aggregationCount-changed]')) {
                    e.preventDefault();

                    var newCount = e.target.closest('[data-tw-criterionOtherCompetency-aggregationCount-changed]').value;

                    if (that.criterion.aggregation.reqitems != newCount) {
                        that.setAggregationCount(newCount);

                        that.triggerEvent('update', {criterion: that.criterion});
                        that.triggerEvent('dirty', {});
                    }
                }
            });
        },

        /**
         * Set parent
         * @param {node} parent
         */
        setParent: function(parent) {
            this.widget = parent;
        },

        /**
         * Retrieve the criterion detail
         * @param {node} wgt
         * @return {Promise}
         */
        getDetail: function(wgt) {
            var that = this,
                criterionNode = this.widget.closest('[data-tw-editScaleValuePaths-criterion-key]'),
                aggregationNode = criterionNode.querySelector('[data-tw-criterionOtherCompetency-aggregation]'),
                pathwayNode = document.querySelector('[data-tw-editAchievementPaths-competency]');

            return new Promise(function(resolve) {
                if (pathwayNode) {
                    that.pwCompetencyId = pathwayNode.getAttribute('data-tw-editAchievementPaths-competency');
                }

                if (criterionNode) {
                    that.criterionKey = criterionNode.hasAttribute('data-tw-editScaleValuePaths-criterion-key')
                        ? criterionNode.getAttribute('data-tw-editScaleValuePaths-criterion-key')
                        : '';
                    that.criterion.id = criterionNode.hasAttribute('data-tw-editScaleValuePaths-criterion-id')
                        ? criterionNode.getAttribute('data-tw-editScaleValuePaths-criterion-id')
                        : 0;
                }

                // Aggregation
                if (aggregationNode) {
                    that.criterion.aggregation.method = aggregationNode.getAttribute('data-tw-criterionOtherCompetency-aggregation');
                    that.criterion.aggregation.reqitems = aggregationNode.hasAttribute('data-tw-criterionOtherCompetency-aggregation-reqitems')
                        ? aggregationNode.getAttribute('data-tw-criterionOtherCompetency-aggregation-reqitems')
                        : 1;
                }

                that.setAggregationMethod(that.criterion.aggregation.method);
                that.setAggregationCount(that.criterion.aggregation.reqitems);

                // Competencies
                that.setCompetencies(wgt);
                that.showHideNotEnoughCompetency();

                Promise.all([that.initCompetencyAdder()]).then(function() {
                    that.triggerEvent('update', {criterion: that.criterion});
                    resolve();
                }).catch(function() {
                    // Failed
                });
            });
        },

        /**
         * Set the aggregation method
         * @param {int} method New aggregation method (method constant as defined in totara/criteria/classes/criterion.php)
         */
        setAggregationMethod: function(method) {
            var methodNode = this.widget.querySelector('[data-tw-criterionOtherCompetency-aggregationMethod="' + method + '"]'),
                methodInput = methodNode.querySelector('[data-tw-criterionOtherCompetency-aggregationMethod-changed]'),
                countInput = this.widget.querySelector('[data-tw-criterionOtherCompetency-aggregationCount-changed]'),
                testCountInput = methodNode.querySelector('[data-tw-criterionOtherCompetency-aggregationCount-changed]');

            this.criterion.aggregation.method = method;

            if (methodInput) {
                methodInput.checked = true;
            }

            if (countInput) {
                // To avoid hardcoding that method 1 == all, etc, we use the fact that the reqItems input
                // is in the same div as the 'any' radio button to determine whether to disable or enable
                // the count input
                countInput.disabled = testCountInput ? false : true;
            }

            this.showHideNotEnoughCompetency();
            this.hideAggregationCountInfo();
        },

        /**
         * Set the aggregation required item
         * @param {int} reqItems Required item count
         */
        setAggregationCount: function(reqItems) {
            var countInput = this.widget.querySelector('[data-tw-criterionOtherCompetency-aggregationCount-changed]'),
                newValue = parseInt(reqItems) || 0;

            this.criterion.aggregation.reqitems = newValue < 1 ? 1 : newValue;

            if (countInput) {
                countInput.value = this.criterion.aggregation.reqitems;
            }

            this.showHideNotEnoughCompetency();
            // We also want to show the information if the user selected something invalid and we reset it
            if (newValue < 1) {
                this.showAggregationCountInfo();
            } else {
                this.hideAggregationCountInfo();
            }
        },

        /**
         * Set and display the competencies to be completed
         *
         * @param {node} wgt
         */
        setCompetencies: function(wgt) {
            var competencyNodes = wgt.querySelectorAll('[data-tw-competency-item-value]'),
                comptencyId;

            this.criterion.itemids = [];

            for (var a = 0; a < competencyNodes.length; a++) {
                comptencyId = parseInt(competencyNodes[a].getAttribute('data-tw-competency-item-value')
                    ? competencyNodes[a].getAttribute('data-tw-competency-item-value')
                    : 0);
                if (comptencyId) {
                    this.criterion.itemids.push(comptencyId);
                }
            }
        },

        /**
         * Create an empty othercompetency criterion instance with the key
         *
         * @param {int} key
         * @return {Promise}
         */
        odlCreateEmptyCriterion: function() {
            // Ensure the basket is empty
            return new Promise(function(resolve) {
                resolve({
                    results: {
                        id: 0,
                        items: [],
                        aggregation: {
                            method: 1,
                            reqitems: 1
                        }
                    }
                });
            });
        },

        /**
         * Initialise the competency adder
         *
         * @return {Promise}
         */
        initCompetencyAdder: function() {
            var that = this;

            return new Promise(function(resolve, reject) {
                that.getCompetencyAdderDate().then(function(data) {
                    ModalList.adder(data).then(function(modal) {
                        that.competencyAdder = modal;
                        resolve(modal);
                    }).catch(function() {
                        reject();
                    });
                }).catch(function() {
                    reject();
                });
            });
        },

        /**
         * Get the competency adder data with hierarchy support
         *
         * @return {Promise}
         */
        getCompetencyAdderDate: function() {
            var that = this;

            return new Promise(function(resolve, reject) {
                HierarchyEvents.init().then(function(eventData) {
                    var adderData = {
                        key: 'competencies',
                        crumbtrail: {
                            service: 'totara_competency_competency_show',
                            stringList: [
                                {
                                    component: 'criteria_othercompetency',
                                    key: 'hierarchy_list_competency_all',
                                },
                                {
                                    component: 'criteria_othercompetency',
                                    key: 'hierarchy_list_competency_all_in_framework',
                                }
                            ]
                        },
                        events: eventData,
                        expandable: {
                            args: {include: {crumbs: 1}},
                            service: 'totara_competency_competency_show',
                            template: 'totara_competency/hierarchy_expanded',
                        },
                        levelToggle: true,
                        list: {
                            map: {
                                cols: [{
                                    dataPath: 'fullname',
                                    expandedViewTrigger: true,
                                    headerString: {
                                        component: 'criteria_othercompetency',
                                        key: 'select_competencies',
                                    },
                                }],
                                extraRowData: [{
                                    key: 'framework',
                                    dataPath: 'frameworkid'
                                }],
                                hasExpandedView: true,
                                hasHierarchy: true,
                            },
                            service: 'totara_competency_competency_index',
                        },
                        onSaved: function(modal, items, selectionData) {
                            that.updateCompetencies(selectionData);
                        },
                        primaryDropDown: {
                            filterKey: 'framework',
                            placeholderString: [{
                                component: 'criteria_othercompetency',
                                key: 'all_frameworks'
                            }],
                            service: 'totara_competency_get_frameworks',
                            serviceArgs: {},
                            serviceLabelKey: 'fullname'
                        },
                        primarySearch: {
                            filterKey: 'text',
                            placeholderString: [{
                                component: 'totara_core',
                                key: 'search'
                            }],
                        },
                        title: [{
                            component: 'criteria_othercompetency',
                            key: 'hierarchy_list_competency_select',
                        }],
                    };
                    resolve(adderData);
                }).catch(function() {
                    reject();
                });
            });
        },

        /**
         * Open the adder to add competencies
         */
        addCompetencies: function() {
            // We don't want to filter out the competency to which the criteria belong to allow for navigation to its child competencies
            // We rather want this competency's id in the list as if it was selected previously to prevent selection
            var that = this,
                selectedIds = this.criterion.itemids.slice(0);

            selectedIds.push(that.pwCompetencyId);
            if (!this.competencyAdder) {
                this.initCompetencyAdder().then(function() {
                    that.competencyAdder.show(selectedIds);
                }).catch(function(e) {
                    e.fileName = that.filename;
                    e.name = 'Error initialsing the competency adder';
                    notification.exception(e);
                });
            } else {
                this.competencyAdder.show(selectedIds);
            }
        },

        /**
         * Update the displayed competencies
         *
         * @param  {[Object]} competencies Selected competencies
         */
        updateCompetencies: function(competencies) {
            var that = this,
                competenciesTarget = that.widget.querySelector('[data-tw-criterionOtherCompetency-competencies]'),
                id,
                fullname,
                idIndex,
                competenciesPromiseArr = [],
                templateData = {};

            for (var a = 0; a < competencies.length; a++) {
                id = competencies[a].id;
                if (id != that.pwCompetencyId) {
                    fullname = competencies[a].fullname;

                    idIndex = this.criterion.itemids.indexOf(id);

                    if (idIndex < 0) {
                        this.criterion.itemids.push(id);

                        templateData = {type: 'competency', value: id, text: fullname};
                        competenciesPromiseArr.push(templates.renderAppend('totara_criteria/partial_item', templateData, competenciesTarget));
                    }
                }
            }

            if (competenciesPromiseArr.length > 0) {
                Promise.all(competenciesPromiseArr).then(function() {
                    that.showHideNotEnoughCompetency();

                    that.triggerEvent('update', {criterion: that.criterion});
                    that.triggerEvent('dirty', {});
                }).catch(function(e) {
                    e.fileName = that.filename;
                    e.name = 'Showing competencies';
                    notification.exception(e);
                });
            }
        },

        removeCompetency: function(id) {
            id = parseInt(id);

            var that = this,
                competencyTarget = that.widget.querySelector('[data-tw-competency-item-value="' + id + '"]'),
                idIndex = this.criterion.itemids.indexOf(id);

            if (idIndex >= 0) {
                this.criterion.itemids.splice(idIndex, 1);

                if (competencyTarget) {
                    competencyTarget.remove();
                }
            }

            // Show nocompetencies warning
            that.showHideNotEnoughCompetency();

            that.triggerEvent('update', {criterion: that.criterion});
            that.triggerEvent('dirty', {});
        },

        /**
         * Show or hide the No Criteria warning depending on the number of items
         */
        showHideNotEnoughCompetency: function() {
            var targetNone = this.widget.querySelector('[data-tw-criterionOtherCompetency-error="noothercompetency"]'),
                targetNotEnough = this.widget.querySelector('[data-tw-criterionOtherCompetency-error="notenoughothercompetency"]');

            if (!targetNone || !targetNotEnough) {
                return;
            }

            if (this.criterion.itemids.length > 0) {
                // Hide no courses warning
                targetNone.classList.add(this.domClasses.hidden);
                if (this.criterion.aggregation.method == 1) {
                    // Hide not enough courses warning
                    targetNotEnough.classList.add(this.domClasses.hidden);
                } else {
                    if (this.criterion.aggregation.reqitems > this.criterion.itemids.length) {
                        targetNotEnough.classList.remove(this.domClasses.hidden);
                    } else {
                        targetNotEnough.classList.add(this.domClasses.hidden);
                    }
                }
            } else {
                targetNone.classList.remove(this.domClasses.hidden);
                targetNotEnough.classList.add(this.domClasses.hidden);
            }
        },

        /**
         * Show the aggregation count information
         */
        showAggregationCountInfo: function() {
            var target = this.widget.querySelector('[data-tw-criterionOtherCompetency-info="aggregation-count"]');
            if (!target) {
                return;
            }
            target.classList.remove(this.domClasses.hidden);
        },

        /**
         * Hide the aggregation count information
         */
        hideAggregationCountInfo: function() {
            var target = this.widget.querySelector('[data-tw-criterionOtherCompetency-info="aggregation-count"]');
            if (!target) {
                return;
            }
            target.classList.add(this.domClasses.hidden);
        },

        /**
         * Trigger event
         *
         * @param {string} eventName
         * @param {object} data
         */
        triggerEvent: function(eventName, data) {
            data.key = this.criterionKey;

            var propagateEvent = new CustomEvent('totara_criteria/criterion:' + eventName, {
                bubbles: true,
                detail: data
            });

            this.widget.dispatchEvent(propagateEvent);
        },
    };

    /**
     * Initialisation method
     *
     * @param {node} parent
     * @returns {Object} promise
     */
    var init = function(parent) {
        return new Promise(function(resolve) {
            var wgt = new CriterionOtherCompetency();
            wgt.setParent(parent);
            wgt.events();
            wgt.loader = Loader.init(parent);
            wgt.loader.show();
            resolve(wgt);

            M.util.js_pending('criterionOtherCompetency');
            wgt.getDetail(parent).then(function() {
                wgt.loader.hide();
                M.util.js_complete('criterionOtherCompetency');
            }).catch(function() {
                // Failed
            });
        });
    };

    return {
        init: init
    };
});

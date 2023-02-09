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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package criteria_childcompetency
 */

define(['core/ajax', 'core/notification', 'totara_competency/loader_manager'],
function(ajax, notification, Loader) {

    /**
     * Class constructor for CriterionChildCompetency.
     *
     * @class
     * @constructor
     */
    function CriterionChildCompetency() {
        if (!(this instanceof CriterionChildCompetency)) {
            return new CriterionChildCompetency();
        }

        this.widget = ''; // Parent widget
        this.competencyKey = 'competency_id'; // Metadata key for competency id
        this.criterionKey = ''; // Unique key to use in bubbled event
        this.loader = null; // Loading overlay manager

        /**
         * Criterion data.
         * This object should only contain the data to be sent on the save api endpoint.
         * The variable names MUST correlate to the save endpoint parameters
         */
        this.criterion = {
            type: 'childcompetency',
            metadata: [],
            aggregation: {
                method: 1,
                reqitems: 1
            },
            singleuse: false,
            expandable: true
        };

        this.domClasses = {
            hidden: 'tw-editAchievementPaths--hidden',
        };
    }

    CriterionChildCompetency.prototype = {

        /**
         * Add event listeners for CriterionChildCompetency
         */
        events: function() {
            var that = this;

            this.widget.addEventListener('change', function(e) {
                if (!e.target) {
                    return;
                }

                // Aggregation method changed
                if (e.target.closest('[data-tw-criterionChildCompetency-aggregationMethod-changed]')) {
                    e.preventDefault();

                    var newMethod = e.target.closest('[data-tw-criterionChildCompetency-aggregationMethod-changed]').value;

                    if (that.criterion.aggregation.method !== newMethod) {
                        that.setAggregationMethod(newMethod);

                        that.triggerEvent('update', {criterion: that.criterion});
                        that.triggerEvent('dirty', {});
                    }

                // Aggregation count changed
                } else if (e.target.closest('[data-tw-criterionChildCompetency-aggregationCount-changed]')) {
                    e.preventDefault();

                    var newCount = e.target.closest('[data-tw-criterionChildCompetency-aggregationCount-changed]').value;

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
         *
         * @param {node} parent
         */
        setParent: function(parent) {
            this.widget = parent;
        },

        /**
         * Retrieve the criterion detail
         * @return {Promise}
         */
        getDetail: function() {
            var that = this,
                criterionNode = this.widget.closest('[data-tw-editScaleValuePaths-criterion-key]'),
                aggregationNode = criterionNode.querySelector('[data-tw-criterionChildCompetency-aggregation]'),
                competencyIdNode = document.querySelector('[data-tw-editAchievementPaths-competency]');


            return new Promise(function(resolve) {
                if (criterionNode) {
                    that.criterionKey = criterionNode.hasAttribute('data-tw-editScaleValuePaths-criterion-key')
                        ? criterionNode.getAttribute('data-tw-editScaleValuePaths-criterion-key')
                        : 0;
                    that.criterion.id = criterionNode.hasAttribute('data-tw-editScaleValuePaths-criterion-id')
                        ? criterionNode.getAttribute('data-tw-editScaleValuePaths-criterion-id')
                        : 0;
                }

                if (competencyIdNode) {
                    var competencyId = competencyIdNode.getAttribute('data-tw-editAchievementPaths-competency')
                        ? competencyIdNode.getAttribute('data-tw-editAchievementPaths-competency') : 1;

                    that.criterion.metadata = [{
                        metakey: that.competencyKey,
                        metavalue: competencyId
                    }];
                }

                // Aggregation
                if (aggregationNode) {
                    that.criterion.aggregation.method = aggregationNode.getAttribute('data-tw-criterionChildCompetency-aggregation');
                    that.criterion.aggregation.reqitems = aggregationNode.hasAttribute('data-tw-criterionChildCompetency-aggregation-reqitems')
                        ? aggregationNode.getAttribute('data-tw-criterionChildCompetency-aggregation-reqitems')
                        : 1;
                }

                that.setAggregationMethod(that.criterion.aggregation.method);
                that.setAggregationCount(that.criterion.aggregation.reqitems);

                that.triggerEvent('update', {criterion: that.criterion});
                resolve();
            });
        },

        /**
         * Set the aggregation method
         *
         * @param {int} method New aggregation method
         */
        setAggregationMethod: function(method) {
            var methodNode = this.widget.querySelector('[data-tw-criterionChildCompetency-aggregationMethod="' + method + '"]'),
                methodInput = methodNode.querySelector('[data-tw-criterionChildCompetency-aggregationMethod-changed]'),
                countInput = this.widget.querySelector('[data-tw-criterionChildCompetency-aggregationCount-changed]'),
                testCountInput = methodNode.querySelector('[data-tw-criterionChildCompetency-aggregationCount-changed]');

            this.criterion.aggregation.method = method;

            if (methodInput) {
                methodInput.checked = true;
            }

            if (countInput) {
                // To avoid hard coding that method 1 == all, etc, we use the fact that the reqItems input
                // is in the same div as the 'any' radio button to determine whether to disable or enable
                // the count input
                countInput.disabled = testCountInput ? false : true;
            }

            this.hideAggregationCountInfo();
        },

        /**
         * Set the aggregation reqItems
         *
         * @param {int} reqItems Required item count
         */
        setAggregationCount: function(reqItems) {
            var countInput = this.widget.querySelector('[data-tw-criterionChildCompetency-aggregationCount-changed]'),
                newValue = parseInt(reqItems) || 0;

            this.criterion.aggregation.reqitems = newValue < 1 ? 1 : newValue;

            if (countInput) {
                countInput.value = this.criterion.aggregation.reqitems;
            }

            // We want to show the information if the user selected something invalid and we reset it
            if (newValue < 1) {
                this.showAggregationCountInfo();
            } else {
                this.hideAggregationCountInfo();
            }
        },

        /**
         * Show the aggregation count information
         */
        showAggregationCountInfo: function() {
            var target = this.widget.querySelector('[data-tw-criterionChildCompetency-info="aggregation-count"]');
            if (!target) {
                return;
            }
            target.classList.remove(this.domClasses.hidden);
        },

        /**
         * Hide the aggregation count information
         */
        hideAggregationCountInfo: function() {
            var target = this.widget.querySelector('[data-tw-criterionChildCompetency-info="aggregation-count"]');
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
            var wgt = new CriterionChildCompetency();
            wgt.setParent(parent);
            wgt.events();
            wgt.loader = Loader.init(parent);
            wgt.loader.show();
            resolve(wgt);

            M.util.js_pending('criterionChildCompetency');
            wgt.getDetail().then(function() {
                wgt.loader.hide();
                M.util.js_complete('criterionChildCompetency');
            }).catch(function() {
                // Failed
            });
        });
    };

    return {
        init: init
    };
 });

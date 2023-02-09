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
 * @package criteria_coursecompletion
 */

define(['core/templates', 'core/notification', 'core/ajax', 'totara_competency/modal_list', 'totara_competency/loader_manager'],
function(templates, notification, ajax, ModalList, Loader) {

    /**
     * Class constructor for CriterionCourseCompletion.
     *
     * @class
     * @constructor
     */
    function CriterionCourseCompletion() {
        if (!(this instanceof CriterionCourseCompletion)) {
            return new CriterionCourseCompletion();
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
            type: 'coursecompletion',
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
        this.courseAdder = null; // Initialized adder for courses

        this.endpoints = {
            detail: 'criteria_coursecompletion_get_detail',
            courses: 'totara_competency_get_courses',
            courseCategories: 'totara_competency_get_categories',
        };

        this.domClasses = {
            hidden: 'tw-editAchievementPaths--hidden',
        };

        this.filename = 'coursecompletion.js';
    }

    CriterionCourseCompletion.prototype = {

        /**
         * Add event listeners for CriterionCourseCompletions
         */
        events: function() {
            var that = this;

            this.widget.addEventListener('click', function(e) {
                if (!e.target) {
                    return;
                }

                // Add courses link clicked
                if (e.target.closest('[data-tw-criterionCourseCompletion-addCourses]')) {
                    e.preventDefault();
                    that.addCourses();

                // Item remove link clicked
                } else if (e.target.closest('[data-tw-course-item-remove]')) {
                    e.preventDefault();

                    var courseNode = e.target.closest('[data-tw-course-item-value]'),
                        courseId;

                    if (!courseNode) {
                        return;
                    }

                    courseId = courseNode.getAttribute('data-tw-course-item-value');
                    that.removeCourse(courseId);
                }
            });

            this.widget.addEventListener('change', function(e) {
                if (!e.target) {
                    return;
                }

                // Aggregation method changed
                if (e.target.closest('[data-tw-criterionCourseCompletion-aggregationMethod-changed]')) {
                    e.preventDefault();

                    var newMethod = e.target.closest('[data-tw-criterionCourseCompletion-aggregationMethod-changed]').value;

                    if (that.criterion.aggregation.method != newMethod) {
                        that.setAggregationMethod(newMethod);

                        that.triggerEvent('update', {criterion: that.criterion});
                        that.triggerEvent('dirty', {});
                    }

                // Aggregation count changed
                } else if (e.target.closest('[data-tw-criterionCourseCompletion-aggregationCount-changed]')) {
                    e.preventDefault();

                    var newCount = e.target.closest('[data-tw-criterionCourseCompletion-aggregationCount-changed]').value;

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
         *
         * @param {node} wgt
         * @return {Promise}
         */
        getDetail: function(wgt) {
            var that = this,
                criterionNode = wgt.closest('[data-tw-editScaleValuePaths-criterion-key]'),
                aggregationNode = criterionNode.querySelector('[data-tw-criterionCourseCompletion-aggregation]');

            return new Promise(function(resolve) {
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
                    that.criterion.aggregation.method = aggregationNode.getAttribute('data-tw-criterionCourseCompletion-aggregation');
                    that.criterion.aggregation.reqitems = aggregationNode.hasAttribute('data-tw-criterionCourseCompletion-aggregation-reqitems')
                        ? aggregationNode.getAttribute('data-tw-criterionCourseCompletion-aggregation-reqitems')
                        : 1;
                }

                that.setAggregationMethod(that.criterion.aggregation.method);
                that.setAggregationCount(that.criterion.aggregation.reqitems);

                // Courses
                that.setCourses(wgt);
                that.showHideNotEnoughCourses();

                Promise.all([that.initCourseAdder()]).then(function() {
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
            var methodNode = this.widget.querySelector('[data-tw-criterionCourseCompletion-aggregationMethod="' + method + '"]'),
                methodInput = methodNode.querySelector('[data-tw-criterionCourseCompletion-aggregationMethod-changed]'),
                countInput = this.widget.querySelector('[data-tw-criterionCourseCompletion-aggregationCount-changed]'),
                testCountInput = methodNode.querySelector('[data-tw-criterionCourseCompletion-aggregationCount-changed]');

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

            this.showHideNotEnoughCourses();
            this.hideAggregationCountInfo();
        },

        /**
         * Set the aggregation required item
         * @param {int} reqItems Required item count
         */
        setAggregationCount: function(reqItems) {
            var countInput = this.widget.querySelector('[data-tw-criterionCourseCompletion-aggregationCount-changed]'),
                newValue = parseInt(reqItems) || 0;

            this.criterion.aggregation.reqitems = newValue < 1 ? 1 : newValue;

            if (countInput) {
                countInput.value = this.criterion.aggregation.reqitems;
            }

            this.showHideNotEnoughCourses();
            // We want to show the warning if the user selected something invalid and we reset it
            if (newValue < 1) {
                this.showAggregationCountInfo();
            } else {
                this.hideAggregationCountInfo();
            }
        },

        /**
         * Retrieve course detail from the dom
         *
         * @param {node} wgt
         */
        setCourses: function(wgt) {
            var courseNodes = wgt.querySelectorAll('[data-tw-course-item-value]'),
                courseId;

            this.criterion.itemids = [];

            for (var a = 0; a < courseNodes.length; a++) {
                courseId = parseInt(courseNodes[a].getAttribute('data-tw-course-item-value')
                    ? courseNodes[a].getAttribute('data-tw-course-item-value')
                    : 0);
                if (courseId) {
                    this.criterion.itemids.push(courseId);
                }
            }
        },


        /**
         * Initialise the course adder
         *
         * @return {Promise}
         */
        initCourseAdder: function() {
            var that = this;

            return new Promise(function(resolve) {
                var adderData = {
                    key: 'courseAdder_' + that.criterionKey,
                    title: [{
                        key: 'select_courses',
                        component: 'criteria_coursecompletion'
                    }],
                    list: {
                        map: {
                            cols: [{
                                dataPath: 'fullname',
                                headerString: {
                                    key: 'select_courses',
                                    component: 'totara_competency',
                                },
                            }],
                        },
                        service: that.endpoints.courses,
                    },
                    primaryDropDown: {
                        filterKey: 'category',
                        serviceLabelKey: 'fullname',
                        placeholderString: [{
                            component: 'totara_competency',
                            key: 'all_categories',
                        }],
                        service: that.endpoints.courseCategories,
                        serviceArgs: {}
                    },
                    primarySearch: {
                        filterKey: 'name',
                        placeholderString: [{
                            component: 'totara_competency',
                            key: 'search_courses'
                        }]
                    },
                    onSaved: function(adder, courseIds, courseData) {
                        that.updateCourses(courseData,courseIds);
                    },
                };

                ModalList.adder(adderData).then(function(modal) {
                    that.courseAdder = modal;
                    resolve(modal);
                }).catch(function(e) {
                    notification.exception({
                        fileName: that.filename,
                        message: e[0] + ' modal: ' + e[1],
                        name: 'Error loading modal list adder'
                    });
                });
            });
        },

        /**
         * Open the adder to add courses
         */
        addCourses: function() {
            var that = this;

            if (!this.courseAdder) {
                this.initCourseAdder().then(function() {
                    that.courseAdder.show(that.criterion.itemids);
                }).catch(function(e) {
                    e.fileName = that.filename;
                    e.name = 'Error initialsing the course adder';
                    notification.exception(e);
                });
            } else {
                this.courseAdder.show(this.criterion.itemids);
            }
        },

        /**
         * Update the displayed courses
         *
         * @param  {[Object]} courses Selected courses
         */
        updateCourses: function(courses, courseIds) {
            var that = this,
                coursesTarget = that.widget.querySelector('[data-tw-criterionCourseCompletion-courses]'),
                id,
                fullname,
                idIndex,
                coursesPromiseArr = [],
                templateData = {};

            for (var i = 0; i < courseIds.length; i++) {
              id = courseIds[i];

              // Find the course fullname based on the current id
              for (var j = 0; j < courses.length; j++) {
                if (courses[j].id == id) {
                  fullname = courses[j].fullname;
                  break;
                }
              }

              idIndex = this.criterion.itemids.indexOf(id);

              if (idIndex < 0) {
                this.criterion.itemids.push(id);

                templateData = { type: 'course', value: id, text: fullname };
                coursesPromiseArr.push(
                  templates.renderAppend(
                    'totara_criteria/partial_item',
                    templateData,
                    coursesTarget
                  )
                );
              }
            }

            if (coursesPromiseArr.length > 0) {
                Promise.all(coursesPromiseArr).then(function() {
                    that.showHideNotEnoughCourses();

                    that.triggerEvent('update', {criterion: that.criterion});
                    that.triggerEvent('dirty', {});
                }).catch(function(e) {
                    e.fileName = that.filename;
                    e.name = 'Showing courses';
                    notification.exception(e);
                });
            }
        },

        removeCourse: function(id) {
            id = parseInt(id);

            var that = this,
                coursesTarget = that.widget.querySelector('[data-tw-course-item-value="' + id + '"]'),
                idIndex = this.criterion.itemids.indexOf(id);

            if (idIndex >= 0) {
                this.criterion.itemids.splice(idIndex, 1);
                if (coursesTarget) {
                    coursesTarget.remove();
                }
            }

            // Show nocourses warning
            that.showHideNotEnoughCourses();

            that.triggerEvent('update', {criterion: that.criterion});
            that.triggerEvent('dirty', {});
        },

        /**
         * Show or hide the 'No Courses' or 'Not enough courses' warning depending on the number of items
         */
        showHideNotEnoughCourses: function() {
            var targetNone = this.widget.querySelector('[data-tw-criterionCourseCompletion-error="nocourses"]'),
                targetNotEnough = this.widget.querySelector('[data-tw-criterionCourseCompletion-error="notenoughcourses"]');
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
                    if (
                        this.criterion.aggregation.reqitems > this.criterion.itemids.length
                    ) {
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
            var target = this.widget.querySelector('[data-tw-criterionCourseCompletion-info="aggregation-count"]');
            if (!target) {
                return;
            }
            target.classList.remove(this.domClasses.hidden);
        },

        /**
         * Hide the aggregation count information
         */
        hideAggregationCountInfo: function() {
            var target = this.widget.querySelector('[data-tw-criterionCourseCompletion-info="aggregation-count"]');
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
            var wgt = new CriterionCourseCompletion();
            wgt.setParent(parent);
            wgt.events();
            wgt.loader = Loader.init(parent);
            wgt.loader.show();
            resolve(wgt);

            M.util.js_pending('criterionCourseCompletion');
            wgt.getDetail(parent).then(function() {
                wgt.loader.hide();
                M.util.js_complete('criterionCourseCompletion');
            }).catch(function() {
                // Failed
            });
        });
    };

    return {
        init: init
    };
});

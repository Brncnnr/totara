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

define(['totara_competency/basket_list', 'totara_competency/list_framework_hierarchy_events'],
function(ListBase, HierarchyEvents) {

    /**
     * Class constructor for the create assignment.
     * @class
     * @constructor
     */
    function Assignment() {
        if (!(this instanceof Assignment)) {
            return new Assignment();
        }
    }

    Assignment.prototype = new ListBase();

    /**
    * Extend base functions
    * @param {Object} events
    */
    Assignment.prototype.setExtendingFunctions = function(events) {
        var that = this;

        // If we don't have custom events, skip
        if (!events) {
            return;
        }

        // Loop through event object
        Object.keys(events).forEach(function(groupKey) {
            // If the key matches an existing reference
            if (that[groupKey]) {
                // Loop through each function in group
                for (var key in events[groupKey]) {
                    if (events[groupKey].hasOwnProperty(key) && typeof events[groupKey][key] == 'function') {
                        that[groupKey][key] = events[groupKey][key].bind(that);
                    }
                }
            }
        });
    };

    /**
    * Additional init function
    */
    Assignment.prototype.initExtend = function() {
        var that = this;
        HierarchyEvents.init().then(function(eventData) {
            that.setExtendingFunctions(eventData);
        });
    };

    /**
     * List mapping properties
     * @returns {Object} mapping structure
     */
    var listMapping = function() {
        return {
            cols: [
                {
                    dataPath: 'fullname',
                    expandedViewTrigger: true,
                    headerString: {
                        component: 'totara_competency',
                        key: 'header_competency',
                    },
                },
                {
                    dataPath: 'assignments_count',
                    headerString: {
                        component: 'totara_competency',
                        key: 'assigned_user_groups',
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
                },
            ],
            extraRowData: [
                {
                    key: 'framework',
                    dataPath: 'frameworkid'
                }
            ],
            hasExpandedView: true,
            hasHierarchy: true
        };
    };

    /**
     * initialisation method
     * @param {node} parent
     * @returns {Promise}
     */
    var init = function(parent) {
        return new Promise(function(resolve) {
            var wgt = new Assignment();

            var data = {
                basketKey: 'totara_competency_create_assignment',
                basketType: 'session',
                crumbtrail: {
                    service: 'totara_competency_competency_show',
                    stringList: [
                        {
                            component: 'totara_competency',
                            key: 'all_competencies',
                        },
                        {
                            component: 'totara_competency',
                            key: 'all_competencies_framework',
                        }
                    ]
                },
                list: {
                    map: listMapping(),
                    service: 'totara_competency_competency_index'
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
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package totara_job
 * @module totara_job/job_management_listing
 */
define(['core/notification', 'core/str', 'core/webapi'], function(Notification, Str, WebAPI) {

    /**
     * The component this module belongs to.
     * @type {string}
     */
    const COMPONENT = 'totara_job';

    /**
     * Services used by this module.
     * @type {{deleteAssignment: string, getAssignment: string, sortAssignments: string}}
     */
    const SERVICE = {
        getAssignment: 'totara_job_assignment',
        deleteAssignment: 'totara_job_delete_assignment',
        sortAssignments: 'totara_job_sort_assignments'
    };

    /**
     * Actions for this module.
     * @type {{deleteItem: string, up: string, down: string}}
     */
    const ACTIONS = {
        up: 'up',
        down: 'down',
        deleteItem: 'delete'
    };

    /**
     * Helper functions for this module.
     * @type {{
     *      itemIds: (function(HTMLElement): Array),
     *      itemIndex: (function(Int, Array): number),
     *      jobListItem: (function(HTMLElement, Integer): HTMLLIElement),
     *      containers: (function(Integer): NodeListOf<Element>),
     *      moveJobItemUp: (function(HTMLElement, Integer): void)
     * }}
     */
    const HELPER = {
        /**
         * Returns the container element for the given user.
         * @param {Integer} userid
         * @returns {HTMLElement}
         */
        containers: function(userid) {
            return document.querySelectorAll('[data-enhance="job-management-listing"][data-enhanced="false"][data-userid="' + userid + '"]');
        },

        /**
         * Returns the job list item for the given job id.
         * @param {HTMLElement} container
         * @param {Integer} itemId
         * @returns {HTMLLIElement}
         */
        jobListItem: function(container, itemId) {
            var itema = container.querySelector('ul a.editjoblink[data-id="' + itemId + '"]'),
                li;
            if (!itema) {
                Notification.alert('Could not find job list item link'); // Coding exception: this should never happen.
                return false;
            }
            li = itema.closest('li');
            if (!li) {
                Notification.alert('Could not find job list item'); // Coding exception: this should never happen.
                return false;
            }
            return li;
        },

        /**
         * Moves the given item up.
         * @param {HTMLElement} container
         * @param {Integer} itemId
         * @param {Boolean|String} highlight Either true of 'current' to highlight the top node, anything else to highlight the bottom.
         */
        moveJobItemUp: function(container, itemId, highlight) {
            var li = HELPER.jobListItem(container, itemId),
                ul = li.closest('ul'),
                libefore = li.previousElementSibling;
            if (ul === null || libefore === null) {
                // Can't find the UL or this is the first node. Nothing to do here.
                return;
            }
            ul.insertBefore(li, libefore);

            if (highlight === true || highlight === 'current') {
                highlight = li;
            } else {
                highlight = libefore;
            }
            highlight.classList.add('highlight');
            setTimeout(function() {
                highlight.classList.remove('highlight');
            }, 500);
        },

        /**
         * Returns an array of all itemIds that appear in the job list.
         * @param {HTMLElement} container
         * @returns {Array}
         */
        itemIds: function(container) {
            var data = [],
                items = container.querySelectorAll('ul.joblist > li > a'),
                i = 0;
            for (i = 0; i < items.length; i++) {
                data.push(items[i].dataset.id);
            }
            return data;
        },

        /**
         * Finds the index of the given item
         * @param {Int} needle
         * @param {Array} haystack
         * @returns {index|*|number}
         */
        itemIndex: function(needle, haystack) {
            return haystack.findIndex(function(id) {
                return (id.toString() === needle.toString());
            });
        }
    };

    /**
     * ListManager class.
     * @param {Integer} userid
     * @param {HTMLElement} container
     * @constructor
     */
    function ListManager(userid, container) {
        var self = this;

        this.userid = userid;
        this.container = container;
        this.container.setAttribute('data-enhanced', 'true');
        this.container.addEventListener('click', function(ev) {
            if (!ev.target) {
                return;
            }
            var anchor = ev.target.closest('a[data-action]'),
                id, li, link;
            if (anchor) {
                ev.preventDefault();
                ev.stopPropagation();
                li = anchor.closest('li');
                if (!li) {
                    Notification.alert('Could not find clicked job list item'); // Coding exception: this should never happen.
                    return;
                }
                link = li.querySelector('a.editjoblink');
                if (!link) {
                    Notification.alert('Could not find clicked job list item link'); // Coding exception: this should never happen.
                    return;
                }
                id = link.dataset.id;
                if (!id) {
                    Notification.alert('Could not find clicked job assignment id'); // Coding exception: this should never happen.
                    return;
                }
                switch (anchor.dataset.action) {
                    case ACTIONS.up:
                        self.moveAssignmentUp(id);
                        break;
                    case ACTIONS.down:
                        self.moveAssignmentDown(id);
                        break;
                    case ACTIONS.deleteItem:
                        self.confirmDelete(id);
                        break;
                    // Equivalent of coding_exception, should never happen so no translation.
                    default:
                        Notification.alert('No valid action found');
                        break;
                }
            }

        });
    }

    /**
     * The id of the user this list belongs to.
     * @type {Integer}
     */
    ListManager.prototype.userid = null;

    /**
     * The container element, set during construction.
     * @type {HTMLElement}
     */
    ListManager.prototype.container = null;

    /**
     * Confirms that users intent to delete this job assignment.
     * @param {Integer} itemId
     */
    ListManager.prototype.confirmDelete = function(itemId) {
        var self = this;
        M.util.js_pending(SERVICE.getAssignment);
        WebAPI.call({
            operationName: SERVICE.getAssignment,
            variables: {
                assignmentid: itemId
            }
        }).then(
            function(data) {
                var ja = data[SERVICE.getAssignment],
                    strings = [
                        {key: 'deletejobassignment', component: COMPONENT, param: null, lang: null},
                        {key: 'confirmdeletejobassignment', component: COMPONENT, param: ja.fullname, lang: null},
                        {key: 'yesdelete', component: 'totara_core', param: null, lang: null},
                        {key: 'cancel', component: 'core', param: null, lang: null}
                    ];
                if (ja.staffcount && ja.tempstaffcount) {
                    strings.push({key: 'warningstaffaffectednote', component: COMPONENT, param: null, lang: null});
                    strings.push({key: 'warningallstafftypeassigned', component: COMPONENT, param: {
                        countstaffassigned: ja.staffcount,
                        counttempstaffassigned: ja.tempstaffcount,
                    }, lang: null});
                } else if (ja.staffcount) {
                    strings.push({key: 'warningstaffassigned', component: COMPONENT, param: ja.staffcount, lang: null});
                } else if (ja.tempstaffcount) {
                    strings.push({key: 'warningtempstaffassigned', component: COMPONENT, param: ja.tempstaffcount, lang: null});
                }

                Str.get_strings(strings).done(
                    function(results) {
                        var question = results[1];
                        if (results[4]) {
                            question += "\n" + results[4];
                        }
                        if (results[5]) {
                            question += "\n" + results[5];
                        }
                        Notification.confirm(results[0], question, results[2], results[3], function() {
                            self.deleteAssignment(itemId);
                        });
                        // The current notification doesn't work with promises and has no was to attach to the completion of the
                        // show event. There will be a small window here where behat may beat the pending call.
                        // Delay the resolution just by 100ms to give us a chance of avoiding it while not delaying it too long.
                        setTimeout(
                            function() {
                                M.util.js_complete(SERVICE.getAssignment);
                            },
                            100
                        );
                    }
                );

            }
        );
    };

    /**
     * Deletes the given job assignment.
     * @param {Integer} itemId
     */
    ListManager.prototype.deleteAssignment = function(itemId) {
        var self = this;

        M.util.js_pending(SERVICE.deleteAssignment);
        WebAPI.call({
            operationName: SERVICE.deleteAssignment,
            variables: {
                userid: this.userid,
                assignmentid: itemId
            }
        }).then(
            function() {
                // Turn the list of arguments (unknown length) into a real array.
                var li = HELPER.jobListItem(self.container, itemId);
                li.parentElement.removeChild(li);
                self.container.setAttribute('data-jobcount', HELPER.itemIds(self.container).length);
                M.util.js_complete(SERVICE.deleteAssignment);
            },
            function() {
                M.util.js_complete(SERVICE.deleteAssignment);
            }
        );
    };

    /**
     * Moves the job assignment up.
     * @param {Integer} itemId
     */
    ListManager.prototype.moveAssignmentUp = function(itemId) {
        var self = this,
            data = HELPER.itemIds(this.container),
            index = HELPER.itemIndex(itemId, data) - 1;

        if (index < 0) {
            return;
        }
        data.splice(index, 2, itemId.toString(), data[index]);

        this.sortAssignments(data, function() {
            HELPER.moveJobItemUp(self.container, itemId, true);
        });
    };

    /**
     * Moves the given job assignment down.
     * @param {Integer} itemId
     */
    ListManager.prototype.moveAssignmentDown = function(itemId) {
        var self = this,
            nextid,
            data = HELPER.itemIds(this.container),
            index = HELPER.itemIndex(itemId, data);

        if (index >= data.length) {
            return;
        }
        nextid = data[(index + 1)];
        data.splice(index, 2, nextid, itemId.toString());

        this.sortAssignments(data, function() {
            HELPER.moveJobItemUp(self.container, nextid, false);
        });
    };

    /**
     * Saves a new job sort order
     * @param {Array} orderedJobIds
     * @param {callable} successCallback
     */
    ListManager.prototype.sortAssignments = function(orderedJobIds, successCallback) {
        M.util.js_pending(SERVICE.sortAssignments);
        WebAPI.call(
            {
                operationName: SERVICE.sortAssignments,
                variables: {
                    userid: this.userid,
                    assignmentids: orderedJobIds
                }
            }
        ).then(
            function() {
                successCallback();
                M.util.js_complete(SERVICE.sortAssignments);
            },
            function() {
                M.util.js_complete(SERVICE.sortAssignments);
            }
        );
    };

    return {
        /**
         * Allow a new job management listing to be initialised for the given used.
         * @param {Integer} userid
         */
        init: function(userid) {
            var containers = HELPER.containers(userid),
                i = 0;
            for (i = 0; i < containers.length; i++) {
                new ListManager(userid, containers[i]);
            }
        }
    };

});
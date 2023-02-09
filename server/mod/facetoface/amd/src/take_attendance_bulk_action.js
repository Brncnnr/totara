/*
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package mod_facetoface
 */
define([], function() {
    /**
     *
     * @param {HTMLDivElement} moduleElement
     * @constructor
     */
    function TakeAttendanceBulkAction(moduleElement) {
        if (!(this instanceof TakeAttendanceBulkAction)) {
            return new TakeAttendanceBulkAction(moduleElement);
        }

        this.component = moduleElement;
    }

    TakeAttendanceBulkAction.prototype = {
        constructor: TakeAttendanceBulkAction,

        /**
         * Building up a list of selector for selecting the learners that has the code associated with. If the
         * HTMLDivElement does not have any of data key within list of specified keys, then null will be returned.
         *
         * @return {{
         *  selectall: (number | string),
         *  selectnone: (number | string),
         *  selectset: (number | string),
         *  selectnotset: (number | string)
         * } | null}
         */
        getOptions: function() {
            var keys = ['selectall', 'selectnone', 'selectset', 'selectnotset'],
                selector = {};

            for (var i in keys) {
                if (!keys.hasOwnProperty(i)) {
                    continue;
                }

                var key = keys[i],
                    dataKey = 'data-' + key;

                if (!this.component.hasAttribute(dataKey)) {
                    return null;
                }

                selector[key] = this.component.getAttribute(dataKey);
            }

            return selector;
        },

        /**
         * Sending an event to the parent component, to change the status code option of attendees, this will depends
         * on the list of selected attendees.
         */
        bulkTakeAttendance: function() {
            var that = this,
                element = that.component.querySelector('#menubulkattendanceop');

            if (null === element) {
                return;
            }

            element.addEventListener(
                'change',
                function() {
                    var self = this;

                    // Hide error msg, if any.
                    that.toggleError(false);

                    if (!self.value || self.value === '-1') {
                        // No point to change the states.
                        return;
                    }

                    that.triggerEvent(
                        'bulk-take-attendance',
                        {
                            statusCode: self.value,
                            /**
                             * Whether telling this child to display error or not.
                             *
                             * @param {boolean} display
                             */
                            toggleError: function(display) {
                                that.toggleError(display);
                            }
                        }
                    );
                }
            );
        },

        /**
         * Sending an event to the parent component, to select the learners/attendees, depends on the selector code.
         */
        bulkSelectLearners: function() {
            var that = this,
                element = that.component.querySelector('#menubulk_select'),
                options = that.getOptions();

            if (null === options || null === element) {
                // No selector/element defined/found for this component. No point to proceed.
                return;
            }

            element.addEventListener(
                'change',
                function() {
                    // By default, leave it as selecting nothing.
                    var value = options.selectnone,
                        self = this;

                    if (self.value) {
                        value = self.value;
                    }

                    /**
                     * @param {{
                     *     selectall: (function():void),
                     *     selectnone: (function():void),
                     *     selectset: (function():void),
                     *     selectnotset: (function():void)
                     * }} operators
                     */
                    var handle = function(operators) {
                        if (!operators.hasOwnProperty(value)) {
                            throw new Error("No operator for selector option: " + value);
                        }

                        var operator = operators[value];
                        if ("function" !== typeof operator) {
                            throw new Error("Expecting function at selector option: " + value);
                        }

                        operator();
                    };

                    that.triggerEvent(
                        'bulk-select-learner',
                        {
                            value: value,
                            options: options,
                            handle: handle
                        }
                    );

                    // Clear error, if any
                    that.toggleError(false);

                    // Reset the bulk attendance operator to default
                    var menu = that.component.querySelector('#menubulkattendanceop');
                    if (null !== menu) {
                        menu.selectedIndex = 0;
                    }
                }
            );
        },

        /**
         * Emit the event to the parent component.
         *
         * @param {string}  name
         * @param {Object}  detail
         */
        triggerEvent: function(name, detail) {
            var event = new CustomEvent(
                'mod_facetoface/take_attendance_bulk_action:' + name,
                {
                    bubbles: true,
                    detail: detail
                }
            );

            this.component.dispatchEvent(event);
        },

        /**
         * Triggering document to whether displaying or hiding error text message.
         *
         * @param {boolean} display
         */
        toggleError: function(display) {
            var notificationBox = this.component.querySelector('#selectoptionbefore');

            if (display) {
                if (!notificationBox.classList.contains('f2f-selectionoptionbefore-error')) {
                    notificationBox.classList.add('f2f-selectionoptionbefore-error');
                }

            } else {
                if (notificationBox.classList.contains('f2f-selectionoptionbefore-error')) {
                    notificationBox.classList.remove('f2f-selectionoptionbefore-error');
                }
            }
        },

        /**
         * Adding event listeners to those elements within this module.
         */
        addEvents: function() {
            var that = this;
            this.bulkTakeAttendance();
            this.bulkSelectLearners();

            this.component.addEventListener('mod_facetoface/take_attendance_bulk_action:user_selected', function() {
                that.component.querySelector('#menubulk_select').value = that.getOptions().selectset;
            });
        }
    };

    return {
        /**
         * The attributes of element are specified as below:
         * + data-selectall: number          Code for select all attendees.
         * + data-selectnone: number         Code for select none attendees.
         * + data-selectset: number          Code for select those attendees that had state set already.
         * + data-selectnotset: number       Code for select those attendees that had no state set yet.
         *
         * @param {HTMLDivElement} element
         * @return {PromiseLike}
         */
        init: function(element) {
            return new Promise(
                function(resolve) {
                    var module = new TakeAttendanceBulkAction(element);
                    module.addEvents();
                    resolve(module);
                }
            );
        }
    };
});
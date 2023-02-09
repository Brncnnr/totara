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
     * @param {HTMLDivElement} moduleElement
     * @constructor
     */
    function TakeAttendance(moduleElement) {
        if (!(this instanceof TakeAttendance)) {
            return new TakeAttendance(moduleElement);
        }

        this.component = moduleElement;
    }

    TakeAttendance.prototype = {
        constructor: TakeAttendance,

        /**
         * Just to toggle those checkboxes that are associated with attendee records. One-to-one relationship.
         *
         * @param {boolean} value
         */
        toggleCheckBoxes: function(value) {
            var checkboxes = this.component.querySelectorAll('.selectedcheckboxes');
            for (var i in checkboxes) {
                if (!checkboxes.hasOwnProperty(i)) {
                    continue;
                }

                checkboxes[i].checked = value;
            }
        },

        /**
         * Mark those checkboxes depends on the operator. Whether it is to be checked or not.
         * If the flag $isEqual is set to true, then only check the users that are in not_set state. Otherwise
         * users that are not in not_set state will be checked.
         *
         * @param {boolean} isEqual
         */
        checkNotSetUsers: function(isEqual) {
            var checkBoxes = this.component.querySelectorAll('.selectedcheckboxes'),

                // By default, not_set code is zero anyway, by any chances.
                value = this.component.getAttribute('data-notsetcode') || 0;

            for (var i in checkBoxes) {
                if (!checkBoxes.hasOwnProperty(i)) {
                    continue;
                }

                var checkBox = checkBoxes[i],
                    querySelectorString = checkBox.getAttribute('data-selectid'),
                    element = this.component.querySelector('#' + querySelectorString);

                if (null === element) {
                    // There should be some sort of  logger here. but no point
                    continue;
                }

                // Reset to a very default state.
                checkBox.checked = false;

                if (isEqual && value == element.value) {
                    // If it is equal, then attendees that are in state not_set will be checked.
                    checkBox.checked = true;
                } else if (!isEqual && value != element.value) {
                    // If it is not equal, then attendees that are not in state not_set will be checked
                    checkBox.checked = true;
                }
            }
        },

        /**
         * Adding event listeners for the child component, which is 'take_atendance_bulk_action'. As the child
         * component will emit an event for this parent component.
         */
        addEventListeners: function() {
            var that = this,
                bulkAction = that.component.querySelector('.tw-takeAttendanceBulkAction');

            bulkAction.addEventListener(
                'mod_facetoface/take_attendance_bulk_action:bulk-select-learner',
                function(event) {
                    // We need to build up the operators first, dependings on the options, then inject it to
                    // handler for the event.
                    var options = event.detail.options,
                        operator = {};

                    for (var optionName in options) {
                        if (!options.hasOwnProperty(optionName)) {
                            continue;
                        }

                        var value = options[optionName];

                        switch (value) {
                            case options.selectall.toString():
                                operator[value] = function() {
                                    that.toggleCheckBoxes(true);
                                };
                                break;
                            case options.selectset.toString():
                                operator[value] = function() {
                                    that.checkNotSetUsers(false);
                                };
                                break;
                            case options.selectnotset.toString():
                                operator[value] = function() {
                                    that.checkNotSetUsers(true);
                                };
                                break;
                            case options.selectnone.toString():
                            default:
                                operator[value] = function() {
                                    that.toggleCheckBoxes(false);
                                };
                                break;
                        }
                    }

                    if (event.detail.hasOwnProperty('handle') && 'function' === typeof event.detail.handle) {
                        event.detail.handle(operator);
                    }
                }
            );

            bulkAction.addEventListener(
                'mod_facetoface/take_attendance_bulk_action:bulk-take-attendance',
                function(event) {
                    var data = event.detail,
                        checkBoxes = that.component.querySelectorAll('.selectedcheckboxes'),
                        totalChecked = 0;

                    for (var i in checkBoxes) {
                        if (!checkBoxes.hasOwnProperty(i)) {
                            continue;
                        }

                        var checkBox = checkBoxes[i];
                        if (!checkBox.checked) {
                            // Just skip those checkboxes that are unchecked.
                            continue;
                        }

                        var querySelectorString = checkBox.getAttribute('data-selectid'),
                            selectElement = that.component.querySelector('#' + querySelectorString);

                        selectElement.value = data.statusCode;
                        totalChecked += 1;
                    }

                    if (0 === totalChecked) {
                        // If the $totalChecked variable is still zero, which means that there are no boxes had been
                        // checked, and so we should toggleError for user to know.
                        if (data.hasOwnProperty('toggleError') && 'function' === typeof data.toggleError) {
                            data.toggleError(true);
                        }
                    }
                }
            );

            that.component.addEventListener('change', function(e) {
                if (e.target.closest('.mod-facetoface-attendees .selectedcheckboxes')) {
                    bulkAction.dispatchEvent(new CustomEvent('mod_facetoface/take_attendance_bulk_action:user_selected'));
                }
            });
        },

        /**
         * Forcing user to download the csv/ods/excel file, depends on the option.
         */
        exportToFile: function() {
            var that = this,
                element = that.component.querySelector('#menuf2f-export-actions');

            if (null === element) {
                return;
            }

            element.addEventListener(
                'change',
                function() {
                    var self = this;

                    if (!self.value || "" == self.value) {
                        // As this is a default option, and it should not be changing the destination of the site.
                        return;
                    }

                    var value = self.value,
                        params = {
                            url: that.component.getAttribute('data-url'),
                            sessionid: that.component.getAttribute('data-sessionid'),
                            sessiondateid: that.component.getAttribute('data-sessiondateid')
                        };

                    window.location.href =
                        params.url + "?s=" + params.sessionid
                        + "&sd=" + params.sessiondateid
                        + "&onlycontent=1&download=" + value.substr(6);
                }
            );
        }
    };

    return {
        /**
         * The attributes of element are specified as below:
         * + data-url: string                Base url of takeattendance page.
         * + data-sessionid: number          Event id.
         * + data-sessiondateid: number      Session date id within an event.
         * + data-notsetcode: number         Code that is being used for state not_set, it is zero by default.
         *
         * @param {HTMLDivElement} element
         */
        init: function(element) {
            var module = new TakeAttendance(element);
            module.addEventListeners();
            module.exportToFile();
        }
    };
});
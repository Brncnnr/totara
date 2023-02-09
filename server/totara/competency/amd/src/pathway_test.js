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
 * @package totara_competency
 */

define(['core/templates', 'core/notification'],
function(templates, notification) {

    /**
     * Class constructor for the PWTest.
     *
     * @class
     * @constructor
     */
    function PwTest() {
        if (!(this instanceof PwTest)) {
            return new PwTest();
        }

        this.widget = '';
        this.clearEvents = false;
    }

    PwTest.prototype = {

        /**
         * Add event listeners for PwTests
         */
        events: function() {
            this.widget.addEventListener('click', function(e) {
                if (!e.target) {
                    return;
                }
            });
        },

        // Listen for propagated events
        bubbledEventsListener: function() {
            var that = this,
                criterionEvents = 'totara_competency/pathway:';

            this.widget.addEventListener(criterionEvents + 'update', function(e) {
                if (!e.detail.key) {
                    that.showNotification('error', e.type + ' event receved without key', true);
                } else if (!e.detail.pathway) {
                    that.showNotification('error', e.type + ' event receved without detail', true);
                } else {
                    that.logEvent(e);
                }
            });

            this.widget.addEventListener(criterionEvents + 'dirty', function(e) {
                if (!e.detail.key) {
                    that.showNotification('error', e.type + ' event receved without key', true);
                } else {
                    that.logEvent(e);
                }

                // We are expecting the 'dirty' event to be the last event received when multiple events are triggered.
                // To avoid too many notifications we clear previous notifications every time after we received a dirty event.
                that.clearEvents = true;

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
         * Log the received event
         */
        logEvent: function(e) {
            var msg = 'Received :"' + e.type + '".<br/>Detail  : <br/>' + JSON.stringify(e.detail) + '<br/><br/>';
            this.showNotification('success', msg);
        },

        /**
         * Show notification
         *
         * @param {String} type
         * @param {String} message
         */
        showNotification : function(type, message) {
            if (this.clearEvents) {
                // Clear old notifications
                notification.clearNotifications();
                this.clearEvents = false;
            }

            notification.addNotification({
                message: message,
                type: type
            });

            // Scroll to top to make sure that the notification is visible
            window.scrollTo(0, 0);
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
            var wgt = new PwTest();
            wgt.setParent(parent);
            wgt.events();
            wgt.bubbledEventsListener();
            resolve(wgt);
        });
    };

    return {
        init: init
    };
 });

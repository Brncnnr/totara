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
 * @package totara_core
 */

define([], function() {

    /**
     * Class constructor for the ListToggleLevel.
     *
     * @class
     * @constructor
     */
    function ListToggleLevel() {
        if (!(this instanceof ListToggleLevel)) {
            return new ListToggleLevel();
        }
        this.activeClass = 'tw-list__btn_active';
        this.disabledClass = 'tw-list__btn_disabled';
        this.eventKey = '';
        this.manualDisable = "data-tw-list-togglelevel-disable";
        this.manualSwitchState = "data-tw-list-togglelevel-manual";
        this.widget = '';
    }

    ListToggleLevel.prototype = {

        /**
         * Disable buttons
         *
         */
        clearAndDisableBtns: function() {
            var active = this.widget.querySelector('.' + this.activeClass);
            if (active) {
                active.classList.remove(this.activeClass);
            }
            this.widget.classList.add(this.disabledClass);
        },

        /**
         * Add event listeners
         *
         */
        events: function() {
            var that = this;

            // Click handler
            this.widget.addEventListener('click', function(e) {
                e.preventDefault();
                if (!e.target) {
                    return;
                }

                if (e.target.closest('.' + that.disabledClass)) {
                    e.preventDefault();
                    return;
                }

                if (e.target.closest('[data-tw-list-toggleLevel-type]')) {
                    var btn = e.target,
                        changeType = btn.getAttribute('data-tw-list-toggleLevel-type');

                    if (btn.classList.contains(that.activeClass)) {
                        return;
                    }

                    that.toggleActive(btn);
                    that.triggerEvent('changed', {
                        level: changeType
                    });
                }
            });

            // Create an observer instance with a callback function for clearing active items
            var observeManualSetDisable = new MutationObserver(function() {
                if (that.widget.getAttribute(that.manualDisable) === 'true') {
                    that.clearAndDisableBtns();
                    that.widget.removeAttribute(that.manualDisable);
                }
            });

            // Start observing the widget for selectGroup clear attribute mutations
            observeManualSetDisable.observe(this.widget, {
                attributes: true,
                attributeFilter: [that.manualDisable],
                subtree: false
            });

            // Create an observer instance with a callback function for clearing active items
            var observeManualSetActive = new MutationObserver(function() {
                if (that.widget.getAttribute(that.manualSwitchState)) {
                    var btn,
                        type = that.widget.getAttribute(that.manualSwitchState);

                    if (type === 'all') {
                        btn = that.widget.querySelector('[data-tw-list-toggleLevel-type="all"]');
                    } else {
                        btn = that.widget.querySelector('[data-tw-list-toggleLevel-type="current"]');
                    }

                    that.toggleActive(btn);
                    that.widget.removeAttribute(that.manualSwitchState);
                }
            });

            // Start observing the widget for selectGroup clear attribute mutations
            observeManualSetActive.observe(this.widget, {
                attributes: true,
                attributeFilter: [that.manualSwitchState],
                subtree: false
            });

        },

        /**
         * Set event propagation key
         *
         */
        setEventKey: function() {
            this.eventKey = this.widget.getAttribute('data-tw-list-toggleLevel-events');
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
         * Toggle active btn
         *
         * @param {node} target
         */
        toggleActive: function(target) {
            var active = this.widget.querySelector('.' + this.activeClass);
            if (active) {
                active.classList.remove(this.activeClass);
            }

            this.widget.classList.remove(this.disabledClass);
            target.classList.add(this.activeClass);
        },

        /**
         * Trigger event
         *
         * @param {string} eventName
         * @param {object} data
         */
        triggerEvent: function(eventName, data) {
            var propagateEvent = new CustomEvent(this.eventKey + ':' + eventName, {
                bubbles: true,
                detail: data
            });
            this.widget.dispatchEvent(propagateEvent);
        }
    };

    /**
     * Initialisation method
     *
     * @param {node} parent
     * @returns {Object} promise
     */
    var init = function(parent) {
        return new Promise(function(resolve) {
            var wgt = new ListToggleLevel();
            wgt.setParent(parent);
            wgt.setEventKey();
            wgt.events();
            resolve(wgt);
        });
    };

    return {
        init: init
    };
 });
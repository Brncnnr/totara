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
 * @author Carl Anderson <carl.anderson@totaralearning.com>
 * @package totara_reportbuilder
 */

define(['core/sessionstorage'], function(Storage) {

    /**
     * Class constructor for the ItemStyleToggle.
     *
     * @param {Element} widget The DOM element to attach this widget to
     * @class
     * @constructor
     */
    function ItemStyleToggle(widget) {
        if (!(this instanceof ItemStyleToggle)) {
            return new ItemStyleToggle(widget);
        }

        this.activeClass = 'totara_core__myreports__itemstyletoggle__btn_active';
        this.storageKey = 'tw-myreports-switcher-state';
        this.widget = widget;

        this.control = widget.querySelector('[data-tw-switcher]');

        this.targetClass = widget.getAttribute('data-tw-target-class');
        this.target = widget.querySelector(widget.getAttribute('data-tw-target'));

        this.setup();
        this.widget.classList.add('tw-list-toggle-loaded');
    }

    ItemStyleToggle.prototype = {

        /**
         * Setup initial state based off widget
         */
        setup: function() {
            // Check session storage to get the last state
            var targetState = Storage.get(this.storageKey);
            if (targetState) {
                var trigger = this.widget.querySelector('[data-tw-trigger=' + targetState + ']');

                if (trigger) {
                    // If already active, abort.
                    if (trigger.classList.contains(this.activeClass)) {
                        return;
                    }

                    this.toggle(trigger);
                    return;
                }
            }

            var controls = this.widget.querySelectorAll('[data-tw-trigger]');
            for (var i = 0; i < controls.length; i++) {
                var value = controls[i].getAttribute('data-tw-trigger');

                var valueClass = this.targetClass + '--' + value;
                if (this.target.classList.contains(valueClass)) {
                    controls[i].classList.add(this.activeClass);
                }
            }
        },

        /**
         * Add event listeners
         *
         */
        events: function() {
            var that = this;

            // Click handler
            this.control.addEventListener('click', function(e) {
                e.preventDefault();
                if (!e.target) {
                    return;
                }

                if (e.target.closest('[data-tw-trigger]')) {
                    var trigger = e.target.closest('[data-tw-trigger]');

                    // If already active, abort.
                    if (trigger.classList.contains(that.activeClass)) {
                        return;
                    }

                    that.toggle(trigger);
                }
            });
        },

        /**
         * Toggle item view
         *
         * @param {node} btn
         */
        toggle: function(btn) {
            var btnList = this.widget.querySelectorAll('[data-tw-trigger]');
            var valueClass;

            // Update UI
            for (var i = 0; i < btnList.length; i++) {
                valueClass = this.targetClass + '--' + btnList[i].getAttribute('data-tw-trigger');
                this.target.classList.remove(valueClass);
                btnList[i].classList.remove(this.activeClass);
            }

            // Update target container to new value
            btn.classList.add(this.activeClass);

            valueClass = this.targetClass + '--' + btn.getAttribute('data-tw-trigger');
            this.target.classList.add(valueClass);

            Storage.set(this.storageKey, btn.getAttribute('data-tw-trigger'));
        },
    };

    /**
     * Initialisation method
     *
     * @param {Element} parent
     * @returns {Object} promise
     */
    var init = function(parent) {
        return new Promise(function(resolve) {
            var wgt = new ItemStyleToggle(parent);
            wgt.events();
            resolve(wgt);
        });
    };

    return {
        init: init
    };
});
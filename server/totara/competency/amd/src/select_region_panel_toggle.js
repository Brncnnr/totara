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

/**
 * Listens for and Propagates events for toggling the visibility of select_region_panel on mobile.
 */
define([], function() {

    /**
     * Class constructor for the SelectRegionPanelToggle.
     *
     * @class
     * @constructor
     */
    function SelectRegionPanelToggle() {
        if (!(this instanceof SelectRegionPanelToggle)) {
            return new SelectRegionPanelToggle();
        }

        this.activeClass = 'tw-selectRegionPanelToggle__active';
        this.targetWidgetClass = 'tw-selectRegionPanel';
        this.toggleClass = 'tw-selectRegionPanel__hiddenOnSmall_show';
        this.widget = null;
    }

    SelectRegionPanelToggle.prototype = {
        /**
         * Add event listeners for toggle sibling widget
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
                if (e.target.closest('[data-tw-selectRegionPanelToggle-trigger]')) {
                    that.toggleWidget();
                }
            });
        },

        /**
         * Set widget parent
         *
         * @param {node} widgetParent
         */
        setParent: function(widgetParent) {
            this.widget = widgetParent;
        },

        /**
         * Toggle widget
         *
         */
        toggleWidget: function() {
            this.widget.classList.toggle(this.activeClass);

            // Inform parent widget of this change
            this.triggerEvent('changed', {
                target: '.' + this.targetWidgetClass,
                toggleClass: this.toggleClass
            });
        },

        /**
         * Trigger event
         *
         * @param {string} eventName
         * @param {object} data
         */
        triggerEvent: function(eventName, data) {
            var propagateEvent = new CustomEvent('totara_competency/select_region_panel_toggle:' + eventName, {
                bubbles: true,
                detail: data
            });
            this.widget.dispatchEvent(propagateEvent);
        }
    };

    /**
     * widget initialisation method
     *
     * @param {node} widgetParent
     * @returns {Promise} promise
     */
    var init = function(widgetParent) {
        return new Promise(function(resolve) {
            var wgt = new SelectRegionPanelToggle();
            wgt.setParent(widgetParent);
            wgt.events();
            resolve(wgt);
        });
    };

    return {
        init: init
    };
 });
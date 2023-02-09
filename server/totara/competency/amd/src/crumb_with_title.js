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
 * @package totara_competency
 * @subpackage crumb_with_title
 */

define([], function() {

    /**
     * Class constructor for the crumbtrail with title.
     *
     * @class
     * @constructor
     */
    function CrumbTrail() {
        if (!(this instanceof CrumbTrail)) {
            return new CrumbTrail();
        }
        this.eventKey = null;
        this.widget = null;
    }

    CrumbTrail.prototype = {

        /**
         * Add event listeners
         *
         */
        events: function() {
            var that = this;

            this.widget.addEventListener('click', function(e) {
                if (!e.target) {
                    return;
                }

                if (e.target.closest('[data-tw-crumbtrailWithTitle-id]')) {
                    e.preventDefault();

                    var item = e.target.closest('[data-tw-crumbtrailWithTitle-id]'),
                        isTopLevel = item.hasAttribute('data-tw-crumbtrailWithTitle-top-level'),
                        id = item.getAttribute('data-tw-crumbtrailWithTitle-id'),
                        name = item.getAttribute('data-tw-crumbtrailWithTitle-name');

                    that.triggerEvent('click', {
                        id: id,
                        isTopLevel: isTopLevel,
                        name: name,
                    });
                }
            });
        },

        /**
         * Set event propagation key
         *
         */
        setEventKey: function() {
            this.eventKey = this.widget.getAttribute('data-tw-crumbtrailWithTitle-events');
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
     * widget initialisation method
     * @param {node} parent
     * @returns {Promise}
     */
    var init = function(parent) {
        return new Promise(function(resolve) {
            // Create an instance of widget
            var wgt = new CrumbTrail();
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
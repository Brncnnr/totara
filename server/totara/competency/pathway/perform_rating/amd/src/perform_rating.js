/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package pathway_perform_rating
 */

define([], function() {

    /**
     * Class constructor for PwPerformRating.
     *
     * @class
     * @constructor
     */
    function PwPerformRating() {
        if (!(this instanceof PwPerformRating)) {
            return new PwPerformRating();
        }

        this.widget = '';

        /**
         * Pathway data.
         * This object should only contain the data to be sent on the save api endpoint.
         *
         * @type {Object}
         */
        this.pathway = {
            id: 0,
            type: 'perform_rating',
            sortorder: 0,
            singleuse: 1
        };

        // Key to use in achievementPath events
        this.pwKey = '';

        this.endpoints = {
            create: 'pathway_perform_rating_create',
            update: 'pathway_perform_rating_update',
        };

        this.filename = 'perform_rating.js';
    }

    PwPerformRating.prototype = {

        /**
         * Add event listeners for PwPerformRating
         *
         */
        events: function() {
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
         * Initialise the data
         *
         * @return {Promise}
         */
        initData: function() {
            var that = this,
                pwWgt = this.widget.closest('[data-tw-editAchievementPaths-pathway-key]'),
                pwKey = 0,
                pwId = 0;

            return new Promise(function(resolve, reject) {
                if (pwWgt) {
                    pwKey = pwWgt.getAttribute('data-tw-editAchievementPaths-pathway-key') ? pwWgt.getAttribute('data-tw-editAchievementPaths-pathway-key') : 0;
                    pwId = pwWgt.getAttribute('data-tw-editAchievementPaths-pathway-id') ? pwWgt.getAttribute('data-tw-editAchievementPaths-pathway-id') : 0;
                }

                that.pwKey = pwKey;

                if (pwId === 0) {
                    delete that.pathway.id;

                    // New pw - we need the competency id
                    // Get the competency ID from higher up in the DOM
                    var competencyIdNode = document.querySelector('[data-tw-editAchievementPaths-competency]'),
                        competencyId = 1;

                    if (competencyIdNode) {
                        competencyId = competencyIdNode.getAttribute('data-tw-editAchievementPaths-competency');
                    }

                    that.pathway.competency_id = competencyId;
                    that.widget.setAttribute('data-tw-editAchievementPaths-save-endPoint', that.endpoints.create);

                } else {
                    that.pathway.id = pwId;
                    that.widget.setAttribute('data-tw-editAchievementPaths-save-endPoint', that.endpoints.update);
                }

                that.triggerEvent('update', {pathway: that.pathway});
            });
        },

        /**
         * Trigger event
         *
         * @param {string} eventName
         * @param {object} data
         */
        triggerEvent: function(eventName, data) {
            data.key = this.pwKey;

            var propagateEvent = new CustomEvent('totara_competency/pathway:' + eventName, {
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
            var wgt = new PwPerformRating();
            wgt.setParent(parent);
            wgt.events();
            resolve(wgt);

            M.util.js_pending('pathwayPerformRating');
            wgt.initData().then(function() {
                M.util.js_complete('pathwayPerformRating');
            }).catch(function() {
                // Failed
            });
        });
    };

    return {
        init: init
    };
});

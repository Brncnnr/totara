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
 * @package criteria_onactivate
 */

define(['totara_competency/loader_manager'],
function(Loader) {

    /**
     * Class constructor for the OnActivate.
     *
     * @class
     * @constructor
     */
    function CriterionOnActivate() {
        if (!(this instanceof CriterionOnActivate)) {
            return new CriterionOnActivate();
        }

        this.widget = ''; // Parent widget
        this.competencyKey = 'competency_id'; // Metadata key for competency id
        this.criterionKey = '';
        this.loader = null; // Loading overlay manager

        /**
         * Criterion data.
         * This object should only contain the data to be sent on the save api endpoints.
         * The variable names MUST correlate to the save endpoint parameters
         */
        this.criterion = {
            type: 'onactivate',
            metadata: [],
            id: 0,
            singleuse: true,
            expandable: false
        };
    }

    CriterionOnActivate.prototype = {

        /**
         * Set parent
         *
         * @param {node} parent
         */
        setParent: function(parent) {
            this.widget = parent;
        },

        /**
         * Retrieve the criterion detail and bubble it up to the parent
         * As this criterion is linked to the competency, we use the competency id on the document instead of making an additional API call to get the detail
         * @return {Promise}
         */
        getDetail: function() {
            var that = this,
                criterionNode = this.widget.closest('[data-tw-editScaleValuePaths-criterion-key]'),
                competencyIdNode = document.querySelector('[data-tw-editAchievementPaths-competency]');


            return new Promise(function(resolve) {
                if (criterionNode) {
                    that.criterionKey = criterionNode.hasAttribute('data-tw-editScaleValuePaths-criterion-key')
                        ? criterionNode.getAttribute('data-tw-editScaleValuePaths-criterion-key')
                        : 0;
                    that.criterion.id = criterionNode.hasAttribute('data-tw-editScaleValuePaths-criterion-id')
                        ? criterionNode.getAttribute('data-tw-editScaleValuePaths-criterion-id')
                        : 0;
                }

                if (competencyIdNode) {
                    var competencyId = competencyIdNode.getAttribute('data-tw-editAchievementPaths-competency')
                        ? competencyIdNode.getAttribute('data-tw-editAchievementPaths-competency') : 1;

                    that.criterion.metadata = [{
                        metakey: that.competencyKey,
                        metavalue: competencyId
                    }];
                }

                that.triggerEvent('update', {criterion: that.criterion});
                resolve();
            });
        },

        /**
         * Trigger event
         *
         * @param {string} eventName
         * @param {object} data
         */
        triggerEvent: function(eventName, data) {
            data.key = this.criterionKey;

            var propagateEvent = new CustomEvent('totara_criteria/criterion:' + eventName, {
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
            var wgt = new CriterionOnActivate();
            wgt.setParent(parent);
            wgt.loader = Loader.init(parent);
            wgt.loader.show();
            resolve(wgt);

            M.util.js_pending('criterionCourseCompletion');
            wgt.getDetail().then(function() {
                wgt.loader.hide();
                M.util.js_complete('criterionCourseCompletion');
            }).catch(function() {
                // Failed
            });
        });
    };

    return {
        init: init
    };
 });

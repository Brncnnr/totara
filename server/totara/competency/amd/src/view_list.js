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

define(['totara_competency/filters', 'totara_competency/action_list_manager', 'totara_competency/filter_manager', 'totara_competency/loader_manager'],
function(Filters, List, Selectors, Loader) {

    /**
     * Class constructor for the view list.
     * This provides the base functionality for constructing a viewer (non-selectable) list
     * A view list is to be used when outputting list content for display purposes without the need to select
     * or store individual items
     * @class
     * @constructor
     */
    function ViewList() {
        if (!(this instanceof ViewList)) {
            return new ViewList();
        }

        this.hideClass = 'tw-basketlist__hide';
        this.filters = null;
        this.loader = null;
        this.selectors = null;
        this.listParent = null;
        this.list = null;
    }

    /**
     * Set list parent node
     *
     * @param {node} parent
     */
    ViewList.prototype.setListParent = function(parent) {
        this.listParent = parent.querySelector('[data-tw-basket-list]');
    };

    /**
     * Reset
     *
     */
    ViewList.prototype.reset = function() {
        this.filters.clearFilters();
        this.selectors.clearFiltersRegionPanel();
        this.selectors.clearPrimarySearch();
        this.selectors.clearPrimaryTree();
    };

    ViewList.prototype.filterEvents = function(filters) {
        var that = this;
        filters.onFiltersUpdate = function() {
            that.list.setRequestArg('filters', filters.getFilters());
        };
    };

    ViewList.prototype.listEvents = function(list) {
        var that = this;

        list.onPreRequest = function() {
            that.loader.show();
        };

        list.onPostRequest = function() {
            that.loader.hide();
        };
    };

    ViewList.prototype.selectorEvents = function(selectors) {
        var that = this;

        /**
         * Added filter region panel filter
         *
         * @param {event} e
         */
        selectors.onFilterRegionPanelAdd = function(e) {
            that.filters.setFilter(e.detail.key, e.detail.val, e.detail.groupValues);
        };

        /**
         * Update with filter region panel
         */
        selectors.onFilterRegionPanelUpdate = function() {
            that.list.update();
        };

        /**
         * Reset filter region panel
         *
         * @param {event} e
         */
        selectors.onFilterRegionPanelReset = function(e) {
            var groupValues = e.detail.groupValues ? e.detail.groupValues : '';
            that.filters.removeFilter(e.detail.key, groupValues);
        };

        /**
         * Added primary search filter
         *
         * @param {event} e
         */
        selectors.onPrimaryFilterSearchAdd = function(e) {
            that.filters.setFilter(e.detail.key, e.detail.val, e.detail.groupValues);
        };

        /**
         * Update with primary search change
         */
        selectors.onPrimaryFilterSearchUpdate = function() {
            that.list.update();
        };

        /**
         * Removed primary search filter
         *
         * @param {event} e
         */
        selectors.onPrimaryFilterSearchReset = function(e) {
            var groupValues = e.detail.groupValues ? e.detail.groupValues : '';
            that.filters.removeFilter(e.detail.key, groupValues);
        };

        /**
         * Added primary tree filter
         *
         * @param {event} e
         */
        selectors.onPrimaryFilterTreeAdd = function(e) {
            that.filters.setFilter(e.detail.key, e.detail.val, e.detail.groupValues);
        };

        /**
         * Update with primary tree filter change
         */
        selectors.onPrimaryFilterTreeUpdate = function() {
            that.list.update();
        };

        /**
         * Removed primary tree filter
         *
         * @param {event} e
         */
        selectors.onPrimaryFilterTreeReset = function(e) {
            var groupValues = e.detail.groupValues ? e.detail.groupValues : '';
            that.filters.removeFilter(e.detail.key, groupValues);
        };
    };

    /* Used for extending initializer */
    ViewList.prototype.initExtend = function() { /* Null */ };

    /**
     * initialise from data
     *
     * @param {object} data required properties for viewer
     * @return {Promise}
     */
    ViewList.prototype.init = function(data) {
        var parent = data.parent,
            behatString = 'viewerPause',
            that = this;

        return new Promise(function(resolve) {
            M.util.js_pending(behatString);
            that.setListParent(parent);

            that.loader = Loader.init(parent);
            that.loader.show();

            that.filters = new Filters();
            that.filterEvents(that.filters);

            if (data.list.defaultFilters) {
                that.filters.setDefaults(data.list.defaultFilters);
                if (data.list.serviceArgs && data.list.serviceArgs.filters) {
                    data.list.serviceArgs.filters = Object.assign(data.list.serviceArgs.filters, data.list.defaultFilters);
                } else {
                    if (!data.list.serviceArgs) {
                        data.list.serviceArgs = {};
                    }
                    data.list.serviceArgs.filters = data.list.defaultFilters;
                }
            }

            that.list = List.init(parent, data.list);
            that.listEvents(that.list);

            that.selectors = Selectors.init(parent);
            that.selectorEvents(that.selectors);

            that.initExtend();
            resolve();

            var promiseList = [that.list.prepare()];
            Promise.all(promiseList).then(function() {
                that.list.update();
                M.util.js_complete(behatString);
            });
        });
    };

    return ViewList;
});
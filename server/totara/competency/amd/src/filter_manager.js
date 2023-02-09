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
 * @subpackage filter_manager
 */

 /*
  * Handle the common selector (primary search, primary tree, region panel) propagated events for updating filters
  */
define([], function() {

    /**
     * Class constructor for the FilterManager.
     *
     * @class
     * @constructor
     */
    function FilterManager() {
        if (!(this instanceof FilterManager)) {
            return new FilterManager();
        }
        this.basketRequestArgs = {
            action: '',
            basket: 0,
            ids: []
        };
        this.primarySearch = null;
        this.primaryTree = null;
        this.regionPanel = null;
        this.widget = null;
    }

    // Listen for propagated events
    FilterManager.prototype.bubbledEventsListener = function() {
        var that = this;

        if (this.primarySearch) {
            this.primarySearch.addEventListener('totara_core/select_search_text:add', function(e) {
                that.onPrimaryFilterSearchAdd(e);
            });

            this.primarySearch.addEventListener('totara_core/select_search_text:changed', function() {
                that.onPrimaryFilterSearchUpdate();
            });

            this.primarySearch.addEventListener('totara_core/select_search_text:remove', function(e) {
                that.onPrimaryFilterSearchReset(e);
            });
        }

        if (this.primaryTree) {
            this.primaryTree.addEventListener('totara_core/select_tree:add', function(e) {
                that.onPrimaryFilterTreeAdd(e);
            });

            this.primaryTree.addEventListener('totara_core/select_tree:changed', function() {
                that.onPrimaryFilterTreeUpdate();
            });

            this.primaryTree.addEventListener('totara_core/select_tree:remove', function(e) {
                that.onPrimaryFilterTreeReset(e);
            });
        }

        if (this.regionPanel) {
            this.regionPanel.addEventListener('totara_core/select_region_panel:add', function(e) {
                that.onFilterRegionPanelAdd(e);
            });
            this.regionPanel.addEventListener('totara_core/select_region_panel:changed', function() {
                that.onFilterRegionPanelUpdate();
            });
            this.regionPanel.addEventListener('totara_core/select_region_panel:remove', function(e) {
                that.onFilterRegionPanelReset(e);
            });

            // Events from mobile toggle panel
            this.regionPanel.addEventListener('totara_competency/select_region_panel_toggle:changed', function(e) {
                var target = that.regionPanel.querySelector(e.detail.target);
                target.classList.toggle(e.detail.toggleClass);
            });
        }

    };

    FilterManager.prototype.onFilterRegionPanelAdd = function() { /* Null */ };
    FilterManager.prototype.onFilterRegionPanelUpdate = function() { /* Null */ };
    FilterManager.prototype.onFilterRegionPanelReset = function() { /* Null */ };
    FilterManager.prototype.onPrimaryFilterSearchAdd = function() { /* Null */ };
    FilterManager.prototype.onPrimaryFilterSearchUpdate = function() { /* Null */ };
    FilterManager.prototype.onPrimaryFilterSearchReset = function() { /* Null */ };
    FilterManager.prototype.onPrimaryFilterTreeAdd = function() { /* Null */ };
    FilterManager.prototype.onPrimaryFilterTreeUpdate = function() { /* Null */ };
    FilterManager.prototype.onPrimaryFilterTreeReset = function() { /* Null */ };

    /**
     * Clear filter region panel
     */
    FilterManager.prototype.clearFiltersRegionPanel = function() {
        if (this.regionPanel) {
            this.regionPanel.querySelector('.tw-selectRegionPanel').setAttribute('data-tw-selectorgroup-clear', true);
        }
    };

    /**
     * Clear primary search
     *
     */
    FilterManager.prototype.clearPrimarySearch = function() {
        if (this.primarySearch) {
            this.primarySearch.querySelector('[data-tw-selectorgroup]').setAttribute('data-tw-selectorgroup-clear', true);
        }
    };

    /**
     * Clear primary tree
     *
     */
    FilterManager.prototype.clearPrimaryTree = function() {
        if (this.primaryTree) {
            this.primaryTree.querySelector('[data-tw-selectorgroup]').setAttribute('data-tw-selectorgroup-clear', true);
        }
    };

    /**
     * Set the value of the primary tree
     *
     */
    FilterManager.prototype.setPrimaryTreeValue = function(value) {
        if (this.primaryTree) {
            this.primaryTree.querySelector('[data-tw-selectorgroup]').setAttribute('data-tw-selector-manualset', value);
        }
    };

    /**
     * Toggle primary search class
     *
     * @param {string} toggleClass
     */
    FilterManager.prototype.primarySearchToggleClass = function(toggleClass) {
        if (this.primarySearch) {
            this.primarySearch.classList.toggle(toggleClass);
        }
    };

    /**
     * Remove primary search class
     *
     * @param {string} removeClass
     */
    FilterManager.prototype.primarySearchRemoveClass = function(removeClass) {
        if (this.primarySearch) {
            this.primarySearch.classList.remove(removeClass);
        }
    };

    /**
     * Toggle primary tree class
     *
     * @param {string} toggleClass
     */
    FilterManager.prototype.primaryTreeToggleClass = function(toggleClass) {
        if (this.primaryTree) {
            this.primaryTree.classList.toggle(toggleClass);
        }
    };

    /**
     * Remove primary tree class
     *
     * @param {string} removeClass
     */
    FilterManager.prototype.primaryTreeRemoveClass = function(removeClass) {
        if (this.primaryTree) {
            this.primaryTree.classList.remove(removeClass);
        }
    };

    /**
     * Set primary search node
     *
     * @param {node} node
     */
    FilterManager.prototype.setPrimarySearch = function(node) {
        this.primarySearch = node;
    };

    /**
     * Set primary tree node
     *
     * @param {node} node
     */
    FilterManager.prototype.setPrimaryTree = function(node) {
        this.primaryTree = node;
    };

    /**
     * Set primary tree node
     *
     * @param {node} node
     */
    FilterManager.prototype.setRegionPanel = function(node) {
        this.regionPanel = node;
    };

    /**
     * initialisation method
     *
     * @param {node} parent
     * @returns {Object} promise
     */
    var init = function(parent) {

        var filterRegionPanel = parent.querySelector('[data-tw-filterRegionPanel]'),
            primaryFilterSearch = parent.querySelector('[data-tw-basket-list-primaryFilterSearch]'),
            primaryFilterTree = parent.querySelector('[data-tw-basket-list-primaryFilterTree]');

        var wgt = new FilterManager();

        if (primaryFilterSearch) {
            wgt.setPrimarySearch(primaryFilterSearch);
        }

        if (primaryFilterTree) {
            wgt.setPrimaryTree(primaryFilterTree);
        }

        if (filterRegionPanel) {
            wgt.setRegionPanel(filterRegionPanel);
        }

        wgt.bubbledEventsListener();
        return wgt;
    };

    return {
        init: init
    };
 });
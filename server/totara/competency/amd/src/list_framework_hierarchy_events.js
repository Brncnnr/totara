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
 */

define([], function() {

    /**
    * Class constructor for framework list hierarchy events
    *
    * @class
    * @constructor
    */
    function HierarchyEvents() {
        if (!(this instanceof HierarchyEvents)) {
            return new HierarchyEvents();
        }
    }

    /**
    * Overwrite event functions
    * @return {Object}
    */
    HierarchyEvents.prototype.getEvents = function() {
        return {
            crumb: {
                /**
                * Correctly format crumb data
                *
                * @param {Object} data
                * @return {Promise}
                */
                formatCrumbData: function(data) {
                    data.custom_top_label = this.crumb.strings.levelTop;
                    return new Promise(function(resolve) {
                        resolve(data);
                    });
                },

                /**
                * Update framework to match crumbtrail change
                *
                * @param {Object} data
                * @return {Promise}
                */
                onCrumbtrailChanged: function(data) {
                    var frameworkTree = this.listParent.querySelector('[data-tw-selecttree-urlkey="framework"]');

                    return new Promise(function(resolve) {
                        if (data.frameworkid > 0 && frameworkTree) {
                            frameworkTree.setAttribute('data-tw-selector-manualset', data.frameworkid);
                        }
                        resolve();
                    });
                },

                /**
                * Change list level based on clicked crumbtrail item
                *
                * @param {Object} data
                */
                onCrumbtrailChangeLevel: function(data) {
                    var crumbId = data.id,
                        filterRegion = this.listParent.querySelector('.tw-selectRegionPanel');

                    this.filters.clearFilters();
                    if (filterRegion) {
                        filterRegion.setAttribute('data-tw-selectorgroup-clear', true);
                    }
                    this.loader.show();

                    this.filters.setFilter('parent', crumbId);
                    this.crumb.setParentId(crumbId);
                    this.list.resetToggleLevel();

                    this.loader.show();
                    this.updatePage([this.list.getUpdateRequestArgs(), this.crumb.updateCrumbAndHeader()]);
                },

                /**
                * Change crumb list level to top based on clicked crumbtrail item
                *
                * @param {Object} data
                */
                onCrumbtrailChangeToTopLevel: function(data) {
                    var crumbId = data.id,
                        filterRegion = this.listParent.querySelector('.tw-selectRegionPanel'),
                        frameworkTree = this.listParent.querySelector('[data-tw-selecttree-urlkey="framework"]');

                    this.filters.clearFilters();
                    if (filterRegion) {
                        filterRegion.setAttribute('data-tw-selectorgroup-clear', true);
                    }
                    this.loader.show();

                    // set framework level to top
                    this.filters.setFilter('framework', crumbId);
                    this.filters.setFilter('parent', 0);

                    this.list.resetToggleLevel();
                    this.list.update();
                    this.crumb.setHeadingToLevelTop();
                    this.crumb.clearCrumbtrail();

                    // Update the framework render tree
                    if (crumbId > 0) {
                        frameworkTree.setAttribute('data-tw-selector-manualset', crumbId);
                    } else {
                        frameworkTree.setAttribute('data-tw-selectorgroup-clear', true);
                    }
                },
            },
            list: {

                /**
                * If a framework has been selected update the browse tree
                *
                * @param {event} e
                */
                onListHierarchyLevelChangeExtend: function(e) {
                    // Update framework tree
                    var frameworkTree = this.listParent.querySelector('[data-tw-selecttree-urlkey="framework"]');
                    frameworkTree.setAttribute('data-tw-selector-manualset', e.detail.extra.framework);
                },

                /**
                 * Exclude sub-level items in results
                 *
                 */
                onHideSubLevelItems: function() {
                    var id = this.filters.getFilter('path');
                    this.filters.removeFilter('path');
                    this.filters.setFilter('parent', id);
                    this.list.update();
                },

                /**
                 * Include sub-level items in results
                 *
                 */
                onShowSubLevelItems: function() {
                    var id = this.filters.getFilter('parent');
                    this.filters.removeFilter('parent');
                    this.filters.setFilter('path', id);
                    this.list.update();
                }
            },
            selectors: {
                /**
                * Update with primary filter change
                *
                */
                onPrimaryFilterTreeUpdate: function() {
                    var basketFilter,
                        frameworkId = this.filters.getFilter('framework');

                    this.loader.show();

                    // If we have a basket filter, retain it
                    if (this.filters.hasFilter('basket')) {
                        basketFilter = this.filters.getFilter('basket');
                    }
                    this.filters.clearFilters();
                    if (basketFilter) {
                        this.filters.setFilter('basket', basketFilter);
                        this.list.enabledActions = false;
                        this.list.enabledHierarchy = false;
                    }

                    // Readd framework & set level to framwork top
                    if (frameworkId) {
                        this.list.resetToggleLevel();
                        this.filters.setFilter('framework', frameworkId);
                        this.filters.setFilter('parent', 0);
                        this.crumb.setHeadingToLevelTop();

                    } else {
                        this.list.disableToggleLevel();
                        this.crumb.setHeadingToTop();
                    }

                    this.selectors.clearPrimarySearch();
                    this.crumb.clearCrumbtrail();
                    this.list.update();
                },
            },
        };
    };

    /**
    * initialisation method
    *
    * @returns {Object} promise
    */
    var init = function() {
        return new Promise(function(resolve) {
            var hierarchyPic = new HierarchyEvents();
            resolve(hierarchyPic.getEvents());
        });
    };

    return {
        init: init
    };
});

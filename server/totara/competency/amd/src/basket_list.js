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

define(['core/ajax', 'totara_competency/filters', 'totara_competency/basket_manager', 'totara_competency/crumb_manager',
'totara_competency/action_list_manager', 'totara_competency/filter_manager', 'totara_competency/loader_manager', 'totara_competency/simple_basket',
'totara_competency/session_basket'],
function(ajax, Filters, BasketManager, Crumb, List, Selectors, Loader, SimpleBasket, SessionBasket) {

    /**
     * Class constructor for the basket list.
     * This provides the base functionality for a basket list (item selection with session storage)
     * This should be used when providing an interface for selecting individual items from a large data set.
     * The selection will be stored in session and can be used for conducting bulk actions.
     *
     * @class
     * @constructor
     */
    function BasketList() {
        if (!(this instanceof BasketList)) {
            return new BasketList();
        }

        this.basketManager = null;
        this.hideClass = 'tw-basketlist__hide';
        this.crumb = null;
        this.filters = null;
        this.loader = null;
        this.listParent = null;
        this.list = null;
        this.selectors = null;
    }

    /**
     * Set list parent node
     *
     * @param {node} parent
     */
    BasketList.prototype.setListParent = function(parent) {
        this.listParent = parent.querySelector('[data-tw-basket-list]');
    };

    /**
     * Reset list base
     *
     */
    BasketList.prototype.reset = function() {
        this.filters.clearFilters();
        if (this.crumb) {
            this.crumb.clearCrumbtrail();
            this.crumb.setHeadingToTop();
        }
        this.selectors.clearFiltersRegionPanel();
        this.selectors.clearPrimarySearch();
        this.selectors.clearPrimaryTree();
        this.list.disableToggleLevel();
    };

    /**
     * Update page: Trigger all requests passed within the promises and if all
     * if finished and all is rendered remove the loading indicator
     *
     * @param {Array} webserviceRequestObjects array of webservice request objects
     * @return {Promise}
     */
    BasketList.prototype.updatePage = function(webserviceRequestObjects) {
        var that = this;

        return new Promise(function(resolve, reject) {
            ajax.getDataUpdate(webserviceRequestObjects).then(function() {
                that.loader.hide();
                resolve();
            }).catch(function() {
                that.loader.hide();
                reject();
            });
        });
    };

    BasketList.prototype.basketEvents = function(basketManager) {
        var that = this;

        /**
         * Clears basket request values
         */
        basketManager.onBasketClear = function() {
            that.loader.show();

            that.basketManager.deleteAndRender().then(function() {
                basketManager.onBasketHide();
                that.loader.hide();
            });
        };

        /**
         * Basket selection has been hidden
         */
        basketManager.onBasketHidden = function() {
            if (that.crumb) {
                that.crumb.removeClass(that.hideClass);
                that.crumb.headingRemoveClass(that.hideClass);
            }
            basketManager.load().then(function() {
                that.filters.removeFilter('ids');
                that.filters.removeFilter('basket');
                that.list.update();
            });
            that.selectors.primarySearchRemoveClass(that.hideClass);
            that.selectors.primaryTreeRemoveClass(that.hideClass);
        };

        /**
         * Hide basket selection
         */
        basketManager.onBasketHide = function() {
            basketManager.toggleExpandedView();
            basketManager.toggleShowBasketBtn();
            that.reset();
            that.list.enabledActions = true;
            that.list.enabledHierarchy = true;
            that.list.paging.resetPageNumber();
            that.list.update();
        };

        /**
         * Show basket selection
         */
        basketManager.onBasketShow = function() {
            basketManager.toggleExpandedView();
            basketManager.toggleShowBasketBtn();

            that.selectors.primarySearchToggleClass(that.hideClass);
            that.selectors.primaryTreeToggleClass(that.hideClass);

            if (that.crumb) {
                that.crumb.toggleClass(that.hideClass);
                that.crumb.headingToggleClass(that.hideClass);
            }

            that.list.disableToggleLevel();

            that.loader.show();
            that.filters.clearFilters();
            that.selectors.clearFiltersRegionPanel();
            that.list.enabledActions = false;
            that.list.enabledHierarchy = false;

            that.list.paging.setPageNumber(0);
            // Add basket to filters and show results
            if (basketManager.getBasket() instanceof SessionBasket) {
                that.filters.setFilter('basket', basketManager.getBasketKey());
                that.list.update();
            } else {
                basketManager.load().then(function(values) {
                    that.filters.setFilter('ids', values);
                    that.list.update();
                });
            }
        };

        basketManager.onBasketUpdate = function(ids) {
            that.list.selectedItems = ids;
        };
    };

    BasketList.prototype.filterEvents = function(filters) {
        var that = this;
        filters.onFiltersUpdate = function() {
            that.list.setRequestArg('filters', filters.getFilters());
        };
    };

    BasketList.prototype.listEvents = function(list) {
        var that = this;

        /**
         * Select list level change
         *
         * @param {event} e
         */
        list.onListHierarchyLevelChange = function(e) {
            // Clear filters & filter view
            that.filters.clearFilters();
            that.selectors.clearFiltersRegionPanel();
            that.selectors.clearPrimarySearch();

            // Set filters for requesting level
            that.filters.setFilter(e.detail.key, e.detail.val, e.detail.groupValues);

            // Set filters for requesting level parent for crumbtrail and heading
            that.crumb.setParentId(e.detail.val);

            list.resetToggleLevel();

            that.loader.show();
            that.updatePage([that.list.getUpdateRequestArgs(), that.crumb.updateCrumbAndHeader()]);
        };

        list.onItemSelected = function(id) {
            list.toggleSelectDisable(true);
            // Queue this action, it wil be processed by onItemUpdate()
            that.basketManager.queueAdd(id);
        };

        list.onItemUnselected = function(id) {
            list.toggleSelectDisable(true);
            // Queue this action, it wil be processed by onItemUpdate()
            that.basketManager.queueRemove(id);
        };

        list.onItemUpdate = function() {
            that.basketManager.updateAndRender().then(function() {
                list.toggleSelectDisable(false);
            });
        };

        list.onPreRequest = function() {
            that.loader.show();
        };

        list.onPostRequest = function() {
            that.loader.hide();
        };

    };

    BasketList.prototype.selectorEvents = function(selectors) {
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

    /* Placeholder function for extending initializer */
    BasketList.prototype.initExtend = function() { /* Null */ };

    /**
     * Generate a pseudo uuid basket key
     *
     * @return {string}
     */
    BasketList.prototype.generateBasketKey = function() {
        return 'simplebasket.' + (Math.floor(Math.random() * Math.floor(Number.MAX_SAFE_INTEGER))).toString(36);
    };

    /**
     * initialise from data
     *
     * @param {object} data required properties for list base
     * @return {Promise}
     */
    BasketList.prototype.init = function(data) {
        var parent = data.parent,
            behatString = 'BasketList-pause',
            that = this;

        M.util.js_pending(behatString);

        return new Promise(function(resolve) {
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

            var basket = null;
            // Either we use the basket provided
            // or we create a new basket depending on the type passed
            // if no basket type and key is passed we create a random basket key
            // and use a simple basket by default
            if (data.basket) {
                basket = data.basket;
            } else {
                if (!data.basketKey) {
                    data.basketKey = that.generateBasketKey();
                }
                if (data.basketType === 'session') {
                    basket = new SessionBasket(data.basketKey);
                } else {
                    basket = new SimpleBasket(data.basketKey);
                }
            }

            that.basketManager = BasketManager.init(parent, basket);

            that.basketEvents(that.basketManager);

            if (data.crumbtrail) {
                that.crumb = Crumb.init(parent, data.crumbtrail);
            }

            // A list selector base requires checkboxes
            data.list.map.hasCheckboxes = true;

            that.list = List.init(parent, data.list);
            that.listEvents(that.list);

            that.selectors = Selectors.init(parent);
            that.selectorEvents(that.selectors);

            that.initExtend();
            resolve();

            // Make sure all strings are loaded before doing the request
            var promiseList = [that.list.prepare()];
            if (data.crumbtrail) {
                promiseList.push(that.crumb.loadCrumbStrings());
            }
            Promise.all(promiseList).then(function() {
                that.basketManager.loadAndRender().then(function() {
                    that.updatePage([that.list.getUpdateRequestArgs()]);
                    M.util.js_complete(behatString);
                });
            });
        });
    };

    return BasketList;
});
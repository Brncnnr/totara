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
 * @subpackage filters
 */

 /*
  * Maintains the state of page filters for service requests
  */
define([], function() {

    /**
     * Class constructor for the Filters.
     *
     * @class
     * @constructor
     */
    function Filters() {
        if (!(this instanceof Filters)) {
            return new Filters();
        }
        this.filters = {};
        this.defaults = {};
    }

    /**
     * Clears all filters
     */
    Filters.prototype.clearFilters = function() {
        this.filters = this.defaults;
        this.onFiltersUpdate();
    };

    /**
     * Sets default filters
     */
    Filters.prototype.setDefaults = function(filters) {
        this.defaults = filters;
        this.filters = filters;
    };

    /**
     * Get a single filter or null if it does not exist
     *
     * @param {string} name
     * @return {string}
     */
    Filters.prototype.getFilter = function(name) {
        return this.filters[name];
    };

    /**
     * Return set filters
     *
     * @return {object} filters
     */
    Filters.prototype.getFilters = function() {
        return this.filters;
    };

    /**
     * Get a single filter or null if it does not exist
     *
     * @param {string} name
     * @return {boolean}
     */
    Filters.prototype.hasFilter = function(name) {
        return this.filters.hasOwnProperty(name);
    };

    /* on filter update */
    Filters.prototype.onFiltersUpdate = function() { /* Null */ };

    /**
     * Removes a single filter from the filters object
     *
     * @param {string} name
     * @param {object} groupValues
     */
    Filters.prototype.removeFilter = function(name, groupValues) {
        // if there are groupValues in there just overwrite the filter
        if (groupValues && groupValues.length > 0) {
            this.setFilter(name, groupValues);
        } else {
            delete this.filters[name];
        }
        this.onFiltersUpdate();
    };

    /**
     * Sets a specific filter
     *
     * @param {string} name
     * @param {string} value
     * @param {object} groupValues
     */
    Filters.prototype.setFilter = function(name, value, groupValues) {
        if (groupValues) {
            value = groupValues;
        }
        this.filters[name] = value;
        this.onFiltersUpdate();
    };

    return Filters;
 });
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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_core
 * @subpackage basket
 */

/*
 * Represents one simple basket, stores the values only internally
 */
define(['totara_competency/basket'], function(Basket) {

    /**
     * Class constructor for the Simple Basket.
     *
     * @class
     * @constructor
     *
     * @param {string} basketKey
     */
    function SimpleBasket(basketKey) {
        Basket.call(this, basketKey, false);
        this.basketItems = [];
    }

    SimpleBasket.prototype = Object.create(Basket.prototype);
    SimpleBasket.prototype.constructor = SimpleBasket;

    /**
     * Add given values to the basket
     *
     * @param {Array|Number} values
     * @return {Promise}
     */
    SimpleBasket.prototype.add = function(values) {
        if (!Array.isArray(values)) {
            values = [values];
        }

        var that = this;

        return new Promise(function(resolve) {
            that.basketItems = that.basketItems.concat(values);
            resolve(that.basketItems);
        });
    };

    /**
     * Remove given values from the basket
     *
     * @param {Array|Number} values
     * @return {Promise}
     */
    SimpleBasket.prototype.remove = function(values) {
        if (!Array.isArray(values)) {
            values = [values];
        }

        var that = this;

        return new Promise(function(resolve) {
            for (var i = 0; i < values.length; i++) {
                that.basketItems.splice(that.basketItems.indexOf(values[i]), 1);
            }
            resolve(that.basketItems);
        });
    };

    /**
     * Replace values with given values
     *
     * @param {Array|Number} values
     * @return {Promise}
     */
    SimpleBasket.prototype.replace = function(values) {
        if (!Array.isArray(values)) {
            values = [values];
        }

        var that = this;

        return new Promise(function(resolve) {
           that.basketItems = values;
           resolve(that.basketItems);
        });
    };

    /**
     * Load values from basket
     *
     * @return {Promise}
     */
    SimpleBasket.prototype.load = function() {
        var that = this;
        return new Promise(function(resolve) {
            resolve(that.basketItems);
        });
    };

    /**
     * Delete this basket
     *
     * @return {SimpleBasket}
     */
    SimpleBasket.prototype.delete = function() {
        var that = this;
        return new Promise(function(resolve) {
            that.basketItems = [];
            resolve();
        });
    };

    return SimpleBasket;

});
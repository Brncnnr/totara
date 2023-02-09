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
 * @package totara_competency
 * @subpackage basket
 */

/*
 * This is the base class for all baskets. Concrete baskets have to implement
 *
 * add()
 * remove()
 * replace()
 * load()
 * delete()
 *
 * all of those methods have to return a Promise.
 */
define(['core/notification'], function(notification) {

    /**
     * @class
     * @constructor
     *
     * @param {string} basketKey
     * @param {Boolean} isPersistent
     */
    function Basket(basketKey, isPersistent) {
        this.basketKey = basketKey;
        this.persistant = isPersistent;
    }

    /**
     * Get basket key
     *
     * @return {string}
     */
    Basket.prototype.getKey = function() {
        return this.basketKey;
    };

    /**
     * Add, replace or remove given values from the basket
     *
     * @param {String} action add or remove
     * @param {Array|Number} values
     * @return {Promise|null}
     */
    Basket.prototype.update = function(action, values) {
        if (action === 'add') {
            return this.add(values);
        } else if (action === 'remove') {
            return this.remove(values);
        } else if (action === 'replace') {
            return this.replace(values);
        } else {
            notification.exception({
                fileName: 'simple_basket.js',
                message: 'Invalid basket action given, expecting either add, remove or replace',
                name: 'Error on basket action'
            });
            return null;
        }
    };

    /**
     * is persistent across requests
     *
     * @return {Boolean}
     */
    Basket.prototype.isPersistent = function() {
        return this.persistant;
    };

    /**
     * Copy from source basket into this one, all existing items within this basket will be replaced by this
     *
     * @param {Basket} sourceBasket
     * @return {Promise}
     */
    Basket.prototype.copyFrom = function(sourceBasket) {
        var that = this;

        return new Promise(function(resolve) {
            sourceBasket.load().then(function(values) {
                that.replace(values).then(function(values) {
                    resolve(values);
                });
            });
        });
    };

    /**
     * Move all values from the Source Basket into this one, all existing items within this basket will be replaced by this
     *
     * @param {Basket} sourceBasket
     * @return {Promise}
     */
    Basket.prototype.moveFrom = function(sourceBasket) {
        var that = this;

        return new Promise(function(resolve) {
            sourceBasket.load().then(function(values) {
                that.add(values).then(function(values) {
                    sourceBasket.delete().then(function() {
                        resolve(values);
                    });
                });
            });
        });
    };

    /**
     * BASKETS HAVE TO IMPLEMENT THE FOLLOWING METHODS
     *
     * ALL METHODS HAVE TO RETURN A PROMISE
     */

    /**
     * Add given values to the basket
     *
     * @param {Array|Number} values
     * @return {Promise}
     */
    Basket.prototype.add = function(values) {
        var error = 'You need to implement the add() function of your basket';
        notification.exception({message: error});
        return Promise.reject(values);
    };

    /**
     * Remove given values from the basket
     *
     * @param {Array|Number} values
     * @return {Promise}
     */
    Basket.prototype.remove = function(values) {
        var error = 'You need to implement the remove() function of your basket';
        notification.exception({message: error});
        return Promise.reject(values);
    };

    /**
     * Replace values with given values
     *
     * @param {Array|Number} values
     * @return {Promise}
     */
    Basket.prototype.replace = function(values) {
        var error = 'You need to implement the replace() function of your basket';
        notification.exception({message: error});
        return Promise.reject(values);
    };

    /**
     * Load values from basket
     *
     * @return {Promise}
     */
    Basket.prototype.load = function() {
        var error = 'You need to implement the load() function of your basket';
        notification.exception({message: error});
        return Promise.reject();
    };

    /**
     * Delete this basket
     *
     * @return {Promise}
     */
    Basket.prototype.delete = function() {
        var error = 'You need to implement the delete() function of your basket';
        notification.exception({message: error});
        return Promise.reject();
    };

    return Basket;

});

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
 * Represents one session basket, baskets are stored in the backend and
 * interaction happens via webservice calls
 */
define(['totara_competency/basket', 'core/ajax'], function(Basket, ajax) {

    /**
     * Class constructor for the Filters.
     *
     * @class
     * @constructor
     *
     * @param {string} basketKey
     */
    function SessionBasket(basketKey) {
        Basket.call(this, basketKey, true);
    }

    SessionBasket.prototype = Object.create(Basket.prototype);
    SessionBasket.prototype.constructor = SessionBasket;

    /**
     * Add given values to the basket
     *
     * @param {Array|Number} values
     * @return {Promise}
     */
    SessionBasket.prototype.add = function(values) {
        return this.doUpdate('add', values);
    };

    /**
     * Remove given values from the basket
     *
     * @param {Array|Number} values
     * @return {Promise}
     */
    SessionBasket.prototype.remove = function(values) {
        return this.doUpdate('remove', values);
    };

    /**
     * Replace values with given values
     *
     * @param {Array|Number} values
     * @return {Promise}
     */
    SessionBasket.prototype.replace = function(values) {
        return this.doUpdate('replace', values);
    };

    /**
     * Add or remove given values from the basket
     *
     * @param {String} action add or remove
     * @param {Array|String} values
     * @return {Promise}
     */
    SessionBasket.prototype.doUpdate = function(action, values) {
        if (!Array.isArray(values)) {
            values = [values];
        }

        var that = this;

        return new Promise(function(resolve) {
            var ajaxRequest = {
                args: {
                    action: action,
                    basket: that.basketKey,
                    ids: values
                },
                methodname: 'totara_core_basket_update'
            };
            ajax.getData(ajaxRequest).then(function(result) {
                resolve(result.results.ids);
            });
        });
    };

    /**
     * Load values from basket
     *
     * @return {Promise}
     */
    SessionBasket.prototype.load = function() {
        var that = this;
        return new Promise(function(resolve) {
            var ajaxRequest = {
                args: {basket: that.basketKey},
                methodname: 'totara_core_basket_show'
            };
            ajax.getData(ajaxRequest).then(function(result) {
                resolve(result.results.ids);
            });
        });
    };

    /**
     * Copy from source basket into this one, all existing items within this basket will be replaced by this
     *
     * @param {SessionBasket} sourceBasket
     * @return {Promise}
     */
    SessionBasket.prototype.copyFrom = function(sourceBasket) {
        var that = this;

        // We can only make use of the copy webservice if sourceBasket is of the same type
        // as the current one, otherwise we need to do use the standard behaviour
        // which loads the source basket and replaces the current items
        if (!(sourceBasket instanceof SessionBasket)) {
            return Basket.prototype.copyFrom.call(that, sourceBasket);
        } else {
            return new Promise(function(resolve) {
                var ajaxRequest = {
                    args: {
                        options: {
                            replace: true
                        },
                        sourcebasket: sourceBasket.getKey(),
                        targetbasket: that.basketKey
                    },
                    methodname: 'totara_core_basket_copy'
                };
                ajax.getData(ajaxRequest).then(function(result) {
                    resolve(result.results.ids);
                });
            });
        }
    };

    /**
     * Move all values from the Source Basket into this one, all existing items within this basket will be replaced by this
     *
     * @param {SessionBasket} sourceBasket
     * @return {Promise}
     */
    SessionBasket.prototype.moveFrom = function(sourceBasket) {
        var that = this;

        // We can only make use of the cpoy webservice if sourceBasket is of the same type
        // as the current one, otherwise we need to do use the standard behaviour
        // which loads the source basket, replaces the current items and deletes the source
        if (!(sourceBasket instanceof SessionBasket)) {
            return Basket.prototype.moveFrom.call(that, sourceBasket);
        } else {
            return new Promise(function(resolve) {
                // We can only make use of the copy webservice if sourceBasket is of the same type
                // as the current one, otherwise we need to do a manual move
                var ajaxRequest = {
                    args: {
                        options: {
                            deletesource: true,
                            replace: true
                        },
                        sourcebasket: sourceBasket.getKey(),
                        targetbasket: that.basketKey
                    },
                    methodname: 'totara_core_basket_copy'
                };
                ajax.getData(ajaxRequest).then(function(result) {
                    resolve(result.results.ids);
                });
            });
        }
    };

    /**
     * Delete this basket
     *
     * @return {Promise}
     */
    SessionBasket.prototype.delete = function() {
        var that = this;
        return new Promise(function(resolve) {
            var ajaxRequest = {
                args: {basket: that.basketKey},
                methodname: 'totara_core_basket_delete'
            };
            ajax.getData(ajaxRequest).then(function() {
                resolve();
            });
        });
    };

    return SessionBasket;

});
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
 * @subpackage basket_manager
 */

 /* The basket manager, handles common actions for basket storage.
  * It also manages events propagated from the selection_basket AMD including clearing the basket
  * and showing/hiding the basket view
  */
define(['core/notification'], function(notification) {

    /**
     * Class constructor for the BasketManager.
     *
     * @class
     * @constructor
     */
    function BasketManager() {
        if (!(this instanceof BasketManager)) {
            return new BasketManager();
        }
        this.basket = null;
        this.classBasketWide = 'tw-selectionBasket--wide';
        this.eventListener = 'totara_core/selection_basket';
        this.eventNode = null;
        this.widget = null;
        this.itemQueueAction = '';
        this.itemQueue = [];
    }

    // Listen for propagated events
    BasketManager.prototype.bubbledEventsListener = function() {
        var that = this;

        // Events from Selection basket
        this.eventNode.addEventListener(this.eventListener + ':transition', function(e) {
            // Basket is now small
            if (e.detail.state === 'small') {
                that.onBasketHidden();
            }
            M.util.js_complete('basketTransition');
        });

        // Events from Selection basket
        this.eventNode.addEventListener(this.eventListener + ':update', function(e) {
            var action = e.detail.action;

            if (action === 'show') {
                M.util.js_pending('basketTransition');
                that.onBasketShow();
            } else if (action === 'hide') {
                that.onBasketHide();
            } else if (action === 'clear') {
                that.onBasketClear();
            }
        });
    };

    /**
     * Get the propagated event name
     *
     * @return {string} event listener
     */
    BasketManager.prototype.getBasketKey = function() {
        return this.basket.getKey();
    };

    /**
     * Get the propagated event name
     *
     * @return {string} event listener
     */
    BasketManager.prototype.getEventListener = function() {
        return this.eventListener;
    };

    /**
     * Return wide class variation name
     *
     * @return {string} class name
     */
    BasketManager.prototype.getWideClass = function() {
        return this.classBasketWide;
    };

    /* Clear basket selection */
    BasketManager.prototype.onBasketClear = function() { /* Null */ };

    /* Hidden basket selection */
    BasketManager.prototype.onBasketHidden = function() { /* Null */ };

    /* Hide basket selection */
    BasketManager.prototype.onBasketHide = function() { /* Null */ };

    /* Show basket selection */
    BasketManager.prototype.onBasketShow = function() { /* Null */ };

    /* on basket update */
    BasketManager.prototype.onBasketUpdate = function() { /* Null */ };

    /**
     * Set unique basket request key for storing selection
     *
     * @param {SessionBasket|SimpleBasket} basket
     */
    BasketManager.prototype.getBasket = function() {
        return this.basket;
    };

    /**
     * Set unique basket request key for storing selection
     *
     * @param {SessionBasket|SimpleBasket} basket
     */
    BasketManager.prototype.setBasket = function(basket) {
        this.basket = basket;
    };

    /**
     * Toggle basket show/hide btn
     */
    BasketManager.prototype.toggleExpandedView = function() {
        this.widget.classList.toggle(this.classBasketWide);
    };

    /**
     * Toggle basket show/hide btn
     */
    BasketManager.prototype.toggleShowBasketBtn = function() {
        this.widget.classList.toggle('tw-selectionBasket__displayed');
    };

    /**
     * Render the basket with the provided data
     *
     * @param {Array} ids
     * @return {Promise}
     */
    BasketManager.prototype.renderBasket = function(ids) {
        var count,
            selectionBasket = this.widget,
            that = this;

        return new Promise(function(resolve) {
            // Update count
            if (selectionBasket) {
                count = ids.length;
                selectionBasket.setAttribute('data-tw-selectionbasket-countchange', count);
            }
            // event basket rendered
            that.onBasketUpdate(ids);
            resolve();
        });
    };

    /**
     * Set page number for request
     *
     * @param {node} parent
     */
    BasketManager.prototype.setParent = function(parent) {
        this.widget = parent;
    };

    /**
     * Set page number for request
     *
     * @param {node} parent
     */
    BasketManager.prototype.setParentEventNode = function(parent) {
        this.eventNode = parent;
    };

    /**
     * Add items to the queue, it will be processed if you call updateAndRender()
     * this method provides a fluent interface so it can be chained.
     *
     * Example:
     * this.basketManager.queueAdd(1).queueAdd(2).queueAdd(3);
     *
     * @param {Array|String} items
     * @return {BasketManager}
     */
    BasketManager.prototype.queueAdd = function(items) {
        if (this.itemQueueAction !== 'add') {
            this.itemQueue = [];
        }
        this.itemQueueAction = 'add';
        if (Array.isArray(items)) {
            this.itemQueue = this.itemQueue.concat(items);
        } else {
            this.itemQueue.push(items);
        }
        return this;
    };

    /**
     * Add items to the queue for the replace action, it will be processed if you call updateAndRender()
     * this method provides a fluent interface so it can be chained.
     *
     * Example:
     * this.basketManager.queueReplace(1).queueReplace(2).queueReplace(3);
     *
     * @param {Array|String} items
     * @return {BasketManager}
     */
    BasketManager.prototype.queueReplace = function(items) {
        if (this.itemQueueAction !== 'replace') {
            this.itemQueue = [];
        }
        this.itemQueueAction = 'replace';
        if (Array.isArray(items)) {
            this.itemQueue = this.itemQueue.concat(items);
        } else {
            this.itemQueue.push(items);
        }
        return this;
    };

    /**
     * Add items to the queue for the remove action, it will be processed if you call updateAndRender()
     * this method provides a fluent interface so it can be chained.
     *
     * Example:
     * this.basketManager.queueRemove(1).queueRemove(2).queueRemove(3);
     *
     * @param {Array|String} items
     * @return {BasketManager}
     */
    BasketManager.prototype.queueRemove = function(items) {
        if (this.itemQueueAction !== 'remove') {
            this.itemQueue = [];
        }
        this.itemQueueAction = 'remove';
        if (Array.isArray(items)) {
            this.itemQueue = this.itemQueue.concat(items);
        } else {
            this.itemQueue.push(items);
        }
        return this;
    };

    /**
     * Update the basket by triggering the action done last by add, remove, replace, and render it.
     *
     * Usage:
     * this.basketManager
     *      .add(1)
     *      .add(2)
     *      .add(4)
     *      .updateAndRender();
     *
     * @return {Promise}
     */
    BasketManager.prototype.updateAndRender = function() {
        // Get items from queue
        var action = this.itemQueueAction,
            items = this.itemQueue,
            that = this;

        if (action === '' || items.length === 0) {
            notification.exception({message: 'No item was queue to perform an action, have you forgotten to call add, remove or replace?'});
            return Promise.reject();
        }

        that.itemQueue = [];
        that.itemQueueAction = '';

        return this.basket.update(action, items).then(function(values) {
            return that.renderBasket(values);
        }).catch(function() {
            notification.exception({message: 'An error occurred updating the basket.'});
        });
    };

    /**
     * Delete the basket
     *
     * @return {Promise}
     */
    BasketManager.prototype.delete = function() {
        return this.basket.delete().catch(function() {
            notification.exception({message: 'An error occurred deleting the basket.'});
        });
    };

    /**
     * Delete the basket and render it
     *
     * @return {Promise}
     */
    BasketManager.prototype.deleteAndRender = function() {
        var that = this;

        return this.delete().then(function() {
            return that.renderBasket([]);
        });
    };

    /**
     * Load the basket and render it
     *
     * @return {Promise}
     */
    BasketManager.prototype.loadAndRender = function() {
        var that = this;

        return this.load().then(function(values) {
            return that.renderBasket(values);
        });
    };

    /**
     * Return the args for delete basket webservice
     *
     * @return {Promise}
     */
    BasketManager.prototype.load = function() {
        return this.basket.load().catch(function() {
            notification.exception({message: 'An error occurred loading the basket.'});
        });
    };

    /**
     * is persistent across requests
     *
     * @return {Boolean}
     */
    BasketManager.prototype.isPersistent = function() {
        return this.basket.isPersistent();
    };

    /**
     * initialisation method
     *
     * @param {node} parent
     * @param {SessionBasket|SimpleBasket} basket
     * @returns {Object} promise
     */
    var init = function(parent, basket) {

        var basketNode = parent.querySelector('[data-tw-selectionBasket]');

        var wgt = new BasketManager();
        wgt.setParent(basketNode);
        wgt.setParentEventNode(parent);
        wgt.setBasket(basket);
        wgt.bubbledEventsListener();
        return wgt;
    };

    return {
        init: init
    };
 });

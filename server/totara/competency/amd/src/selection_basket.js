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

define([], function() {

        /**
         * Class constructor for the selection basket.
         * A selection basket is a UI driving widget which propagates events for clearing, showing and hiding a basket view
         *
         * @class
         * @constructor
         */
        function SelectionBasket() {
            if (!(this instanceof SelectionBasket)) {
                return new SelectionBasket();
            }

            this.countChange = 'data-tw-selectionbasket-countchange';
            this.countRequired = 'data-tw-selectionBasket-countReq';
            this.disabledBtnsClass = 'tw-selectionBasket__btn_disabled';
            this.eventKey = null;
            this.emptyBasketClass = 'tw-selectionBasket__empty';
            this.expandedActionsClass = 'tw-selectionBasket__actions_group_active';
            this.wideBasketClass = 'tw-selectionBasket--wide';
            this.widget = null;
        }

        SelectionBasket.prototype = {
            constructor: SelectionBasket,

            /**
             * Add event listeners
             *
             */
            events: function() {
                var that = this;

                this.widget.addEventListener('click', function(e) {
                    if (!e.target) {
                        return;
                    }

                    if (e.target.closest('.' + that.disabledBtnsClass)) {
                        e.preventDefault();
                        return;
                    }

                    var actionType,
                        node;

                    if (e.target.closest('[data-tw-selectionBasket-group-trigger]')) {
                        e.preventDefault();

                        var list = e.target.closest('[data-tw-selectionBasket-group]');
                        list.classList.toggle(that.expandedActionsClass);

                    } else if (e.target.closest('[data-tw-selectionbasket-action]')) {
                        e.preventDefault();
                        node = e.target.closest('[data-tw-selectionbasket-action]');
                        actionType = node.getAttribute('data-tw-selectionbasket-action');


                        that.hideExpandedList();
                        that.triggerEvent('update', {
                            action: actionType
                        });

                    } else if (e.target.closest('[data-tw-selectionbasket-customAction]')) {
                        node = e.target.closest('[data-tw-selectionbasket-customAction]');
                        actionType = node.getAttribute('data-tw-selectionbasket-customAction');

                        that.hideExpandedList();
                        that.triggerEvent('customUpdate', {
                            action: actionType
                        });
                    }
                });

                this.widget.addEventListener('transitionend', function(e) {
                    if (e.propertyName === 'max-width') {
                        if (e.target.classList.contains(that.wideBasketClass)) {
                            that.triggerEvent('transition', {
                                state: 'large'
                            });
                        } else {
                            that.triggerEvent('transition', {
                                state: 'small'
                            });
                        }
                    }
                });

                // Check if expanded view displayed when clicking out of context
                document.addEventListener('click', function(e) {
                    var expandedList = that.widget.querySelector('.' + that.expandedActionsClass);

                    if (!expandedList || !e.target || e.target.closest('.' + that.expandedActionsClass)) {
                        return;
                    }

                    expandedList.classList.toggle(that.expandedActionsClass);
                });

                // Create an observer for updating basket count
                var observeCountChange = new MutationObserver(function() {
                    if (that.widget.hasAttribute(that.countChange)) {
                        var count = parseInt(that.widget.getAttribute(that.countChange));

                        if (!count) {
                            count = 0;
                        }

                        that.updateCount(count);
                        that.widget.removeAttribute(that.countChange);
                    }
                });

                // Start observing the parent for changes to the count
                observeCountChange.observe(this.widget, {
                    attributes: true,
                    attributeFilter: [that.countChange],
                    subtree: false
                });

            },

            /**
             * Hide expanded action list
             *
             */
            hideExpandedList: function() {
                var expandedListShown = this.widget.querySelector('.' + this.expandedActionsClass);

                // If expanded view shown, hide it
                if (expandedListShown) {
                    expandedListShown.classList.toggle(this.expandedActionsClass);
                }
            },

            /**
             * Set event propagation key
             *
             */
            setEventKey: function() {
                this.eventKey = this.widget.getAttribute('data-tw-selectionBasket-events');
            },

            /**
             * Set parent
             *
             * @param {node} parent
             */
            setParent: function(parent) {
                this.widget = parent;
            },

            /**
             * Trigger event
             *
             * @param {string} eventName
             * @param {object} data
             */
            triggerEvent: function(eventName, data) {
                var propagateEvent = new CustomEvent(this.eventKey + ':' + eventName, {
                    bubbles: true,
                    detail: data
                });
                this.widget.dispatchEvent(propagateEvent);
            },

            /**
             * Update button state based on count
             *
             * @param {int} count
             */
            updateBtnState: function(count) {
                var btns = this.widget.querySelectorAll('[' + this.countRequired + ']'),
                    hasDisabledBtn = this.widget.querySelector('.' + this.disabledBtnsClass),
                    i;

                // If buttons should be enabled
                if (hasDisabledBtn && count) {
                    for (i = 0; i < btns.length; i++) {
                        btns[i].classList.remove(this.disabledBtnsClass);
                    }

                // If buttons should be disabled
                } else if (!hasDisabledBtn && !count) {
                    for (i = 0; i < btns.length; i++) {
                        btns[i].classList.add(this.disabledBtnsClass);
                    }
                }
            },

            /**
             * Update count
             *
             * @param {int} count
             */
            updateCount: function(count) {
                var countDisplay = this.widget.querySelector('[data-tw-selectionbasket-count]');
                countDisplay.innerHTML = count;
                this.updateBtnState(count);
                if (count === 0) {
                    this.widget.classList.add(this.emptyBasketClass);
                } else {
                    this.widget.classList.remove(this.emptyBasketClass);
                }
            }

        };

        /**
         * widget initialisation method
         *
         * @param {node} widgetParent
         * @returns {ES6Promise} promise
         */
        var init = function(widgetParent) {
            return new Promise(function(resolve) {
                // Create an instance of widget
                var wgt = new SelectionBasket();
                wgt.setParent(widgetParent);
                wgt.setEventKey();
                wgt.events();
                resolve(wgt);
            });
        };

        return {
            init: init
        };
    });
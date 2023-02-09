/*
 * This file is part of Totara LMS
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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_facetoface
 */

define([], function() {
    /**
     * Get the viewport size excluding scroll bars.
     * Based on the following code: https://bugzilla.mozilla.org/show_bug.cgi?id=156388#c14
     * @returns {object} width and height
     */
    function getViewSize() {
        var doc = document.documentElement || document.body.parentNode;
        if (typeof doc.clientWidth !== 'number') {
            doc = document.body;
        }
        if (typeof document.clientWidth === 'number') {
            return {width: document.clientWidth, height: document.clientHeight};
        }
        var body = document.body,
            win = document.defaultView || window,
            w, h;
        if (doc === body || (w = Math.max(doc.clientWidth, body.clientWidth)) > win.innerWidth || (h = Math.max(doc.clientHeight, body.clientHeight)) > win.innerHeight) {
            return {width: body.clientWidth, height: body.clientHeight};
        } else {
            return {width: w, height: h};
        }
    }

    /**
     * Call js_pending and return a function that calls js_complete.
     * @param {...*} args
     * @returns {function}
     */
    function jsPending(args) {
        var key = 'mod_facetoface_event-action:';
        key += arguments.length === 1 ? args : Array.prototype.reduce.call(arguments, function(p, e) {
            return p + ':' + e;
        });
        M.util.js_pending(key);
        return function() {
            M.util.js_complete(key);
        };
    }

    /**
     * The drop-down button implementation.
     * @param {HTMLElement} element
     * @class
     * @constructor
     */
    function DropDownButton(element) {
        if (!(this instanceof DropDownButton)) {
            return new DropDownButton(element);
        }
        this._init(element);
    }
    DropDownButton.OPEN_EVENT = 'mod_facetoface:dropdown.open';
    DropDownButton.CLOSED_EVENT = 'mod_facetoface:dropdown.closed';

    (function() {
        var keys = {
            TAB: 9,
            ENTER: 13,
            ESCAPE: 27,
            SPACE: 32,
            LEFT: 37,
            UP: 38,
            RIGHT: 39,
            DOWN: 40
        };

        this.constructor = DropDownButton;

        /**
         * Initialiser.
         * @param {HTMLElement} element
         */
        this._init = function(element) {
            if (!element.matches('.mod_facetoface__sessionlist__action__dropdown')) {
                throw new Error('DD: not a dropdown button');
            }
            this.el = element;
            this.parent = element.closest('.mod_facetoface__sessionlist__action__buttons');
            if (this.parent === null) {
                throw new Error('DD: button group not found');
            }
            this.menu = this.parent.querySelector('[data-f2f-dropdown-id="' + element.id + '"]');
            if (this.menu === null) {
                throw new Error('DD: menu not found');
            }
            this.menuItems = Array.prototype.slice.apply(this.menu.querySelectorAll('li:not(.disabled) > .dropdown-item'))
                .map(function(e) {
                    return e.parentNode;
                });
            this.scrollTimerId = null;
            this.scrollDone = null;

            this.el.setAttribute('aria-expanded', 'false');

            this._onKeyDownMenu = this._onKeyDownMenu.bind(this);
            this._onScrollOrResize = this._onScrollOrResize.bind(this);
            this._onClickBackground = this._onClickBackground.bind(this);
            this._onBlurButton = this._onBlurButton.bind(this);
            this._onKeyDownButton = this._onKeyDownButton.bind(this);
            this.el.addEventListener('click', this._onClickButton.bind(this));
            this.el.addEventListener('focus', this._onFocusButton.bind(this));
        };

        /**
         * Check the visibility of the menu.
         * @returns {boolean}
         */
        this.isMenuOpen = function() {
            return this.parent.classList.contains('open');
        };

        /**
         * Open the menu.
         * @returns {Promise}
         */
        this.openMenu = function() {
            this.parent.classList.add('open'); // Let Bootstrap CSS handle this
            this.el.setAttribute('aria-expanded', 'true');

            window.addEventListener('click', this._onClickBackground, true);
            window.addEventListener('keydown', this._onKeyDownMenu, true);
            window.addEventListener('scroll', this._onScrollOrResize, true);
            window.addEventListener('resize', this._onScrollOrResize, true);
            this._fireEvent(DropDownButton.OPEN_EVENT);
            return this.repositionMenu();
        };

        /**
         * Close the menu.
         * @param {boolean} focus whether the button regains focus
         * @returns {Promise}
         */
        this.closeMenu = function(focus) {
            this.parent.classList.remove('open'); // Let Bootstrap CSS handle this
            this.el.setAttribute('aria-expanded', 'false');

            window.removeEventListener('click', this._onClickBackground, true);
            window.removeEventListener('keydown', this._onKeyDownMenu, true);
            window.removeEventListener('scroll', this._onScrollOrResize, true);
            window.removeEventListener('resize', this._onScrollOrResize, true);

            if (this.scrollTimerId) {
                window.cancelAnimationFrame(this.scrollTimerId);
                this.scrollTimerId = null;
            }
            if (this.scrollDone) {
                this.scrollDone();
                this.scrollDone = null;
            }
            if (focus) {
                this.el.focus();
            }
            this._fireEvent(DropDownButton.CLOSED_EVENT);
            // just return a Promise in case we need some asynchronous code in the future.
            return Promise.resolve();
        };

        /**
         * Update the location of the menu.
         * @returns {Promise}
         */
        this.repositionMenu = function() {
            if (this.scrollTimerId) {
                window.cancelAnimationFrame(this.scrollTimerId);
                this.scrollTimerId = null;
            }
            var that = this;
            return new Promise(function(resolve) {
                var callback = function() {
                    that.scrollTimerId = null;
                    var buttonRect = that.el.getBoundingClientRect();
                    var viewSize = getViewSize();
                    var top = buttonRect.top + buttonRect.height;
                    that.menu.style.top = top + 'px';
                    if (getComputedStyle(that.el).direction === 'rtl') {
                        var left = buttonRect.left;
                        if (left < 0) {
                            left = 0;
                        }
                        that.menu.style.left = left + 'px';
                        that.menu.style.right = '';
                    } else {
                        var right = viewSize.width - buttonRect.left - buttonRect.width;
                        if (right < 0) {
                            right = 0;
                        }
                        that.menu.style.left = '';
                        that.menu.style.right = right + 'px';
                    }
                    that.scrollTimerId = null;
                    if (that.scrollDone) {
                        that.scrollDone();
                        that.scrollDone = null;
                    }
                    resolve();
                };
                that.scrollDone = that.scrollDone || jsPending('reposition', that.el.id);
                that.scrollTimerId = window.requestAnimationFrame(callback);
            });
        };

        /**
         * Select a menu item.
         * @param {integer} index position of the menu item
         * @returns {Promise}
         */
        this.selectItem = function(index) {
            if (this.menuItems.length) {
                index = index % this.menuItems.length;
                if (index < 0) {
                    index += this.menuItems.length;
                }
                var item = this.menuItems[index].querySelector('a');
                var done = jsPending('selectitem', this.el.id, index);
                return new Promise(function(resolve) {
                    setTimeout(function() {
                        item.focus();
                        done();
                        resolve();
                    }, 0);
                });
            }
            return Promise.resolve();
        };

        /**
         * Open the menu and select a menu item.
         * This function just calls openMenu and selectItem.
         * @param {integer} index position of the menu item
         * @returns {Promise}
         */
        this.openMenuAndSelectItem = function(index) {
            var that = this;
            return that.openMenu().then(function() {
                return that.selectItem(index);
            });
        };

        /**
         * Trigger a custom event.
         * @param {string} type event type name
         */
        this._fireEvent = function(type) {
            var event = new CustomEvent(type, {
                bubbles: true, detail: this
            });
            this.el.dispatchEvent(event);
        };

        /**
         * A 'click' event listener for the dropdown button.
         * @param {event} event
         */
        this._onClickButton = function(event) {
            event.preventDefault();
            // Override Bootstrap.
            event.stopPropagation();
            if (this.isMenuOpen()) {
                this.closeMenu();
            } else {
                this.openMenu();
            }
        };

        /**
         * A 'focus' event listener for the dropdown button.
         */
        this._onFocusButton = function() {
            // Override Bootstrap.
            event.stopPropagation();

            this.el.addEventListener('blur', this._onBlurButton);
            this.el.addEventListener('keydown', this._onKeyDownButton);
        };

        /**
         * A 'blur' event listener for the dropdown button.
         */
        this._onBlurButton = function() {
            // Override Bootstrap.
            event.stopPropagation();

            this.el.removeEventListener('blur', this._onBlurButton);
            this.el.removeEventListener('keydown', this._onKeyDownButton);
        };

        /**
         * A 'keydown' event listener for the dropdown button.
         * @param {KeyboardEvent} event
         */
        this._onKeyDownButton = function(event) {
            // Override Bootstrap.
            event.stopPropagation();

            if (this.isMenuOpen()) {
                switch (event.keyCode) {
                    case keys.TAB:
                        // mouse click + press the tab key.
                        if (event.shiftKey) {
                            this.closeMenu();
                        } else {
                            this.selectItem(0);
                        }
                        break;

                    case keys.ESCAPE:
                        this.closeMenu();
                        break;
                }
            } else {
                switch (event.keyCode) {
                    case keys.DOWN:
                        event.preventDefault();
                        this.openMenuAndSelectItem(0);
                        break;

                    case keys.UP:
                        event.preventDefault();
                        this.openMenuAndSelectItem(-1);
                        break;

                    case keys.ENTER:
                    case keys.SPACE:
                        event.preventDefault();
                        this.openMenuAndSelectItem(0);
                        break;
                }
            }
        };

        /**
         * A 'keydown' event listener for the dropdown menu.
         * @param {event} event
         */
        this._onKeyDownMenu = function(event) {
            var item = event.target.closest('li');
            if (item === null && (event.target !== this.el && !this.el.contains(event.target))) {
                return;
            }
            // Override Bootstrap.
            event.stopPropagation();

            var index = this.menuItems.indexOf(item);

            switch (event.keyCode) {
                case keys.ESCAPE:
                    event.preventDefault();
                    this.closeMenu(true);
                    break;

                case keys.UP:
                    event.preventDefault();
                    this.selectItem(index < 0 ? index : index - 1);
                    break;

                case keys.DOWN:
                    event.preventDefault();
                    this.selectItem(index + 1);
                    break;

                case keys.TAB:
                    event.stopPropagation();
                    if (event.shiftKey) {
                        if (index <= 0) {
                            this.closeMenu(true);
                        } else {
                            event.preventDefault();
                            this.selectItem(index - 1);
                        }
                    } else {
                        if (index >= this.menuItems.length - 1) {
                            this.closeMenu(true);
                        } else {
                            event.preventDefault();
                            this.selectItem(index + 1);
                        }
                    }
                    break;
            }
        };

        /**
         * The 'click' event listener for the dropdown menu.
         * @param {event} event
         */
        this._onClickBackground = function(event) {
            if (!this.el.contains(event.target) && this.isMenuOpen()) {
                setTimeout(function() {
                    this.closeMenu();
                }.bind(this), 0);
            }
        };

        /**
         * The 'scroll' or 'resize' event listener for the dropdown menu.
         * @param {event} event
         */
        this._onScrollOrResize = function(event) {
            if ((event.target === document || event.target === window) && this.isMenuOpen()) {
                this.repositionMenu();
            }
        };

    }).call(DropDownButton.prototype);

    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    /**
     * Action!
     * @param {HTMLElement} element
     * @class
     * @constructor
     */
    function Action(element) {
        if (!(this instanceof Action)) {
            return new Action(element);
        }
        this._init(element);
    }

    (function() {
        // Only one menu can be active at the same time.
        var currentDropDown = null;

        Object.defineProperty(Action, 'current', {
            get: function() {
                return currentDropDown;
            }
        });

        this.constructor = Action;

        /**
         * Initialiser.
         * @param {HTMLElement} element
         */
        this._init = function(element) {
            this.el = element;
            var button = element.querySelector('.mod_facetoface__sessionlist__action__dropdown');

            if (button) {
                this.dropdown = new DropDownButton(button);
                element.addEventListener(DropDownButton.OPEN_EVENT, this._onDropDownOpen.bind(this));
                element.addEventListener(DropDownButton.CLOSED_EVENT, this._onDropDownClosed.bind(this));
            }
        };

        /**
         * The 'menu has been opened' event listener.
         * @param {event} event
         */
        this._onDropDownOpen = function(event) {
            if (event.detail !== currentDropDown) {
                currentDropDown = event.detail;
            }
        };

        /**
         * The 'menu is closed' event listener.
         * @param {event} event
         */
        this._onDropDownClosed = function(event) {
            if (event.detail === currentDropDown) {
                currentDropDown = null;
            }
        };

    }).call(Action.prototype);

    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    var action = {
        /**
         * Initialiser.
         * @param {Element} root
         * @returns {Promise}
         */
        init: function(root) {
            return new Promise(function(resolve) {
                var action = new Action(root);
                resolve(action);
            });
        },

        /**
         * Update the content.
         */
        update: function() {
            var dropdown = Action.current;
            if (dropdown) {
                dropdown.repositionMenu();
            }
        }
    };

    return action;
});

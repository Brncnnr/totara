/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @package totara_msteams
 */

define(['core/ajax', 'core/log', 'core/templates'], function(ajax, log, templates) {
    // eslint-disable-next-line spaced-comment, no-redeclare
    /*global microsoftTeams,Promise,CustomEvent*/

    /**
     * Call js_pending and return a function that calls js_complete.
     * @param {...*} args
     * @returns {function}
     */
    function jsPending(args) {
        var key = 'totara_msteams:config_tab:';
        key += arguments.length === 1 ? args : Array.prototype.reduce.call(arguments, function(p, e) {
            return p + ':' + e;
        });
        M.util.js_pending(key);
        return function() {
            M.util.js_complete(key);
        };
    }

    var debouncing = 0;

    /**
     * Return a debouncing function triggered at the trailing edge.
     * This is effectively the same as _.debounce({trailing:true}) of Lodash.
     * @param {function} func
     * @param {number=} wait
     * @returns {function}
     */
    function debounce(func, wait) {
        var timer;
        var key = 'totara_msteams:config_tab:debounce:' + (++debouncing);
        return function() {
            var that = this;
            var args = arguments;
            var timeout = function() {
                timer = null;
                // Trailing edge.
                func.apply(that, args);
                M.util.js_complete(key);
            };
            if (timer) {
                clearTimeout(timer);
            } else {
                M.util.js_pending(key);
            }
            timer = setTimeout(timeout, wait || 0);
        };
    }

    /**
     * A list box class based on the scrollable listbox example found at:
     * https://www.w3.org/TR/wai-aria-practices/examples/listbox/listbox-scrollable.html
     *
     * @param {HTMLElement} element
     * @class
     */
    function ListBox(element) {
        this._init(element);
    }

    (function() {
        var Keys = {
            TAB: 9,
            ENTER: 13,
            ESCAPE: 27,
            SPACE: 32,
            PAGE_UP: 33,
            PAGE_DOWN: 34,
            END: 35,
            HOME: 36,
            LEFT: 37,
            UP: 38,
            RIGHT: 39,
            DOWN: 40
        };
        var Classes = {
            LOADING: 'totara_msteams_form__list--loading',
            LIST_ITEM: 'totara_msteams_form__list__item',
            LIST_ITEM_ACTIVE: 'totara_msteams_form__list__item--active',
            DOT_LIST_ITEM: '.totara_msteams_form__list__item',
            DOT_LIST_ITEM_EMPTY: '.totara_msteams_form__list__item--empty',
            DOT_LIST_SPINNER: '.totara_msteams_form__list__spinner'
        };
        var Attrs = {
            URL: 'data-url',
            TITLE: 'data-title'
        };
        var id = 0;

        /**
         * Simulate jQuery(el).outerHeight()
         *
         * @param {HTMLElement} el
         * @returns {number}
         */
        function getOuterHeight(el) {
            var style = getComputedStyle(el);
            return ['height', 'marginTop', 'marginBottom', 'paddingTop', 'paddingBottom'].reduce(function(y, prop) {
                return y + parseFloat(style[prop]);
            }, 0);
        }

        /**
         * Get the URL associated to the item.
         *
         * @param {?element} item
         * @returns {string}
         */
        function getItemUrl(item) {
            if (item === null) {
                return '';
            }
            return item.getAttribute(Attrs.URL);
        }

        /**
         * Get the name of the item.
         *
         * @param {?element} item
         * @returns {string}
         */
        function getItemTitle(item) {
            if (item === null) {
                return '';
            }
            return item.getAttribute(Attrs.TITLE);
        }

        /**
         * Initialiser.
         *
         * @param {HTMLElement} element
         */
        this._init = function(element) {
            this.el = element;
            this.el.addEventListener('scroll', this._onScroll.bind(this));
            this.el.addEventListener('focus', this._onFocus.bind(this));
            this.el.addEventListener('mousedown', this._onMouseDown.bind(this));
            this.el.addEventListener('keydown', this._onKeyDown.bind(this));
            this._id = ++id;
            this._request = 0;
            this._more = false;
            this._loadingMore = false;
            this._selectedItem = null;
            this._searchText = '';
            this._timerClear = null;
            this._fetchLimit = 10;
            this._calcLimit();
            this.refresh();
        };

        /**
         * Get the URL associated to the selected item.
         *
         * @returns {string}
         */
        this.getSelectedUrl = function() {
            return getItemUrl(this._selectedItem);
        };

        /**
         * Get the name of the selected item.
         *
         * @returns {string}
         */
        this.getSelectedTitle = function() {
            return getItemTitle(this._selectedItem);
        };

        /**
         * Refresh the list.
         *
         * @param {number=} from
         * @param {number=} limit
         */
        this.refresh = function(from, limit) {
            if (typeof from === 'undefined') {
                from = 0;
            }
            if (typeof limit === 'undefined') {
                limit = this._fetchLimit;
            }
            this._clearIncrementalSearchState();
            if (from === 0) {
                Array.prototype.forEach.call(this.el.querySelectorAll(Classes.DOT_LIST_ITEM_EMPTY), function(e) {
                    e.parentNode.removeChild(e);
                });
            }
            var items = this._getAllItems();
            var index = items.indexOf(this._selectedItem);
            if (index !== -1 && index >= from) {
                this._selectItem(null, false);
            }
            if (from < items.length) {
                items.splice(from).forEach(function(e) {
                    e.parentNode.removeChild(e);
                });
            }
            this.el.classList.add(Classes.LOADING);
            this._loadingMore = false;
            this._fireEventLoad(from, limit);
        };

        /**
         * Calculate how many items to prefetch at once.
         */
        this._calcLimit = function() {
            var item = document.createElement('li');
            item.className = Classes.LIST_ITEM;
            this.el.appendChild(item);
            var itemHeight = getOuterHeight(item);
            this.el.removeChild(item);
            if (itemHeight <= 0) {
                this._fetchLimit = 10;
            } else {
                this._fetchLimit = Math.ceil(this.el.clientHeight / itemHeight * 1.25) + 1;
            }
        };

        /**
         * Focus the first item.
         */
        this._selectFirstItem = function() {
            var items = this._getAllItems();
            this._selectItem(items.length ? items[0] : null, true);
        };

        /**
         * Focus the last item.
         */
        this._selectLastItem = function() {
            var items = this._getAllItems();
            this._selectItem(items.length ? items[items.length - 1] : null, true);
        };

        /**
         * Get all items.
         *
         * @returns {HTMLElement[]}
         */
        this._getAllItems = function() {
            return Array.prototype.slice.apply(this.el.querySelectorAll(Classes.DOT_LIST_ITEM));
        };

        /**
         * Select an item.
         *
         * @param {?HTMLElement} item
         * @param {bool} clearSearchState set true to clear internal bookkeeping for incremental search
         */
        this._selectItem = function(item, clearSearchState) {
            if (item === this._selectedItem) {
                return;
            }
            if (clearSearchState) {
                this._clearIncrementalSearchState();
            }

            var currentItem = null;
            this._getAllItems().forEach(function(e) {
                if (e === item) {
                    e.classList.add(Classes.LIST_ITEM_ACTIVE);
                    e.setAttribute('aria-selected', true);
                    currentItem = item;
                } else {
                    e.classList.remove(Classes.LIST_ITEM_ACTIVE);
                    e.removeAttribute('aria-selected');
                }
            });

            this._selectedItem = currentItem;
            if (currentItem) {
                this.el.setAttribute('aria-activedescendant', currentItem.id);

                if (this.el.scrollHeight > this.el.clientHeight) {
                    // The element has a scroll bar.
                    var bottom = this.el.scrollTop + this.el.clientHeight;
                    var itemBottom = currentItem.offsetTop + currentItem.offsetHeight;
                    if (itemBottom > bottom) {
                        this.el.scrollTop = itemBottom - this.el.clientHeight;
                    } else if (currentItem.offsetTop < this.el.scrollTop) {
                        this.el.scrollTop = currentItem.offsetTop;
                    }
                }
            } else {
                this.el.removeAttribute('aria-activedescendant');
            }

            this._fireEventChange(currentItem);
        };

        /**
         * A callback function called by the client through the requestitems event.
         *
         * @param {object} context
         * @returns {Promise}
         */
        this._updateCallback = function(context) {
            var that = this;
            return new Promise(function(resolve) {
                var updateInnerHTML = function(html) {
                    var dummy = document.createElement('ul');
                    dummy.innerHTML = html;
                    var spinner = that.el.querySelector(Classes.DOT_LIST_SPINNER);
                    for (var i = 0, array = dummy.querySelectorAll(Classes.DOT_LIST_ITEM + ',' + Classes.DOT_LIST_ITEM_EMPTY), len = array.length; i < len; i++) {
                        that.el.insertBefore(array[i], spinner);
                    }
                    // Set IDs of child items to uniquely distinguish them.
                    that._getAllItems().forEach(function(e, i) {
                        e.id = 'msteams_listbox_' + that._id + '_' + i;
                    });
                    that._more = context.more;
                    that._loadingMore = false;
                    that.el.classList.remove(Classes.LOADING);
                    resolve();
                };
                templates.render('totara_msteams/listbox_items', context).done(updateInnerHTML);
            });
        };

        /**
         * Dispatch a requestitems event.
         *
         * @param {number} from
         * @param {number} limit
         */
        this._fireEventLoad = function(from, limit) {
            var request = ++this._request;
            var customEvent = new CustomEvent('listbox:requestitems', {
                bubbles: true,
                detail: {
                    update: function(context) {
                        if (request === this._request) {
                            return this._updateCallback(context);
                        } else {
                            // Lost the race.
                            return Promise.resolve();
                        }
                    }.bind(this),
                    from: from,
                    limit: limit
                }
            });
            this.el.dispatchEvent(customEvent);
        };

        /**
         * Dispatch a change event.
         *
         * @param {?HTMLElement} item
         */
        this._fireEventChange = function(item) {
            var detail = {
                target: item
            };
            Object.defineProperties(detail, {
                url: {
                    get: function() {
                        return getItemUrl(item);
                    }
                },
                title: {
                    get: function() {
                        return getItemTitle(item);
                    }
                }
            });
            var customEvent = new CustomEvent('listbox:change', {
                bubbles: true,
                detail: detail
            });
            this.el.dispatchEvent(customEvent);
        };

        /**
         * The scroll event listener.
         */
        this._onScroll = function() {
            if (this._more && !this._loadingMore) {
                var bottom = this.el.scrollTop + this.el.clientHeight;
                var scrollBottom = this.el.scrollHeight;
                if (scrollBottom - 10 <= bottom) {
                    this._loadingMore = true;
                    this.el.classList.add(Classes.LOADING);
                    var spinner = this.el.querySelector(Classes.DOT_LIST_SPINNER);
                    this.el.scrollTop = spinner.offsetTop + spinner.offsetHeight - this.el.clientHeight;
                    this._fireEventLoad(this._getAllItems().length, this._fetchLimit);
                }
            }
        };

        /**
         * The focus event listener.
         */
        this._onFocus = function() {
            if (this._selectedItem === null) {
                this._selectFirstItem();
            }
        };

        /**
         * The mousedown event listener.
         *
         * @param {event} event
         */
        this._onMouseDown = function(event) {
            // See if the primary button is pressed.
            if ((event.buttons % 2) === 0) {
                return;
            }
            var item = event.target.closest(Classes.DOT_LIST_ITEM);
            if (item) {
                this._selectItem(item, true);
            }
        };

        /**
         * The keydown event listener.
         *
         * @param {event} event
         */
        this._onKeyDown = function(event) {
            var keyCode = event.which || event.keyCode;
            switch (keyCode) {
                case Keys.HOME:
                    event.preventDefault();
                    this._selectFirstItem();
                    return;
                case Keys.END:
                    event.preventDefault();
                    this._selectLastItem();
                    return;
                case Keys.DOWN:
                case Keys.UP:
                    event.preventDefault();
                    this._onKeyUpOrDown(event);
                    return;
                case Keys.ESCAPE:
                    event.preventDefault();
                    this._clearIncrementalSearchState();
                    return;
            }

            if (('' + event.key).length === 1) {
                event.preventDefault();
                var item = this._incrementalSearchItem(event.key);
                if (item !== null) {
                    this._selectItem(item, false);
                }
            }
        };

        /**
         * Handle up and down keys.
         *
         * @param {event} event
         */
        this._onKeyUpOrDown = function(event) {
            var items = this._getAllItems();
            var index = items.indexOf(this._selectedItem);
            if (index !== -1) {
                if (event.keyCode == Keys.UP) {
                    index = Math.max(0, index - 1);
                } else {
                    index = Math.min(items.length - 1, index + 1);
                }
                this._selectItem(items[index], true);
            }
        };

        /**
         * Perform incremental search.
         *
         * @param {string} keyChar
         * @returns {HTMLElement}
         */
        this._incrementalSearchItem = function(keyChar) {
            var items = this._getAllItems();
            var index = items.indexOf(this._selectedItem);
            this._searchText += keyChar;
            this._initiateTimer();

            if (index === -1 || this._searchText.length === 1) {
                ++index;
            }

            var i = 0,
                len = items.length,
                text = this._searchText.toUpperCase();
            for (; i < len; i++) {
                var item = items[(index + i) % len];
                var title = getItemTitle(item).toUpperCase();
                if (title.indexOf(text) === 0) {
                    return item;
                }
            }
            return null;
        };

        /**
         * Set a timer to clear incremental search.
         */
        this._initiateTimer = function() {
            var that = this;
            if (this._timerClear) {
                clearTimeout(this._timerClear);
            }
            that._timerClear = setTimeout(function() {
                that._timerClear = null;
                that._searchText = '';
            }, 700);
        };

        /**
         * Stop the ongoing timer and reset the internal state of incremental search.
         */
        this._clearIncrementalSearchState = function() {
            if (this._timerClear) {
                clearTimeout(this._timerClear);
                this._timerClear = null;
            }
            this._searchText = '';
        };

    }).call(ListBox.prototype);

    return {
        form: null,
        name: null,
        search: null,
        list: null,
        inList: false,
        debug: false,

        /**
         * Initialiser.
         *
         * @param {object} config
         * @returns {Promise}
         */
        init: function(config) {
            var that = this;
            var done = jsPending('init');

            return new Promise(function(resolve) {
                /**
                 * Render the configuration page.
                 */
                function render() {
                    templates.render('totara_msteams/config_tab', config.context).done(function(html, js) {
                        var parent = document.getElementById(config.id);
                        parent.innerHTML = html;
                        // eslint-disable-next-line promise/catch-or-return
                        templates.runTemplateJS(js).then(function() {
                            that.form = parent.querySelector('.totara_msteams__config');
                            that.form.addEventListener('listbox:change', that._onChange.bind(that));
                            that.form.addEventListener('listbox:requestitems', that._onRequestItems.bind(that));

                            that.name = document.getElementById(config.context.name.id);
                            that.name.addEventListener('input', that._onNameInput.bind(that));

                            that.search = document.getElementById(config.context.search.id);
                            that.search.addEventListener('input', that._onSearchInput.bind(that));

                            that.list = new ListBox(document.getElementById(config.context.list.idlist));

                            setTimeout(function() {
                                var el = parent.querySelector('[autofocus]');
                                if (el) {
                                    el.focus();
                                }
                                // eslint-disable-next-line promise/no-callback-in-promise
                                done();
                            }, 1);
                            resolve();
                        });
                    });
                }
                microsoftTeams.initialize(render);
            });
        },

        /**
         * The input event listener of the search input field.
         */
        _onSearchInput: debounce(function() {
            this.list.refresh();
        }, 400),

        /**
         * The input event listener of the name input field.
         */
        _onNameInput: function() {
            if (!this.inList) {
                this._update();
            }
        },

        /**
         * The change event listener.
         *
         * @param {event} event
         */
        _onChange: function(event) {
            this.name.value = event.detail.title;
            // Fire a fake event to update the validation state.
            this.inList = true;
            var e = new CustomEvent('input', {bubbles: true});
            this.name.dispatchEvent(e);
            this.inList = false;
            this._update();
        },

        /**
         * The requestitems event listener.
         *
         * @param {event} event
         */
        _onRequestItems: function(event) {
            var done = jsPending('requestItems');
            var deferreds = ajax.call([{
                methodname: 'totara_msteams_search_catalog',
                args: {
                    query: this.search.value,
                    from: event.detail.from,
                    limit: event.detail.limit
                }
            }], true, true);
            deferreds[0].then(function(json) {
                // eslint-disable-next-line promise/no-callback-in-promise
                event.detail.update(json).then(done).catch(function(error) {
                    log.error(error);
                });
            }).fail(function(ex) {
                log.error(ex);
            });
        },

        /**
         * Update internal state.
         */
        _update: function() {
            var newSettings = null;
            var tabName = this.name.value;
            if (tabName) {
                var url = this.list.getSelectedUrl();
                if (url && url.indexOf(M.cfg.wwwroot) === 0) {
                    newSettings = {
                        contentUrl: M.cfg.wwwroot + '/totara/msteams/tabs/customtab.php?url=' + encodeURIComponent(url),
                        websiteUrl: url,
                        suggestedTabName: tabName
                    };
                }
            }

            if (newSettings) {
                var that = this;
                microsoftTeams.settings.setValidityState(true);
                microsoftTeams.settings.registerOnSaveHandler(function(saveEvent) {
                    var done = jsPending('update');
                    templates.render('totara_msteams/spinner', {}).done(function(html) {
                        that.form.innerHTML = html;
                        microsoftTeams.settings.setSettings(newSettings);
                        saveEvent.notifySuccess();
                        done();
                    });
                });
            } else {
                microsoftTeams.settings.setValidityState(false);
            }
        }
    };
});

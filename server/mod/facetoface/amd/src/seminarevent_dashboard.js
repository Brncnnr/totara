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

define(['core/ajax', 'core/templates', 'core/notification', 'mod_facetoface/seminarevent_dashboard_action'], function(ajax, templates, notification, action) {
    /**
     * Return a debouncing function triggered at both the leading and the trailing edge.
     * This is effectively the same as _.debounce({leading:true, trailing:true}) of Lodash.
     * @param {function} func
     * @param {number=} wait
     * @returns {function}
     */
    function debounce(func, wait) {
        var timer, later;
        return function() {
            var that = this;
            var args = arguments;
            var timeout = function() {
                timer = null;
                if (later) {
                    later = false;
                    // Trailing edge.
                    func.apply(that, args);
                }
            };
            if (timer) {
                later = true;
                clearTimeout(timer);
            } else {
                // Leading edge.
                func.apply(that, args);
            }
            timer = setTimeout(timeout, wait || 0);
        };
    }

    /**
     * Call js_pending and return a function that calls js_complete.
     * @param {...*} args
     * @returns {function}
     */
    function jsPending(args) {
        var key = 'mod_facetoface_event-dashboard:';
        key += arguments.length === 1 ? args : Array.prototype.reduce.call(arguments, function(p, e) {
            return p + ':' + e;
        });
        M.util.js_pending(key);
        return function() {
            M.util.js_complete(key);
        };
    }

    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    /**
     * The cut-down version of URLSearchParams.
     * @param {string=} str
     * @class
     * @constructor
     */
    function SearchParams(str) {
        if (!(this instanceof SearchParams)) {
            return new SearchParams(str);
        }
        this._init(str);
    }

    (function() {
        this.constructor = SearchParams;

        /**
         * Initialiser.
         * @param {string=} str
         */
        this._init = function(str) {
            var items = {};
            if (typeof str !== 'undefined') {
                if (str.indexOf('?') === 0) {
                    str = str.substring(1);
                }
                if (str.length) {
                    str.split('&').forEach(function(param) {
                        var key,
                            value,
                            pos = param.indexOf('=');
                        if (pos !== -1) {
                            key = decodeURIComponent(param.substring(0, pos));
                            value = decodeURIComponent(param.substring(pos + 1));
                        } else {
                            key = decodeURIComponent(param);
                            value = '';
                        }
                        items[key] = value;
                    });
                }
            }
            this._items = items;
        };

        /**
         * Return a query string without the question mark.
         * @returns {string}
         */
        this.toString = function() {
            var query = '';
            for (var key in this._items) {
                if (query !== '') {
                    query += '&';
                }
                query += encodeURIComponent(key) + '=' + encodeURIComponent(this._items[key]);
            }
            return query;
        };

        /**
         * Return a boolean about whether the specified name exists or not.
         * @param {string} name
         * @returns {boolean}
         */
        this.has = function(name) {
            return name in this._items;
        };

        /**
         * Return a value associated to the given name, or null if not found.
         * @param {string} name
         * @returns {string|null}
         */
        this.get = function(name) {
            if (name in this._items) {
                return this._items[name];
            }
            return null;
        };

        /**
         * Iterate through all search parameters.
         * @param {function(string, string): void} callback
         */
        this.forEach = function(callback) {
            for (var key in this._items) {
                callback(this._items[key], key);
            }
        };

        /**
         * Set the value associated to the given name.
         * Unlike URLSearchParams, name must be unique.
         * @param {string} name
         * @param {*} value
         */
        this.set = function(name, value) {
            this._items[name] = '' + value;
        };

        /**
         * Delete the given search parameter.
         * @param {string} key
         */
        this.delete = function(key) {
            delete this._items[key];
        };
    }).call(SearchParams.prototype);

    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    /**
     * New filter bar.
     * @param {HTMLFormElement} element
     * @param {SearchParams} params
     * @class
     * @constructor
     */
    function FilterBar(element, params) {
        if (!(this instanceof FilterBar)) {
            return new FilterBar(element, params);
        }
        this._init(element, params);
    }
    FilterBar.FILTER_CHANGED_EVENT = 'mod_facetoface:filter.changed';

    (function() {
        var ATTR_SHOW_TOOLTIPS = 'data-show-tooltips';
        var CSS_FILTER_ACTIVE = 'mod_facetoface__filter--active';
        var CSS_FILTER_CLOSED = 'mod_facetoface__filter--closed';

        this.constructor = FilterBar;

        /**
         * Initialiser.
         * @param {HTMLFormElement} element
         * @param {SearchParams} params
         */
        this._init = function(element, params) {
            this.el = element;
            this.resetlink = element.querySelector('.mod_facetoface__filter__link');
            this.filters = Array.prototype.slice.apply(element.querySelectorAll('select'));

            // Add only parameters known to the filter bar.
            this.filterparams = new SearchParams();
            this.filters.forEach(function(el) {
                var name = el.name;
                var value = params.get(name);
                if (value !== null) {
                    el.value = value;
                    this.filterparams.set(name, value);
                } else {
                    el.selectedIndex = 0;
                }
                this._updateTooltip(el);
            }.bind(this));

            this.resetlink.addEventListener('click', this._onReset.bind(this));
            element.addEventListener('change', this._onChange.bind(this));

            var toggle = element.querySelector('.mod_facetoface__filter__toggle-button__label');
            if (toggle) {
                toggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    this._showHideBar();
                }.bind(this));
            } else {
                this._showHideBar(true);
            }
            window.addEventListener('resize', function() {
                this._updateAriaAttributes();
            }.bind(this));

            this.filters.forEach(function(el) {
                el.disabled = false;
            });
            element.classList.remove('mod_facetoface__filter--loading');
            this._updateAriaAttributes();
            this.showResetLink();
        };

        /**
         * The 'change' event listener.
         * @param {event} event
         */
        this._onChange = function(event) {
            var el = event.target.closest('select');
            if (this.filters.includes(el) === false) {
                return;
            }
            this._updateTooltip(el);
            if (el.selectedIndex > 0) {
                this.filterparams.set(el.name, el.value);
            } else {
                this.filterparams.delete(el.name);
            }
            this._fireEvent();
        };

        /**
         * The 'click' event listener for the reset link.
         * @param {event} event
         */
        this._onReset = function(event) {
            var that = this;
            var resetfilters = this.filters.filter(function(el) {
                if (el.selectedIndex > 0) {
                    el.selectedIndex = 0;
                    that.filterparams.delete(el.name);
                    that._updateTooltip(el);
                    return true;
                }
                return false;
            });
            if (resetfilters.length) {
                this._fireEvent();
            }
            event.preventDefault();
        };

        /**
         * Update aria attributes.
         */
        this._updateAriaAttributes = function() {
            var hidden = this.el.querySelector('.mod_facetoface__filter__toggle-button__label__hidden');
            var shown = this.el.querySelector('.mod_facetoface__filter__toggle-button__label__shown');
            var expanded = true;
            if (hidden !== null && shown !== null && hidden.clientWidth && !shown.clientWidth) {
                expanded = false;
            }
            this.el.setAttribute('aria-expanded', expanded.toString());
        };

        /**
         * Show or hide the filter bar.
         * @param {bool=} state
         */
        this._showHideBar = function(state) {
            if (typeof state === 'undefined') {
                state = !this.el.classList.contains(CSS_FILTER_CLOSED);
            }
            if (state) {
                this.el.classList.add(CSS_FILTER_CLOSED);
            } else {
                this.el.classList.remove(CSS_FILTER_CLOSED);
            }
            this._updateAriaAttributes();
        };

        /**
         * Update the tooltip text aka the title attribute.
         * @param {HTMLSelectElement} el
         */
        this._updateTooltip = function(el) {
            if (el.getAttribute(ATTR_SHOW_TOOLTIPS) == 'true') {
                var selectedText = '';
                var index = el.selectedIndex;
                if (0 < index && index < el.options.length) {
                    selectedText = el.options[index].text;
                }
                el.setAttribute('title', selectedText);
            }
        };

        /**
         * Trigger a custom event.
         */
        this._fireEvent = function() {
            var event = new CustomEvent(FilterBar.FILTER_CHANGED_EVENT, {
                bubbles: true, detail: this
            });
            this.el.dispatchEvent(event);
        };

        /**
         * Update the visibility of the reset link.
         */
        this.showResetLink = function() {
            var modifiedfilters = this.filters.filter(function(el) {
                return el.selectedIndex > 0;
            });
            if (modifiedfilters.length) {
                this.el.classList.add(CSS_FILTER_ACTIVE);
            } else {
                this.el.classList.remove(CSS_FILTER_ACTIVE);
            }
        };

        /**
         * Iterate through filter parameters.
         * @param {function(string, string): void} callback
         */
        this.forEachParams = function(callback) {
            this.filterparams.forEach(callback);
        };

        /**
         * Set the given filter parameter.
         * @param {string} name
         * @param {*} value
         */
        this.setParam = function(name, value) {
            this.filterparams.set(name, value);
        };

        /**
         * Delete the given search parameter.
         * @param {string} name
         */
        this.deleteParam = function(name) {
            this.filterparams.delete(name);
        };

        /**
         * Get the filter parameters as a query string without the question mark.
         * @returns {string}
         */
        this.getParamsString = function() {
            return this.filterparams.toString();
        };
    }).call(FilterBar.prototype);

    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    var dash = {
        args: {
            id: null,
            f: null,
            type: '',
            cookie: 0,
            filterparams: {},
            debug: false
        },
        requestCookie: 0,
        filter: null,
        initDone: false,

        /**
         * Initialiser.
         * @param {Element} root
         */
        init: function(root) {
            this.el = root;
            var that = this;
            var done = jsPending('init');
            // This setTimeout() brings a meaningful error message in the devtools.
            setTimeout(function() {
                var params = that._loadParams();
                var filter = root.querySelector('.mod_facetoface__filter');
                if (filter !== null) {
                    that.filter = new FilterBar(filter, params);
                    root.addEventListener(FilterBar.FILTER_CHANGED_EVENT, that._onFilterChanged.bind(that));
                }
                root.addEventListener('click', that._onClick.bind(that));
                root.addEventListener('scroll', that._onScroll.bind(that), true);
                that.initDone = true;
                done();
            }, 0);
        },

        /**
         * Load search parameters from a query string.
         * @returns {SearchParams}
         */
        _loadParams: function() {
            var params = new SearchParams(location.search);
            var value = params.get('id');
            if (value !== null) {
                this.args.id = parseInt(value, 10);
            } else {
                value = params.get('f');
                if (value !== null) {
                    this.args.f = parseInt(value, 10);
                }
            }
            if (params.get('debug') == 1) {
                this.args.debug = 1;
            }
            return params;
        },

        /**
         * The 'filters are changed' event listener.
         * @param {event} event
         */
        _onFilterChanged: debounce(function(event) {
            var filter = event.detail;
            if (filter !== this.filter) {
                return;
            }
            this.update(true, true);
        }, 400),

        /**
         * The 'click' event listener for the 'show previous' link.
         * @param {event} event
         */
        _onClick: function(event) {
            if (!this.filter) {
                return;
            }
            var link = event.target.closest('.mod_facetoface__sessionlist__show-previous__link');
            if (link !== null) {
                event.preventDefault();
                var result = /\ballpreviousevents=(\d+)/.exec(link.getAttribute('href'));
                if (result && result[1]) {
                    this.filter.setParam('allpreviousevents', 1);
                } else {
                    this.filter.deleteParam('allpreviousevents');
                }
                this.update(false, true);
            }
        },

        /**
         * The 'scroll' event listener for the session list tables.
         * @param {event} event
         */
        _onScroll: function(event) {
            if (event.target.classList.contains('mod_facetoface__sessionlist')) {
                action.update();
            }
        },

        /**
         * Update seminar event tables.
         * @param {bool} upcoming Update upcoming table.
         * @param {bool} past Update past table.
         */
        update: function(upcoming, past) {
            if (!upcoming && !past) {
                return;
            }
            var params = this.filterParams();
            history.replaceState({ajax: true, filter: params}, '', this.url());
            if (this.initDone && this.filter) {
                this.filter.showResetLink();
            }
            var cookie = ++this.requestCookie;
            if (upcoming) {
                this.updateWorker('upcoming', cookie, params);
            }
            if (past) {
                this.updateWorker('past', cookie, params);
            }
        },

        /**
         * Helper function of update.
         * @param {string} type upcoming or past
         * @param {integer} cookie unique identifier to distinguish requests
         * @param {object} params filter parameters
         */
        updateWorker: function(type, cookie, params) {
            var that = this;
            var root = this.el;
            var parent = root.querySelector('.mod_facetoface__sessions--' + type);
            parent.classList.add('loading');

            var doneOuter = jsPending('update', type, cookie);
            this.fetch(type, cookie, params).then(function(json) {
                if (that.requestCookie === json.cookie) {
                    var title = root.querySelector('.mod_facetoface__event-dashboard__title');
                    title.textContent = json.title;
                    var doneInner = jsPending('update-table', type, cookie);
                    templates.render('mod_facetoface/seminarevent_dashboard_sessions', json.data).done(function(html, js) {
                        parent.outerHTML = html;
                        parent.classList.remove('loading');
                        templates.runTemplateJS(js);
                        doneInner();
                    });
                    doneOuter();
                } else {
                    doneOuter();
                }
            }).catch(notification.exception);
        },

        /**
         * Get the up-to-date URL.
         * @return {string}
         */
        url: function() {
            var url = location.href.replace(/\?.*$/, '') + '?';
            if (this.args.id) {
                url += 'id=' + this.args.id;
            } else {
                url += 'f=' + this.args.f;
            }
            if (this.args.debug) {
                url += '&debug=1';
            }
            if (!this.filter) {
                return url;
            }
            var params = this.filter.getParamsString();
            if (params) {
                return url + '&' + params;
            } else {
                return url;
            }
        },

        /**
         * Get filter parameters as an object.
         * @returns {object}
         */
        filterParams: function() {
            if (!this.filter) {
                return {};
            }
            var params = {};
            this.filter.forEachParams(function(value, name) {
                params[name] = value;
            });
            return params;
        },

        /**
         * Helper function to perform AJAX request.
         * @param {string} type upcoming or past
         * @param {integer} cookie unique identifier to distinguish requests
         * @param {object} params filter parameters
         * @returns {Promise}
         */
        fetch: function(type, cookie, params) {
            var args = Object.assign(this.args, {
                type: type,
                cookie: cookie,
                filterparams: params
            });
            var promises = ajax.call([{
                methodname: 'mod_facetoface_render_session_list',
                args: args
            }], true, true);
            return promises[0].fail(function(ex) {
                notification.exception(ex);
            });
        }
    };
    return dash;
});

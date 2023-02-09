// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Wrap an instance of the browser's local or session storage to handle
 * cache expiry, key namespacing and other helpful things.
 *
 * @module     core/storagewrapper
 * @package    core
 * @copyright  2017 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/config'], function(config) {

    /**
     * Constructor.
     *
     * @param {object} storage window.localStorage or window.sessionStorage
     */
    var Wrapper = function(storage) {
        this.storage = storage;
        this.supported = this.detectSupport();
        this.prefix = 'totara:cache:' + this._rootPath() + ':';
        this.jsrevPrefix = this.prefix + '__jsrev';
        this.validateCache();
    };

    /**
     * Check if the browser supports the type of storage.
     *
     * @method detectSupport
     * @return {boolean} True if the browser supports storage.
     */
    Wrapper.prototype.detectSupport = function() {
        if (config.jsrev == -1) {
            // Disable cache if debugging.
            return false;
        }
        if (typeof (this.storage) === "undefined") {
            return false;
        }
        var testKey = 'test';
        try {
            if (this.storage === null) {
                return false;
            }
            // MDL-51461 - Some browsers misreport availability of the storage
            // so check it is actually usable.
            this.storage.setItem(testKey, '1');
            this.storage.removeItem(testKey);
            return true;
        } catch (ex) {
            return false;
        }
    };

    /**
     * Add a unique prefix to all keys so multiple moodle sites do not share caches.
     *
     * @method prefixKey
     * @param {string} key The cache key to prefix.
     * @return {string} The new key
     */
    Wrapper.prototype.prefixKey = function(key) {
        return this.prefix + key;
    };

    /**
     * Check the current jsrev version and clear the cache if it has been bumped.
     *
     * @method validateCache
     */
    Wrapper.prototype.validateCache = function() {
        var cacheVersion = this.storage.getItem(this.jsrevPrefix);
        if (cacheVersion === null || config.jsrev != cacheVersion) {
            this._clear();
            this.storage.setItem(this.jsrevPrefix, config.jsrev);
        }
    };

    /**
     * Get a value from local storage. Remember - all values must be strings.
     *
     * @method get
     * @param {string} key The cache key to check.
     * @return {boolean|string} False if the value is not in the cache, or some other error - a string otherwise.
     */
    Wrapper.prototype.get = function(key) {
        if (!this.supported) {
            return false;
        }
        key = this.prefixKey(key);

        return this.storage.getItem(key);
    };

    /**
     * Set a value to local storage. Remember - all values must be strings.
     *
     * @method set
     * @param {string} key The cache key to set.
     * @param {string} value The value to set.
     * @return {boolean} False if the value can't be saved in the cache, or some other error - true otherwise.
     */
    Wrapper.prototype.set = function(key, value) {
        if (!this.supported) {
            return false;
        }
        key = this.prefixKey(key);
        // This can throw exceptions when the storage limit is reached.
        try {
            this.storage.setItem(key, value);
        } catch (e) {
            return false;
        }
        return true;
    };

    /**
     * Get path to use to separate data for sites in the same origin.
     *
     * @method _rootPath
     * @private
     * @returns {string}
     */
    Wrapper.prototype._rootPath = function() {
        // localStorage is per-origin so we can omit the domain
        var result = /^\w+:\/\/[^/]+\/?(.*)$/.exec(config.wwwroot);
        return result ? result[1] : config.wwwroot;
    };

    /**
     * Clear all entries.
     *
     * @method _clear
     * @private
     */
    Wrapper.prototype._clear = function() {
        var self = this;
        var keys = [];
        for (var i = 0; i < this.storage.length; i++) {
            var key = this.storage.key(i);
            // match keys with our prefix, but also match the old format so
            // we don't end up leaving old data around in localstorage
            // (it is limited to ~5mb)
            if (key.startsWith(this.prefix) || /^-?\d+\//.test(key)) {
                keys.push(key);
            }
        }
        // must not mutate storage while we are iterating through it
        keys.forEach(function(key) {
            return self.storage.removeItem(key);
        });
    };

    return Wrapper;
});

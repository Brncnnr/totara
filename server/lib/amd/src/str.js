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
 * Fetch and render language strings.
 * Hooks into the old M.str global - but can also fetch missing strings on the fly.
 *
 * @module     core/str
 * @class      str
 * @package    core
 * @copyright  2015 Damyon Wiese <damyon@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      2.9
 */
// Disable no-restriced-properties because M.str is expected here:
/* eslint-disable no-restricted-properties */
define(['jquery', 'core/webapi', 'core/localstorage', 'core/config', 'core/log'], function($, webapi, storage, config, log) {

    var promiseCache = [];

    return /** @alias module:core/str */ {
        // Public variables and functions.
        /**
         * Return a promise object that will be resolved into a string eventually (maybe immediately).
         *
         * Totara: there used to be 'lang' parameter, but it was removed because M.util.get_string() cannot handle it!
         *
         * @method get_string
         * @param {string} key The language string key
         * @param {string} component The language string component
         * @param {string} param The param for variable expansion in the string.
         * @return {Promise}
         */
         // eslint-disable-next-line camelcase
        get_string: function(key, component, param) {
            if (arguments.length > 3) {
                log.debug('get_string() cannot accept lang parameter');
            }
            var request = this.get_strings([{
                key: key,
                component: component,
                param: param
            }]);

            return request.then(function(results) {
                return results[0];
            });
        },

        /**
         * Make a batch request to load a set of strings
         *
         * @method get_strings
         * @param {Object[]} requests Array of { key: key, component: component, param: param };
         *                                      See get_string for more info on these args.
         * @return {Promise}
         */
         // eslint-disable-next-line camelcase
        get_strings: function(requests) {

            var deferred = $.Deferred();
            var awaitPromises = [];
            var results = [];
            var i = 0;
            var cacheKey;
            var request;
            var str;
            var search = [];
            // Totara: lang cannot be specified here because M.util.get_string() cannot handle it!
            var lang = config.currentlanguage;

            for (i = 0; i < requests.length; i++) {
                request = requests[i];

                // Normalise the component.
                if (typeof request.component === 'undefined') {
                    request.component = 'moodle';
                } else if (request.component === 'core') {
                    request.component = 'moodle';
                } else if (request.component.substring(0, 5) === 'core_') {
                    request.component = request.component.substring(5);
                } else if (request.component.substring(0, 4) === 'mod_') {
                    request.component = request.component.substring(4);
                }

                // Do we have the string cached in M.str already?
                if (typeof M.str[request.component] === 'undefined') {
                    M.str[request.component] = [];
                }
                if (typeof M.str[request.component][request.key] !== 'undefined') {
                    continue;
                }

                // Does the request cache contain string in this language?
                cacheKey = 'core_str/' + lang + '/' + request.component + '/' + request.key;
                var cached = storage.get(cacheKey);
                if (cached) {
                    M.str[request.component][request.key] = JSON.parse(cached);
                    continue;
                }

                // If we are already fetching then wait for promise.
                if (typeof promiseCache[cacheKey] !== 'undefined') {
                    awaitPromises.push(promiseCache[cacheKey]);
                    continue;
                }

                // We have to use WebAPI to fetch it.
                search.push({'identifier': request.key, 'component': request.component, 'lang': lang});
            }

            if (search.length === 0 && awaitPromises.length === 0) {
                // We have all the strings already in M.str object.
                for (i = 0; i < requests.length; i++) {
                    request = requests[i];
                    results[i] = M.util.get_string(request.key, request.component, request.param);
                }
                deferred.resolve(results);
                return deferred.promise();
            }

            if (search.length > 0) {
                // Use the Web API ajax.
                var ids = search.map(function(e) {
                    return e.identifier + ", " + e.component;
                });
                var promise = webapi.call({
                    operationName: 'core_lang_strings_nosession',
                    variables: {
                        lang: lang,
                        ids: ids
                    }
                });
                promise.then(
                    function(data) {
                        // Add to M.str and cache.
                        for (i = 0; i < data.lang_strings.length; i++) {
                            str = data.lang_strings[i];
                            if (typeof M.str[str.component][str.identifier] !== 'undefined') {
                                continue;
                            }
                            M.str[str.component][str.identifier] = str.string;

                            storage.set('core_str/' + str.lang + '/' + str.component + '/' + str.identifier, JSON.stringify(str.string));
                        }
                    }
                );
                awaitPromises.push(promise);
                for (i = 0; i < search.length; i++) {
                    str = search[i];
                    promiseCache['core_str/' + str.lang + '/' + str.component + '/' + str.identifier] = promise;
                }
            }

            Promise.all(awaitPromises).then(
                function() {
                    // We have all the strings finally.
                    for (i = 0; i < requests.length; i++) {
                        request = requests[i];
                        results[i] = M.util.get_string(request.key, request.component, request.param);
                    }
                    deferred.resolve(results);
                }
            ).catch(
                function() {
                    // We do not have all strings, but return whatever we have.
                    for (i = 0; i < requests.length; i++) {
                        request = requests[i];
                        results[i] = M.util.get_string(request.key, request.component, request.param);
                    }
                    deferred.resolve(results);
                }
            );

            return deferred.promise();
        }
    };
});

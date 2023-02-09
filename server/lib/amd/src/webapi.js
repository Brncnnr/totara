// This file is part of Totara Learn
//
//  Copyright (C) 2019 onwards Totara Learning Solutions LTD
//
//  This program is free software; you can redistribute it and/or modify
//  it under the terms of the GNU General Public License as published by
//  the Free Software Foundation; either version 3 of the License, or
//  (at your option) any later version.
//
//  This program is distributed in the hope that it will be useful,
//  but WITHOUT ANY WARRANTY; without even the implied warranty of
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//  GNU General Public License for more details.
//
//  You should have received a copy of the GNU General Public License
//  along with this program.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Class used to access Totara Web API ajax end points.
 *
 * NOTE: the code is based on core/ajax and should be used as a direct replacement.
 *
 * @module     core/webapi
 * @class      webapi
 * @package    core
 * @copyright  2019 Petr Skoda <petr.skoda@totaralearning.com>
 * @since      Totara 13
 */
define(['core/config', 'core/log'], function(config, Log) {
    /**
     * Time to wait for requests to batch.
     *
     * 10ms batch timeout, enough to collect all the requests we need, but not so
     * long that we're noticeably delaying requests.
     *
     * @constant {number}
     */
    const BATCH_TIMEOUT = 10;
    /**
     * Maximum number of requests to put in one batch.
     *
     * We don't want to have too many in one request as it might take a while for
     * them all to complete, or a long running one might delay the entire batch.
     *
     * @constant {number}
     */
    const BATCH_MAX_SIZE = 10;

    var inited = false,
        unloading = false,
        pendingKey = 'core_webapi',
        timeoutPendingKey = 'core_webapi_timeout';

    /**
     * Web API error
     *
     * @class
     * @extends Error
     * @param {Object} options object containing message, networkError, and/or graphQLErrors
     */
    function WebapiError(options) {
        Error.call(this, options.message);
        this.graphQLErrors = options.graphQLErrors || [];
        this.networkError = options.networkError || null;
        if (!options.message) {
            var message = [];
            if (Array.isArray(this.graphQLErrors)) {
                this.graphQLErrors.forEach(function(err) {
                    var errorMessage = err ? err.message : 'Error message not found.';
                    message.push('GraphQL error: ' + errorMessage);
                });
            }
            if (this.networkError) {
                message.push('Network error: ' + this.networkError.message);
            }
            this.message = message.join('\n');
        } else {
            this.message = options.message;
        }
    }

    WebapiError.prototype = Object.create(Error.prototype);
    WebapiError.prototype.constructor = WebapiError;
    // remove "Error: " prefix
    WebapiError.prototype.name = '';

    /**
     * Take an unknown promise rejection value and create a WebapiError from it
     *
     * @private
     * @param {*} value
     * @return {WebapiError}
     */
    function createError(value) {
        if (value instanceof WebapiError) {
            return value;
        }
        // error thrown during network request
        if (value instanceof Error) {
            return new WebapiError({networkError: value});
        }
        // error returned from API
        if (value && value.errors) {
            return new WebapiError({graphQLErrors: value.errors});
        }
        // something else, convert it to a string
        return new WebapiError({message: String(value)});
    }

    /**
     * Send the batch result to the requesters
     *
     * @private
     * @param {Object[]} batch Batched requests
     * @param {string} method Method to call on batch
     * @param {*} result Result from server. If this is an array, each request in the batch will receive
     *                   the entry corresponding to its index.
     */
    function applyBatchResult(batch, method, result) {
        var isArray = Array.isArray(result);
        for (var i in batch) {
            var value = isArray ? result[i] : result;
            batch[i][method](value);
        }
    }

    /**
     * Wrap the provided function and automatically batch calls to it
     *
     * @private
     * @param {Object} options Bundle options object containing timeout and maxSize
     * @param {function} executeBatch Function to call to complete batch
     * @return {function}
     */
    function createBatcher(options, executeBatch) {
        var currentBatch = null;
        var currentTimeout = null;

        /**
         * Send the currently accumulated batch to the callback
         *
         * @private
         */
        function sendBatch() {
            var batch = currentBatch;
            currentBatch = null;
            var args = batch.map(function(x) {
                return x.arg;
            });
            executeBatch(args)
                .then(
                    function(result) {
                        applyBatchResult(batch, 'resolve', result);
                    },
                    function(e) {
                        applyBatchResult(batch, 'reject', e);
                    }
                ).then(function() {
                    M.util.js_complete(timeoutPendingKey);
                });
        }

        return function(arg) {
            if (currentBatch === null) {
                currentBatch = [];
                currentTimeout = setTimeout(sendBatch, options.timeout);
                M.util.js_pending(timeoutPendingKey);
            }
            return new Promise(function(resolve, reject) {
                currentBatch.push({
                    arg: arg,
                    resolve: resolve,
                    reject: reject
                });

                if (currentBatch.length >= options.maxSize) {
                    clearTimeout(currentTimeout);
                    sendBatch();
                }
            });
        };
    }

    /**
     * Send a request to the GraphQL endpoint
     *
     * @private
     * @param {(Object|Object[])} requestData Data object to send, or array of data objects to make a batched request.
     * @return {(Promise)} Promise resolving to GraphQL API's response
     */
    function makeRequest(requestData) {
        M.util.js_pending(pendingKey);
        // Add cosmetic only operationName(s) parameter to help diagnostics in browser console.
        var url = config.wwwroot + '/totara/webapi/ajax.php' +
            (Array.isArray(requestData)
                ? '?operationNames=' + requestData.map(function(r) {
                    return r.operationName;
                }).join(',')
                : '?operationName=' + requestData.operationName);

        return new Promise(function(resolve, reject) {
            fetch(url, {
                credentials: 'same-origin',
                method: 'post',
                body: JSON.stringify(requestData),
                headers: new Headers({
                    'X-Totara-Sesskey': config.sesskey
                })
            }).then(function(response) {
                if (response.ok) {
                    return response.json();
                } else {
                    // Try and parse response as JSON, if that doesn't work do a generic rejection
                    return response.json().then(
                        function(result) {
                            return Promise.reject(result);
                        },
                        function() {
                            throw new Error('Request failed with status code ' + response.status);
                        }
                    );
                }
            }).then(function(result) {
                M.util.js_complete(pendingKey);
                resolve(result);
            }).catch(function(result) {
                M.util.js_complete(pendingKey);
                if (unloading) {
                    // No need to trigger an error because we are already navigating.
                    Log.error('Page unloaded.');
                    Log.error(result);
                } else {
                    Log.error(result);
                    reject(result);
                }
            });
        });
    }

    var batchConfig = {timeout: BATCH_TIMEOUT, maxSize: BATCH_MAX_SIZE};
    var batchedRequest = createBatcher(batchConfig, makeRequest);
    var batchedRequestNosession = createBatcher(batchConfig, makeRequest);

    /**
     * Convert request to a form suitable for sending to the server
     *
     * @private
     * @param {Object} request
     * @return {Object}
     */
    function processRequest(request) {
        return {
            operationName: request.operationName,
            variables: request.variables || {},
            extensions: request.extensions
        };
    }

    /**
     * Wait for response and process it to return to the caller
     *
     * @private
     * @param {Promise} response Response promise
     * @return {Promise}
     */
    function processResponsePromise(response) {
        return response
            .then(function(result) {
                if (result.errors) {
                    return Promise.reject(result);
                }
                return result.data;
            })
            .catch(function(result) {
                return Promise.reject(createError(result));
            });
    }

    return /** @alias module:core/webapi */ {
        // Public variables and functions.
        /**
         * Make an ajax Web API requests and return all the responses.
         *
         * @method call
         * @param {Object} request Request object
         * @param {string} request.operationName Name of query to execute
         * @param {object} request.variables Variables to pass to query
         * @param {boolean} request.rawResponse Return raw GraphQL protocol response
         * @param {boolean} request.batch
         *     Should this request be batched?
         *     Requests with { batch: true } made within a short window will be automatically
         *     combined.
         *     Use this parameter if you are making a lot of small requests to the server
         *     around the same time in order to reduce overhead.
         *     '_nosession' requests will be batched separately from regular requests.
         * @return {Promise} Promise that will be resolved when the ajax call returns.
         */
        call: function(request) {
            if (!inited) {
                window.addEventListener('beforeunload', function() {
                    unloading = true;
                });
                inited = true;
            }

            var requestData = processRequest(request);
            var promise;

            if (request.batch) {
                if (request.operationName.endsWith('_nosession')) {
                    promise = batchedRequestNosession(requestData);
                } else {
                    promise = batchedRequest(requestData);
                }
            } else {
                promise = makeRequest(requestData);
            }

            return request.rawResponse ? promise : processResponsePromise(promise);
        },
    };
});

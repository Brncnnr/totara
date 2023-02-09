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


(function(global) {
    // eslint-disable-next-line spaced-comment, no-redeclare
    /*global microsoftTeams,Promise*/

    // eslint-disable-next-line camelcase
    global.totara_msteams_auth = global.totara_msteams_auth || {};

    /**
     * Decode base64 url string. Note that the output string is in the ASCII range.
     *
     * @param {string} base64url
     * @returns {string|false}
     */
    function base64urlDecode(base64url) {
        if (!/^[A-Za-z0-9\-_=]+$/.test(base64url)) {
            return false;
        }
        var base64 = base64url.replace(/-/g, '+').replace(/_/g, '/');
        return global.atob(base64);
    }

    /**
     * Finalise the sign-in workflow.
     *
     * @param {object} config
     * @returns {Promise}
     */
    global.totara_msteams_auth.complete_login = global.totara_msteams_auth.complete_login || function completeLogin(config) {
        return new Promise(function(resolve) {
            microsoftTeams.initialize();
            microsoftTeams.authentication.notifySuccess(config.code);
            resolve();
        });
    };

    /**
     * Handle a deep link URL.
     *
     * @param {object} config
     * @returns {Promise}
     */
    global.totara_msteams_auth.redirect_deeplink = global.totara_msteams_auth.redirect || function redirect(config) {
        return new Promise(function(resolve) {
            microsoftTeams.initialize();
            microsoftTeams.getContext(function(context) {
                try {
                    if (typeof context.subEntityId !== 'undefined' && context.subEntityId) {
                        var subEntity = JSON.parse(base64urlDecode(context.subEntityId) || '{}');
                        if (!('type' in subEntity && 'value' in subEntity)) {
                            throw new Error('invalid state');
                        }
                        // Override the return url.
                        if (subEntity.type === 'openUrl') {
                            var url = new global.URL(subEntity.value, config.wwwroot).href;
                            // Accept only local URLs.
                            if (url.indexOf(config.wwwroot) === 0) {
                                config.redirectUrl = url;
                            }
                        }
                    }
                } finally {
                    global.location.href = config.redirectUrl;
                    resolve();
                }
            });
        });
    };

    /**
     * Perform single sign-on.
     *
     * @param {object} config
     * @returns {Promise}
     */
    global.totara_msteams_auth.sso_login = global.totara_msteams_auth.sso_login || function ssoLogin(config) {
        return new Promise(function(resolve, reject) {
            var debug = 'debug' in config && config.debug;
            var log = debug ? global.console.log : function() {
                // do nothing
            };

            microsoftTeams.initialize();
            document.body.classList.remove('sso-failure');

            var authTokenRequest = {
                successCallback: function(result) {
                    log('getAuthToken succeeded: ' + result);
                    global.location.href = config.oidcLoginUrl
                                        + '?idtoken=' + encodeURIComponent(result)
                                        + '&sesskey=' + encodeURIComponent(config.sesskey)
                                        + '&returnurl=' + encodeURIComponent(config.returnUrl);
                    resolve();
                },
                failureCallback: function(error) {
                    log('getAuthToken failed: ' + error);
                    document.body.classList.add('sso-failure');
                    reject(error);
                }
            };

            microsoftTeams.authentication.getAuthToken(authTokenRequest);
        });
    };
})(window);

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
 * @author Brian Barnes <brian.barnes@totaralearning.com>
 * @package mod_facetoface
 */
define(['core/str'], function(str) {
    /** @type {null|WindowProxy} */
    var popup = null;

    /** @type {HTMLSelectElement} */
    var pluginSelector;

    /** @type {HTMLInputElement} */
    var authoriseButton;

    /** @type {HTMLInputElement} */
    var connectionState;

    /**
     * Handles after log in through oAuth works expected
     *
     * @param {MessageEvent} e Sent after teams log in has been completed
     */
    function messageCallback(e) {
        if (popup) {
            if (e.data.sender && e.data.sender === 'auth_callback') {
                if (e.data.status === 'success') {
                    verifyConnection(pluginSelector.value);
                } else {
                    str.get_string('connectionfailed', 'mod_facetoface').then(function(string) {
                        document.querySelector('.mod_facetoface-connected').innerText = string;
                    });
                }
                popup.close();
            }
        }
    }

    /**
     * Confirms if a user is connected to an authorisation provider
     *
     * @param {String} method The plugin to check
     */
    function verifyConnection(method) {
        M.util.js_pending('mod_facetoface-room_integration-oauth_confirmation');
        connectionState.value = 0;
        new Promise(function (resolve) {
            require(['core/ajax'], resolve);
        }).then(function(ajaxLib) {
            return ajaxLib.call([{
                methodname:'mod_facetoface_user_profile',
                args: {
                    plugin: method
                }
            }])[0];
        }).then(function (data) {
            if ('name' in data) {
                if ('friendly_name' in data) {
                    data.name = data.friendly_name;
                }
                if ('email' in data) {
                    return str.get_string('connectedasx', 'mod_facetoface', data);
                } else {
                    return str.get_string('connectedas', 'mod_facetoface', data.name);
                }
            } else {
                return Promise.resolve('');
            }
        }).then(function (string) {
            connectionState.value = string !== '' ? 1 : 0;
            document.querySelector('.mod_facetoface-connected').innerText = string;
            M.util.js_complete('mod_facetoface-room_integration-oauth_confirmation');
        }).catch(function() {
            document.querySelector('.mod_facetoface-connected').innerText = '';
            M.util.js_complete('mod_facetoface-room_integration-oauth_confirmation');
        });
    }

    /**
     * Handles changing of the virtual room provider dropdown
     */
    function changeHandler() {
        /** @type {HTMLInputElement} */
        var endpoint = document.querySelector('[name="' + pluginSelector.value + '_auth_endpoint"]');

        if (popup) {
            popup.close();
        }

        if (endpoint) {
            authoriseButton.disabled = false;
            verifyConnection(pluginSelector.value);
        } else {
            authoriseButton.disabled = true;
            connectionState.value = 1;
        }
    }

    /**
     * Starts the authentication process for an oAuth provider
     *
     * @param {PointerEvent} e The click event
     */
    function connect(e) {
        if (authoriseButton.disabled) {
            return;
        }
        e.preventDefault();

        /** @type {HTMLInputElement} */
        var endpoint = document.querySelector('[name="' + pluginSelector.value + '_auth_endpoint"]');

        if (endpoint) {
            if (!popup || popup.closed) {
                popup = window.open(endpoint.value, 'virtualmeeting_connect', 'width=400,height=500,opener');
            } else {
                popup.focus();
            }
        }
    }

    return {
        /**
         * Initialises additional form element handlers
         */
        init: function() {
            pluginSelector = document.getElementById('id_plugin');
            authoriseButton = document.getElementById('show-authorise-dialog');
            authoriseButton.disabled = true;
            connectionState = document.getElementById('plugin-connection-state');
            connectionState.value = 0;

            changeHandler();

            pluginSelector.removeEventListener('change', changeHandler);
            pluginSelector.addEventListener('change', changeHandler);

            authoriseButton.removeEventListener('click', connect);
            authoriseButton.addEventListener('click', connect);

            window.removeEventListener('message', messageCallback);
            window.addEventListener('message', messageCallback);
        }
    };
});

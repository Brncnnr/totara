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
 * @package theme_msteams
 */

 /**
  * Simulate the Microsoft Team SDK for automated testing.
  * https://docs.microsoft.com/en-us/javascript/api/overview/msteams-client
  *
  * Note that this fake SDK does not implement all the features the real SDK provides.
  */
(function(global) {
    if ('microsoftTeams' in global) {
        return;
    }
    var that = {
        _state: {
            canSave: false,
            settings: {},
            notifyState: {},
        },
        _context: null,
        _saveHandler: null,
        _saveEvent: null,
        _themeChangeHandler: null,

        initialize: function(callback) {
            global.console.log('initialize');
            var doc = global.document;
            doc.documentElement.classList.add('theme_msteams__iframe');
            doc.body.classList.add('theme_msteams__theme--default');

            if (typeof callback !== 'undefined') {
                callback();
            }
        },

        getContext: function(callback) {
            global.console.log('getContext');
            that._context = {
                locale: 'en-US',
                hostClientType: 'web',
                theme: 'default',
                channelType: 'Regular',
                isFullScreen: false,
                subEntityId: ''
                // Add missing properties here if necessary:
                // https://docs.microsoft.com/en-us/javascript/api/@microsoft/teams-js/microsoftteams.context
            };
            callback(that._context);
        },

        settings: {
            setValidityState: function(state) {
                global.console.log('setValidityState', state);
                that._state.canSave = state;
            },

            setSettings: function(instanceSettings, onComplete) {
                global.console.log('setSettings', instanceSettings);
                that._state.settings = Object.assign({}, {
                    contentUrl: '',
                    entityId: '',
                    removeUrl: '',
                    suggestedDisplayName: '',
                    websiteUrl: ''
                }, instanceSettings);
                if (typeof onComplete !== 'undefined') {
                    onComplete(true);
                }
            },

            registerOnSaveHandler: function(handler) {
                that._saveHandler = handler;
            }
        },

        registerOnThemeChangeHandler: function(handler) {
            // nothing to do.
            that._themeChangeHandler = handler;
        },

        _getState: function() {
            return that._state;
        },

        _save: function() {
            if (that._state.canSave && that._saveHandler) {
                that._saveHandler(that._createSaveEvent());
            }
        },

        _createSaveEvent: function() {
            return {
                notifySuccess: function() {
                    that._state.notifyState = {success: true};
                },

                notifyFailure: function(reason) {
                    if (typeof reason === 'undefined' || reason === null) {
                        reason = true;
                    }
                    that._state.notifyState = {failure: reason};
                }
            };
        },
    };
    global.microsoftTeams = that;
})(window);

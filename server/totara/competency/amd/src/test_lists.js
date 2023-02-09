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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_core
 */

define([], function() {

    /**
     * Class constructor for the testLists
     *
     * @class
     * @constructor
     */
    function TestLists() {
        if (!(this instanceof TestLists)) {
            return new TestLists();
        }
    }

    // Listen for propagated events
    TestLists.prototype.registerEventListeners = function() {
        var that = this;

        document.addEventListener('totara_core/lists:action', function(e) {
            that.showNotification('info', '\'' + e.detail.key + '\' action clicked for row with id \'' + e.detail.val + '\'');
            if (e.detail.key === 'delete') {
                e.target.closest('[data-tw-list]').setAttribute('data-tw-list-removerow', JSON.stringify([e.detail.val]));
            }
        });

        document.addEventListener('totara_core/lists:hierarchyRequest', function(e) {
            that.showNotification('info', 'hierarchy icon clicked for row with id \'' + e.detail.val + '\'');
        });

        document.addEventListener('totara_core/lists:add', function(e) {
            that.showNotification('info', 'selected row with id \'' + e.detail.val + '\'');
        });

        document.addEventListener('totara_core/lists:remove', function(e) {
            that.showNotification('info', 'deselected row with id \'' + e.detail.val + '\'');
        });
    };

    /**
     * Show notification
     *
     * @param {String} type success, warning, error, etc/
     * @param {String} message used for get_string
     */
    TestLists.prototype.showNotification = function(type, message) {
        require(['core/notification'], function(notification) {
            notification.clearNotifications();
            notification.addNotification({
                message: message,
                type: type
            });
        });
    };

    /**
    * widget initialisation method
    *
    * @returns {Promise}
    */
    var init = function() {
        return new Promise(function(resolve) {
            // Create an instance of widget
            var wgt = new TestLists();
            wgt.registerEventListeners();
            resolve(wgt);
        });
    };

    return {
        init: init
    };
});
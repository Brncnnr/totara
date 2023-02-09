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

define(['totara_competency/action_list_manager', 'totara_competency/loader_manager'], function(ListManager, LoaderManager) {

    /**
     * Show notification
     *
     * @param {String} type success, warning, error, etc/
     * @param {String} message used for get_string
     */
    var showNotification = function(type, message) {
        require(['core/notification'], function(notification) {
            notification.clearNotifications();
            notification.addNotification({
                message: message,
                type: type
            });
        });
    };

    /**
     * Register event listeners
     *
     * @param {ListManager} listManager
     * @param {LoaderManager} loaderManager
     */
    var registerEventListeners = function(listManager, loaderManager) {
        listManager.onListHierarchyLevelChange = function(e) {
            showNotification('info', 'hierarchy level for item with id \'' + e.detail.val + '\' changed');
        };

        listManager.onListHierarchyLevelChangeExtend = function(e) {
            showNotification('info', 'extended hierarchy level for item with id \'' + e.detail.val + '\' changed');
        };

        listManager.onItemSelected = function(id) {
            showNotification('info', 'item with id \'' + id + '\' selected');
        };

        listManager.onItemUnselected = function(id) {
            showNotification('info', 'item with id \'' + id + '\' unselected');
        };

        listManager.onItemUpdate = function() {
            showNotification('info', 'item got updated');
        };

        listManager.onPreRequest = function() {
            showNotification('info', 'update pre request');
            loaderManager.show();
        };

        listManager.onPostRequest = function() {
            showNotification('info', 'update post request');
            loaderManager.hide();
        };

        listManager.onCustomAction = function(e) {
            showNotification('info', 'action \'' + e.detail.key + '\' for id \'' + e.detail.val + '\' triggered');
        };
    };

    /**
     * Defines actions
     *
     * @return {Object[]}
     */
    var actionsCallback = function() {
        return [
            {
                name: 'delete',
                icon: 'delete',
                eventKey: 'deleteClicked',
                disabled: false,
                hidden: false
            }, {
                name: 'archive',
                icon: 'archive',
                eventKey: 'archiveClicked',
                disabled: false,
                hidden: false
            }, {
                name: 'activate',
                icon: 'activate',
                eventKey: 'activateClicked',
                disabled: false,
                hidden: false
            }
        ];
    };

    /**
     * Defines icons to be used by the actions
     *
     * @return {Object[]}
     */
    var iconsCallback = function() {
        return [
            {
                key: 'delete',
                name: 'delete',
                string: 'delete',
                component: 'totara_core',
            }, {
                key: 'activate',
                name: 'check',
                string: 'activate',
                component: 'totara_core',
            }, {
                key: 'archive',
                name: 'archive',
                string: 'archive',
                component: 'totara_core',
            }
        ];
    };

    /**
     * List mapping properties
     *
     * @param {int} hasHierarchy
     * @param {int} hasCheckboxes
     *
     * @returns {JSON} mapping structure
     */
    var listMapping = function(hasHierarchy, hasCheckboxes) {
        return {
            actions: actionsCallback,
            cols: [
                {
                    dataPath: 'column1',
                    headerString: {
                        'value': 'Column 1'
                    },
                    expandedViewTrigger: true
                }, {
                    dataPath: 'column2',
                    headerString: {
                        'value': 'Column 2'
                    },
                }, {
                    dataPath: 'column3',
                    headerString: {
                        'value': 'Column 3'
                    },
                }, {
                    dataPath: 'column4',
                    headerString: {
                        'value': 'Column 4'
                    },
                    size: 'sm'
                }
            ],
            extraRowData: [
                {
                    key: 'column4',
                    dataPath: 'column4'
                }
            ],
            hasHierarchy: hasHierarchy > 0,
            hasCheckboxes: hasCheckboxes > 0,
            hasExpandedView: true,
            expandTemplate: 'totara_competency/test_lists_expand',
            expandWebservice: 'totara_core_test_lists_manager_show',
            icons: iconsCallback
        };
    };

    /**
     * widget initialisation method
     *
     * @param {node} parent
     *
     * @returns {Promise}
     */
    var init = function(parent) {
        var behatString = 'TestListManagerPause';

        return new Promise(function(resolve) {
            M.util.js_pending(behatString);

            // Let's be able to turn hierarchy on or off by changing template vars
            // this allows for using GET params on this page to influence the JS here
            var hasHierarchy = parent.getAttribute('data-tw-list-hasHierarchy'),
                hasCheckboxes = parent.getAttribute('data-tw-list-hasCheckboxes');

            var listConfig = {
                map: listMapping(hasHierarchy, hasCheckboxes),
                service: 'totara_core_test_lists_manager',
                serviceArgs: {page: 0}
            };

            var listManager = ListManager.init(parent, listConfig);

            // We also want to show a loading indicator when the request is made
            var loaderManager = LoaderManager.init(parent);
            // We want to be able to react on events of the list
            registerEventListeners(listManager, loaderManager);

            // Prepare and load data
            listManager.load().then(function() {
                M.util.js_complete(behatString);
            });

            resolve();
        });
    };

    return {
        init: init
    };
 });
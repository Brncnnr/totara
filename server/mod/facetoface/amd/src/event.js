/*
 * This file is part of Totara LMS
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

/* global totaraDialog, totaraDialogs, totaraDialog_handler_form, totaraDialog_handler_treeview_multiselect */

define(['jquery', 'core/config', 'core/str', 'core/templates', 'core/notification'], function($, cfg, strLib, templatesLib, NotificationLib) {
    var THROTTLE = 10;
    var rootURL = cfg.wwwroot + '/mod/facetoface/';
    var pageConfig = null;

    // Virtual room statuss
    var STATUS_LEGACY = null;
    var STATUS_UNAVAILABLE = 0;
    var STATUS_AVAILABLE = 1;
    var STATUS_PENDING_UPDATE = -2;
    var STATUS_PENDING_DELETION = -3;
    var STATUS_FAILURE_CREATION = -4;
    var STATUS_FAILURE_UPDATE = -5;
    var STATUS_FAILURE_DELETION = -6;

    M.util.js_pending('mod_facetoface-events__dialogues_inited');
    var TotaraDialogsInited = new Promise(function(resolve) {
        if (window.dialogsInited) {
            resolve();
        } else {
            if (!window.dialoginits) {
                window.dialoginits = [];
            }
            window.dialoginits.push(resolve);
        }
    }).then(function() {
        M.util.js_complete('mod_facetoface-events__dialogues_inited');
    });

    var generalStrings = strLib.get_strings([
        {key: 'ok', component: 'moodle'},
        {key: 'cancel', component: 'moodle'},
        {key: 'loadinghelp', component: 'moodle'}
    ]).then(function(strings) {
        var str = {
            ok: strings[0],
            cancel: strings[1],
            loading: strings[2]
        };
        return str;
    });

    /**
     * Check if all dates are removed/there no dates and show notifications, disable certain elements
     */
    var dates_count_changed = function() {
        var hasSessions = false;
        var hasRooms = false;

        var useCapcity = document.getElementById('id_defaultcapacity');
        for (var offset = 0; offset < Number($('input[name="cntdates"]').val()); offset++) {
            if ($('input[name="datedelete[' + offset + ']"]').val() > 0) {
                continue;
            }
            var $input = $('input[name="roomids[' + offset + ']"]');
            if ($input.val().length > 0) {
                hasRooms = true;

            }
            hasSessions = true;
            if (hasSessions && hasRooms) {
                break;
            }
        }

        useCapcity.disabled = !hasRooms;

        if (!hasSessions) {
            var $sesstable = $('.sessiondates table.f2fmanagedates');
            $sesstable.hide();
            $sesstable.parent().append($('<div class="nodates_notification">' + M.util.get_string('nodatesyet', 'facetoface') + '</div>'));
        }
    };

    /**
     * Constructs Asset library object
     *
     * @param {Object} config Event config
     */
    function Resources(config) {
        if (!config) {
            NotificationLib.Exception('Config not supplied');
        }
    }

    Resources.prototype = {
        /**
         * Initialises the resource type
         */
        init: function() {
            var cntdates = Number(document.querySelector('input[name="cntdates"]').value);

            this.localStrings = strLib.get_strings([
                {key: 'choose' + this.type + 's', component: 'mod_facetoface'},
                {key: 'createnew' + this.type, component: 'mod_facetoface'}
            ]).then(function(strings) {
                var str = {
                    choose: strings[0],
                    create: strings[1]
                };
                return str;
            });

            for (var date = 0; date < cntdates; date++) {
                if (document.querySelector('input[name="datedelete[' + date + ']"]').value > 0) {
                    continue;
                }
                var input = document.querySelector('input[name="' + this.type + 'ids[' + date + ']"]');

                this.render(date);
                this.showCreateResouceDialog(input, date);
                this.showSelectResourceDialog(input, date);
            }
        },

        /**
         * Gets a resource Object from the server
         *
         * Note: these will be batched up and sent off to the server in one request
         *
         * @param {Number} id The id of the resource to retrieve from the server
         * @returns {Promise} resolves with the requested resource
         */
        getResource: function(id) {
            var that = this;
            if (!this.resources) {
                this.resources = [];
            }
            if (!this.requested) {
                this.requested = [];
            }

            if (this.resources[id]) {
                return Promise.resolve(this.resources[id]);
            } else if (this.isLoading) {
                if (that.requested.indexOf(id) === -1) {
                    that.requested.push(id);
                }
                return this.loadingPromise.then(function() {
                    return that.getResource(id);
                });
            } else {
                this.requested.push(id);
                return this._triggerLoad().then(function() {
                    return Promise.resolve(that.resources[id]);
                });
            }
        },

        /**
         * Generates a single resource HTML element
         *
         * @param {Number} id The ID of the resource requested
         * @returns {Promise} resolved with a li DOMElement for the given resource (complete with edit and delete icons)
         */
        generateElement: function(id) {
            var that = this;
            return this.getResource(id)
                .then(function(resource) {
                    var remove = strLib.get_string('remove' + that.type + 'x', 'mod_facetoface', resource.name_only)
                        .then(function(name) {
                            return templatesLib.renderIcon('delete', name);
                        }).then(function(deleteIcon) {
                            return '<a href="#" data-action="removeresource">' + deleteIcon + '</a>';
                        });

                    return Promise.all([resource, that.editIcon(resource), remove]);
                }).then(function(results) {
                    var resource = results[0];
                    var resourceElement = document.createElement('li');
                    var name = '<span class="mod_facetoface-resource-title">' + resource.name + '</span>';

                    that.getLiAttributes(resource, resourceElement);
                    resourceElement.innerHTML = name + results[1] + results[2];

                    return resourceElement;
                });
        },

        /**
         * Generates the edit icon
         *
         * @param {Resource} resource The resource that the edit icon is being derrived from
         * @returns {Promise} Resolves with the icon or an empty string if edit icon is not displayed
         */
        editIcon: function(resource) {
            var that = this;
            if (that.manageCustom && resource.custom) {
                return strLib
                    .get_string('editcustom' + that.type + 'x', 'mod_facetoface', resource.name_only)
                    .then(function(name) {
                        return templatesLib.renderIcon('edit', name);
                    }).then(function(editIcon) {
                        return '<a href="#" data-action="editresource">' + editIcon + '</a>';
                    });
            } else {
                return Promise.resolve('');
            }
        },

        /**
         * Triggers loading a bunch of resources
         *
         * @returns {Promise} Resolved once the latest batch of results has been loaded
         */
        _triggerLoad: function() {
            var that = this;
            this.isLoading = true;
            this.loadingPromise = new Promise(function(resolve, reject) {
                setTimeout(function() {
                    that._loadResources().then(function() {
                        that.isLoading = false;
                        resolve();
                    }).catch(reject);
                }, THROTTLE);
            });

            return this.loadingPromise;
        },

        /**
         * Loads a batch of resources from the server
         * @returns {Promise} resolved with the resource once loaded
         */
        _loadResources: function() {
            var that = this;
            var formdata = new FormData();
            if (this.requested.length > 0) {
                formdata.append('facetofaceid', pageConfig.facetofaceid);
                formdata.append('itemids', that.requested);
                formdata.append('sesskey', cfg.sesskey);
                M.util.js_pending('mod_facetoface-events__loading_resources');
                return fetch(this.loadUrl, {
                    credentials: 'same-origin',
                    method: 'POST',
                    body: formdata
                }).then(function(results) {
                    return results.json();
                }).then(function(items) {
                    that.requested = [];
                    items.forEach(function(item) {
                        that.resources[item.id] = item;
                    });
                    M.util.js_complete('mod_facetoface-events__loading_resources');
                });
            } else {
                return Promise.resolve();
            }
        },

        /**
         * Adds attributes to the appropriate DOM node
         * @param {Object} resource individual resource element
         * @param {DOMNode} listItem the DOM Element to add the attributes to
         */
        getLiAttributes: function(resource, listItem) {
            listItem.setAttribute('data-id', resource.id);
            listItem.setAttribute('data-custom', resource.custom);
            listItem.classList.add(this.cssClass);
        },

        /**
         * Shows the select resource totara dialog
         *
         * @param {InputElement} input The hidden input element for this resource/session
         * @param {Number} offset The session number that is being modified
         * @returns {Promise} resolved once the dialog has been displayed
         */
        showSelectResourceDialog: function(input, offset) {
            var that = this;
            var result = Promise.all([generalStrings, this.localStrings, TotaraDialogsInited])
                .then(function(results) {
                    var strings = results[0];
                    var localStrings = results[1];
                    var handler = new totaraDialog_handler_treeview_multiselect();
                    var buttonsObj = {};

                    handler._update = function() {
                        var elements = $('.selected > div > span', this._container);
                        var ids = this._get_ids(elements);
                        input.value = ids.join();
                        that.render(offset);
                        this._dialog.hide();
                    };

                    buttonsObj[strings.ok] = function() { handler._update(); };
                    buttonsObj[strings.cancel] = function() { handler._cancel(); };

                    handler.oldLoad = handler.load;

                    handler.load = function(response) {
                        handler.oldLoad(response);
                        var context = $(".ui-dialog [id^='select" + that.type + "s'][id$='-dialog']"),
                            height = context.height() - $('.dialog-footer', context).outerHeight();
                        $('.select', context).outerHeight(height);
                    };

                    totaraDialogs['select' + that.type + 's' + offset] = new totaraDialog(
                        'select' + that.type + 's' + offset + '-dialog',
                        'show-select' + that.type + 's' + offset + '-dialog',
                        {
                            buttons: buttonsObj,
                            title: '<h2>' + localStrings.choose + '</h2>'
                        },
                        function() {
                            var sessionid = pageConfig.sessionid;
                            if (Number(pageConfig.clone) == 1) {
                                sessionid = 0;
                            }
                            return that.selectUrl + '?sessionid=' + sessionid +
                                '&facetofaceid=' + pageConfig.facetofaceid +
                                '&timestart=' + $('input[name="timestart[' + offset + ']"]').val() +
                                '&timefinish=' + $('input[name="timefinish[' + offset + ']"]').val() +
                                '&selected=' + $('input[name="' + that.type + 'ids[' + offset + ']"]').val() +
                                '&offset=' + offset +
                                '&sesskey=' + cfg.sesskey;
                        },
                        handler
                    );
                }
            );

            return result;
        },

        /**
         * Shows a totara dialog to create a new adhoc resource
         *
         * @param {InputElement} input The hidden input element associated with this resource/session
         * @param {Number} offset The session number that this resource is being created for
         * @returns {Promise} Resolved when the resulting totara dialog is being displayed
         */
        showCreateResouceDialog: function(input, offset) {
            var that = this;
            var result = Promise.all([generalStrings, this.localStrings, TotaraDialogsInited])
                .then(function(results) {
                    var strings = results[0];
                    var localStrings = results[1];
                    var handler = new totaraDialog_handler_form();
                    handler.every_load = function() {
                        totaraDialog_handler_form.prototype.every_load.call(this);
                        totaraDialogs['select' + that.type + 's' + offset].handler._dialog.hide();
                        totaraDialogs['editcustom' + that.type + offset].config.title = '<h2>' + localStrings.create + '</h2>';
                    };
                    // Change behaviour of update function.
                    handler._updatePage = function(response) {
                        try {
                            // We expect json if dates processed without errors.
                            var elem = $.parseJSON(response);
                            var ids = [];
                            that.forceReload(elem.id);
                            if (input.value.length > 0) {
                                ids = input.value.split(',');
                            }
                            if (ids.indexOf(elem.id.toString()) === -1) {
                                ids.push(elem.id);
                            }
                            input.value = ids.toString();
                            that.render(offset);
                            handler._dialog.hide();
                        } catch (e) {
                            this._dialog.render(response);
                        }
                    };

                    // Create new dialog.
                    var buttonsObj = {};
                    buttonsObj[strings.ok] = function() { handler.submit(); };
                    buttonsObj[strings.cancel] = function() { handler._cancel(); };

                    totaraDialogs['editcustom' + that.type + offset] = new totaraDialog(
                        'editcustom' + that.type + offset + '-dialog',
                        'show-editcustom' + that.type + offset + '-dialog',
                        {
                            buttons: buttonsObj,
                            title: '<h2>' + localStrings.create + '</h2>'
                        },
                        function() {
                            var id = 0;
                            // Store id in pageConfig.config for now to allow edit custom facilitators.
                            if (typeof pageConfig.editcustom !== "undefined") {
                                id = Number(pageConfig.editcustom);
                                pageConfig.editcustom = 0;
                            }
                            return that.createUrl + '?id=' + id + '&f=' + pageConfig.facetofaceid +
                                '&s=' + pageConfig.sessionid + '&sesskey=' + cfg.sesskey;
                        },
                        handler
                    );

                }
            );

            return result;
        },

        /**
         * Forces a reload of a resource and updates the display of that item
         *
         * @param {Number} id The id of the resource to be reloaded
         */
        forceReload: function(id) {
            var reload = document.querySelectorAll('.' + this.cssClass + '[data-id="' + id + '"]');

            if (this.resources && this.resources[id]) {
                delete this.resources[id];
            }

            if (reload.length > 0) {
                this.generateElement(id).then(function(newElement) {
                    var element;
                    for (element = 0; element < reload.length; element++) {
                        // Use outerHTML as simply assigning it places it into the last location (as it'll only appear in the DOM once) ;
                        reload[element].outerHTML = newElement.outerHTML;
                    }
                }).catch(NotificationLib.exception);
            }
        },

        /**
         * Renders the list of resources for the session
         *
         * @param {Number} offset The session id that is being modified
         */
        render: function(offset) {
            if (document.querySelector('input[name="datedelete[' + offset + ']"]').value > 0) {
                // Do nothing if date has been deleted
                return;
            }

            var items = document.querySelector('input[name="' + this.type + 'ids[' + offset + ']"]').value.split(',');
            var list = document.getElementById(this.type + 'list' + offset);
            var that = this;

            if (items.length && items[0] !== '') {
                var loading = document.createElement('li');
                generalStrings.then(function(strings) {
                    loading.innerText = strings.loading;
                }).catch(NotificationLib.exception);

                list.appendChild(loading);

                var ResourcePromises = items.map(function(resourceId) {
                    return that.generateElement(resourceId);
                });

                Promise.all(ResourcePromises).then(function(data) {
                    list.innerHTML = '';
                    data.forEach(function(elem) {
                        list.appendChild(elem);
                    });
                    if (that.updateCapacity) {
                        that.updateCapacity(offset);
                    }

                    if (that.resourcesUpdated) {
                        that.resourcesUpdated(offset);
                    }
                }).catch(NotificationLib.exception);
            }

        }
    };

    /**
     * @inheritdoc
     */
    function Facilitators(config) {
        this.manageCustom = config.manageadhocfacilitators;
        this.init();
    }

    Facilitators.prototype = Object.assign({}, Resources.prototype);
    Facilitators.prototype.type = 'facilitator';
    Facilitators.prototype.loadUrl = rootURL + 'facilitator/ajax/facilitator_item.php';
    Facilitators.prototype.cssClass = 'facilitatorname';
    Facilitators.prototype.selectUrl = rootURL + 'facilitator/ajax/sessionfacilitators.php';
    Facilitators.prototype.createUrl = rootURL + 'facilitator/ajax/edit.php';

    /**
     * @inheritdoc
     */
    function Assets(config) {
        this.manageCustom = config.manageadhocassets;
        this.init();
    }

    Assets.prototype = Object.assign({}, Resources.prototype);
    Assets.prototype.type = 'asset';
    Assets.prototype.loadUrl = rootURL + 'asset/ajax/asset_item.php';
    Assets.prototype.cssClass = 'assetname';
    Assets.prototype.selectUrl = rootURL + 'asset/ajax/sessionassets.php';
    Assets.prototype.createUrl = rootURL + 'asset/ajax/asset_edit.php';

    /**
     * @inheritdoc
     */
    function Rooms(config) {
        this.manageCustom = config.manageadhocrooms;
        this.init();
    }

    Rooms.prototype = Object.assign({}, Resources.prototype);
    Rooms.prototype.type = 'room';
    Rooms.prototype.loadUrl = rootURL + 'room/ajax/room_item.php';
    Rooms.prototype.cssClass = 'roomname';
    Rooms.prototype.selectUrl = rootURL + 'room/ajax/sessionrooms.php';
    Rooms.prototype.createUrl = rootURL + 'room/ajax/room_edit.php';

    /**
     * @inheritdoc
     */
    Rooms.prototype.getLiAttributes = function(resource, listItem) {
        listItem.setAttribute('data-id', resource.id);
        listItem.setAttribute('data-custom', resource.custom);
        listItem.setAttribute('data-capacity', resource.capacity);
        listItem.setAttribute('data-can-manage', resource.can_manage);
        listItem.setAttribute('data-is-virtual', resource.virtualmeeting);
        listItem.setAttribute('data-lossy-update', resource.lossyupdate);
        listItem.classList.add(this.cssClass);
    };

    /**
     * Renders the edit icon (if required for the Room)
     *
     * @param {Room} resource The Room to render
     * @returns {Promise} Resolves with what should go where the edit icon belongs
     */
    Rooms.prototype.editIcon = function(resource) {
        var that = this;
        if (that.manageCustom && resource.custom) {
            if (resource.can_manage) {
                return strLib
                    .get_string('editcustom' + that.type + 'x', 'mod_facetoface', resource.name_only)
                    .then(function(name) {
                        return templatesLib.renderIcon('edit', name);
                    }).then(function(editIcon) {
                        return '<a href="#" data-action="editresource">' + editIcon + '</a>';
                    });
            } else {
                return strLib
                    .get_string('virtual_meeting_not_owner', 'mod_facetoface', resource.name_only)
                    .then(function(name) {
                        return templatesLib.renderIcon('lock', name);
                    });
            }
        } else {
            return Promise.resolve('');
        }
    };

    /**
     * Updates the hidden capacity field when a room is added/removed/changed from a session
     *
     * @param {Number} eventid The event id that is being has the room change
     */
    Rooms.prototype.updateCapacity = function(eventid) {
        var rooms = document.querySelectorAll('.mod_facetoface-roomlist[data-offset="' + eventid + '"] [data-capacity]');
        if (rooms.length === 0) {
            document.querySelector('input[name="roomcapacity[' + eventid + ']"]').value = 0;
            this._updateCapacityState();
            return;
        }
        var capacities = [];
        rooms.forEach(function(room) {
            capacities.push(parseInt(room.getAttribute('data-capacity'), 10));
        });

        var capacity = Math.min.apply(null, capacities);
        if (capacity === Infinity) {
            capacity = 0;
        }
        document.querySelector('input[name="roomcapacity[' + eventid + ']"]').value = capacity;
        this._updateCapacityState();
    };

    /**
     * Updates whether the "Use room capacity" button is disabled or enabled
     */
    Rooms.prototype._updateCapacityState = function() {
        var totalDates = document.querySelector('[name="cntdates"]').value;
        var capacityButton = true;
        for (var date = 0; date < totalDates; date++) {
            if (document.querySelector('input[name="datedelete[' + date + ']"]').value == 1) {
                continue;
            }

            if (document.querySelector('input[name="roomcapacity[' + date + ']"]').value == 0) {
                capacityButton = false;
                break;
            }
        }

        document.getElementById('id_defaultcapacity').disabled = !capacityButton;
    };

    /**
     * Does any work required when a resource has beed updated
     * @param {Number|String} offset The session number that is being looked at
     */
    Rooms.prototype.resourcesUpdated = function(offset) {
        /** @type {NodeList} */
        var roomnames = document.querySelectorAll('#roomlist' + offset + ' .roomname');
        var dateLink = document.getElementById('show-selectdate' + offset + '-dialog');
        var sessiondateid = document.querySelector('[name="sessiondateid[' + offset + ']').value;
        var row = dateLink.closest('tr');
        var canManage = true;
        var virtualCount = 0;
        var lossyUpdate = false;
        var that = this;

        roomnames.forEach(function(roomname) {
            that.getResource(roomname.getAttribute('data-id')).then(function(room) {
                // Find the element that matches this record
                room.virtualroom_status.filter(function(status) {
                    return sessiondateid == status.sessionsdateid;
                }).forEach(function(status) {
                    // Add any additional information required based on the state
                    // Strikethroug is done on the CSS class
                    switch (status.status) {
                        case STATUS_LEGACY:
                            // shouldn't be hit, but here for completeness
                            roomname.classList.add('mod_facetoface-room_legacy');
                            break;

                        case STATUS_UNAVAILABLE:
                            roomname.classList.add('mod_facetoface-room_unavailable');
                            strLib.get_string('virtual_meeting_failed', 'mod_facetoface')
                                .then(function(string) {
                                    roomname.setAttribute('title', string);
                                    roomname.setAttribute('aria-label', string);
                                });
                            /** @type {HTMLElement} */
                            var editIcon = roomname.querySelector('[data-action="editresource"]');
                            if (editIcon) {
                                editIcon.innerHTML = '';
                                templatesLib.renderIcon('lock').then(function(icon) {
                                    editIcon.outerHTML = icon;
                                });
                            }
                            break;

                        case STATUS_AVAILABLE:
                            roomname.classList.add('mod_facetoface-room_available');
                            break;

                        case STATUS_PENDING_UPDATE:
                            roomname.classList.add('mod_facetoface-room_pending_update');
                            break;

                        case STATUS_PENDING_DELETION:
                            roomname.classList.add('mod_facetoface-room_pending_deletion');
                            break;

                        case STATUS_FAILURE_CREATION:
                            roomname.classList.add('mod_facetoface-room_failure_creation');
                            strLib.get_string('virtual_meeting_failed', 'mod_facetoface')
                                .then(function(string) {
                                    roomname.setAttribute('title', string);
                                    roomname.setAttribute('aria-label', string);
                                });
                            break;

                        case STATUS_FAILURE_UPDATE:
                            roomname.classList.add('mod_facetoface-room_failure_update');
                            strLib.get_string('virtual_meeting_failed', 'mod_facetoface')
                                .then(function(string) {
                                    roomname.setAttribute('title', string);
                                    roomname.setAttribute('aria-label', string);
                                });
                            break;

                        case STATUS_FAILURE_DELETION:
                            roomname.classList.add('mod_facetoface-room_failure_deletion');
                            strLib.get_string('virtual_meeting_failed', 'mod_facetoface')
                                .then(function(string) {
                                    roomname.setAttribute('title', string);
                                    roomname.setAttribute('aria-label', string);
                                });
                            break;
                    }
                });
            });
        });

        roomnames.forEach(function (name) {
            if (name.getAttribute('data-can-manage') === 'false') {
                canManage = false;
            }

            if (name.getAttribute('data-is-virtual') === 'true') {
                virtualCount++;
            }

            if (name.getAttribute('data-lossy-update') === 'true') {
                lossyUpdate = true;
            }
        });

        NotificationLib.clearNotifications();
        if (virtualCount > 1) {
            strLib.get_string('error:toomanyvirtualmeetings', 'mod_facetoface').then(function(string) {
                NotificationLib.addNotification({message: string});
            });
        }

        if (lossyUpdate) {
            if (pageConfig.sessionid && Number(pageConfig.clone) != 1) {
                strLib.get_string('warning:meetingmaynotbepreserved', 'mod_facetoface').then(function(string) {
                    NotificationLib.addNotification({type: 'warning', message: string});
                });
            }
        }

        if (!canManage) {
            row.classList.add('mod_facetoface-date-other-virtual-room');
        } else {
            row.classList.remove('mod_facetoface-date-other-virtual-room');
        }
    };

    return {

        // Optional php params and defaults defined here, args passed to init method
        // below will override these values
        config: {},

        /**
         * Per-date fields that should be copied to clone event.
         */
        clonefields: ['roomids', 'assetids', 'facilitatorids', 'timestart', 'timefinish', 'sessiontimezone'],

        /**
         * Base url
         * @var string
         */
        url: cfg.wwwroot + '/mod/facetoface/',

        /**
         * module initialisation method called by php js_init_call()
         *
         * @param {Object} config configuration from PHP script
         */
        init: function(config) {
            pageConfig = config;
            this.addDOMEvents();
            this.facilitatorLib = new Facilitators(config);
            this.assetLib = new Assets(config);
            this.roomLib = new Rooms(config);

            this.config = config;

            this.init_dates();

            // Count of all dates (active and removed).
            var cntdates = Number($('input[name="cntdates"]').val());

            // Use room capacity button.
            var $capacitybtn = $('<input name="defaultcapacity" type="button" id="id_defaultcapacity">');
            strLib.get_string('useroomcapacity', 'mod_facetoface').then(function(str) {
                $capacitybtn.val(str);
            }).catch(NotificationLib.exception);

            $capacitybtn.click(function(e) {
                e.preventDefault();
                var min = 0;
                for (var offset = 0; offset < cntdates; offset++) {
                    if ($('input[name="datedelete[' + offset + ']"]').val() > 0) {
                        continue;
                    }

                    var current = Number($('input[name="roomcapacity[' + offset + ']"]').val());
                    if (min === 0 || (min > current && current > 0)) {
                        min = current;
                    }
                }
                if (min > 0) {
                    $('#id_capacity').val(min);
                }
            });
            $capacitybtn.insertAfter($('#id_capacity'));

            dates_count_changed();

            // Remove date.
            $('a.dateremove').each(function() {
                var offset = $(this).data('offset');
                // Delete date set field "datedelete[offset]" to 1 and hide row (do not remove row,
                // as it needs to be submitted in order to process form).
                if ($('input[name="datedelete[' + offset + ']"]').val() > 0) {
                    $(this).closest('tr').hide();
                }
            });

            // Add new date.
            $('input[name="date_add_fields"]').click(function() {
                // eslint-disable-next-line no-undef, no-implicit-globals
                skipClientValidation = true;
                $('input[name="cntdates"]').val(cntdates + 1);
            });

            // Show sesion dates.
            $('.sessiondates').removeClass('hidden');
        },

        /**
         * Adds event listeners delgated on the session table
         */
        addDOMEvents: function() {
            var that = this;
            var sessionTable = document.querySelector('.sessiondates .f2fmanagedates');
            var form = document.getElementById('mform_seminar_event');

            sessionTable.addEventListener('click', function(event) {
                var actionElement = event.target.closest('[data-action]');

                if (actionElement) {
                    event.preventDefault();
                    var offset = actionElement.getAttribute('data-offset');
                    switch (actionElement.getAttribute('data-action')) {
                        case 'removedate':
                            document.querySelector('input[name="datedelete[' + offset + ']"]').value = 1;
                            actionElement.closest('tr').style.display = 'none';
                            dates_count_changed();
                            break;
                        case 'clonedate':
                            // Offset starts with 0, so no increment is needed here.
                            var newoffset = Number($('input[name="cntdates"]').val());
                            var form = actionElement.closest('form');

                            that.clonefields.forEach(function(name) {
                                var newelem = document.createElement('input');
                                newelem.setAttribute('name', name + '[' + newoffset + ']');
                                newelem.value = form.elements[name + '[' + offset + ']'].value;
                                form.append(newelem);
                            });
                            $('input[name="date_add_fields"]').click();
                            break;
                        case 'removeresource':
                            that._removeResource(actionElement);
                            break;
                        case 'editresource':
                            that._editResource(actionElement);
                            break;
                    }
                }
            });

            // Prevent form submission if more than virtual room per session is detected
            form.addEventListener('submit', function(ev) {
                var isValid = true;

                sessionTable.querySelectorAll('.mod_facetoface-roomlist').forEach(function(node) {
                    var hasVirtual = false;
                    node.querySelectorAll('.roomname').forEach(function(room) {
                        if (room.getAttribute('data-is-virtual') === "true") {
                            if (hasVirtual) {
                                isValid = false;
                            }
                            hasVirtual = true;
                        }
                    });
                });

                if (!isValid) {
                    ev.preventDefault();
                }
            });
        },

        /**
         * Removes a resource from the session
         *
         * @param {DOMNode} actionElement The remove link that was clicked
         */
        _removeResource: function(actionElement) {
            var list = actionElement.closest('[data-offset]');
            var listItem = actionElement.closest('[data-id]');
            var input = null;

            if (listItem.classList.contains('assetname')) {
                input = document.querySelector('[name="assetids[' + list.getAttribute('data-offset') + ']"]');
            } else if (listItem.classList.contains('roomname')) {
                input = document.querySelector('[name="roomids[' + list.getAttribute('data-offset') + ']"]');
            } else if (listItem.classList.contains('facilitatorname')) {
                input = document.querySelector('[name="facilitatorids[' + list.getAttribute('data-offset') + ']"]');
            } else {
                throw new Error('unknown resource type');
            }

            input.value = input.value.split(',')
                .filter(function(item) {
                    return item != listItem.getAttribute('data-id');
                })
                .join(',');
            list.removeChild(listItem);
            if (listItem.classList.contains('roomname')) {
                this.roomLib.updateCapacity(list.getAttribute('data-offset'));
                this.roomLib.resourcesUpdated(list.getAttribute('data-offset'));
            }
        },

        /**
         * Opens up the edit dialog
         *
         * @param {DOMNode} actionElement The edit link that was clicked
         */
        _editResource: function(actionElement) {
            var list = actionElement.closest('[data-offset]');
            var listItem = actionElement.closest('[data-id]');
            var offset = list.getAttribute('data-offset');
            if (listItem.getAttribute('data-custom') !== 'true') {
                throw new Error('Not a custom resource');
            }

            pageConfig.editcustom = listItem.getAttribute('data-id');

            if (listItem.classList.contains('assetname')) {
                totaraDialogs['editcustomasset' + offset].config.title = '<h2>' + M.util.get_string('editasset', 'facetoface') + '</h2>';
                totaraDialogs['editcustomasset' + offset].open();
            } else if (listItem.classList.contains('roomname')) {
                totaraDialogs['editcustomroom' + offset].config.title = '<h2>' + M.util.get_string('editroom', 'facetoface') + '</h2>';
                totaraDialogs['editcustomroom' + offset].open();
            } else if (listItem.classList.contains('facilitatorname')) {
                totaraDialogs['editcustomfacilitator' + offset].config.title = '<h2>' + M.util.get_string('editfacilitator', 'facetoface') + '</h2>';
                totaraDialogs['editcustomfacilitator' + offset].open();
            } else {
                throw new Error('unknown resource type');
            }
        },

        /**
         * Initialises session dates & handling
         */
        init_dates: function() {
            var url = this.url;
            var that = this;
            var localStrings = strLib.get_strings([
                {key: 'dateselect', component: 'mod_facetoface'}
            ]).then(function(strings) {
                return {
                    dateselect: strings[0]
                };
            });
            Promise.all([generalStrings, localStrings, TotaraDialogsInited]).then(function(results) {
                var globalStrings = results[0];
                var localStrings = results[1];
                // Select date dialog.
                $('.mod_facetoface-show-selectdate-dialog').each(function() {
                    var offset = $(this).data('offset');
                    var $dateitem = $('#timeframe-text' + offset);

                    if ($('input[name="datedelete[' + offset + ']"]').val() > 0) {
                        return;
                    }

                    // Init date display.
                    $dateitem.empty();
                    $dateitem.text(globalStrings.loading);
                    $.post(
                        url + 'events/ajax/date_item.php',
                        {
                            timestart: $('input[name="timestart[' + offset + ']"]').val(),
                            timefinish: $('input[name="timefinish[' + offset + ']"]').val(),
                            sesiontimezone: $('input[name="sessiontimezone[' + offset + ']"]').val(),
                            sesskey: cfg.sesskey
                        },
                        function(elem) {
                            $dateitem.empty();
                            $dateitem.html(elem);
                            $dateitem.addClass('nonempty');
                        },
                        'json'
                    );

                    // Date dialog & handler.
                    var handler = new totaraDialog_handler_form();

                    var buttonsObj = {};
                    buttonsObj[globalStrings.ok] = function() { handler.submit(); };
                    buttonsObj[globalStrings.cancel] = function() { handler._cancel(); };

                    // Change behaviour of update function.
                    handler._updatePage = function(response) {
                        try {
                            // We expect json if dates processed without errors.
                            var dates = $.parseJSON(response);
                            $('input[name="timestart[' + offset + ']"]').val(dates.timestart);
                            $('input[name="timefinish[' + offset + ']"]').val(dates.timefinish);
                            $('input[name="sessiontimezone[' + offset + ']"]').val(dates.sessiontimezone);
                            $('#timeframe-text' + offset).html(dates.html);

                            handler._dialog.hide();
                        } catch (e) {
                            this._dialog.render(response);
                        }
                    };

                    totaraDialogs['selectdate'+offset] = new totaraDialog(
                        'selectdate'+offset+'-dialog',
                        $(this).attr('id'),
                        {
                            buttons: buttonsObj,
                            title: '<h2>' + localStrings.dateselect + '</h2>'
                        },
                        function() {
                            var sessiondateid = $('input[name="sessiondateid[' + offset + ']"]').val();
                            if (Number(that.config.clone) == 1) {
                                sessiondateid = 0;
                            }
                            return url + 'events/ajax/sessiondates.php?sessiondateid=' + sessiondateid +
                                '&facetofaceid=' + that.config.facetofaceid +
                                '&roomids=' + $('input[name="roomids[' + offset + ']"]').val() +
                                '&assetids=' + $('input[name="assetids[' + offset + ']"]').val() +
                                '&facilitatorids=' + $('input[name="facilitatorids[' + offset + ']"]').val() +
                                '&timezone=' + encodeURIComponent($('input[name="sessiontimezone[' + offset + ']"]').val()) +
                                '&start=' + $('input[name="timestart[' + offset + ']"]').val() +
                                '&finish=' + $('input[name="timefinish[' + offset + ']"]').val() +
                                '&sesskey=' + cfg.sesskey;
                        },
                        handler
                    );
                });
            }).catch(NotificationLib.exception);
        }
    };
});
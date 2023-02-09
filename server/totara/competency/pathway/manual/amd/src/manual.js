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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package pathway_manual
 */

define(['core/templates', 'core/notification', 'core/ajax', 'totara_competency/modal_list'],
function(templates, notification, ajax, ModalList) {

    /**
     * Class constructor for PwManual.
     *
     * @class
     * @constructor
     */
    function PwManual() {
        if (!(this instanceof PwManual)) {
            return new PwManual();
        }

        this.widget = '';

        /**
         * Pathway data.
         * This object should only contain the data to be sent on the save api endpoint.
         *
         * @type {Object}
         */
        this.pathway = {
            id: 0,
            type: 'manual',
            sortorder: 0,
            roles: [],
        };

        // Key to use in achievementPath events
        this.pwKey = '';

        this.rolesPicker = null;
        this.fullRoles = []; // Contains the full role data indexed by the id to assist with deletion of pathway.roles
        this.roleIds = []; // Contains the ids of the selected roles

        this.endpoints = {
            create: 'pathway_manual_create',
            update: 'pathway_manual_update',
            allroles: 'pathway_manual_get_roles',
        };

        this.filename = 'manual.js';
    }

    PwManual.prototype = {

        /**
         * Add event listeners for PwManual
         *
         */
        events: function() {
            var that = this;

            this.widget.addEventListener('click', function(e) {
                if (!e.target) {
                    return;
                }

                if (e.target.closest('[data-pw-manual-action]')) {
                    var action = e.target.closest('[data-pw-manual-action]').getAttribute('data-pw-manual-action');

                    if (action === 'addraters') {
                        that.pickRaters();
                    }
                }

                if (e.target.closest('[data-pw-item-remove]')) {
                    if (e.target.closest('[data-pw-item-id]')) {
                        var id = e.target.closest('[data-pw-item-id]').getAttribute('data-pw-item-id');

                        that.removeRole(id);
                    }
                }
            });
        },

        /**
         * Set parent
         *
         * @param {node} parent
         */
        setParent: function(parent) {
            this.widget = parent;
        },

        /**
         * Initialise the data
         *
         * @return {Promise}
         */
        initData: function() {
            var that = this,
                pwWgt = this.widget.closest('[data-tw-editAchievementPaths-pathway-key]'),
                pwKey = 0,
                pwId = 0;

            return new Promise(function(resolve, reject) {
                if (pwWgt) {
                    pwKey = pwWgt.getAttribute('data-tw-editAchievementPaths-pathway-key') ? pwWgt.getAttribute('data-tw-editAchievementPaths-pathway-key') : 0;
                    pwId = pwWgt.getAttribute('data-tw-editAchievementPaths-pathway-id') ? pwWgt.getAttribute('data-tw-editAchievementPaths-pathway-id') : 0;
                }

                that.pwKey = pwKey;

                if (pwId === 0) {
                    delete that.pathway.id;

                    // New pw - we need the competency id
                    // Get the competency ID from higher up in the DOM
                    var competencyIdNode = document.querySelector('[data-tw-editAchievementPaths-competency]'),
                        competencyId = 1;

                    if (competencyIdNode) {
                        competencyId = competencyIdNode.getAttribute('data-tw-editAchievementPaths-competency');
                    }

                    that.pathway.competency_id = competencyId;
                    that.widget.setAttribute('data-tw-editAchievementPaths-save-endPoint', that.endpoints.create);

                } else {
                    that.pathway.id = pwId;
                    that.widget.setAttribute('data-tw-editAchievementPaths-save-endPoint', that.endpoints.update);

                    that.getRoles();
                }

                that.showHideNoRaters();

                // Init the picker
                that.initRolesPicker().then(function() {
                    that.triggerEvent('update', {pathway: that.pathway});
                    resolve();
                }).catch(function(e) {
                    notification.exception({
                        fileName: that.filename,
                        message: e[0] + ' modal: ' + e[1],
                        name: 'Error initialising manual data'
                    });
                    reject();
                });
            });
        },

        /**
         * Retrieve the roles from the dom
         */
        getRoles: function() {
            var roleNodes = this.widget.querySelectorAll('.pw_item'),
                role;

            for (var a = 0; a < roleNodes.length; a++) {
                role = {};
                role.id = parseInt(roleNodes[a].getAttribute('data-pw-item-id') ? roleNodes[a].getAttribute('data-pw-item-id') : 0);
                role.value = roleNodes[a].getAttribute('data-pw-item-value') ? roleNodes[a].getAttribute('data-pw-item-value') : '';
                this.pathway.roles.push(role.value);
                this.roleIds.push(role.id);
                this.fullRoles[role.id] = role;
            }
        },

        /**
         * Initialise the roles picker
         *
         * @return {Promise}
         */
        initRolesPicker: function() {
            var that = this;

            return new Promise(function(resolve) {
                var pickerData = {
                    key: 'pwManualRolesPicker_' + that.pwKey,
                    title: [{
                        key: 'select_raters',
                        component: 'pathway_manual'
                    }],
                    list: {
                        map: {
                            cols: [{
                                dataPath: 'text',
                                headerString: {
                                    key: 'select_raters',
                                    component: 'pathway_manual',
                                },
                            }],
                        },
                        service: that.endpoints.allroles,
                    },
                    onSaved: function(picker, items, itemData) {
                        that.updateRoles(itemData);
                    },
                };

                ModalList.adder(pickerData).then(function(modal) {
                    that.rolesPicker = modal;
                    resolve(modal);
                }).catch(function(e) {
                    notification.exception({
                        fileName: that.filename,
                        message: e[0] + ' modal: ' + e[1],
                        name: 'Error loading modal list adder'
                    });
                });
            });
        },

        /**
         * Open the picker to add raters
         *
         */
        pickRaters: function() {
            var that = this;

            if (!this.rolesPicker) {
                this.initRolesPicker().then(function(modal) {
                    modal.show(that.roleIds);
                }).catch(function(e) {
                    notification.exception({
                        fileName: that.filename,
                        message: e[0] + ' modal: ' + e[1],
                        name: 'Error loading modal list adder'
                    });
                });
            } else {
                that.rolesPicker.show(that.roleIds);
            }
        },

        /**
         * @param {Array} roles
         */
        updateRoles: function(roles) {
            var that = this,
                target = this.widget.querySelector('[data-pw-roles]'),
                promiseArr = [];

            for (var a = 0; a < roles.length; a++) {
                var role = roles[a];

                if (this.roleIds.indexOf(role.id) < 0) {
                    this.pathway.roles.push(role.value);
                    this.roleIds.push(role.id);
                    this.fullRoles[role.id] = role;

                    promiseArr.push(templates.renderAppend('totara_competency/partial_item', role, target));
                }
            }

            // Hide the noraters warning
            that.showHideNoRaters();

            if (promiseArr.length > 0) {
                Promise.all(promiseArr).then(function() {
                    that.triggerEvent('update', {pathway: that.pathway});
                    that.triggerEvent('dirty', {});
                }).catch(function(e) {
                    e.fileName = that.filename;
                    e.name = 'Error showing updated roles';
                    notification.exception(e);
                });
            }
        },

        /**
         * @param {int} id
         */
        removeRole: function(id) {
            id = parseInt(id);

            var idIndex = this.roleIds.indexOf(id),
                roleIndex = -1,
                target;

            if (idIndex >= 0) {
                if (this.fullRoles[id]) {
                    roleIndex = this.pathway.roles.indexOf(this.fullRoles[id].value);
                    delete this.fullRoles[id];
                }

                if (roleIndex >= 0) {
                    this.pathway.roles.splice(roleIndex, 1);
                }

                this.roleIds.splice(idIndex, 1);

                target = this.widget.querySelector('.pw_item[data-pw-item-id="' + id + '"');
                if (target) {
                    target.remove();

                    this.triggerEvent('update', {pathway: this.pathway});
                    this.triggerEvent('dirty', {});
                }
            }

            // Show noraters warning
            this.showHideNoRaters();
        },

        /**
         * Show or hide the No Raters warning depending on the number of raters
         */
        showHideNoRaters: function() {
            var target = this.widget.querySelector('[data-pw-manual-error-no-raters]');
            if (this.roleIds.length == 0) {
                target.classList.remove('tw-editAchievementPaths--hidden');
            } else {
                target.classList.add('tw-editAchievementPaths--hidden');
            }
        },

        /**
         * Trigger event
         *
         * @param {string} eventName
         * @param {object} data
         */
        triggerEvent: function(eventName, data) {
            data.key = this.pwKey;

            var propagateEvent = new CustomEvent('totara_competency/pathway:' + eventName, {
                bubbles: true,
                detail: data
            });

            this.widget.dispatchEvent(propagateEvent);
        },
    };

    /**
     * Initialisation method
     *
     * @param {node} parent
     * @returns {Object} promise
     */
    var init = function(parent) {
        return new Promise(function(resolve) {
            var wgt = new PwManual();
            wgt.setParent(parent);
            wgt.events();
            resolve(wgt);

            M.util.js_pending('pathwayManual');
            wgt.initData().then(function() {
                M.util.js_complete('pathwayManual');
            }).catch(function() {
                // Failed
            });
        });
    };

    return {
        init: init
    };
});

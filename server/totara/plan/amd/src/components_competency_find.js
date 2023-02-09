/*
 * This file is part of Totara LMS
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
 * @author Alvin Smith <alvin.smith@totaralearning.com>
 * @package totara_plan
 */

define(['jquery', 'core/str', 'totara_plan/component', 'core/config', 'core/notification'], function($, str, component, cfg, notify) {
    /* global totaraDialog
        totaraDialogs
        comp_update_allowed
        totaraDialog_handler */

    return {
        /**
         * module initialisation method called by php js_call_amd()
         * @param {number} planId the plan ID
         * @param {number} page the page
         * @param {string} componentName the component_name
         */
        init: function(planId, page, componentName) {
            component.init({plan_id: planId, page: page, component_name: componentName});

            /*
            * Make a wrapper function to avoid the race condition between bundle.js and totaraDialog.
            * The dialoginits array executes callbacks in the order that they were added,
            * and component.init add it's callback before this callback (to handle the totaraDialog_handler_preRequisite).
            */
            const uniqueId = 'initialise_competency_find_dialog';
            var initialiseCompetencyFindDialog = function() {

                var TotaraDialogHandlerCompetency = function() {
                    this.baseUrl = '';
                    this.standardButtons = {};
                    this.continueButtons = {};
                    this.continuesaveButtons = {};
                };

                TotaraDialogHandlerCompetency.prototype = new component.totaraDialog_handler_preRequisite();

                TotaraDialogHandlerCompetency._open = function() {
                    // Check if user has allow permissions for updating compentencies
                    var buttons = comp_update_allowed ? this.continueButtons : this.standardButtons;

                    // Reset buttons
                    this._dialog.dialog.dialog('option', 'buttons', buttons);
                };

                /**
                * Load intermediate page for selecting courses
                * @param {string} url
                * @return {void}
                */
                TotaraDialogHandlerCompetency.prototype._continue = function(url) {
                    // Serialize data
                    const elements = $('.selected > div > span', this._container);
                    const selectedStr = this._get_ids(elements).join(',');
                    if (!selectedStr) {
                        str.get_string('error:nocompetency', 'totara_program').done(function(string) {
                            notify.alert('', string);
                        });
                        return;
                    }

                    // Load url in dialog
                    this._dialog._request(url + selectedStr, {object: this, method: '_continueRender'});
                };

                /**
                * Check result, if special string, redirect. Else, render;
                *
                * If rendering, update dialog buttons to be ok/cancel
                *
                * @param {object} response asyncRequest response
                * @return {boolean}
                */
                TotaraDialogHandlerCompetency.prototype._continueRender = function(response) {
                    // Check result
                    if (response.substring(0, 9) === 'NOCOURSES') {
                        // Generate url
                        const url = this.continueskipurl + response.substr(10);

                        // Send to server
                        this._dialog._request(url, {object: this, method: '_update'});

                        // Do not render
                        return false;
                    }

                    // Update buttons
                    this._dialog.dialog.dialog('option', 'buttons', this.continuesaveButtons);

                    // Render result
                    return true;
                };

                /**
                * Serialize linked courses and send to url,
                * update table with result
                *
                * @param {string} url URL to send dropped items to
                * @return {void}
                */
                totaraDialog_handler.prototype._continueSave = function(url) {
                    // Serialize form data
                    const suffix = $('form', TotaraDialogHandlerCompetency._container).serialize();
                    // Send to server
                    this._dialog._request(url + suffix, {object: this, method: '_update'});
                };

                const url = cfg.wwwroot + '/totara/plan/components/competency/';
                const continueurl = url + 'confirm.php?sesskey=' + cfg.sesskey + '&id=' + planId + '&update=';
                const saveurl = url + 'update.php?sesskey='+ cfg.sesskey + '&id=' + planId + '&update=';
                const continueskipurl = saveurl + 'id=' + planId + '&update=';
                const continuesaveurl = url + 'update.php?';

                const handler = new TotaraDialogHandlerCompetency();
                handler.baseurl = url;
                handler.continueskipurl = continueskipurl;

                const requiredStrs = [
                    {key: 'save', component: 'totara_core'},
                    {key: 'cancel', component: 'moodle'},
                    {key: 'continue', component: 'moodle'},
                    {key: 'addcompetencys', component: 'totara_plan'},
                ];

                str.get_strings(requiredStrs).done(function(strings) {
                    const save = strings[0];
                    const cancel = strings[1];
                    const continueStr = strings[2];

                    handler.standardButtons[save] = function() {
                        handler._save(saveurl);
                    };
                    handler.standardButtons[cancel] = function() {
                        handler._cancel();
                    };

                    handler.continueButtons[continueStr] = function() {
                        handler._continue(continueurl);
                    };
                    handler.continueButtons[cancel] = function() {
                        handler._cancel();
                    };

                    handler.continuesaveButtons[save] = function() {
                        handler._continueSave(continuesaveurl);
                    };
                    handler.continuesaveButtons[cancel] = function() {
                        handler._cancel();
                    };

                    totaraDialogs.evidence = new totaraDialog(
                        'assigncompetencies',
                        'show-competency-dialog',
                        {
                            buttons: comp_update_allowed ? handler.continueButtons : handler.standardButtons,
                            title: '<h2>' + strings[3] + '</h2>'
                        },
                        url + 'find.php?id=' + planId,
                        handler
                    );
                    M.util.js_complete(uniqueId);
                });
            };
            M.util.js_pending(uniqueId);
            if (window.dialogsInited) {
                initialiseCompetencyFindDialog();
            } else {
                window.dialoginits = window.dialoginits || [];
                window.dialoginits.push(initialiseCompetencyFindDialog);
            }

        }
    };
});
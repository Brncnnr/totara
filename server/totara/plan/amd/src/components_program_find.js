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

define(['core/config', 'core/str', 'totara_plan/component'], function(cfg, str, component) {
    return {
        /**
         * module initialisation method called by php js_call_amd()
         * @param {number} planId the plan ID
         * @param {number} page the page
         * @param {string} componentName the component_name
         */
        init: function(planId, page, componentName) {
            const url = cfg.wwwroot + '/totara/plan/components/program/';
            const saveurl = url + 'update.php?sesskey=' + cfg.sesskey + '&id=' + planId + '&update=';

            component.init({plan_id: planId, page: page, component_name: componentName});

            /*
            * Make a wrapper function to avoid the race condition between bundle.js and totaraDialog.
            * The dialoginits array executes callbacks in the order that they were added,
            * and component.init add it's callback before this callback (to handle the totaraDialog_handler_preRequisite).
            */
            const uniqueId = 'initialise_program_find_dialog';
            var initialiseProgramFindDialog = function() {

                const handler = new component.totaraDialog_handler_preRequisite();
                handler.baseurl = url;

                const buttonsObj = {};

                const requiredStrs = [
                    {key: 'save', component: 'totara_core'},
                    {key: 'cancel', component: 'moodle'},
                    {key: 'addprograms', component: 'totara_plan'},
                ];
                str.get_strings(requiredStrs).done(function(strings) {
                    buttonsObj[strings[0]] = function() {
                        handler._save(saveurl);
                    };
                    buttonsObj[strings[1]] = function() {
                        handler._cancel();
                    };
                    // eslint-disable-next-line no-undef
                    totaraDialogs.evidence = new totaraDialog(
                        'assignprograms',
                        'show-program-dialog',
                        {
                            buttons: buttonsObj,
                            title: '<h2>' + strings[2] + '</h2>'
                        },
                        url + 'find.php?id=' + planId,
                        handler
                    );
                    M.util.js_complete(uniqueId);
                });
            };

            M.util.js_pending(uniqueId);
            if (window.dialogsInited) {
                initialiseProgramFindDialog();
            } else {
                window.dialoginits = window.dialoginits || [];
                window.dialoginits.push(initialiseProgramFindDialog);
            }

        }
    };
});
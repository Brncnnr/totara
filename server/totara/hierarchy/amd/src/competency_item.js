/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Simon Coggins <simon.coggins@totaralms.com>
 * @author Eugene Venter <eugene@catalyst.net.nz>
 * @author Aaron Barnes <aaron.barnes@totaralms.com>
 * @author Dave Wallace <dave.wallace@kineo.co.nz>
 * @package totara_hierarchy
 */

define(['jquery', 'core/config', 'core/str'], function ($, config, strings) {

    /* global totaraDialog
              totaraDialogs
              totaraMultiSelectDialog
              totaraDialog_handler_treeview
              totaraDialog_handler_treeview_multiselect */

    var totaraDialog_handler_assignEvidence = function() {};
    var totaraDialog_handler_compEvidence = function() {};

    var loadItemDialogs = function(id, competencyuseresourcelevelevidence) {
        // Add related competency dialog.
        strings.get_string('assignrelatedcompetencies', 'totara_hierarchy').done(function (assignrelatedcompetencies) {
            var url = config.wwwroot+'/totara/hierarchy/prefix/competency/related/';
            totaraMultiSelectDialog(
                'related',
                assignrelatedcompetencies,
                url + 'find.php?sesskey=' + config.sesskey + '&id=' + id,
                url + 'save.php?sesskey=' + config.sesskey + '&id=' + id + '&deleteexisting=1&add='
            );
        });

        // Create handler for the dialog
        totaraDialog_handler_compEvidence.prototype = new totaraDialog_handler_treeview_multiselect();

        /**
         * Add a row to a table on the calling page
         * Also hides the dialog and any no item notice
         *
         * @param string    HTML response
         * @return void
         */
        totaraDialog_handler_compEvidence.prototype._update = function(response) {

            // Hide dialog
            this._dialog.hide();

            // Remove no item warning (if exists)
            $('.noitems-' + this._title).remove();

            //Split response into table and div
            var new_table = $(response).find('#list-evidence');

            // Grab table
            var table = $('#list-evidence');

            // If table found
            if (table.length) {
                table.replaceWith(new_table);
            }
            else {
                // Add new table
                $('div#evidence-list-container').append(new_table);
            }
        };

        var url = config.wwwroot + '/totara/hierarchy/prefix/competency/evidenceitem/';
        var saveurl = url + 'add.php?sesskey=' + config.sesskey + '&competency=' + id + '&type=coursecompletion&instance=0&deleteexisting=1&update=';
        var buttonsObj = {};
        var handler = new totaraDialog_handler_compEvidence();
        handler.baseurl = url;
        var requiredstrings = [];
        requiredstrings.push({key: 'save', component: 'totara_core'});
        requiredstrings.push({key: 'cancel', component: 'moodle'});
        requiredstrings.push({key: 'assigncoursecompletions', component: 'totara_hierarchy'});

        strings.get_strings(requiredstrings).done(function (translated) {
            var tstr = [];
            for (var i = 0; i < requiredstrings.length; i++) {
                tstr[requiredstrings[i].key] = translated[i];
            }
            buttonsObj[tstr.save] = function() { handler._save(saveurl);};
            buttonsObj[tstr.cancel] = function() { handler._cancel();};

            totaraDialogs.evidence = new totaraDialog(
                'evidence',
                'show-evidence-dialog',
                {
                     buttons: buttonsObj,
                     title: '<h2>' +  tstr.assigncoursecompletions + '</h2>'
                },
                url + 'edit.php?id=' + id,
                handler
            );
        });

    };

    return {
        /**
         * module initialisation method called by php js_call_amd()
         *
         * @param integer the id of the item that is required
         */
        item: function(id, competencyuseresourcelevelevidence) {
            var iteminited = $.Deferred();
            iteminited.done(function () {
                loadItemDialogs(id, competencyuseresourcelevelevidence);
            });


            if (window.dialogsInited) {
                iteminited.resolve();
            } else {
                // Queue it up.
                if (!$.isArray(window.dialoginits)) {
                    window.dialoginits = [];
                }
                window.dialoginits.push(iteminited.resolve);
            }

        },

        template: function (id) {
            var templateinited = $.Deferred();

            templateinited.done(function () {
                var url = config.wwwroot+'/totara/hierarchy/prefix/competency/template/';

                strings.get_string('assignnewcompetency', 'competency').done(function (assignnewcompetency) {
                    totaraMultiSelectDialog(
                        'assignment',
                        '<h2>' + assignnewcompetency + '</h2>',
                        url + 'find_competency.php?templateid=' + id,
                        url + 'save_competency.php?templateid=' + id + '&deleteexisting=1&add='
                    );
                });
            });

            if (window.dialogsInited) {
                templateinited.resolve();
            } else {
                // Queue it up.
                if (!$.isArray(window.dialoginits)) {
                    window.dialoginits = [];
                }
                window.dialoginits.push(templateinited.resolve);
            }
        }
    };
});

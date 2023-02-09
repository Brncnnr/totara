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
 * @author Maria Torres <maria.torres@totaralms.com>
 * @package totara
 * @subpackage reportbuilder
 */

/**
 * Javascript file containing JQuery bindings for show saved searches popup dialog box
 */

M.totara_reportbuilder_savedsearches = M.totara_reportbuilder_savedsearches || {

    Y: null,

    /**
     * module initialisation method called by php js_init_call()
     *
     * @param object    YUI instance
     * @param string    args supplied in JSON format
     */
    init: function(Y, args) {
        // Save a reference to the Y instance (all of its dependencies included).
        this.Y = Y;

        // Check jQuery dependency is available.
        if (typeof $ === 'undefined') {
            throw new Error('M.totara_reportbuilder_savevedsearch.init()-> jQuery dependency required for this module to function.');
        }

        var totaraDialog_saved_search_handler = function() {};

        totaraDialog_saved_search_handler.prototype = new totaraDialog_handler();

        totaraDialog_saved_search_handler.prototype.every_load = function() {
            // We are in confirmation dialog to delete a saved search.
            $('input[value="Continue"]', this._container).click(function() {
                var idsearch = $(this).siblings(":hidden[name=sid]").val();
                var action = $(this).siblings(":hidden[name=action]").val();
                // Deleting is confirmed, so remove the search from the drop-down list.
                if (action == 'delete') {
                    $('select[name=sid] option[value=' + idsearch + ']').remove();
                    // Ask for the elements of the select sid. If none. remove the view saved search option.
                    // And the manage button.
                    if ($('select[name=sid] option').length == 1) { // 1 because of the Choose option.
                        $('#totara_reportbuilder_viewsavedsearch').remove();
                    }
                }
            });

            // We are editing, then update the saved search.
            $('form').submit(function() {
                var form = this;
                var action = $(":hidden[name=action]", form).val();

                // Editing is confirmed, so update the search from the drop-down list.
                if (action == 'edit') {
                    var idsearch = $(":hidden[name=sid]", form).val();
                    var searchname = $("#id_name", form).val();
                    $('select[name=sid] option[value=' + idsearch + ']').text(searchname);
                }
            });
        }

        // The manage searches link.
        $('#totara_reportbuilder_manageseacheslink').on("click", function (e) {
            var path = M.cfg.wwwroot + '/totara/reportbuilder/';
            var handler = new totaraDialog_saved_search_handler();
            var name = 'searchlist';
            var id = e.target.getAttribute('data-id');

            var buttons = {};
            buttons[M.util.get_string('close', 'form')] = function() { handler._cancel() };

            totaraDialogs[name] = new totaraDialog(
                name,
                'totara_reportbuilder_manageseacheslink',
                {
                    buttons: buttons,
                    title: '<h2>' + M.util.get_string('managesavedsearches', 'totara_reportbuilder') + '</h2>'
                },
                path + 'savedsearches.php?id=' + id.toString(),
                handler
            );
        });

        // View a saved search.
        $('#id_sid').on("change", function() {
            if (this.value) {
                var url = $('#totara_reportbuilder_viewsavedsearch').attr("action");
                var breakchar = (url.match(/\?/)) ? '&' : '?';
                window.location.href = url + breakchar + 'sid=' + this.value;
            }
        });

        // Set saved search as default.
        var savedsearchdefault = $('#id_sdefault');
        savedsearchdefault.on("change", function() {
            require(['core/ajax', 'core/notification'], function(ajax, notification) {
                var request = {
                    methodname: 'totara_reportbuilder_set_default_search',
                    args: {
                        reportid: args['rb_reportid'],
                        sid: document.getElementById('id_sid').value,
                        setdefault: savedsearchdefault.is(':checked')
                    }
                };

                ajax.call([request])[0]
                    .done(function(result) {
                        if (result['warnings'].length != 0) {
                            notification.exception(result['warnings'][0]);
                        } else {
                            // Update saved search select showing default if set.
                            var savedsearches = result['savedsearches'];
                            for (var search in savedsearches) {
                                $('select[name=sid] option[value=' + savedsearches[search]['sid'] + ']').text(savedsearches[search]['name']);
                            }
                        }
                    })
                    .fail(notification.exception);
            });
        });
    }
}

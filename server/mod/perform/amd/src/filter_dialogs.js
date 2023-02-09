/**
 * This file is part of Totara Perform
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package totara_reportbuilder
 * @subpackage mod_perform
 */

/**
 * Javascript file containing JQuery bindings for dialog filters.
 */
/*globals totaraDialog_handler_treeview_multiselect_rb_filter,totaraDialogs,totaraDialog*/
define(['jquery', 'core/config', 'core/str'], function ($, mdlcfg) {

    /*globals totaraDialog_handler_treeview_multiselect_rb_filter,totaraDialogs,totaraDialog*/

    var handler = {

        // Holds items that need to be initialised.
        waitingitems: [],
        reportid: 0,

        /**
         * Module initialisation method called by php js_init_call().
         *
         * @param string    The filter to apply (hierarchy, badge, hierarchy_multi, cohort, category, course_multi)
         * @param {string} name The name of the filter. Optional, may be undefined.
         */
        init: function(filter, name, reportid) {
            handler.waitingitems.push({
                filter: filter,
                name: name
            });
            handler.reportid = reportid;

            if (window.dialogsInited) {
                this.rb_init_filter_dialogs();
            } else {
                // Queue it up.
                if (!$.isArray(window.dialoginits)) {
                    window.dialoginits = [];
                }

                // Only need need to add the function once as it goes through all current ones.
                if (this.waitingitems.length === 1) {
                    window.dialoginits.push(this.rb_init_filter_dialogs);
                }
            }
        },

        rb_init_filter_dialogs: function() {

            // Copy the waiting items to a holding array, and empty the waiting items array.
            // This was we know exactly what we need to initialise here.
            var waitingitems = $.extend(true, [], handler.waitingitems);
            handler.waitingitems = [];

            $.each(waitingitems, function () {
                switch (this.filter) {
                    case "element-type":
                        handler.rb_load_element_type_filters(this);
                        break;
                    case "additional-linked_review_content_type":
                        handler.rb_load_review_type_filters(this);
                        break;
                    case "participant_instance-relationship_id":
                        handler.rb_load_participant_instance_relationship_id_filters(this);
                        break;
                    case "section-id":
                        handler.rb_load_section_title_filters(this);
                        break;
                }
            });

            // Activate the 'delete' option next to any selected items in filters (for this dialog only)
            $(document).on('click', '.multiselect-selected-item a', function(event) {
                event.preventDefault();

                var container = $(this).parents('div.multiselect-selected-item');
                var filtername = container.data('filtername');
                var id = container.data('id');
                var hiddenfield = $('input[name='+filtername+']');

                // Take this element's ID out of the hidden form field.
                var ids = hiddenfield.val();
                var id_array = ids.split(',');
                var new_id_array = $.grep(id_array, function(n) { return n != id; });
                var new_ids = new_id_array.join(',');
                hiddenfield.val(new_ids);
                // Remove this element from the DOM.
                container.remove();

            });
        },

        rb_load_element_type_filters: function(obj) {

            handler.rb_disable_dialog(obj.filter);

            $('input.rb-filter-choose-'+obj.filter).each(function() {
                var id = $(this).attr('id');
                // Remove 'show-' and '-dialog' from ID.
                id = id.substr(5, id.length - 12);

                var url = mdlcfg.wwwroot + '/mod/perform/reporting/performance/filters/';

                handler.performMultiSelectDialogRbFilter(
                    id,
                    M.util.get_string('choose_'+obj.filter.replace('-', '_')+'_plural', 'mod_perform'),
                    url + 'element_type.php?action=find&sesskey=' + mdlcfg.sesskey,
                    url + 'element_type.php?action=save&filtername=' + id + '&sesskey=' + mdlcfg.sesskey +'&ids='
                );

                // Disable popup buttons if first pulldown is set to 'any value'.
                if ($('select[name='+id+'_op]').val() === '0') {
                    $('#show-'+id+'-dialog').prop('disabled',true);
                }
            });
        },

        rb_load_review_type_filters: function(obj) {

            handler.rb_disable_dialog(obj.filter);

            $('input.rb-filter-choose-review-type').each(function() {
                var id = $(this).attr('id');
                // Remove 'show-' and '-dialog' from ID.
                id = id.substr(5, id.length - 12);

                var url = mdlcfg.wwwroot + '/mod/perform/reporting/performance/filters/';

                handler.performMultiSelectDialogRbFilter(
                    id,
                    M.util.get_string('choose_review_type_plural', 'performelement_linked_review'),
                    url + 'review_type.php?action=find&sesskey=' + mdlcfg.sesskey,
                    url + 'review_type.php?action=save&filtername=' + id + '&sesskey=' + mdlcfg.sesskey +'&ids='
                );

                // Disable popup buttons if first pulldown is set to 'any value'.
                if ($('select[name='+id+'_op]').val() === '0') {
                    $('#show-'+id+'-dialog').prop('disabled',true);
                }
            });
        },

        rb_load_participant_instance_relationship_id_filters: function(obj) {

            handler.rb_disable_dialog(obj.filter);

            $('input.rb-filter-choose-relationship-name').each(function() {
                var id = $(this).attr('id');
                // Remove 'show-' and '-dialog' from ID.
                id = id.substr(5, id.length - 12);

                var url = mdlcfg.wwwroot + '/mod/perform/reporting/performance/filters/';

                handler.performMultiSelectDialogRbFilter(
                    id,
                    M.util.get_string('choose_relationship_name_plural', 'mod_perform'),
                    url + 'relationship_name.php?action=find&sesskey=' + mdlcfg.sesskey,
                    url + 'relationship_name.php?action=save&filtername=' + id + '&sesskey=' + mdlcfg.sesskey +'&ids='
                );

                // Disable popup buttons if first pulldown is set to 'any value'.
                if ($('select[name='+id+'_op]').val() === '0') {
                    $('#show-'+id+'-dialog').prop('disabled',true);
                }
            });
        },

        rb_load_section_title_filters: function(obj) {
            if ($('input[name=activity_id]').length) {
                handler.rb_disable_dialog(obj.filter);

                $('input.rb-filter-choose-' + obj.filter).each(function () {
                    var id = $(this).attr('id');
                    // Remove 'show-' and '-dialog' from ID.
                    id = id.substr(5, id.length - 12);

                    var activity_id = $('input[name=activity_id]').val();

                    var url = mdlcfg.wwwroot +
                        '/mod/perform/reporting/performance/filters/section_title.php?' +
                        'sesskey=' + mdlcfg.sesskey +
                        '&activity_id=' + activity_id +
                        '&action=';

                    handler.performMultiSelectDialogRbFilter(
                        id,
                        M.util.get_string('choose_' + obj.filter.replace('-', '_') + '_plural', 'mod_perform'),
                        url + 'find',
                        url + 'save&filtername=' + id + '&ids='
                    );

                    // Disable popup buttons if first pulldown is set to 'any value'.
                    if ($('select[name=' + id + '_op]').val() === '0') {
                        $('#show-' + id + '-dialog').prop('disabled', true);
                    }
                });
            } else {
                $('#id_section-id_op').prop('disabled', true);
                $('#show-section-id-dialog').prop('disabled', true);
                $('#fgroup_id_section-id_grp').hide();
            }
        },

        rb_disable_dialog: function(filter) {
            $(document).on('change', '#id_'+filter+'_op', function(event) {
                event.preventDefault();
                var name = $(this).attr('name');
                name = name.substr(0, name.length - 3);// Remove _op.
                if ($(this).val() === '0') {
                    $('#show-'+name+'-dialog').addClass("disabled");
                    $('#show-'+name+'-dialog').prop('disabled', true);
                    $('#show-'+name+'-dialog').removeAttr('href');
                    $('*[data-filtername="' + name + '"] a').addClass("disabled");
                    $('*[data-filtername="' + name + '"] a').prop('disabled', true);
                    $('*[data-filtername="' + name + '"] a').removeAttr('href');
                } else {
                    $('#show-'+name+'-dialog').removeClass("disabled");
                    $('#show-'+name+'-dialog').prop('disabled', false);
                    $('#show-'+name+'-dialog').attr('href', '#');
                    $('*[data-filtername="' + name + '"] a').removeClass("disabled");
                    $('*[data-filtername="' + name + '"] a').prop('disabled', false);
                    $('*[data-filtername="' + name + '"] a').attr('href', '#');
                }
            });
        },

        performMultiSelectDialogRbFilter: function(id, title, find_url, save_url) {

            var handler = new totaraDialog_handler_treeview_multiselect_rb_filter();
            // Overwrite _get_ids parent function.
            handler._get_ids = function(elements) {
                var ids = [];
                // Loop through elements
                elements.each(function() {
                    var id = $(this).attr('id').slice(5);// remove 'item_' from our id
                    // Append to list
                    ids.push(id);
                });
                return ids;
            };
            // Overwrite _update parent function.
            handler._update = function(response) {
                var id = this._title;
                // update the hidden field
                var hiddenfield = $('input[name='+id+']');
                var ids = hiddenfield.val();
                var id_array = (ids) ? ids.split(',') : [];
                // pull out selected IDs from selected column
                $('#'+id+' .selected .clickable').each(function(){
                    id_array.push($(this).attr('id').slice(5));
                });
                var combined_ids = id_array.join(',');
                hiddenfield.val(combined_ids);

                // Hide dialog
                this._dialog.hide();

                // Sometimes we want to have two dialogs changing the same table,
                // so here we support tagging tables by id, or class
                var content = $('div.list-'+this._title);

                // Replace div with updated data
                content.replaceWith(response);

                // Hide noscript objects
                $('.totara-noscript', $('div.list-'+this._title)).hide();
            };

            var limitfield = 'input[name=' + id + '_selection_limit]';
            if ($(limitfield)) {
                handler.set_selection_limit($(limitfield).val());
            }

            var buttonObj = {};
            buttonObj[M.util.get_string('save', 'totara_core')] = function() { handler._save(save_url); };
            buttonObj[M.util.get_string('cancel', 'moodle')] = function() { handler._cancel(); };

            totaraDialogs[id] = new totaraDialog(
                id,
                'show-'+id+'-dialog',
                {
                    buttons: buttonObj,
                    title: '<h2>' + title + '</h2>'
                },
                find_url,
                handler
            );
        }
    };

    return handler;
});

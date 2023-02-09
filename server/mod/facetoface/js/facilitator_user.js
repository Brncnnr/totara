/*
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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_facetoface
 */

/* global $, totaraSingleSelectDialog */

M.totara_seminar_facilitator = M.totara_seminar_facilitator || {

    Y: null,
    config: {},

    /**
     * module initialisation method called by php js_init_call()
     * @param object YUI instance
     * @param array args
     */
    init: function(Y, args) {
        this.Y = Y;
        this.config = args;
        // Check jQuery dependency is available
        if (typeof $ === 'undefined') {
            throw new Error('M.totara_seminar_facilitator.init()-> jQuery dependency required for this module to function.');
        }

        $('#id_submitbutton').on('click', function (e) {
            var div = $('div#fgroup_id_labeltype > fieldset > div.felement');
            div.removeClass('error');
            $("#id_error_userid").remove();
            $("#id_error_break_userid").remove();
            var option = $('select[name="facilitatortype"] option:selected');
            if (option.val() == '0') {
                var val = $('input[name="userid"]').val();
                if (val == '0' || val == '') {
                    $('div#fgroup_id_labeltype > fieldset > div.felement')
                        .addClass('error')
                        .prepend(M.totara_seminar_facilitator.config.errorblock);
                    $('html,body').scrollTop($("#id_error_userid").offset().top);
                    e.preventDefault();
                    return false;
                }
            }
        });

        $('select[name="facilitatortype"]').change(M.totara_seminar_facilitator.checkuseraddvisibility);

        var show_warning = function () {
            if (Number($('input[name="id"]').val()) > 0 && M.totara_seminar_facilitator.config.userid != '0') {
                if (M.totara_seminar_facilitator.config.userid != $('input[name="userid"]').val()) {
                    var notificationHolder = $('#user-notifications');
                    if (!notificationHolder) {
                        return;
                    }
                    notificationHolder.append(M.totara_seminar_facilitator.config.warningblock);
                }
            }
            var div = $('div#fgroup_id_labeltype > fieldset > div.felement');
            div.removeClass('error');
            $(M.totara_seminar_facilitator.config.errorblock).detach();
        };

        (function () {
            var url = M.cfg.wwwroot + '/mod/facetoface/facilitator/ajax/users.php?userid=';
            totaraSingleSelectDialog(
                'facilitator',
                M.util.get_string('choosefacilitator', 'mod_facetoface') + M.totara_seminar_facilitator.config.dialog_display_facilitator,
                url + M.totara_seminar_facilitator.config.userid,
                'userid',
                'facilitatortitle',
                show_warning,
                true // this user allows to remove a facilitator from the list
            );
        })();
    },

    checkuseraddvisibility: function () {
        var facilitatortype = M.totara_seminar_facilitator.config.facilitatortype;
        var option = $('select[name="facilitatortype"] option:selected');
        if (option.val() != facilitatortype.internal) {
            $('input[name="userid"]').val('0');
            $('span#facilitatortitle').html('');
        }
        var div = $('div#fgroup_id_labeltype > fieldset > div.felement');
        div.removeClass('error');
        $("#id_error_userid").detach();
        $("#id_error_break_userid").detach();
    }
};


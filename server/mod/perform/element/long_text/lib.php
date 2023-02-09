<?php
/**
 * This file is part of Totara Learn
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package performelement_long_text
 */

use mod_perform\entity\activity\element_response;
use mod_perform\models\activity\helpers\external_participant_token_validator;
use mod_perform\models\response\section_element_response;
use performelement_long_text\long_text;
use totara_core\advanced_feature;

/**
 * This is a callback from the file system. Use for serving the file to the user.
 * @see file_pluginfile
 *
 * @param stdClass  $course        Unused
 * @param stdClass  $cm            Unused
 * @param context   $context
 * @param string    $filearea
 * @param array     $args
 * @param bool      $forcedownload
 * @param array     $options
 *
 * @return void
 */
function performelement_long_text_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, $options) {
    global $CFG;
    require_once($CFG->dirroot . '/lib/filelib.php');

    $component = long_text::get_response_files_component_name();
    $response_id = $args[0];

    if ($context->contextlevel != CONTEXT_MODULE) {
        debugging('Long text file responses must always be in the context of their activity.', DEBUG_DEVELOPER);
        send_file_not_found();
    }

    if ($filearea !== long_text::get_response_files_filearea_name()) {
        send_file_not_found();
    }

    if (advanced_feature::is_disabled('performance_activities')) {
        send_file_not_found();
    }

    /** @var element_response $response */
    $response = element_response::repository()->find($response_id);
    if (!$response) {
        send_file_not_found();
    }

    $external_participant_token = external_participant_token_validator::find_token_in_session();
    if ($external_participant_token) {
        // Handle external participant authorisation
        $token_validator = new external_participant_token_validator($external_participant_token);
        if (!$token_validator->is_valid_for_response($response)) {
            send_file_not_found();
        }
    } else {
        // Handle internal user authorisation
        require_login();

        if ($context->is_user_access_prevented()) {
            send_file_not_found();
        }

        if (!section_element_response::can_user_view_response($response)) {
            send_file_not_found();
        }
    }

    // The user is allowed to see the file, so return it.
    $fs = get_file_storage();
    $file_path = "/{$context->id}/{$component}/{$filearea}/{$args[0]}/{$args[1]}";
    $file = $fs->get_file_by_hash(sha1($file_path));

    if (!$file || $file->is_directory()) {
        send_file_not_found();
    }

    send_stored_file($file, DAYSECS, 0, true, $options);
}

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
 * @author Johannes Cilliers <simon.coggins@totaralearning.com>
 * @package performelement_static_content
 */

use mod_perform\models\activity\helpers\external_participant_token_validator;
use totara_core\advanced_feature;

/**
 * This is a callback from the file system. Use for serving the file to the user.
 * @see file_pluginfile
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param context  $context
 * @param string    $filearea
 * @param array     $args
 * @param bool      $forcedownload
 * @param array     $options
 *
 * @return void
 */
function performelement_static_content_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, $options) {
    global $CFG, $DB;
    require_once("{$CFG->dirroot}/lib/filelib.php");

    // Whitelisted file areas.
    if (!in_array($filearea, ['content'])) {
        send_file_not_found();
    }

    if (advanced_feature::is_disabled('performance_activities')) {
        send_file_not_found();
    }

    // Handle external participant.
    $token = external_participant_token_validator::find_token_in_session();
    if ($token) {
        $validator = new external_participant_token_validator($token);
        if (!$validator->is_valid()) {
            send_file_not_found();
        }
        $token_context = $validator->get_participant_instance()->get_context();
        if ($token_context->id !== $context->id) {
            send_file_not_found();
        }
    } else {
        require_login();

        // Check multi-tenancy.
        if ($context->is_user_access_prevented()) {
            send_file_not_found();
        }
    }

    if ($context->contextlevel == CONTEXT_MODULE) {
        $element = [
            'context_id' => $context->id,
            'plugin_name' => 'static_content',
        ];
        if (!$DB->record_exists('perform_element', $element)) {
            // somebody tries to gain illegal access!
            send_file_not_found();
        }
    }

    $relativepath = implode("/", $args);
    $fullpath = "/{$context->id}/performelement_static_content/{$filearea}/{$relativepath}";

    $fs = get_file_storage();
    $file = $fs->get_file_by_hash(sha1($fullpath));

    if (!$file) {
        send_file_not_found();
    }

    send_stored_file($file, 360, 0, $forcedownload, $options);
}
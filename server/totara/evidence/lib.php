<?php
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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_evidence
 */

use core_user\output\myprofile;
use totara_core\advanced_feature;
use totara_evidence\models\evidence_type;
use totara_evidence\models\helpers\evidence_item_capability_helper;

/**
 * File serving code for the totara evidence plugin.
 *
 * @param object $course course object
 * @param cm_info $cm course module object
 * @param context $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return false|void false if file not found, does not return if found - just send the file
 */
function totara_evidence_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    if ($filearea !== evidence_type::DESCRIPTION_FILEAREA) {
        send_file_not_found();
    }

    if (advanced_feature::is_disabled('evidence')) {
        send_file_not_found();
    }

    // Description for a type is needed when viewing or creating an evidence item,
    // so we don't require any additional capabilities other than being logged in.
    require_login();

    $file_storage = get_file_storage();
    $file_path = "/{$context->id}/totara_evidence/{$filearea}/{$args[0]}/{$args[1]}";
    $file = $file_storage->get_file_by_hash(sha1($file_path));

    if (!$file || $file->is_directory()) {
        send_file_not_found();
    }

    send_stored_file($file, 86400, 0, true, $options);
}

/**
 * Add evidence to myprofile page.
 *
 * @param myprofile\tree $tree Tree object
 * @param object $user user object
 * @param bool $iscurrentuser
 * @param object $course Course object
 *
 * @return bool
 */
function totara_evidence_myprofile_navigation(myprofile\tree $tree, $user, $iscurrentuser, $course) {
    if (advanced_feature::is_disabled('evidence') || isguestuser($user)) {
        return false;
    }

    if (!evidence_item_capability_helper::for_user($user->id)->can_view_list()) {
        return false;
    }

    $tree->add_node(
        new myprofile\node(
            'mylearning',
            'evidence',
            get_string('evidence_bank', 'totara_evidence'),
            null,
            new moodle_url('/totara/evidence/index.php', $iscurrentuser ? []: ['user_id' => $user->id])
        )
    );

    return true;
}

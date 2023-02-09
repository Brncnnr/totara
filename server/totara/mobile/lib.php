<?php
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_mobile
 */

/**
 * Add mobile related nodes to myprofile page.
 *
 * @param core_user\output\myprofile\tree $tree Tree object
 * @param stdClass $user user object
 * @param bool $iscurrentuser
 * @param stdClass $course Course object
 */
function totara_mobile_myprofile_navigation(core_user\output\myprofile\tree $tree, $user, $iscurrentuser, $course) {
    global $CFG;

    if (!$iscurrentuser) {
        return;
    }

    if ($course and $course->id != SITEID) {
        return;
    }

    if (!get_config('totara_mobile', 'enable')) {
        return;
    }

    $url = new moodle_url('/totara/mobile/index.php');
    $node = new core_user\output\myprofile\node('administration', 'managemobile', get_string('managedevices', 'totara_mobile'), null, $url);
    $tree->add_node($node);
}

/**
 * To download the file we upload in totara_mobile filearea
 *
 * @param $course
 * @param $cm
 * @param $context
 * @param $filearea
 * @param $args
 * @param $forcedownload
 * @param array $options
 * @return void Download the file
 */
function totara_mobile_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, $options=array()) {
    $component = 'totara_mobile';
    $itemid = $args[0];
    $filename = $args[1];
    $fs = get_file_storage();

    $file = $fs->get_file($context->id, $component, $filearea, $itemid, '/', $filename);

    if (empty($file)) {
        send_file_not_found();
    }

    send_stored_file($file, DAYSECS, 0, false, $options); // Enable long cache and disable forcedownload.
}

<?php
/**
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @author Andrew McGhie <andrew.mcghie@totaralearning.com>
 * @package block_totara_featured_links
 */

defined('MOODLE_INTERNAL') || die();


require_once($CFG->dirroot.'/totara/cohort/lib.php');

/**
 * Gets a file so that it can be show to the user
 * @param int $course
 * @param \stdClass $birecord_or_cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return bool
 */
function block_totara_featured_links_pluginfile($course, $birecord_or_cm, context $context, $filearea, $args, $forcedownload, array $options= []) {
    global $DB, $CFG, $USER;

    $totara_featured_links = block_instance('totara_featured_links', $birecord_or_cm);
    if (!$totara_featured_links || !$totara_featured_links->user_can_view()) {
        send_file_not_found();
    }

    if ($context->contextlevel != CONTEXT_BLOCK) {
        send_file_not_found();
    }

    $blockinstance = $DB->get_record('block_instances', ['id' => $context->instanceid]);
    if (!$blockinstance) {
        send_file_not_found();
    }

    // Get parent context and see if user have proper permission.
    $parentcontext = $context->get_parent_context();
    if (!$parentcontext) {
        send_file_not_found();
    }

    // If block is in course context, then check if user has capability to access course.
    if ($context->get_course_context(false)) {
        require_course_login($course);
    } else if ($CFG->forcelogin) {
        require_login();
    }

    if ($context->is_user_access_prevented()) {
        send_file_not_found();
    }

    // NOTE: temporary fix for TL-21682
    if ($parentcontext->contextlevel == CONTEXT_COURSECAT) {
        // Check if category is visible and user can view this category.
        $category = coursecat::get($parentcontext->instanceid);
        if (!$category->is_uservisible()) {
            send_file_not_found();
        }
    } else if ($parentcontext->contextlevel == CONTEXT_USER) {
        if ($parentcontext->instanceid != $USER->id) {
            if ($blockinstance->pagetypepattern !== 'user-profile') {
                // There is only one page that can be viewed by other users where users can customise blocks,
                // it is their public profile page.
                send_file_not_found();
            }
            if (!user_can_view_profile($parentcontext->instanceid)) {
                send_file_not_found();
            }
        }
    }
    // At this point there is no way to check SYSTEM context, so ignoring it.

    $fs = get_file_storage();

    $fileid = $args[0];
    $filename = $args[1];
    $tile_instance = block_totara_featured_links\tile\base::get_tile_instance($fileid);
    if (!$tile_instance->can_edit_tile() && !$tile_instance->is_visible()) {
        send_file_not_found();
    }

    $fullpath = "/{$context->id}/block_totara_featured_links/{$filearea}/{$fileid}/{$filename}";
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        send_file_not_found();
    }
    // Finally send the file.
    send_stored_file($file, null, 0, true, $options); // Download MUST be forced - security!
    return true;
}
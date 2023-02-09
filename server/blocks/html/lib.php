<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die();

/**
 * Form for editing HTML block instances.
 *
 * @copyright 2010 Petr Skoda (http://skodak.org)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package   block_html
 * @category  files
 * @param stdClass $course course object
 * @param stdClass $birecord_or_cm block instance record
 * @param context $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool
 * @todo MDL-36050 improve capability check on stick blocks, so we can check user capability before sending images.
 */
function block_html_pluginfile($course, $birecord_or_cm, context $context, $filearea, $args, $forcedownload, array $options=array()) {
    global $DB, $CFG, $USER;

    $block_html = block_instance('html', $birecord_or_cm);
    if (!$block_html || !$block_html->user_can_view()) {
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
    $forcedownload = false;
    if ($parentcontext->contextlevel == CONTEXT_COURSECAT) {
        // Check if category is visible and user can view this category.
        $category = coursecat::get($parentcontext->instanceid);
        if (!$category->is_uservisible()) {
            send_file_not_found();
        }
    } else if ($parentcontext->contextlevel == CONTEXT_USER) {
        // force download on all personal pages including /my/
        // because we do not have reliable way to find out from where this is used
        $forcedownload = true;
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

    $filename = array_pop($args);
    $filepath = $args ? '/'.implode('/', $args).'/' : '/';

    if (!$file = $fs->get_file($context->id, 'block_html', 'content', 0, $filepath, $filename) or $file->is_directory()) {
        send_file_not_found();
    }

    // NOTE: it would be nice to have file revisions here, for now rely on standard file lifetime,
    //       do not lower it because the files are displayed very often.
    \core\session\manager::write_close();
    send_stored_file($file, null, 0, $forcedownload, $options);
}

/**
 * Perform global search replace such as when migrating site to new URL.
 * @param  $search
 * @param  $replace
 * @return void
 */
function block_html_global_db_replace($search, $replace) {
    global $DB;

    $instances = $DB->get_recordset('block_instances', array('blockname' => 'html'));
    foreach ($instances as $instance) {
        // TODO: intentionally hardcoded until MDL-26800 is fixed
        $config = unserialize_object(base64_decode($instance->configdata));
        if (isset($config->text) and is_string($config->text)) {
            $config->text = str_replace($search, $replace, $config->text);
            $DB->update_record('block_instances', ['id' => $instance->id,
                    'configdata' => base64_encode(serialize($config)), 'timemodified' => time()]);
        }
    }
    $instances->close();
}

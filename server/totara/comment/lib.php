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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_comment
 */
defined('MOODLE_INTERNAL') || die();

use totara_comment\comment;

/**
 * This is a callback from the file system. Use for serving the file to user.
 *
 * @param stdClass  $course
 * @param stdClass  $cm
 * @param context   $context
 * @param string    $file_area
 * @param array     $args
 * @param bool      $force_download
 * @param array     $options
 */
function totara_comment_pluginfile(
    ?stdClass $course,
    ?stdClass $cm,
    context $context,
    string $file_area,
    ?array $args,
    bool $force_download,
    ?array $options
): void {
    global $CFG;
    require_once("{$CFG->dirroot}/lib/filelib.php");

    if (!in_array($file_area, [comment::REPLY_AREA, comment::COMMENT_AREA])) {
        return;
    }

    // Serving default image of a workspace.
    $relative_path = implode("/", $args);
    $full_path = "/{$context->id}/totara_comment/{$file_area}/{$relative_path}";

    $fs = get_file_storage();
    $file = $fs->get_file_by_hash(sha1($full_path));

    if ($file) {
        send_stored_file($file, 360, 0, $force_download, $options);
    }
}
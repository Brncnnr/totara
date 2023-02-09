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
 * @package engage_article
 */

use engage_article\totara_engage\resource\article;
use totara_engage\access\access_manager;

defined('MOODLE_INTERNAL') || die();

/**
 * This is a callback from the file system. Use for serving the file to the user.
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
function engage_article_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, $options) {
    global $CFG, $USER;
    require_once("{$CFG->dirroot}/lib/filelib.php");

    if (!in_array($filearea, ['content', 'image'])) {
         // Invalid file area.
        return;
    }

    if (empty($CFG->publishgridcatalogimage) || !in_array($filearea, ['image']) || empty($options['preview']) || $options['preview'] !== 'totara_catalog_medium') {
        //check just login as engage does not support guests
        if (!isloggedin()) {
            send_file_not_found();
        }

        /** @var article $article */
        $article = article::from_resource_id((int)$args[0]);
        if (!access_manager::can_access($article, $USER->id)) {
            send_file_not_found();
        }
    }

    $relativepath = implode("/", $args);
    $fullpath = "/{$context->id}/engage_article/{$filearea}/{$relativepath}";

    $fs = get_file_storage();
    $file = $fs->get_file_by_hash(sha1($fullpath));

    if (!$file) {
        return;
    }

    send_stored_file($file, 360, 0, $forcedownload, $options);
}
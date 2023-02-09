<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2010-2019 Totara Learning Solutions Ltd
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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package totara_core
 */
namespace totara_core\upload;

use context_system;
use context_user;
use repository;
use moodle_url;

global $CFG;
require_once($CFG->dirroot . '/repository/lib.php');

/**
 * Facilitate drag-n-drop and select files upload for vue.js components.
 * It doesn't use repositories and only suitable for (re-)uploading files
 */
class upload {
    /**
     * Returns new draftId required for file upload
     * @return int
     */
    public static function get_draft_id() : int {
        global $CFG;
        include_once($CFG->dirroot . '/lib/filelib.php');

        return file_get_unused_draft_itemid();
    }

    /**
     * Move files from drafts into their permanent location
     * @param int $draftid
     * @param int $contextid
     * @param string $component
     * @param string $filearea
     * @param int $itemid
     */
    public static function save_draft(int $draftid, int $contextid, string $component, string $filearea, int $itemid) {
        global $CFG;
        include_once($CFG->dirroot . '/lib/filelib.php');
        file_merge_files_from_draft_area_into_filearea($draftid, $contextid, $component, $filearea, $itemid);
        static::clean_draft($draftid);
    }

    /**
     * Remove all files from specific draft area
     * @param int $draftid
     */
    private static function clean_draft(int $draftid) {
        global $USER;
        $context = context_user::instance($USER->id, MUST_EXIST);

        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'user', 'draft', $draftid);
    }

    /**
     * Remove file from the draft area so it won't be saved later
     * This function works in the same way as file_storage::delete_area_files() but with more conditions
     * @param int $draftid
     * @param string $filename
     * @param int $userid
     */
    public static function delete_draft_file(int $draftid, string $filename, int $userid = 0) {
        global $DB, $USER;

        $userid = $userid ?: $USER->id;
        $fs = get_file_storage();
        $context = context_user::instance($userid, MUST_EXIST);

        $conditions = [
            'contextid' => $context->id,
            'component' => 'user',
            'filearea'  => 'draft',
            'itemid'    => $draftid,
            'filename'  => $filename
        ];

        $filerecords = $DB->get_records('files', $conditions);
        foreach ($filerecords as $filerecord) {
            $fs->get_file_instance($filerecord)->delete();
        }
    }

    /**
     * Get required params for vue upload component
     * @param int $userid User ID
     * @param string $uploadurl Custom URL for upload backend script
     * @return array
     */
    public static function get_vue_params(int $userid = 0, string $uploadurl = "") {
        global $USER;

        $userid = $userid ?: $USER->id;
        $repository = current(repository::get_instances([
            'currentcontext' => context_system::instance(),
            'type' => 'upload',
            'userid' => $userid
        ]));


        $uploadurl = $uploadurl ?: new moodle_url('/repository/repository_ajax.php', ['action' => 'upload']);
        return [
            'repository-id' => (int)$repository->id,
            'item-id' => self::get_draft_id(),
            'href' => $uploadurl->out()
        ];
    }
}
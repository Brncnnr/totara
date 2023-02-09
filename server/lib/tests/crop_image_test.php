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
 * @package core
 */
defined('MOODLE_INTERNAL') || die();

use core\image\preview_helper;

class core_crop_image_testcase extends advanced_testcase {
    /**
     * @return stored_file
     */
    private function create_stored_file(): stored_file {
        global $CFG, $USER;
        require_once("{$CFG->dirroot}/lib/filelib.php");

        $fs = get_file_storage();
        $context = context_user::instance($USER->id);

        $record = new stdClass();
        $record->contextid = $context->id;
        $record->itemid = 42;
        $record->filepath = '/';
        $record->filename = 'me.png';
        $record->component = 'user';
        $record->filearea = 'draft';
        $record->mimetype = 'image/png';

        $file_content = file_get_contents("{$CFG->dirroot}/lib/tests/fixtures/image_test.png");

        return $fs->create_file_from_string($record, $file_content);
    }

    /**
     * @return void
     */
    public function test_create_preview_image(): void {
        global $CFG;
        require_once("{$CFG->dirroot}/lib/filelib.php");

        $this->setAdminUser();
        $stored_file = $this->create_stored_file();

        $helper = preview_helper::instance();
        $preview_file = $helper->get_file_preview($stored_file, 'tinyicon');

        $this->assertGreaterThan(
            $preview_file->get_filesize(),
            $stored_file->get_filesize()
        );
    }

    /**
     * @return void
     */
    public function test_create_preview_file_with_invalid_preview_option(): void {
        global $CFG;
        require_once("{$CFG->dirroot}/lib/filelib.php");

        $this->setAdminUser();

        $stored_file = $this->create_stored_file();
        $this->expectException(file_exception::class);

        $helper = preview_helper::instance();
        $helper->get_file_preview($stored_file, 'some_random_option');
    }

    /**
     * First creating a file, then create preview file. After all the creation, delete the original
     * file, and check that if the preview file(s) are going to be deleted as well.
     * @return void
     */
    public function test_delete_original_file_will_delete_preview_files(): void {
        global $CFG, $DB, $USER;
        require_once("{$CFG->dirroot}/lib/filelib.php");

        $this->setAdminUser();
        $stored_file = $this->create_stored_file();

        $helper = preview_helper::instance();
        $preview_file = $helper->get_file_preview($stored_file, 'thumb');

        $fs = get_file_storage();

        $context = context_user::instance($USER->id);
        $fs->delete_area_files($context->id, 'user', 'draft', $stored_file->get_itemid());

        $params = [
            'id' => $preview_file->get_id(),
        ];

        // Need to run cron to actually delete the files.
        ob_start();
        $fs->cron();
        ob_end_clean();

        $result = $DB->record_exists('files', $params);
        $this->assertFalse($result);
    }
}
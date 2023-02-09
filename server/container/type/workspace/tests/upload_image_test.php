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
 * @package container_workspace
 */
defined('MOODLE_INTERNAL') || die();

use container_workspace\local\workspace_helper;
use container_workspace\workspace;

/**
 * Test uploading image for workspace to always pick the last image of the list of draft files.
 */
class container_workspace_upload_image_testcase extends advanced_testcase {
    /**
     * Given that there are 10 uploaded files to the draft.
     * Then the process of saving image will pick the last one out of ten - which is
     * the latest image, and the rest of the files will be either deleted or move to different draft.
     *
     * @return void
     */
    public function test_save_image_from_draft_files(): void {
        global $CFG;
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $this->setUser($user_one);

        require_once("{$CFG->dirroot}/lib/filelib.php");

        $user_context = \context_user::instance($user_one->id);
        $draft_id = file_get_unused_draft_itemid();
        $fs = get_file_storage();

        $time = time();

        for ($i = 1; $i <= 10; $i++) {
            // Increasing 10 seconds for every single file so that we can make sure the last
            // file is the latest.
            $time += 10;

            $file_record = new stdClass();
            $file_record->filename = "File {$i}.png";
            $file_record->contextid = $user_context->id;
            $file_record->component = 'user';
            $file_record->filearea = 'draft';
            $file_record->filepath = '/';
            $file_record->itemid = $draft_id;
            $file_record->timecreated = $time;
            $file_record->timemodified = $time;

            $fs->create_file_from_string($file_record, "file {$i}");
        }

        $workspace = workspace_helper::create_workspace(
            "Workspace 101",
            $user_one->id,
            null,
            "This is summary",
            FORMAT_PLAIN,
            $draft_id
        );

        $this->assertDebuggingCalled();

        $files = $fs->get_area_files(
            $workspace->get_context()->id,
            workspace::get_type(),
            workspace::IMAGE_AREA,
            0
        );

        $this->assertNotEmpty($files);

        $files = array_filter(
            $files,
            function (\stored_file $file): bool {
                return !$file->is_directory();
            }
        );

        $this->assertCount(1, $files);

        // Expecting the last stored file is with the name 'File 10.png'.
        $file = reset($files);
        $this->assertEquals("File 10.png", $file->get_filename());

        // Check that if the draft files are gone.
        $draft_files = $fs->get_area_files(
            $user_context->id,
            'user',
            'draft',
            $draft_id
        );

        $this->assertNotEmpty($draft_files);

        // Only 2 items left which are the directory and the file itself. the other draft files are removed.
        $this->assertCount(2, $draft_files);
    }
}
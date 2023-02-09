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

use core\json_editor\node\paragraph;
use core\json_editor\node\image;
use container_workspace\discussion\discussion_helper;
use container_workspace\member\member;
use container_workspace\workspace;
use container_workspace\discussion\discussion;
use totara_comment\comment_helper;

/**
 * This is the test case to assure that the trailing files are not being
 * included within the workspace via discussion's content.
 *
 * Which it will test both create and update paths.
 */
class container_workspace_discussion_trailing_testcase extends advanced_testcase {
    /**
     * Test to assure that the trailing files are not being included.
     * @return void
     */
    public function test_create_discussion_with_trailing_files(): void {
        global $CFG, $DB;
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace('Space force 101');

        require_once("{$CFG->dirroot}/lib/filelib.php");
        $fs = get_file_storage();

        $user_two = $generator->create_user();
        $this->setUser($user_two);

        member::join_workspace($workspace, $user_two->id);
        $user_context = context_user::instance($user_two->id);
        $draft_id = file_get_unused_draft_itemid();

        // Start populating files.
        $stored_files = [];

        for ($i = 1; $i <= 5; $i++) {
            $file_record = new stdClass();
            $file_record->component = 'user';
            $file_record->filearea = 'draft';
            $file_record->itemid = $draft_id;
            $file_record->contextid = $user_context->id;
            $file_record->filename = "file_{$i}.png";
            $file_record->filepath = '/';

            $stored_files[] = $fs->create_file_from_string($file_record, "This is the file {$i}");
        }

        // After the files are created, we can now delete craft a content that does not have the last file.
        // Remove the last stored file from the list, so that we can check if it is included in the list of files
        // for discussion.

        /** @var stored_file $last_stored_file */
        $last_stored_file = array_pop($stored_files);

        $json_document = [
            'type' => 'doc',
            'content' => [
                paragraph::create_json_node_from_text('These are the files')
            ],
        ];

        foreach ($stored_files as $stored_file) {
            $json_document['content'][] = image::create_raw_node_from_image($stored_file);
        }

        // Start create the discussion.
        $discussion = discussion_helper::create_discussion(
            $workspace,
            json_encode($json_document),
            $draft_id,
            FORMAT_JSON_EDITOR,
            $user_two->id
        );

        // The process of saving files will delete the draft trailing files from the document.
        // Hence we would expect the record of last_stored_file to not be existing anymore.
        $this->assertFalse($DB->record_exists('files', ['id' => $last_stored_file->get_id()]));
        $files = $discussion->get_files();

        foreach ($files as $file) {
            $this->assertNotEquals($file->get_filename(), $last_stored_file->get_filename());
            $this->assertNotEquals($file->get_contenthash(), $last_stored_file->get_contenthash());
        }
    }

    /**
     * This test is about updating the workspace's discussion that remove the
     * old files that had been added to the discussion's content previously.
     *
     * @return void
     */
    public function test_update_discussion_remove_trailing_files(): void {
        global $CFG;

        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();
        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        $user_two = $generator->create_user();
        $this->setUser($user_two);

        member::join_workspace($workspace, $user_two->id);
        $user_context = context_user::instance($user_two->id);

        require_once("{$CFG->dirroot}/lib/filelib.php");

        $fs = get_file_storage();
        $draft_id = file_get_unused_draft_itemid();

        $file_record = new stdClass();
        $file_record->filename = 'first_file.png';
        $file_record->component = 'user';
        $file_record->filearea = 'draft';
        $file_record->itemid = $draft_id;
        $file_record->filepath = '/';
        $file_record->contextid = $user_context->id;

        $stored_file = $fs->create_file_from_string($file_record, 'some string');
        $discussion = discussion_helper::create_discussion(
            $workspace,
            json_encode([
                'type' => 'doc',
                'content' => [
                    paragraph::create_json_node_from_text('This is a file'),
                    image::create_raw_node_from_image($stored_file)
                ],
            ]),
            $draft_id,
            FORMAT_JSON_EDITOR,
            $user_two->id
        );

        $files = $discussion->get_files();
        $this->assertCount(1, $files);

        // Start updating the discussion content, however, we will move the current files from
        // discussion area into the new draft areas.
        $new_draft_id = null;
        $course_context = $discussion->get_context();

        file_prepare_draft_area(
            $new_draft_id,
            $course_context->id,
            workspace::get_type(),
            discussion::AREA,
            $discussion->get_id()
        );

        $updated_discussion = discussion_helper::update_discussion_content(
            $discussion->get_id(),
            json_encode([
                'type' => 'doc',
                'content' => [
                    paragraph::create_json_node_from_text('This is something else now')
                ],
            ]),
            $new_draft_id,
            FORMAT_JSON_EDITOR,
            $user_two->id
        );

        $updated_discussion_files = $updated_discussion->get_files();
        $this->assertEmpty($updated_discussion_files);
    }

    /**
     * This test is to assure that there are no trailing files being added via comments.
     *
     * @return void
     */
    public function test_add_comment_with_trailing_files(): void {
        global $CFG, $DB;

        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');

        $workspace = $workspace_generator->create_workspace();
        $discussion = $workspace_generator->create_discussion($workspace->get_id());

        $user_two = $generator->create_user();
        $user_context = context_user::instance($user_two->id);

        $this->setUser($user_two);
        member::join_workspace($workspace, $user_two->id);

        // Preparing files for comments
        require_once("{$CFG->dirroot}/lib/filelib.php");
        $fs = get_file_storage();
        $draft_files = [];

        $draft_id = file_get_unused_draft_itemid();
        for ($i = 0; $i < 5; $i++) {
            $file_record = new stdClass();
            $file_record->component = 'user';
            $file_record->filearea = 'draft';
            $file_record->itemid = $draft_id;
            $file_record->filename = "file_{$i}.png";
            $file_record->filepath = '/';
            $file_record->contextid = $user_context->id;

            $draft_files[] = $fs->create_file_from_string($file_record, "This is the content {$i}");
        }

        // Pop the last file out as this is the file we would not want to be included within the content of the comment.

        /** @var stored_file $last_draft_file */
        $last_draft_file = array_pop($draft_files);
        $json_document = [
            'type' => 'doc',
            'content' => [
                paragraph::create_json_node_from_text('This is the content of the comment')
            ],
        ];

        foreach ($draft_files as $draft_file) {
            $json_document['content'][] = image::create_raw_node_from_image($draft_file);
        }

        // Start adding comments to the workspace's discussion.
        $comment = comment_helper::create_comment(
            workspace::get_type(),
            discussion::AREA,
            $discussion->get_id(),
            json_encode($json_document),
            FORMAT_JSON_EDITOR,
            $draft_id,
            $user_two->id
        );

        // The process to save the comments will remove any trailing the files that are not being used within the content.
        // Therefore we can check whether the last_draft_file is existing within the system or not.
        $this->assertFalse(
            $DB->record_exists('files', ['id' => $last_draft_file->get_id()])
        );

        $files = comment_helper::get_files($comment);

        $this->assertNotEmpty($files);
        $files = array_filter(
            $files,
            function (stored_file $file): bool {
                return !$file->is_directory();
            }
        );

        foreach ($files as $file) {
            $this->assertNotEquals($last_draft_file->get_filename(), $file-> get_filename());
            $this->assertNotEquals($last_draft_file->get_contenthash(), $file->get_contenthash());
        }
    }
}
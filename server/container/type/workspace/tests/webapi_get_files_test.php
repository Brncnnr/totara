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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package container_workspace
 */
defined('MOODLE_INTERNAL') || die();

use container_workspace\discussion\discussion;
use container_workspace\file\file;
use container_workspace\member\member;
use container_workspace\query\file\sort;
use container_workspace\workspace;
use core\json_editor\node\image;
use core\json_editor\node\paragraph;
use totara_webapi\phpunit\webapi_phpunit_helper;

class container_workspace_webapi_get_files_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_get_files_for_public_workspaces(): void {
        $workspace = $this->create_workspace_with_files();

        // Verify the owner can access the files
        $this->assert_can_see_files($workspace);

        // Verify a member can access files
        $member = $this->getDataGenerator()->create_user();
        member::added_to_workspace($workspace, $member->id, false, $workspace->get_user_id());
        $this->setUser($member);
        $this->assert_can_see_files($workspace);

        // Verify a non-member can access the files
        $non_member = $this->getDataGenerator()->create_user();
        $this->setUser($non_member);
        $this->assert_can_see_files($workspace);
    }

    /**
     * @return void
     */
    public function test_get_files_for_private_workspaces(): void {
        $workspace = $this->create_workspace_with_files(true, false);

        // Verify the owner can access the files
        $this->assert_can_see_files($workspace);

        // Verify a member can access files
        $member = $this->getDataGenerator()->create_user();
        member::added_to_workspace($workspace, $member->id, false, $workspace->get_user_id());
        $this->setUser($member);
        $this->assert_can_see_files($workspace);

        // Verify a non-member cannot access the files
        $non_member = $this->getDataGenerator()->create_user();
        $this->setUser($non_member);
        $this->assert_cannot_see_files($workspace);
    }

    /**
     * @return void
     */
    public function test_get_files_for_hidden_workspaces(): void {
        $workspace = $this->create_workspace_with_files(true, true);

        // Verify the owner can access the files
        $this->assert_can_see_files($workspace);

        // Verify a member can access files
        $member = $this->getDataGenerator()->create_user();
        member::added_to_workspace($workspace, $member->id, false, $workspace->get_user_id());
        $this->setUser($member);
        $this->assert_can_see_files($workspace);

        // Verify a non-member cannot access the files
        $non_member = $this->getDataGenerator()->create_user();
        $this->setUser($non_member);
        $this->assert_cannot_see_files($workspace);
    }

    /**
     * Assert the files query succeeds
     *
     * @param workspace $workspace
     */
    private function assert_can_see_files(workspace $workspace): void {
        $results = $this->resolve_graphql_query(
            'container_workspace_files',
            [
                'workspace_id' => $workspace->get_id(),
                'page' => 1,
                'sort' => sort::get_code(sort::RECENT),
                'extension' => null
            ]
        );
        $this->assertCount(1, $results);

        $first = current($results);
        $this->assertInstanceOf(file::class, $first);
    }

    /**
     * Assert the files query fails
     *
     * @param workspace $workspace
     */
    private function assert_cannot_see_files(workspace $workspace): void {
        $exception = null;
        $results = null;
        try {
            $results = $this->resolve_graphql_query(
                'container_workspace_files',
                [
                    'workspace_id' => $workspace->get_id(),
                    'page' => 1,
                    'sort' => sort::get_code(sort::RECENT),
                    'extension' => null
                ]
            );
        } catch (\Exception $ex) {
            $exception = $ex;
        }
        $this->assertNotNull($exception);
        $this->assertNull($results);
    }

    /**
     * Create a test workspace & add a discussion with a file in it
     *
     * @param bool $private
     * @param bool $hidden
     * @return workspace
     */
    private function create_workspace_with_files(bool $private = false, bool $hidden = false): workspace {
        global $CFG, $USER;

        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $this->setUser($user);
        /** @var \container_workspace\testing\generator $workspace_gen */
        $workspace_gen = $this->getDataGenerator()->get_plugin_generator('container_workspace');
        $workspace = $workspace_gen->create_workspace(null, null, null, $user->id, $private, $hidden);

        require_once("{$CFG->dirroot}/lib/filelib.php");

        $fs = get_file_storage();
        $context = \context_user::instance($USER->id);
        $draft_id = file_get_unused_draft_itemid();

        $file_record = new \stdClass();
        $file_record->contextid = $context->id;
        $file_record->component = 'user';
        $file_record->filearea = 'draft';
        $file_record->itemid = $draft_id;
        $file_record->filepath = '/';
        $file_record->filename = "file_image.png";
        $file_record->author = $user->username;

        $file = $fs->create_file_from_string($file_record, "This is the file");
        $document = json_encode([
            'type' => 'doc',
            'content' => [
                paragraph::create_json_node_from_text('This is the content'),
                image::create_raw_node_from_image($file)
            ],
        ]);

        discussion::create($document, $workspace->get_id(), $draft_id, FORMAT_JSON_EDITOR);

        return $workspace;
    }
}
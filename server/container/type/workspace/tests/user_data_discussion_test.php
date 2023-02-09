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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package container_workspace
 */
defined('MOODLE_INTERNAL') || die();

use container_workspace\discussion\discussion;
use totara_userdata\userdata\target_user;
use container_workspace\userdata\discussion as user_data_discussion;
use container_workspace\member\member;
use container_workspace\workspace;
use totara_comment\comment;
use container_workspace\entity\workspace_discussion;

class container_workspace_user_data_discussion_testcase extends advanced_testcase {

    /**
     * @return void
     */
    public function test_purge_discussion(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $user1 = $generator->create_user();


        /** @var \container_workspace\testing\generator $workspace_gen */
        $workspace_gen = $generator->get_plugin_generator('container_workspace');

        //Create discussion for user.
        $this->setUser($user);
        $workspace = $workspace_gen->create_workspace();

        for ($i = 0; $i < 2; $i++) {
            discussion::create(
                "This is the content of the discussion " .$i,
                $workspace->get_id()
            );
        }

        //Create discussion for user1.
        $this->setUser($user1);
        for ($i = 0; $i < 2; $i++) {
            discussion::create(
                "This is the content of the discussion " .$i,
                $workspace->get_id()
            );
        }

        // Four discussion created.
        $this->assertEquals(4,
            $DB->count_records('workspace_discussion', ['course_id' => $workspace->get_id()])
        );

        // Two discussion created by user.
        $this->assertEquals(2,
            $DB->count_records('workspace_discussion',
                ['course_id' => $workspace->get_id(), 'user_id' => $user->id]
            )
        );

        // Two discussion created by user1.
        $this->assertEquals(2,
            $DB->count_records('workspace_discussion',
                ['course_id' => $workspace->get_id(), 'user_id' => $user1->id]
            )
        );

        $user->deleted = 1;
        $DB->update_record('user', $user);

        $target_user = new target_user($user);
        $context = context_system::instance();

        $result = user_data_discussion::execute_purge($target_user, $context);
        $this->assertEquals(user_data_discussion::RESULT_STATUS_SUCCESS, $result);

        // Two discussions have to be left.
        $this->assertEquals(2,
            $DB->count_records('workspace_discussion', ['course_id' => $workspace->get_id()])
        );

        // User is purged.
        $this->assertEquals(0,
            $DB->count_records('workspace_discussion',
                ['course_id' => $workspace->get_id(), 'user_id' => $user->id]
            )
        );

        // User1' discussions are still in the table.
        $this->assertEquals(2,
            $DB->count_records('workspace_discussion',
                ['course_id' => $workspace->get_id(), 'user_id' => $user1->id]
            )
        );
    }

    /**
     * @return void
     */
    public function test_purge_discussion_that_purge_comments(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        // Now create a discussion for the workspace.
        $discussion = $workspace_generator->create_discussion(
            $workspace->get_id(),
            json_encode([
                'type' => 'doc',
                'content' => []
            ]),
            null,
            FORMAT_JSON_EDITOR
        );

        // Now log in as user two and created several comments to the discussion.
        $user_two = $generator->create_user();
        $this->setUser($user_two);

        member::join_workspace($workspace, $user_two->id);

        /** @var \totara_comment\testing\generator $comment_generator */
        $comment_generator = $generator->get_plugin_generator('totara_comment');

        /** @var comment[] $comments */
        $comments = [];

        $workspace_type = workspace::get_type();

        for ($i = 0; $i < 5; $i++) {
            $comment = $comment_generator->create_comment(
                $discussion->get_id(),
                $workspace_type,
                discussion::AREA
            );

            $comments[] = $comment;
        }

        $this->assertEquals(
            5,
            $DB->count_records(
                comment::get_entity_table(),
                [
                    'component' => $workspace_type,
                    'area' => discussion::AREA,
                    'instanceid' => $discussion->get_id()
                ]
            )
        );

        foreach ($comments as $comment) {
            $this->assertTrue($DB->record_exists(comment::get_entity_table(), ['id' => $comment->get_id()]));
        }

        // Now purge the user.
        delete_user($user_one);
        $user_one = core_user::get_user($user_one->id, '*', MUST_EXIST);

        $target_user = new target_user($user_one);
        user_data_discussion::execute_purge($target_user, context_system::instance());

        $this->assertFalse(
            $DB->record_exists(workspace_discussion::TABLE, ['id' => $discussion->get_id()])
        );

        $this->assertEquals(
            0,
            $DB->count_records(
                comment::get_entity_table(),
                [
                    'component' => $workspace_type,
                    'area' => discussion::AREA,
                    'instanceid' => $discussion->get_id()
                ]
            )
        );

        foreach ($comments as $comment) {
            $this->assertFalse(
                $DB->record_exists(
                    comment::get_entity_table(),
                    ['id' => $comment->get_id()]
                )
            );
        }
    }

    /**
     * @return void
     */
    public function test_export_discussion(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $user1 = $generator->create_user();
        $this->setUser($user);

        /** @var \container_workspace\testing\generator $workspace_gen */
        $workspace_gen = $generator->get_plugin_generator('container_workspace');
        $workspace =  $workspace_gen->create_workspace();

        for ($i = 0; $i < 3; $i++) {
            discussion::create(
                "This is the content of the discussion " .$i,
                $workspace->get_id()
            );
        }

        $this->setUser($user1);

        for ($i = 0; $i < 2; $i++) {
            discussion::create(
                "This is the content of the discussion " .$i,
                $workspace->get_id()
            );
        }

        $this->assertEquals(5,
            $DB->count_records('workspace_discussion', ['course_id' => $workspace->get_id()])
        );

        $this->assertTrue( $DB->record_exists('workspace_discussion',
            ['course_id' => $workspace->get_id(), 'user_id' => $user->id]
        ));
        $this->assertTrue( $DB->record_exists('workspace_discussion',
            ['course_id' => $workspace->get_id(), 'user_id' => $user1->id]
        ));

        $target_user = new target_user($user);
        $context = context_system::instance();

        $export = user_data_discussion::execute_export($target_user, $context);
        $this->assertNotEmpty($export->data);
        $this->assertCount(3, $export->data);

        foreach ($export->data as $record) {
            $this->assertIsArray($record);
            $this->assertArrayHasKey('id', $record);
            $this->assertArrayHasKey('content', $record);
            $this->assertArrayHasKey('time_created', $record);
            $this->assertArrayHasKey('user_id', $record);
            $this->assertArrayHasKey('time_modified', $record);

            // Export data belongs to user.
            $this->assertEquals($user->id, $record['user_id']);
        }
    }
}
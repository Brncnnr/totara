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

use container_workspace\discussion\discussion;
use core\json_editor\node\image;
use core\json_editor\node\paragraph;
use core\webapi\execution_context;
use totara_webapi\graphql;

class container_workspace_discussion_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_create_discussion(): void {
        global $DB;
        $generator = $this->getDataGenerator();

        $user = $generator->create_user();
        $this->setUser($user);

        /** @var \container_workspace\testing\generator $workspace_gen */
        $workspace_gen = $generator->get_plugin_generator('container_workspace');
        $workspace =  $workspace_gen->create_workspace();

        $discussion = discussion::create(
            "This is the content of the discussion",
            $workspace->get_id()
        );

        $this->assertTrue($DB->record_exists('workspace_discussion', ['id' => $discussion->get_id()]));
    }

    /**
     * @return void
     */
    public function test_create_discussion_with_files(): void {
        global $CFG, $USER;
        $generator = $this->getDataGenerator();

        $user = $generator->create_user();
        $this->setUser($user);

        /** @var \container_workspace\testing\generator $workspace_gen */
        $workspace_gen = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_gen->create_workspace();

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

        $file = $fs->create_file_from_string($file_record, "This is the file");
        $document = json_encode([
            'type' => 'doc',
            'content' => [
                paragraph::create_json_node_from_text('This is the content'),
                image::create_raw_node_from_image($file)
            ],
        ]);

        $discussion = discussion::create($document, $workspace->get_id(), $draft_id, FORMAT_JSON_EDITOR);
        $this->assertTrue(
            $fs->file_exists(
                $workspace->get_context()->id,
                'container_workspace',
                discussion::AREA,
                $discussion->get_id(),
                '/',
                $file_record->filename
            )
        );

        $content_text = content_to_text($document, FORMAT_JSON_EDITOR);
        $content_text = file_rewrite_urls_to_pluginfile($content_text, $draft_id);

        $this->assertEquals($content_text, $discussion->get_content_text());
    }

    /**
     * @return void
     */
    public function test_update_discussion_with_files(): void {
        global $USER, $CFG;

        $generator = $this->getDataGenerator();

        $user = $generator->create_user();
        $this->setUser($user);

        /** @var \container_workspace\testing\generator $workspace_gen */
        $workspace_gen = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_gen->create_workspace();

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

        $file = $fs->create_file_from_string($file_record, "This is the file");
        $document = json_encode([
            'type' => 'doc',
            'content' => [
                paragraph::create_json_node_from_text('This is the content'),
                image::create_raw_node_from_image($file)
            ],
        ]);

        $discussion = discussion::create($document, $workspace->get_id(), $draft_id, FORMAT_JSON_EDITOR);
        $this->assertTrue(
            $fs->file_exists(
                $workspace->get_context()->id,
                'container_workspace',
                discussion::AREA,
                $discussion->get_id(),
                '/',
                $file_record->filename
            )
        );

        $document = json_encode([
            'type' => 'doc',
            'content' => [
                paragraph::create_json_node_from_text('This is the content')
            ]
        ]);

        $draft_id = file_get_unused_draft_itemid();
        $discussion->update_content($document, $draft_id);

        $this->assertFalse(
            $fs->file_exists(
                $workspace->get_context()->id,
                'container_workspace',
                discussion::AREA,
                $discussion->get_id(),
                '/',
                $file_record->filename
            )
        );
    }

    /**
     * @return void
     */
    public function test_create_discussion_via_graphql(): void {
        global $CFG, $USER;
        $generator = $this->getDataGenerator();
        $user = $generator->create_user();

        $this->setUser($user);

        /** @var \container_workspace\testing\generator $workspace_gen */
        $workspace_gen = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_gen->create_workspace();

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

        $file = $fs->create_file_from_string($file_record, "This is the file");

        $ec = execution_context::create('ajax', 'container_workspace_post_discussion');
        $result = graphql::execute_operation($ec, [
            'workspace_id' => $workspace->get_id(),
            'draft_id' => $draft_id,
            'content_format' => FORMAT_JSON_EDITOR,
            'content' => json_encode([
                'type' => 'doc',
                'content' => [
                    paragraph::create_json_node_from_text("This is the text content"),
                    image::create_raw_node_from_image($file)
                ],
            ]),
        ]);

        $this->assertEmpty($result->errors);
        $this->assertNotEmpty($result->data);
    }

    /**
     * @return void
     */
    public function test_update_discussion_via_graphql(): void {
        $generator = $this->getDataGenerator();
        $user = $generator->create_user();

        $this->setUser($user);

        /** @var \container_workspace\testing\generator $workspace_gen */
        $workspace_gen = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_gen->create_workspace();

        $discussion = discussion::create("This is content", $workspace->get_id());

        $ec = execution_context::create('ajax', 'container_workspace_update_discussion_content');
        $result = graphql::execute_operation($ec, [
            'id' => $discussion->get_id(),
            'content' => json_encode([
                'type' => 'doc',
                'content' => [ paragraph::create_json_node_from_text('New content') ]
            ]),
            'content_format' => FORMAT_JSON_EDITOR
        ]);

        $this->assertEmpty($result->errors);
        $this->assertNotEmpty($result->data);
    }

    /**
     * @return void
     */
    public function test_delete_discussion_via_graphql(): void {
        $generator = $this->getDataGenerator();
        $user = $generator->create_user();

        $this->setUser($user);

        /** @var \container_workspace\testing\generator $workspace_gen */
        $workspace_gen = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_gen->create_workspace();

        $discussion = discussion::create("This is content", $workspace->get_id());

        $ec = execution_context::create('ajax', 'container_workspace_delete_discussion');
        $result = graphql::execute_operation($ec, ['id' => $discussion->get_id()]);

        $this->assertEmpty($result->errors);
        $this->assertNotEmpty($result->data);
    }

    /**
     * @return void
     */
    public function test_create_discussion_with_files_via_graphql(): void {
        global $CFG;

        $generator = $this->getDataGenerator();
        $user = $generator->create_user();

        // Log in as user and start creating a workspace.
        $this->setUser($user);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        $user_context = \context_user::instance($user->id);

        require_once("{$CFG->dirroot}/lib/filelib.php");
        $draft_id = file_get_unused_draft_itemid();
        $fs = get_file_storage();

        $document = [
            'type' => 'doc',
            'content' => [
                paragraph::create_json_node_from_text('This is some text me')
            ]
        ];

        for ($i = 0; $i < 5; $i++) {
            $file_record = new stdClass();
            $file_record->component = 'user';
            $file_record->filearea = 'draft';
            $file_record->itemid = $draft_id;
            $file_record->filename = uniqid() . '.png';
            $file_record->filepath = '/';
            $file_record->contextid = $user_context->id;

            $stored_file = $fs->create_file_from_string($file_record, "This is {$i}");
            $document['content'][] = image::create_raw_node_from_image($stored_file);
        }


        // Start creating a discussions with files.
        $ec = execution_context::create("ajax", 'container_workspace_post_discussion');
        $result = graphql::execute_operation(
            $ec,
            [
                'workspace_id' => $workspace->get_id(),
                'content' => json_encode($document),
                'content_format' => FORMAT_JSON_EDITOR,
                'draft_id' => $draft_id
            ]
        );

        $this->assertEmpty($result->errors);
        $this->assertNotEmpty($result->data);

        $this->assertIsArray($result->data);
        $this->assertArrayHasKey('discussion', $result->data);
    }
}
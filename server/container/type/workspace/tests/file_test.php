<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
use container_workspace\file\file;
use container_workspace\loader\file\loader;
use container_workspace\query\file\query;
use core\json_editor\node\image;
use core\json_editor\node\paragraph;
use container_workspace\query\file\sort;
use core\webapi\execution_context;
use totara_webapi\graphql;

class container_workspace_file_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_get_file(): void {
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

        discussion::create($document, $workspace->get_id(), $draft_id, FORMAT_JSON_EDITOR);

        $query = new query($workspace->get_id());
        $query->set_sort(sort::RECENT);

        $paginator = loader::get_files($query);
        $items = $paginator->get_items()->all();

        $this->assertEquals(1, count($items));

        /*@var file $item */
        foreach ($items as $item) {
            $this->assertEquals('PNG', strtoupper($item->get_extension()));
            $this->assertEquals("file_image.png", $item->get_filename());
            $this->assertNotEmpty(fullname($item->get_user()));
            $this->assertNotEmpty($item->get_file_url());
            $this->assertNotEmpty($item->get_time_created());
            $this->assertNotEmpty($item->get_time_modified());
            $this->assertNotEmpty($item->get_filesize());
        }
    }

    /**
     * @return void
     */
    public function test_filter_file(): void {
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

        // Creating 10 discussions, and each of them will have a single file.
        for ($i = 0; $i < 10; $i++) {
            $draft_id = file_get_unused_draft_itemid();
            if ($i % 3 == 0) {
                $file_record = new \stdClass();
                $file_record->contextid = $context->id;
                $file_record->component = 'user';
                $file_record->filearea = 'draft';
                $file_record->itemid = $draft_id;
                $file_record->filepath = '/';
                $file_record->filename = "png" . $i . ".png";
            } elseif ($i % 3 == 1) {
                $file_record = new \stdClass();
                $file_record->contextid = $context->id;
                $file_record->component = 'user';
                $file_record->filearea = 'draft';
                $file_record->itemid = $draft_id;
                $file_record->filepath = '/';
                $file_record->filename = "jpg" . $i . ".jpg";
            } else {
                $file_record = new \stdClass();
                $file_record->contextid = $context->id;
                $file_record->component = 'user';
                $file_record->filearea = 'draft';
                $file_record->itemid = $draft_id;
                $file_record->filepath = '/';
                $file_record->filename = "jpeg" . $i . ".jpeg";
            }

            $file = $fs->create_file_from_string($file_record, "This is the file");
            $document = json_encode([
                'type' => 'doc',
                'content' => [
                    paragraph::create_json_node_from_text('This is the content'),
                    image::create_raw_node_from_image($file)
                ],
            ]);

            discussion::create($document, $workspace->get_id(), $draft_id, FORMAT_JSON_EDITOR);
        }

        $query = new query($workspace->get_id());

        // Test filter All.
        $paginator = loader::get_files($query);
        $items = $paginator->get_items()->all();
        $this->assertCount(10, $items);

        $query->set_extension('png');
        $paginator = loader::get_files($query);
        $items = $paginator->get_items()->all();

        // Test filter files by one extension.
        /** @var file $item */
        foreach ($items as $item) {
            $this->assertEquals('png', $item->get_extension());
        }

        // Test file format option.
        $extentions = loader::get_extensions((int)$workspace->id);
        $this->assertArrayHasKey('jpg', $extentions);
        $this->assertArrayHasKey('png', $extentions);
        $this->assertArrayHasKey('jpeg', $extentions);
        $this->assertCount(3, $extentions);

        // Test file format option alphabetically.
        $this->assertEquals('jpeg', $extentions['jpeg']);
        $this->assertEquals('jpg', $extentions['jpg']);
        $this->assertEquals('png', $extentions['png']);
    }
}
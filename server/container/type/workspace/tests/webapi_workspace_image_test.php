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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package container_workspace
 */
defined('MOODLE_INTERNAL') || die();

use container_workspace\theme\file\workspace_image;
use core\theme\settings;
use totara_core\advanced_feature;
use totara_webapi\phpunit\webapi_phpunit_helper;

class container_workspace_webapi_workspace_image_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    public function test_no_workspace(): void {
        global $CFG;

        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();
        $this->setUser($user_one);

        $result = $this->execute_graphql_operation(
            'container_workspace_upload_metadata', [
                'theme' => 'ventura',
            ]
        );

        $this->assertEmpty($result->errors);
        $this->assertNotEmpty($result->data);
        $this->assertArrayHasKey('image_url', $result->data);
        $this->assertSame(
            'https://www.example.com/moodle/theme/image.php/_s/ventura/container_workspace/1/default_space',
            $result->data['image_url']
        );

        // Confirm that we get debug message when theme not passed.
        $result = $this->execute_graphql_operation(
            'container_workspace_upload_metadata', []
        );
        $this->assertEmpty($result->errors, !empty($result->errors) ? $result->errors[0]->message : '');
        $this->assertDebuggingCalled(
            "'theme' parameter not set. Falling back on {$CFG->theme}. The resolved assets "
            . "will be associated with {$CFG->theme}, which might not be the expected result."
        );
    }

    public function test_workspace_default_image(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();
        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        $result = $this->execute_graphql_operation(
            'container_workspace_upload_metadata', [
                'workspace_id' => $workspace->id,
                'theme' => 'ventura',
            ]
        );

        $this->assertEmpty($result->errors);
        $this->assertNotEmpty($result->data);
        $this->assertArrayHasKey('image_url', $result->data);
        $this->assertSame(
            'https://www.example.com/moodle/theme/image.php/_s/ventura/container_workspace/1/default_space',
            $result->data['image_url']
        );
    }

    public function test_workspace_custom_image(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();
        $this->setUser($user_one);

        $user_context = \context_user::instance($user_one->id);
        $draft_id = file_get_unused_draft_itemid();
        $fs = get_file_storage();
        $time = time();
        $file_record = new stdClass();
        $file_record->filename = "file_1.png";
        $file_record->contextid = $user_context->id;
        $file_record->component = 'user';
        $file_record->filearea = 'draft';
        $file_record->filepath = '/';
        $file_record->itemid = $draft_id;
        $file_record->timecreated = $time;
        $file_record->timemodified = $time;
        $fs->create_file_from_string($file_record, 'file_1');

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace(
            null,
            null,
            null,
            null,
            false,
            false,
            $draft_id
        );

        $result = $this->execute_graphql_operation(
            'container_workspace_upload_metadata', [
                'workspace_id' => $workspace->id,
                'theme' => 'ventura',
            ]
        );

        $this->assertEmpty($result->errors);
        $this->assertNotEmpty($result->data);
        $this->assertArrayHasKey('image_url', $result->data);
        $rev = $workspace->get_context()->id;
        $this->assertSame(
            "https://www.example.com/moodle/pluginfile.php/{$rev}/container_workspace/image/0/file_1.png",
            $result->data['image_url']
        );
    }

}

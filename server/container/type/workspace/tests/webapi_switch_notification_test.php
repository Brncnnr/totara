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

use container_workspace\notification\workspace_notification;
use container_workspace\entity\workspace_off_notification;
use totara_webapi\phpunit\webapi_phpunit_helper;

class container_workspace_webapi_switch_notification_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_switch_on_notification(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        $workspace_id = $workspace->get_id();
        workspace_notification::off($workspace_id, $user_one->id);

        $this->assertTrue(
            $DB->record_exists(
                workspace_off_notification::TABLE,
                [
                    'course_id' => $workspace_id,
                    'user_id' => $user_one->id
                ]
            )
        );

        // Turn off notification - but via webapi
        $result = $this->resolve_graphql_mutation(
            'container_workspace_switch_notification',
            [
                'workspace_id' => $workspace_id,
                'status' => 'ON'
            ]
        );

        $this->assertTrue($result);
        $this->assertFalse(
            $DB->record_exists(
                workspace_off_notification::TABLE,
                [
                    'course_id' => $workspace_id,
                    'user_id' => $user_one->id
                ]
            )
        );
    }

    /**
     * @return void
     */
    public function test_switch_off_notification(): void {
        global $DB;
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        $workspace_id = $workspace->get_id();
        $result = $this->resolve_graphql_mutation(
            'container_workspace_switch_notification',
            [
                'workspace_id' => $workspace_id,
                'status' => 'OFF'
            ]
        );

        $this->assertTrue($result);
        $this->assertTrue(
            $DB->record_exists(
                workspace_off_notification::TABLE,
                [
                    'course_id' => $workspace_id,
                    'user_id' => $user_one->id
                ]
            )
        );
    }

    /**
     * @return void
     */
    public function test_mute_workspace_notification(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_hidden_workspace();

        $workspace_id = $workspace->get_id();
        $result = $this->execute_graphql_operation(
            'container_workspace_mute_workspace',
            ['workspace_id' => $workspace_id]
        );

        $this->assertEmpty($result->errors);
        $this->assertNotEmpty($result->data);

        $this->assertArrayHasKey('result', $result->data);
        $this->assertTrue($result->data['result']);

        $this->assertTrue(
            $DB->record_exists(
                workspace_off_notification::TABLE,
                [
                    'course_id' => $workspace_id,
                    'user_id' => $user_one->id
                ]
            )
        );
    }

    /**
     * @return void
     */
    public function test_unmute_workspace_notification(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_private_workspace();

        $workspace_id = $workspace->get_id();
        workspace_notification::off($workspace_id, $user_one->id);

        $this->assertTrue(workspace_notification::is_off($workspace_id, $user_one->id));

        $result = $this->execute_graphql_operation(
            'container_workspace_unmute_workspace',
            ['workspace_id' => $workspace_id]
        );

        $this->assertEmpty($result->errors);
        $this->assertNotEmpty($result->data);
        $this->assertArrayHasKey('result', $result->data);
        $this->assertTrue($result->data['result']);

        $this->assertFalse(
            $DB->record_exists(
                workspace_off_notification::TABLE,
                [
                    'course_id' => $workspace_id,
                    'user_id' => $user_one->id
                ]
            )
        );
    }
}
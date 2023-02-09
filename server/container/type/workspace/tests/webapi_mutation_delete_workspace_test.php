<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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

use totara_webapi\phpunit\webapi_phpunit_helper;

class container_workspace_webapi_mutation_delete_workspace_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_delete_workspace(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();
        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_private_workspace();

        $workspace_id = $workspace->get_id();

        $this->assertTrue($DB->record_exists('course', ['id' => $workspace_id]));
        $this->assertTrue(
            $DB->record_exists(
                'workspace',
                [
                    'course_id' => $workspace_id,
                    'to_be_deleted' => 0
                ]
            )
        );

        $result = $this->resolve_graphql_mutation(
            'container_workspace_delete',
            ['workspace_id' => $workspace_id]
        );

        $this->assertTrue($result);

        // Mutation should not delete the workspace straight away but only flagging up the record to be deleted.
        $this->assertTrue($DB->record_exists('course', ['id' => $workspace_id]));
        $this->assertFalse(
            $DB->record_exists(
                'workspace',
                [
                    'course_id' => $workspace_id,
                    'to_be_deleted' => 0
                ]
            )
        );

        $this->assertTrue(
            $DB->record_exists(
                'workspace',
                [
                    'course_id' => $workspace_id,
                    'to_be_deleted' => 1
                ]
            )
        );
    }
}
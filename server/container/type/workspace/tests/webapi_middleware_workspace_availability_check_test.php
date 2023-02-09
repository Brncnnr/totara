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

use container_workspace\webapi\middleware\workspace_availability_check;
use core\webapi\execution_context;
use core\webapi\resolver\payload;
use core\webapi\resolver\result;

class container_workspace_webapi_middleware_workspace_availability_check_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_run_against_course_id(): void {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();

        $ec = execution_context::create('dev');
        $middleware = new workspace_availability_check('id');

        $payload = new payload(['id' => $course->id], $ec);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Cannot find workspace by id '{$course->id}'");

        $middleware->handle(
            $payload,
            function (payload $payload): result {
                return new result($payload->get_variables());
            }
        );
    }

    /**
     * @return void
     */
    public function test_run_without_field(): void {
        $ec = execution_context::create('dev');
        $middleware = new workspace_availability_check('workspace_id');

        $payload = new payload(['id' => 'something_else'], $ec);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Cannot find the field 'workspace_id' in payload");

        $middleware->handle(
            $payload,
            function (payload $payload): result {
                return new result($payload->get_variables());
            }
        );
    }

    /**
     * @return void
     */
    public function test_run_against_delete_workspace(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        $workspace->mark_to_be_deleted(true);
        $ec = execution_context::create('dev');

        $middleware = new workspace_availability_check('id');
        $payload = new payload(['id' => $workspace->get_id()], $ec);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The workspace is deleted");

        $middleware->handle(
            $payload,
            function (payload $payload): result {
                return new result($payload->get_variables());
            }
        );
    }

    /**
     * @return void
     */
    public function test_run_against_normal_workspace(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        $ec = execution_context::create('dev');
        $middleware = new workspace_availability_check('id');
        $payload = new payload(['id' => $workspace->get_id()], $ec);

        $result = $middleware->handle(
            $payload,
            function (payload $payload): result {
                return new result($payload->get_variables());
            }
        );

        $data = $result->get_data();

        self::assertIsArray($data);
        self::assertArrayHasKey('id', $data);
        self::assertEquals($workspace->get_id(), $data['id']);
    }
}
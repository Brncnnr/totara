<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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

use container_workspace\webapi\middleware\require_workspace_access;
use core\webapi\execution_context;
use core\webapi\resolver\payload;
use core\webapi\resolver\result;
use core_phpunit\testcase;

/**
 * @group container_workspace
 */
class container_workspace_webapi_middleware_require_workspace_access_testcase extends testcase {
    /**
     * @return void
     */
    public function test_deleted_workspace(): void {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $gen = container_workspace\testing\generator::instance();
        $workspace = $gen->create_workspace();
        $workspace->mark_to_be_deleted(true);

        $middleware = new require_workspace_access('input.workspace_id');

        $ec = execution_context::create('dev');
        $payload = new payload([
            'input' => [
                'workspace_id' => $workspace->get_id(),
            ],
        ], $ec);

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage("Invalid workspace");

        $middleware->handle($payload, function (payload $payload): result {
            return new result($payload->get_variables());
        });
    }

    /***
     * @return void
     */
    public function test_invalid_interactor_ability(): void {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $gen = container_workspace\testing\generator::instance();
        $workspace = $gen->create_workspace();

        $middleware = new require_workspace_access('input.workspace_id', ['not_real']);

        $ec = execution_context::create('dev');
        $payload = new payload([
            'input' => [
                'workspace_id' => $workspace->get_id(),
            ],
        ], $ec);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("There is no interactor method 'not_real'");

        $middleware->handle($payload, function (payload $payload): result {
            return new result($payload->get_variables());
        });
    }

    /**
     * @return void
     */
    public function test_missing_workspace_id(): void {
        $middleware = new require_workspace_access('input.workspace_id');

        $ec = execution_context::create('dev');
        $payload = new payload([
            'input' => [
                'workspace_id' => 0,
            ],
        ], $ec);

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage("Invalid workspace");

        $middleware->handle($payload, function (payload $payload): result {
            return new result($payload->get_variables());
        });
    }

    /***
     * @return void
     */
    public function test_no_interactor_ability(): void {
        $user = $this->getDataGenerator()->create_user();
        $user_two = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $gen = container_workspace\testing\generator::instance();
        $workspace = $gen->create_workspace();
        $this->setUser($user_two);

        $middleware = new require_workspace_access('input.workspace_id', ['is_owner']);

        $ec = execution_context::create('dev');
        $payload = new payload([
            'input' => [
                'workspace_id' => $workspace->get_id(),
            ],
        ], $ec);

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage("Invalid workspace");

        $middleware->handle($payload, function (payload $payload): result {
            return new result($payload->get_variables());
        });
    }

    /**
     * @return void
     */
    public function test_path_deep_missing(): void {
        $middleware = new require_workspace_access();

        $ec = execution_context::create('dev');
        $payload = new payload([
            'input' => [
                'workspace_id' => 33,
            ],
            'another' => 2
        ], $ec);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Cannot find the field 'input.workspaceid'");

        // Expect to see a coding exception
        $this->call_get_workspace_id($middleware, $payload, 'input.workspaceid');
    }

    /**
     * @return void
     */
    public function test_path_deep_wrapper_missing(): void {
        $middleware = new require_workspace_access();

        $ec = execution_context::create('dev');
        $payload = new payload([
            'input' => [
                'workspace_id' => 33,
            ],
            'another' => 2
        ], $ec);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Cannot find the field 'data.workspace_id'");

        // Expect to see a coding exception
        $this->call_get_workspace_id($middleware, $payload, 'data.workspace_id');
    }

    /**
     * @return void
     */
    public function test_path_shallow_missing(): void {
        $middleware = new require_workspace_access();

        $ec = execution_context::create('dev');
        $payload = new payload([
            'input' => [
                'workspace_id' => 33,
            ],
            'another' => 2
        ], $ec);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Cannot find the field 'workspace_id'");

        // Expect to see a coding exception
        $this->call_get_workspace_id($middleware, $payload, 'workspace_id');
    }

    /**
     * @return void
     */
    public function test_path_valid(): void {
        $middleware = new require_workspace_access();

        $ec = execution_context::create('dev');
        $payload = new payload([
            'input' => [
                'workspace_id' => 33,
            ],
            'another' => 2
        ], $ec);

        $result = $this->call_get_workspace_id($middleware, $payload, 'another');
        $this->assertSame(2, $result);

        $result = $this->call_get_workspace_id($middleware, $payload, 'input.workspace_id');
        $this->assertSame(33, $result);
    }

    /**
     * @return void
     */
    public function test_valid_interactor_ability(): void {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $gen = container_workspace\testing\generator::instance();
        $workspace = $gen->create_workspace();

        $middleware = new require_workspace_access('workspace_id', ['is_owner']);

        $ec = execution_context::create('dev');
        $input = [
            'workspace_id' => $workspace->get_id(),
        ];
        $payload = new payload($input, $ec);

        $result = $middleware->handle(
            $payload,
            function (payload $payload): result {
                return new result($payload->get_variables());
            }
        );

        $data = $result->get_data();

        self::assertIsArray($data);
        self::assertEqualsCanonicalizing($input, $data);
    }

    /**
     * @return void
     */
    public function test_valid_workspace_id(): void {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $gen = container_workspace\testing\generator::instance();
        $workspace = $gen->create_workspace();

        $middleware = new require_workspace_access('input.workspace_id');

        $ec = execution_context::create('dev');
        $input = [
            'input' => [
                'workspace_id' => $workspace->get_id(),
            ],
        ];
        $payload = new payload($input, $ec);

        $result = $middleware->handle(
            $payload,
            function (payload $payload): result {
                return new result($payload->get_variables());
            }
        );

        $data = $result->get_data();

        self::assertIsArray($data);
        self::assertEqualsCanonicalizing($input, $data);
    }

    public function test_workspace_with_course_id(): void {
        $course = $this->getDataGenerator()->create_course();

        $middleware = new require_workspace_access('input.workspace_id');

        $ec = execution_context::create('dev');
        $payload = new payload([
            'input' => [
                'workspace_id' => $course->id,
            ],
        ], $ec);

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage("Invalid workspace");

        $middleware->handle($payload, function (payload $payload): result {
            return new result($payload->get_variables());
        });
    }

    /**
     * @return void
     */
    public function test_workspace_with_invalid_id(): void {
        $middleware = new require_workspace_access('input.workspace_id');

        $ec = execution_context::create('dev');
        $payload = new payload([
            'input' => [
                'workspace_id' => -99,
            ],
        ], $ec);

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage("Invalid workspace");

        $middleware->handle($payload, function (payload $payload): result {
            return new result($payload->get_variables());
        });
    }

    /**
     * @param require_workspace_access $middleware
     * @param payload $payload
     * @param string $path
     * @return mixed
     */
    private function call_get_workspace_id(require_workspace_access $middleware, payload $payload, string $path) {
        $method = new ReflectionMethod($middleware, 'get_workspace_id');
        $method->setAccessible(true);

        return $method->invoke($middleware, $payload, $path);
    }
}
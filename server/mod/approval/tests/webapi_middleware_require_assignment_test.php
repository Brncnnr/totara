<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_approval
 */

use core\webapi\execution_context;
use core\webapi\resolver\payload;
use core\webapi\resolver\result;
use mod_approval\entity\workflow\workflow_stage_approval_level as approval_level_entity;
use mod_approval\exception\access_denied_exception;
use mod_approval\webapi\middleware\require_assignment;

require_once(__DIR__ . '/testcase.php');

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\webapi\middleware\require_assignment
 */
class mod_approval_webapi_middleware_require_assignment_testcase extends mod_approval_testcase {
    public function setUp(): void {
        parent::setUp();
    }

    public function tearDown(): void {
        parent::tearDown();
    }

    /**
     * @covers ::by_application_id
     * @covers ::handle
     */
    public function test_handle_by_application_id(): void {
        $next = function (payload $pay) {
            return new result($pay->get_variables());
        };
        $middleware1 = require_assignment::by_application_id(true);
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $application = $this->create_application_for_user();
        // succeeds
        $ec = execution_context::create('ajax');
        $middleware1->handle(new payload(['application_id' => $application->id], $ec), $next);
        $this->assertEquals($application->assignment->get_context()->id, $ec->get_relevant_context()->id);
        // fails
        try {
            $ec = execution_context::create('ajax');
            $middleware1->handle(new payload([], $ec), $next);
            $this->fail('invalid_parameter_exception expected');
        } catch (invalid_parameter_exception $ex) {
            $this->assertStringContainsString('invalid application id', $ex->getMessage());
        }
        // fails
        try {
            $ec = execution_context::create('ajax');
            $middleware1->handle(new payload(['input' => ['application_id' => $application->id]], $ec), $next);
            $this->fail('invalid_parameter_exception expected');
        } catch (invalid_parameter_exception $ex) {
            $this->assertStringContainsString('invalid application id', $ex->getMessage());
        }
        // fails
        try {
            $ec = execution_context::create('ajax');
            $middleware1->handle(new payload(['application_id' => 42], $ec), $next);
            $this->fail('moodle_exception expected');
        } catch (moodle_exception $ex) {
            $this->assertStringContainsString('Invalid assignment', $ex->getMessage());
        }
        // fails
        $this->update_course_visibility($application->assignment->course_id, $user->id, false);
        try {
            $ec = execution_context::create('ajax');
            $middleware1->handle(new payload(['application_id' => $application->id], $ec), $next);
            $this->fail('access_denied_exception expected');
        } catch (access_denied_exception $ex) {
            $this->assertStringContainsString('Parent workflow is hidden', $ex->getMessage());
        }
        // succeeds
        $this->update_course_visibility($application->assignment->course_id, $user->id, true);
        $middleware2 = require_assignment::by_application_id(false);
        $ec = execution_context::create('ajax');
        $middleware2->handle(new payload(['application_id' => $application->id], $ec), $next);
        try {
            $ec->get_relevant_context();
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
            $this->assertStringContainsString('Context has not been provided for this execution', $ex->getMessage());
        }
    }

    /**
     * @covers ::by_input_application_id
     * @covers ::handle
     */
    public function test_handle_by_input_application_id(): void {
        $next = function (payload $pay) {
            return new result($pay->get_variables());
        };
        $middleware1 = require_assignment::by_input_application_id(true);
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $application = $this->create_application_for_user();
        $assignment = $application->assignment;

        // succeeds
        $correct_args['input'] = [
            'application_id' => $application->id
        ];
        $ec = execution_context::create('ajax');
        $middleware1->handle(new payload($correct_args, $ec), $next);
        $this->assertEquals($application->assignment->get_context()->id, $ec->get_relevant_context()->id);

        // fails
        $fail_args = ['application_id' => $application->id];
        try {
            $ec = execution_context::create('ajax');
            $middleware1->handle(new payload($fail_args, $ec), $next);
            $this->fail('invalid_parameter_exception expected');
        } catch (invalid_parameter_exception $ex) {
            $this->assertStringContainsString('invalid application id', $ex->getMessage());
        }

        // fails
        $fail_args['input'] = [
            'application_id' => ''
        ];
        try {
            $ec = execution_context::create('ajax');
            $middleware1->handle(new payload($fail_args, $ec), $next);
            $this->fail('invalid_parameter_exception expected');
        } catch (invalid_parameter_exception $ex) {
            $this->assertStringContainsString('invalid application id', $ex->getMessage());
        }

        // fails
        $fail_args['input'] = [
            ['application_id' => $application->id]
        ];
        try {
            $ec = execution_context::create('ajax');
            $middleware1->handle(new payload($fail_args, $ec), $next);
            $this->fail('invalid_parameter_exception expected');
        } catch (invalid_parameter_exception $ex) {
            $this->assertStringContainsString('invalid application id', $ex->getMessage());
        }
        // fails
        $fail_args['input'] = [
            'application_id' => 42
        ];
        try {
            $ec = execution_context::create('ajax');
            $middleware1->handle(new payload($fail_args, $ec), $next);
            $this->fail('moodle_exception expected');
        } catch (moodle_exception $ex) {
            $this->assertStringContainsString('Invalid assignment', $ex->getMessage());
        }

        // fails
        $this->update_course_visibility($application->assignment->course_id, $user->id, false);
        try {
            $ec = execution_context::create('ajax');
            $middleware1->handle(new payload($correct_args, $ec), $next);
            $this->fail('access_denied_exception expected');
        } catch (access_denied_exception $ex) {
            $this->assertStringContainsString('Parent workflow is hidden', $ex->getMessage());
        }
        // succeeds
        $this->update_course_visibility($application->assignment->course_id, $user->id, true);
        $middleware2 = require_assignment::by_input_application_id(false);
        $ec = execution_context::create('ajax');
        $middleware2->handle(new payload($correct_args, $ec), $next);
        try {
            $ec->get_relevant_context();
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
            $this->assertStringContainsString('Context has not been provided for this execution', $ex->getMessage());
        }
    }

    /**
     * @covers ::by_input_assignment_id
     * @covers ::handle
     */
    public function test_handle_by_input_assignment_id(): void {
        $next = function (payload $pay) {
            return new result($pay->get_variables());
        };
        $middleware1 = require_assignment::by_input_assignment_id(true);
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $application = $this->create_application_for_user();
        $assignment = $application->assignment;

        // succeeds
        $correct_args['input'] = [
            'assignment_id' => $assignment->id
        ];
        $ec = execution_context::create('ajax');
        $middleware1->handle(new payload($correct_args, $ec), $next);
        $this->assertEquals($application->assignment->get_context()->id, $ec->get_relevant_context()->id);

        // fails
        $fail_args = ['assignment_id' => $assignment->id];
        try {
            $ec = execution_context::create('ajax');
            $middleware1->handle(new payload($fail_args, $ec), $next);
            $this->fail('invalid_parameter_exception expected');
        } catch (invalid_parameter_exception $ex) {
            $this->assertStringContainsString('invalid assignment id', $ex->getMessage());
        }

        // fails
        $fail_args['input'] = [
            'assignment_id' => ''
        ];
        try {
            $ec = execution_context::create('ajax');
            $middleware1->handle(new payload($fail_args, $ec), $next);
            $this->fail('invalid_parameter_exception expected');
        } catch (invalid_parameter_exception $ex) {
            $this->assertStringContainsString('invalid assignment id', $ex->getMessage());
        }

        // fails
        $fail_args['input'] = [
            ['assignment_id' => $assignment->id]
        ];
        try {
            $ec = execution_context::create('ajax');
            $middleware1->handle(new payload($fail_args, $ec), $next);
            $this->fail('invalid_parameter_exception expected');
        } catch (invalid_parameter_exception $ex) {
            $this->assertStringContainsString('invalid assignment id', $ex->getMessage());
        }
        // fails
        $fail_args['input'] = [
            'assignment_id' => 42
        ];
        try {
            $ec = execution_context::create('ajax');
            $middleware1->handle(new payload($fail_args, $ec), $next);
            $this->fail('moodle_exception expected');
        } catch (moodle_exception $ex) {
            $this->assertStringContainsString('Invalid assignment', $ex->getMessage());
        }

        // fails
        $this->update_course_visibility($application->assignment->course_id, $user->id, false);
        try {
            $ec = execution_context::create('ajax');
            $middleware1->handle(new payload($correct_args, $ec), $next);
            $this->fail('access_denied_exception expected');
        } catch (access_denied_exception $ex) {
            $this->assertStringContainsString('Parent workflow is hidden', $ex->getMessage());
        }
        // succeeds
        $this->update_course_visibility($application->assignment->course_id, $user->id, true);
        $middleware2 = require_assignment::by_input_assignment_id(false);
        $ec = execution_context::create('ajax');
        $middleware2->handle(new payload($correct_args, $ec), $next);
        try {
            $ec->get_relevant_context();
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
            $this->assertStringContainsString('Context has not been provided for this execution', $ex->getMessage());
        }
    }

    /**
     * @covers ::by_application_id
     * @covers ::handle
     */
    public function test_handle_default_by_input_approval_level_id(): void {
        $next = function (payload $pay) {
            return new result($pay->get_variables());
        };
        $middleware1 = require_assignment::default_by_input_approval_level_id(true);
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $application = $this->create_application_for_user();
        $assignment = $application->assignment;

        // succeeds
        $correct_args = [
            'input' => [
                'approval_level_id' => approval_level_entity::repository()->one()->id,
            ],
        ];
        $ec = execution_context::create('ajax');
        $middleware1->handle(new payload($correct_args, $ec), $next);
        $this->assertEquals($application->assignment->get_context()->id, $ec->get_relevant_context()->id);

        // fails
        $fail_args = [
            'approval_level_id' => approval_level_entity::repository()->one()->id
        ];
        try {
            $ec = execution_context::create('ajax');
            $middleware1->handle(new payload($fail_args, $ec), $next);
            $this->fail('invalid_parameter_exception expected');
        } catch (invalid_parameter_exception $ex) {
            $this->assertStringContainsString('invalid approval_level_id', $ex->getMessage());
        }

        // fails
        $fail_args = [
            'input' => [
                'approval_level_id' => '',
            ],
        ];
        try {
            $ec = execution_context::create('ajax');
            $middleware1->handle(new payload($fail_args, $ec), $next);
            $this->fail('invalid_parameter_exception expected');
        } catch (invalid_parameter_exception $ex) {
            $this->assertStringContainsString('invalid approval_level_id', $ex->getMessage());
        }

        // fails
        $fail_args = [
            'input' => [
                'approval_level_id' => 42,
            ],
        ];
        try {
            $ec = execution_context::create('ajax');
            $middleware1->handle(new payload($fail_args, $ec), $next);
            $this->fail('moodle_exception expected');
        } catch (moodle_exception $ex) {
            $this->assertStringContainsString('Invalid assignment', $ex->getMessage());
        }

        // fails
        $this->update_course_visibility($application->assignment->course_id, $user->id, false);
        try {
            $ec = execution_context::create('ajax');
            $middleware1->handle(new payload($correct_args, $ec), $next);
            $this->fail('access_denied_exception expected');
        } catch (access_denied_exception $ex) {
            $this->assertStringContainsString('Parent workflow is hidden', $ex->getMessage());
        }
        // succeeds
        $this->update_course_visibility($application->assignment->course_id, $user->id, true);
        $middleware2 = require_assignment::default_by_input_approval_level_id(false);
        $ec = execution_context::create('ajax');
        $middleware2->handle(new payload($correct_args, $ec), $next);
        try {
            $ec->get_relevant_context();
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
            $this->assertStringContainsString('Context has not been provided for this execution', $ex->getMessage());
        }
    }

    /**
     * @param integer $course_id
     * @param boolean $visibility
     */
    private function update_course_visibility(int $course_id, int $user_id, bool $visibility): void {
        // HACK: update only the cache entry totara_course_is_viewable looks for.
        $cache = cache::make('totara_core', 'totara_course_is_viewable', ['userid' => $user_id]);
        $cache->set($course_id, $visibility ? 1 : 0);
    }
}

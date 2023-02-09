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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package core
 * @category test
 */

use core\webapi\middleware\require_login_course_via_coursemodule;
use core\webapi\execution_context;
use core\webapi\resolver\payload;
use core\webapi\resolver\result;

/**
 * @coversDefaultClass \core\webapi\middleware\require_login_course_via_coursemodule
 *
 * @group core_webapi
 */
class core_webapi_middleware_require_login_course_via_coursemodule_testcase extends advanced_testcase {
    /**
     * @covers ::handle
     */
    public function test_require(): void {
        $expected = 34324;
        [$activity, $context, $next] = $this->create_test_data($expected);

        // Test with single key.
        $id_key = 'abc';
        $single_key_args = [$id_key => $activity->cmid];
        $single_key_payload = payload::create($single_key_args, $context);

        $require = new require_login_course_via_coursemodule($id_key, false);
        $result = $require->handle($single_key_payload, $next);

        $this->assertEquals($expected, $result->get_data(), 'wrong result');
        $this->assertFalse($context->has_relevant_context(), 'relevant context set');

        // Test with wrong key.
        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage('Invalid course module ID');
        $require = new require_login_course_via_coursemodule('foo', false);
        $require->handle($single_key_payload, $next);
    }

    /**
     * Generates test data.
     *
     * @param mixed $expected_result value to return as the result of the next
     *        chained "processor" after the require_activity handler.
     *
     * @return array (activity with one track and one section, graphql execution
     *         context, next handler to execute) tuple.
     * @throws coding_exception
     */
    private function create_test_data($expected_result = null): array {
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_and_enrol($course);
        $record = new \stdClass();
        $record->course = $course->id;

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');
        $activity = $generator->create_instance($record);

        $this->setUser($user);

        $next = function (payload $payload) use ($expected_result): result {
            return new result($expected_result);
        };

        $context = execution_context::create("dev");
        return [$activity, $context, $next];
    }
}

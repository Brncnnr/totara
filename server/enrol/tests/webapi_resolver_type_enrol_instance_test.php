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
 * @author David Curry <david.curry@totaralearning.com>
 * @package core_enrol
 */

defined('MOODLE_INTERNAL') || die();

use totara_webapi\phpunit\webapi_phpunit_helper;
use core\webapi\execution_context;

/**
 * @coversDefaultClass \core\webapi\resolver\type\enrol_instance
 *
 * @group core_enrol
 */
class core_webapi_type_enrol_instance_testcase extends \core_phpunit\testcase {

    use webapi_phpunit_helper;

    public $testcrs = null;
    public $control = null;

    private function resolve($field, $data, $course = null, array $args = [], $ec = null) {
        $courseid = empty($course) ? $this->testcrs->id : $course->id;

        // Set up a default execution context.
        $excontext = execution_context::create('dev');
        $context = \context_course::instance($courseid);
        $excontext->set_relevant_context($context);

        // Call the type resolver with specified arguments.
        return \core\webapi\resolver\type\enrol_instance::resolve(
            $field,
            $data,
            $args,
            $ec ?? $excontext
        );
    }

    /**
     * Take a course and return the expected data format for the type resolver
     *
     * @param object $course
     * @return array
     */
    private static function get_instances($course): array {
        $data = [];
        $supported = ['guest', 'self']; // Limit response to guest and self enrolments.
        $instances = enrol_get_instances($course->id, true); // Excludes disabled instances.
        foreach ($instances as $instance) {
            if (!in_array($instance->enrol, $supported)) {
                continue;
            }

            $data[$instance->sortorder] = $instance;
        }

        return $data;
    }

    public function setup(): void {
        global $DB;

        // Create a test and control course.
        $this->testcrs = self::getDataGenerator()->create_course(['fullname' => 'Test Course']);
        $this->control = self::getDataGenerator()->create_course(['fullname' => 'Control Crs']);

        // Get enrolment plugins.
        $selfplugin = enrol_get_plugin('self');
        $this->assertNotEmpty($selfplugin);
        $manualplugin = enrol_get_plugin('manual');
        $this->assertNotEmpty($manualplugin);

        // Get/create some roles for the instances.
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $this->assertNotEmpty($studentrole);

        $teacherrole = $DB->get_record('role', ['shortname' => 'editingteacher']);
        $this->assertNotEmpty($teacherrole);

        // Create a role to add the allowedcaps. Users will have this role assigned.
        $role = new \stdClass();
        $role->name = 'Custom Test Role';
        $role->archetype = 'editingteacher';
        $roleid = $this->getDataGenerator()->create_role();

        // Add enrolment methods for course.
        $instanceid1 = $selfplugin->add_instance(
            $this->testcrs,
            [
                'status' => ENROL_INSTANCE_ENABLED,
                'name' => 'Test instance 1',
                'customint6' => 1,
                'roleid' => $studentrole->id
            ]
        );

        $instanceid2 = $selfplugin->add_instance(
            $this->testcrs,
            [
                'status' => ENROL_INSTANCE_DISABLED,
                'name' => 'Test instance 2',
                'roleid' => $teacherrole->id
            ]
        );

        $instanceid3 = $selfplugin->add_instance(
            $this->testcrs,
            [
                'status' => ENROL_INSTANCE_ENABLED,
                'name' => 'Test instance 3',
                'roleid' => $teacherrole->id,
                'password' => 'abc123'
            ]
        );
    }

    /**
     * Unset the class variables.
     */
    public function tearDown(): void {
        $this->testcrs = null;
        $this->control = null;
    }

    public function invalid_args_data_provider(): array {
        return [
            ['Test $data type expectation failure' => 7], // Int.
            ['Test $data type expectation failure' => []], // Array.
            ['Test $data type expectation success, but empty' => new stdClass()], // valid object missing id.
        ];
    }

    /**
     * @dataProvider invalid_args_data_provider
     */
    public function test_invalid_args($invalid_arg): void {
        $user = self::getDataGenerator()->create_user();
        $this->setUser($user);

        // Test $data type expectation failure.
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Invalid data handed to enrol_instance type resolver');
        $data = $invalid_arg;
        $this->resolve('id', $data);
    }

    public function test_field_id(): void {
        $instances = self::get_instances($this->testcrs);
        $user = self::getDataGenerator()->create_user();
        $this->setUser($user);

        foreach ($instances as $instance) {
            $value = $this->resolve('id', $instance);
            $this->assertSame($instance->id, $value);
        }
    }

    public function test_field_type(): void {
        $instances = self::get_instances($this->testcrs);
        $user = self::getDataGenerator()->create_user();
        $this->setUser($user);

        foreach ($instances as $instance) {
            $value = $this->resolve('type', $instance);
            $this->assertSame($instance->enrol, $value);
        }
    }

    public function test_field_role_name(): void {
        global $DB;

        $instances = self::get_instances($this->testcrs);
        $user = self::getDataGenerator()->create_user();
        $this->setUser($user);

        foreach ($instances as $instance) {
            $context = \context_course::instance($this->testcrs->id);
            $role = $DB->get_record('role', ['id' => $instance->roleid], '*', MUST_EXIST);
            $expected = role_get_name($role, $context);

            $value = $this->resolve('role_name', $instance);
            $this->assertSame($expected, $value);
        }
    }

    public function test_field_custom_name(): void {
        $instances = self::get_instances($this->testcrs);
        $user = self::getDataGenerator()->create_user();
        $this->setUser($user);

        foreach ($instances as $instance) {
            $value = $this->resolve('custom_name', $instance);
            $expected = empty($instance->name) ? null : format_string($instance->name);
            $this->assertSame($expected, $value);
        }
    }

    public function test_field_sortorder(): void {
        $instances = self::get_instances($this->testcrs);
        $user = self::getDataGenerator()->create_user();
        $this->setUser($user);

        foreach ($instances as $instance) {
            $value = $this->resolve('sort_order', $instance);
            $this->assertSame($instance->sortorder, $value);
        }
    }

    public function test_field_password_required(): void {
        $instances = self::get_instances($this->testcrs);
        $user = self::getDataGenerator()->create_user();
        $this->setUser($user);

        foreach ($instances as $instance) {
            $expected = !empty($instance->password);
            $value = $this->resolve('password_required', $instance);
            $this->assertSame($expected, $value);
        }
    }

    public function test_field_unrecognised(): void {
        $instances = self::get_instances($this->testcrs);
        $user = self::getDataGenerator()->create_user();
        $this->setUser($user);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Unrecognised field requested for enrol_instance type: nonsensewords');
        $this->resolve('nonsensewords', array_pop($instances));
    }
}

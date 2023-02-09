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
 * @coversDefaultClass \core\webapi\resolver\type\enrol_course_info
 *
 * @group core_enrol
 */
class core_webapi_type_enrol_course_info_testcase extends \core_phpunit\testcase {

    use webapi_phpunit_helper;

    public $testcrs = null;
    public $control = null;

    private function resolve($field, $data, array $args = [], $ec = null) {

        // Set up a default execution context.
        $excontext = execution_context::create('dev');
        if (is_array($data) && isset($data['course']) && !empty($data['course']->id)) {
            $context = \context_course::instance($data['course']->id);
            $excontext->set_relevant_context($context);
        }

        // Call the type resolver with specified arguments.
        return \core\webapi\resolver\type\enrol_course_info::resolve(
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
    private static function format_data($course): array {
        // Set up some basic return data.
        $data = [
            'course' => $course,
            'instances' => [],
            'canenrol' => false,
            'guestaccess' => false,
        ];

        // Mimic the query to set up the instances etc.
        $supported = ['guest', 'self']; // Limit response to guest and self enrolments.
        $instances = enrol_get_instances($course->id, true); // Excludes disabled instances.
        foreach ($instances as $instance) {
            if (!in_array($instance->enrol, $supported)) {
                continue;
            } else if ($instance->enrol == 'guest' && empty($data['guestaccess'])) {
                $data['guestaccess'] = true;
            } else if ($instance->enrol == 'self' && empty($data['canenrol'])) {
                $data['canenrol'] = true;
            }

            $data['instances'][$instance->sortorder] = $instance;
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
            ['Test $data type expectation failure' => new stdClass()], // Object
            ['Test $data type expectation success, but empty' => []], // Array
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
        $this->expectExceptionMessage('Invalid data handed to enrol_course_info type resolver');
        $data = $invalid_arg;
        $this->resolve('id', $data);
    }

    public function test_field_is_complete(): void {
        $user = self::getDataGenerator()->create_user();
        $this->setUser($user);

        // Test the value is false as expected.
        $value = $this->resolve('is_complete', self::format_data($this->testcrs));
        $this->assertFalse($value);

        // Now enrol and complete the user to make sure the value updates.
        self::getDataGenerator()->enrol_user($user->id, $this->testcrs->id);
        $completion = new completion_completion(array('userid' => $user->id, 'course' => $this->testcrs->id));
        $completion->mark_complete();

        $value = $this->resolve('is_complete', self::format_data($this->testcrs));
        $this->assertTrue($value);

    }

    public function test_field_is_enrolled(): void {
        $user = self::getDataGenerator()->create_user();
        $this->setUser($user);

        // Test false prior to enrolment.
        $value = $this->resolve('is_enrolled', self::format_data($this->testcrs));
        $this->assertFalse($value);

        // Make sure it updates after enrolment.
        self::getDataGenerator()->enrol_user($user->id, $this->testcrs->id);
        $value = $this->resolve('is_enrolled', self::format_data($this->testcrs));
        $this->assertTrue($value);
    }

    public function test_field_guest_access(): void {
        global $DB;

        $user = self::getDataGenerator()->create_user();
        $this->setUser($user);

        // Default guest access is disabled, so check it is false.
        $value = $this->resolve('guest_access', self::format_data($this->testcrs));
        $this->assertFalse($value);

        // Now add an enabled instance and check that is is true.
        $plugin = enrol_get_plugin('guest');
        $role = $DB->get_record('role', array('shortname'=>'student'));
        $instance = $plugin->add_instance(
            $this->testcrs,
            [
                'status' => ENROL_INSTANCE_ENABLED,
                'name' => 'Guest access',
                'customint6' => 1,
                'roleid' => $role->id
            ]
        );

        $value = $this->resolve('guest_access', self::format_data($this->testcrs));
        $this->assertTrue($value);
    }

    public function test_field_can_enrol(): void {
        $user = self::getDataGenerator()->create_user();
        $this->setUser($user);

        // Lots of enrolment options for tstcourse.
        $value = $this->resolve('can_enrol', self::format_data($this->testcrs));
        $this->assertTrue($value);

        // The control course only has manual enrolment by default.
        $value = $this->resolve('can_enrol', self::format_data($this->control));
        $this->assertFalse($value);
    }

    public function test_field_can_view(): void {
        $user = self::getDataGenerator()->create_user();
        $this->setUser($user);

        $value = $this->resolve('can_view', self::format_data($this->testcrs));
        $this->assertFalse($value);

        $this->setAdminUser();
        $value = $this->resolve('can_view', self::format_data($this->testcrs));
        $this->assertTrue($value);
    }

    public function test_field_unrecognised(): void {
        $user = self::getDataGenerator()->create_user();
        $this->setUser($user);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Unrecognised field requested for enrol_course_info type: nonsensewords');
        $this->resolve('nonsensewords', self::format_data($this->testcrs));
    }
}

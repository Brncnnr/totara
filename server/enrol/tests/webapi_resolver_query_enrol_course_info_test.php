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

/**
 * @coversDefaultClass \core\webapi\resolver\query\enrol_course_info
 *
 * @group core_enrol
 */
class core_webapi_query_enrol_course_info_testcase extends \core_phpunit\testcase {

    use webapi_phpunit_helper;

    public $testcrs = null;
    public $control = null;

    /**
     * Set up a couple of courses with some enrolment instances for testing
     */
    public function setUp(): void {
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

    public function test_no_login(): void {
        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Course or activity not accessible. (You are not logged in)');

        $result = $this->resolve_graphql_query('core_enrol_course_info', ['courseid' => $this->testcrs->id]);
    }

    public function test_resolve_without_args(): void {
        $user = self::getDataGenerator()->create_user();
        $this->setUser($user);

        // Test the query without any args.
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Missing courseid argument for enrol_course_info query');

        $this->resolve_graphql_query('core_enrol_course_info', []);
    }

    public function test_resolve_invalid_courseid(): void {
        $user = self::getDataGenerator()->create_user();
        $this->setUser($user);

        // Test the query with an invalid courseid.
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Invalid courseid argument for enrol_course_info query');

        $this->resolve_graphql_query('core_enrol_course_info', ['courseid' => $this->testcrs->id * 5]);
    }

    public function test_resolve_user(): void {
        $user = self::getDataGenerator()->create_user();
        $this->setUser($user);

        $result = $this->resolve_graphql_query('core_enrol_course_info', ['courseid' => $this->testcrs->id]);
        $this->assertTrue(is_array($result));
        $this->assertNotEmpty($result['course']);
        $this->assertEquals($this->testcrs->id, $result['course']->id);
        $this->assertTrue($result['canenrol']);
        $this->assertFalse($result['guestaccess']);
        $this->assertNotEmpty($result['instances']);
        $this->assertCount(2, $result['instances']);
    }

    public function test_resolve_admin(): void {
        $user = self::getDataGenerator()->create_user();
        $this->setUser($user);

        $result = $this->resolve_graphql_query('core_enrol_course_info', ['courseid' => $this->testcrs->id]);
        $this->assertTrue(is_array($result));
        $this->assertNotEmpty($result['course']);
        $this->assertEquals($this->testcrs->id, $result['course']->id);
        $this->assertTrue($result['canenrol']);
        $this->assertFalse($result['guestaccess']);
        $this->assertNotEmpty($result['instances']);
        $this->assertCount(2, $result['instances']);
    }
}

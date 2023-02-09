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
 * @package mobile_findlearning
 */

defined('MOODLE_INTERNAL') || die();

use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @coversDefaultClass \mobile_findlearning\webapi\resolver\query\validate_guest_password
 *
 * @group mobile_findlearning
 */
class mobile_webapi_resolver_query_validate_guest_password_testcase extends \core_phpunit\testcase {

    use webapi_phpunit_helper;

    public $testcrs = null;
    public $control = null;
    public $instances = null;

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

        // Get/create some roles for the instances.
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $this->assertNotEmpty($studentrole);

        $teacherrole = $DB->get_record('role', ['shortname' => 'editingteacher']);
        $this->assertNotEmpty($teacherrole);

        // Add enrolment methods for course.
        $this->instances[1] = $selfplugin->add_instance(
            $this->testcrs,
            [
                'status' => ENROL_INSTANCE_ENABLED,
                'name' => 'Test instance 1',
                'customint6' => 1,
                'roleid' => $studentrole->id
            ]
        );
    }

    /**
     * Add a guest instance with optional passsword to the specified course
     *
     * @param object $course
     * @param string $password - optional
     *
     * @return int - The id of a guest instance now associated with the course
     */
    private static function add_guest_instance($course, $password = ''): int {
        global $DB;

        $plugin = enrol_get_plugin('guest');
        self::assertNotEmpty($plugin);

        // Get/create some roles for the instances.
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        self::assertNotEmpty($studentrole);

        // Add enrolment methods for course.
        $instance = $plugin->add_instance(
            $course,
            [
                'status' => ENROL_INSTANCE_ENABLED,
                'name' => 'Guest instance',
                'roleid' => $studentrole->id,
                'password' => $password ?? null
            ]
        );

        return $instance;
    }

    /**
     * Unset the class variables.
     */
    public function tearDown(): void {
        $this->testcrs = null;
        $this->control = null;
        $this->instances = null;
    }

    private function resolve(array $args = []) {
        $excontext = $this->get_execution_context();

        return \mobile_findlearning\webapi\resolver\query\validate_guest_password::resolve(
            $args,
            $excontext
        );
    }

    private function get_execution_context(string $type = 'dev', ?string $operation = null) {
        return \core\webapi\execution_context::create($type, $operation);
    }

    /**
     * Test the mutation when not logged in.
     */
    public function test_no_login(): void {
        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Course or activity not accessible. (You are not logged in)');

        $result = $this->resolve_graphql_query(
            'mobile_findlearning_validate_guest_password',
            []
        );
    }

    /**
     * Test the mutation handling all the invalid parameters,
     * password is optional and will be handled elsewhere.
     * 1) No params
     * 2) Missing instanceid
     * 3) Missing courseid
     * 4) Mismatched instanceid and courseid
     */
    public function test_invalid_parameters(): void {
        global $DB;

        $user = self::getDataGenerator()->create_user();
        $this->setUser($user);

        $this->assertEquals(0, $DB->count_records('user_enrolments', ['userid' => $user->id]));

        try {
            $this->resolve([
                'input' => [
                ]
            ]);
            $this->fail('Coding exception expected');
        } catch (\coding_exception $e) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: missing arguments for validate_guest_password query',
                $e->getMessage()
            );
        }

        try {
            $this->resolve([
                'input' => [
                    'courseid' =>  $this->testcrs->id,
                ]
            ]);
            $this->fail('Coding exception expected');
        } catch (\coding_exception $e) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: missing arguments for validate_guest_password query',
                $e->getMessage()
            );
        }

        try {
            $this->resolve([
                'input' => [
                    'instanceid' => $this->instances[1]
                ]
            ]);
            $this->fail('Coding exception expected');
        } catch (\coding_exception $e) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: missing arguments for validate_guest_password query',
                $e->getMessage()
            );
        }

        try {
            $this->resolve([
                'input' => [
                    'courseid' =>  $this->control->id,
                    'instanceid' => $this->instances[1]
                ]
            ]);
            $this->fail('Coding exception expected');
        } catch (\coding_exception $e) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: invalid arguments for validate_guest_password query',
                $e->getMessage()
            );
        }
    }

    /**
     * Test the mutation when attempting to use a non self enrolment plugin
     */
    public function test_unsupported_enrolment_plugins(): void {
        global $DB;

        $user = self::getDataGenerator()->create_user();
        $this->setUser($user);

        // Expect valid arguments if not enabled or not an instance of 'self'.
        // Note: expectException doesn't let you test multiple things, it dies after 1 exception.
        try {
            $this->resolve([
                'input' => [
                    'courseid' =>  $this->testcrs->id,
                    'instanceid' => $this->instances[1]
                ]
            ]);
            $this->fail('Coding exception expected');
        } catch (\coding_exception $e) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: invalid arguments for validate_guest_password query',
                $e->getMessage()
            );
        }
    }

    /**
     * Test successful self enrolment
     */
    public function test_guest_access_success(): void {
        global $DB;

        $user = self::getDataGenerator()->create_user();
        $this->setUser($user);

        $instanceid = self::add_guest_instance($this->testcrs);

        $result = $this->resolve([
            'input' => [
                'courseid' =>  $this->testcrs->id,
                'instanceid' => $instanceid
            ]
        ]);

        $this->assertNotEmpty($result);
        $this->assertTrue($result['success']);
        $this->assertEmpty($result['message']);

        $this->assertEquals(0, $DB->count_records('user_enrolments', ['userid' => $user->id]));
    }

    /**
     * Test attempting to self enrol without a required password
     * Expectation: Exception, missing required params.
     */
    public function test_guest_access_password_missing(): void {
        global $DB;

        $user = self::getDataGenerator()->create_user();
        $this->setUser($user);

        $instanceid = self::add_guest_instance($this->testcrs, 'abc1233');

        try {
            $this->resolve([
                'input' => [
                    'courseid' =>  $this->testcrs->id,
                    'instanceid' => $instanceid
                ]
            ]);
            $this->fail('Coding exception expected');
        } catch (\coding_exception $e) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: missing arguments for validate_guest_password query',
                $e->getMessage()
            );
        }

        $this->assertEquals(0, $DB->count_records('user_enrolments', ['userid' => $user->id]));
    }

    /**
     * Test attempting to access guest with an incorrect password
     * Expectation: No exception, result should be false with a warning.
     */
    public function test_guest_access_password_failure(): void {
        global $DB;

        $user = self::getDataGenerator()->create_user();
        $this->setUser($user);

        $instanceid = self::add_guest_instance($this->testcrs, 'abc123');

        $result = $this->resolve([
            'input' => [
                'courseid' =>  $this->testcrs->id,
                'instanceid' => $instanceid,
                'password' => 'xyz987'
            ]
        ]);

        $this->assertNotEmpty($result);
        $this->assertFalse($result['success']);
        $this->assertSame('Incorrect access password, please try again', $result['message']);

        $this->assertEquals(0, $DB->count_records('user_enrolments', ['userid' => $user->id]));
    }

    /**
     * Test attempting to self enrol with a password when it is not required.
     * Expectation: This should work, it's a bit weird though.
     */
    public function test_guest_access_password_extraneous(): void {
        global $DB;

        $user = self::getDataGenerator()->create_user();
        $this->setUser($user);

        $instanceid = self::add_guest_instance($this->testcrs);

        $result = $this->resolve([
            'input' => [
                'courseid' =>  $this->testcrs->id,
                'instanceid' => $instanceid,
                'password' => 'xyz987'
            ]
        ]);

        $this->assertNotEmpty($result);
        $this->assertTrue($result['success']);
        $this->assertEmpty($result['message']);

        $this->assertEquals(0, $DB->count_records('user_enrolments', ['userid' => $user->id]));
    }

    /**
     * Test successful self enrolment with a password
     * Expectation: This should work, it's a bit weird though.
     */
    public function test_guest_access_password_success(): void {
        global $DB;

        $user = self::getDataGenerator()->create_user();
        $this->setUser($user);

        $instanceid = self::add_guest_instance($this->testcrs, 'abc123');

        $result = $this->resolve([
            'input' => [
                'courseid' =>  $this->testcrs->id,
                'instanceid' => $instanceid,
                'password' => 'abc123'
            ]
        ]);

        $this->assertNotEmpty($result);
        $this->assertTrue($result['success']);
        $this->assertEmpty($result['message']);

        $this->assertEquals(0, $DB->count_records('user_enrolments', ['userid' => $user->id]));
    }
}

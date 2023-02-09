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
 * @coversDefaultClass \core\webapi\resolver\mutation\enrol_attempt_self_enrolment
 *
 * @group core_enrol
 */
class core_webapi_mutation_enrol_attempt_self_enrolment_testcase extends \core_phpunit\testcase {

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
        $manualplugin = enrol_get_plugin('manual');
        $this->assertNotEmpty($manualplugin);

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

        $this->instances[2] = $selfplugin->add_instance(
            $this->testcrs,
            [
                'status' => ENROL_INSTANCE_DISABLED,
                'name' => 'Test instance 2',
                'customint6' => 0,
                'roleid' => $teacherrole->id
            ]
        );

        $this->instances[3] = $selfplugin->add_instance(
            $this->testcrs,
            [
                'status' => ENROL_INSTANCE_ENABLED,
                'name' => 'Test instance 3',
                'customint6' => 1,
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
        $this->instances = null;
    }

    private function resolve(array $args = []) {
        $excontext = $this->get_execution_context();

        return \core\webapi\resolver\mutation\enrol_attempt_self_enrolment::resolve(
            $args,
            $excontext
        );
    }

    private function get_execution_context(string $type = 'dev', ?string $operation = null): execution_context {
        return execution_context::create($type, $operation);
    }

    /**
     * Test the mutation when not logged in.
     */
    public function test_no_login(): void {
        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Course or activity not accessible. (You are not logged in)');

        $result = $this->resolve_graphql_mutation(
            'core_enrol_attempt_self_enrolment',
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
            $this->resolve(
                [
                    'input' => [
                    ]
                ]
            );
            $this->fail('Coding exception expected');
        } catch (\coding_exception $e) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: missing arguments for attempt_self_enrolment mutation',
                $e->getMessage()
            );
        }

        try {
            $this->resolve(
                [
                    'input' => [
                        'courseid' =>  $this->testcrs->id,
                    ]
                ]
            );
            $this->fail('Coding exception expected');
        } catch (\coding_exception $e) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: missing arguments for attempt_self_enrolment mutation',
                $e->getMessage()
            );
        }

        try {
            $this->resolve(
                [
                    'input' => [
                        'instanceid' => $this->instances[1]
                    ]
                ]
            );
            $this->fail('Coding exception expected');
        } catch (\coding_exception $e) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: missing arguments for attempt_self_enrolment mutation',
                $e->getMessage()
            );
        }

        try {
            $this->resolve(
                [
                    'input' => [
                        'courseid' =>  $this->control->id,
                        'instanceid' => $this->instances[1]
                    ]
                ]
            );
            $this->fail('Coding exception expected');
        } catch (\coding_exception $e) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: invalid arguments for attempt_self_enrolment mutation',
                $e->getMessage()
            );
        }

        // Make sure none of these ended up creating an enrolment.
        $this->assertEquals(0, $DB->count_records('user_enrolments', ['userid' => $user->id]));
    }

    /**
     * Test the mutation when attempting to use a non self enrolment plugin
     */
    public function test_unsupported_enrolment_plugins(): void {
        global $DB;

        $user = self::getDataGenerator()->create_user();
        $this->setUser($user);

        $plugins = enrol_get_plugins(false);
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        foreach ($plugins as $name => $plugin) {
            // Skip self since that one should work, and lti because it's being a pain.
            $skipme = ['self', 'lti'];
            if (in_array($name, $skipme)) {
                continue;
            }

            $exists = ['manual']; // There can be only one.
            if (in_array($name, $exists)) {
                $instanceid = $DB->get_field('enrol', 'id', ['enrol' => $name, 'courseid' => $this->testcrs->id]);
            } else {
                // Everything else, add to the test course and attempt to use.
                $instanceid = $plugin->add_instance(
                    $this->testcrs,
                    [
                        'status' => ENROL_INSTANCE_ENABLED,
                        'name' => 'Test plugin ' . $name,
                        'roleid' => $studentrole->id,
                        'customint1' => 1
                    ]
                );
            }

            // Expect valid arguments if not enabled or not an instance of 'self'.
            // Note: expectException doesn't let you test multiple things, it dies after 1 exception.
            try {
                $this->resolve(
                    [
                        'input' => [
                            'courseid' =>  $this->testcrs->id,
                            'instanceid' => $instanceid
                        ]
                    ]
                );
                $this->fail('Coding exception expected');
            } catch (\coding_exception $e) {
                $this->assertSame(
                    'Coding error detected, it must be fixed by a programmer: invalid arguments for attempt_self_enrolment mutation',
                    $e->getMessage()
                );
            }
        }

        $this->assertEquals(0, $DB->count_records('user_enrolments', ['userid' => $user->id]));
    }

    /**
     * Test attempted self enrolment for pre-enrolled user.
     *
     * This should allow enrolment the first time even though the user already has a manual enrolment,
     * But should fail if a second attempt is made with the same enrolment instance.
     */
    public function test_preenrolled_user(): void {
        global $DB;

        $user = self::getDataGenerator()->create_user();
        $this->setUser($user);

        self::getDataGenerator()->enrol_user($user->id, $this->testcrs->id);

        $this->assertEquals(1, $DB->count_records('user_enrolments', ['userid' => $user->id]));

        // First try should be successfull.
        $result = $this->resolve_graphql_mutation(
            'core_enrol_attempt_self_enrolment',
            [
                'input' => [
                    'courseid' =>  $this->testcrs->id,
                    'instanceid' => $this->instances[1]
                ]
            ]
        );

        $this->assertNotEmpty($result);
        $this->assertTrue($result['success']);
        $this->assertEmpty($result['msg_key']);
        $this->assertEquals(2, $DB->count_records('user_enrolments', ['userid' => $user->id]));

        // Second try should complain about reusing instances.
        $result = $this->resolve_graphql_mutation(
            'core_enrol_attempt_self_enrolment',
            [
                'input' => [
                    'courseid' =>  $this->testcrs->id,
                    'instanceid' => $this->instances[1]
                ]
            ]
        );

        $this->assertNotEmpty($result);
        $this->assertFalse($result['success']);
        $this->assertSame('Enrolment is disabled or inactive', $result['msg_key']);
        $this->assertEquals(2, $DB->count_records('user_enrolments', ['userid' => $user->id]));
    }

    /**
     * Test successful self enrolment
     */
    public function test_self_enrolment_success(): void {
        global $DB;

        $user = self::getDataGenerator()->create_user();
        $this->setUser($user);

        $this->assertEquals(0, $DB->count_records('user_enrolments', ['userid' => $user->id]));

        $result = $this->resolve_graphql_mutation(
            'core_enrol_attempt_self_enrolment',
            [
                'input' => [
                    'courseid' =>  $this->testcrs->id,
                    'instanceid' => $this->instances[1]
                ]
            ]
        );

        $this->assertNotEmpty($result);
        $this->assertTrue($result['success']);
        $this->assertEmpty($result['msg_key']);

        $this->assertEquals(1, $DB->count_records('user_enrolments', ['userid' => $user->id]));
    }

    /**
     * Test attempting to self enrol without a required password
     * Expectation: Exception, missing required params.
     */
    public function test_self_enrolment_password_missing(): void {
        global $DB;

        $user = self::getDataGenerator()->create_user();
        $this->setUser($user);

        $this->assertEquals(0, $DB->count_records('user_enrolments', ['userid' => $user->id]));

        try {
            $this->resolve(
                [
                    'input' => [
                        'courseid' =>  $this->testcrs->id,
                        'instanceid' => $this->instances[3],
                    ]
                ]
            );
            $this->fail('Coding exception expected');
        } catch (\coding_exception $e) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: invalid arguments for attempt_self_enrolment mutation',
                $e->getMessage()
            );
        }

        $this->assertEquals(0, $DB->count_records('user_enrolments', ['userid' => $user->id]));
    }

    /**
     * Test attempting to self enrol with an incorrect password
     * Expectation: No exception, result should be false with a warning.
     */
    public function test_self_enrolment_password_failure(): void {
        global $DB;

        $user = self::getDataGenerator()->create_user();
        $this->setUser($user);

        $this->assertEquals(0, $DB->count_records('user_enrolments', ['userid' => $user->id]));

        $result = $this->resolve_graphql_mutation(
            'core_enrol_attempt_self_enrolment',
            [
                'input' => [
                    'courseid' =>  $this->testcrs->id,
                    'instanceid' => $this->instances[3],
                    'password' => 'xyz987' // Should be abc123.
                ]
            ]
        );

        $this->assertNotEmpty($result);
        $this->assertFalse($result['success']);
        $this->assertSame('Incorrect enrolment key, please try again', $result['msg_key']);

        $this->assertEquals(0, $DB->count_records('user_enrolments', ['userid' => $user->id]));
    }

    /**
     * Test attempting to self enrol with a password when it is not required.
     * Expectation: This should work, it's a bit weird though.
     */
    public function test_self_enrolment_password_extraneous(): void {
        global $DB;

        $user = self::getDataGenerator()->create_user();
        $this->setUser($user);

        $this->assertEquals(0, $DB->count_records('user_enrolments', ['userid' => $user->id]));

        $result = $this->resolve_graphql_mutation(
            'core_enrol_attempt_self_enrolment',
            [
                'input' => [
                    'courseid' =>  $this->testcrs->id,
                    'instanceid' => $this->instances[1], // No password required.
                    'password' => 'abc123'
                ]
            ]
        );

        $this->assertNotEmpty($result);
        $this->assertTrue($result['success']);
        $this->assertEmpty($result['msg_key']);

        $this->assertEquals(1, $DB->count_records('user_enrolments', ['userid' => $user->id]));
    }

    /**
     * Test successful self enrolment with a password
     * Expectation: This should work, it's a bit weird though.
     */
    public function test_self_enrolment_password_success(): void {
        global $DB;

        $user = self::getDataGenerator()->create_user();
        $this->setUser($user);

        $this->assertEquals(0, $DB->count_records('user_enrolments', ['userid' => $user->id]));

        $result = $this->resolve_graphql_mutation(
            'core_enrol_attempt_self_enrolment',
            [
                'input' => [
                    'courseid' =>  $this->testcrs->id,
                    'instanceid' => $this->instances[3],
                    'password' => 'abc123'
                ]
            ]
        );

        $this->assertNotEmpty($result);
        $this->assertTrue($result['success']);
        $this->assertEmpty($result['msg_key']);

        $this->assertEquals(1, $DB->count_records('user_enrolments', ['userid' => $user->id]));
    }
}

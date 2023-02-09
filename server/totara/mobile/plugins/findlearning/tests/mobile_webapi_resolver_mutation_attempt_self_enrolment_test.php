<?php
/*
 * This file is part of Totara LMS
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author David Curry <david.curry@totaralearning.com>
 * @package mobile_findlearning
 */

defined('MOODLE_INTERNAL') || die();

use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * Note: This only tests the mobile embedded query, the rest of the tests are in lib
 */
class mobile_findlearning_webapi_resolver_mutation_attempt_self_enrolment extends \core_phpunit\testcase {

    use webapi_phpunit_helper;

    /**
     * Test the results of the embedded mobile mutation through the GraphQL stack.
     */
    public function test_embedded_mutation() {
        global $DB;

        // Create a test and control course.
        $testcrs = self::getDataGenerator()->create_course(['fullname' => 'Test Course']);

        // Get enrolment plugins.
        $selfplugin = enrol_get_plugin('self');
        $this->assertNotEmpty($selfplugin);

        // Get/create some roles for the instances.
        $studentrole = $DB->get_record('role', array('shortname'=>'student'));
        $this->assertNotEmpty($studentrole);

        // Create a role to add the allowedcaps. Users will have this role assigned.
        $role = new \stdClass();
        $role->name = 'Custom Test Role';
        $role->archetype = 'editingteacher';
        $roleid = $this->getDataGenerator()->create_role();

        // Add enrolment methods for course.
        $instanceid = $selfplugin->add_instance(
            $testcrs,
            [
                'status' => ENROL_INSTANCE_ENABLED,
                'name' => 'Test instance 1',
                'customint6' => 1,
                'roleid' => $studentrole->id,
                'password' => 'abc123'
            ]
        );

        $context = \context_course::instance($testcrs->id);
        $user = self::getDataGenerator()->create_user();
        $this->setUser($user);

        try {
            $result = \totara_webapi\graphql::execute_operation(
                \core\webapi\execution_context::create(
                    'mobile',
                    'mobile_findlearning_attempt_self_enrolment'
                ),
                [
                    'input' => [
                        'courseid' => $testcrs->id,
                        'instanceid' => $instanceid,
                        'password' => 'abc123'
                    ]
                ]
            );

            $data = $result->toArray()['data'];
            $this->assertNotEmpty($data['mobile_findlearning_enrolment_result']);
            $info = $data['mobile_findlearning_enrolment_result'];

            $expected = [
                'success' => true,
                'msgKey' => null,
                '__typename' => 'core_enrol_attempt_self_enrolment_result'

            ];

            $this->assertSame($expected, $info);
        } catch (\moodle_exception $ex) {
            $this->fail($ex->getMessage());
        }
    }
}

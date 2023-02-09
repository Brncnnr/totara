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

use container_workspace\testing\generator as workspace_generator;
use container_workspace\webapi\resolver\mutation\add_audiences;
use core_phpunit\testcase;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @group container_workspace
 * @group totara_engage
 */
class container_workspace_webapi_add_audiences_testcase extends testcase {

    use webapi_phpunit_helper;

    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();

        global $CFG;
        require_once($CFG->dirroot . '/cohort/lib.php');
    }

    /**
     * @return void
     */
    public function test_audience_enrol_admin(): void {
        [$users, $cohorts] = $this->make_users_and_audiences();
        $this->setAdminUser();

        $workspace = $this->workspace_generator()->create_workspace(
            null,
            null,
            null,
            $users['u1']->id
        );

        $this->assert_enrolment_tests(
            $workspace->id,
            $cohorts,
        );
    }

    /**
     * @return void
     */
    public function test_audience_enrol_owner(): void {
        [$users, $cohorts] = $this->make_users_and_audiences();
        $this->setUser($users['u1']->id);

        $workspace = $this->workspace_generator()->create_workspace();

        // Owner needs to have the capability against the workspace
        $owner_role = current(get_archetype_roles('workspaceowner'));
        $context = $workspace->get_context();
        assign_capability(
            'moodle/cohort:view',
            CAP_ALLOW,
            $owner_role->id,
            $context->id
        );

        $this->assert_enrolment_tests(
            $workspace->id,
            $cohorts,
        );
    }

    /**
     * Assert that an invalid workspace ID will fail the request
     *
     * @return void
     */
    public function test_audience_input_invalid_workspace(): void {
        $this->setAdminUser();

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Invalid workspace');

        $this->call_mutation(123456789, [1234]);
    }

    /**
     * Assert that an invalid container ID will fail the request
     *
     * @return void
     */
    public function test_audience_input_invalid_workspace_type(): void {
        $this->setAdminUser();

        $course = self::getDataGenerator()->create_course();

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Invalid workspace');

        $this->call_mutation($course->id, [1234]);
    }

    /**
     * Assert that empty audience IDs will fail the request
     *
     * @return void
     */
    public function test_audience_input_missing_ids(): void {
        $this->setAdminUser();
        $workspace0 = $this->workspace_generator()->create_workspace();

        $response = $this->call_mutation($workspace0->id, []);
        $this->assertArrayHasKey('audience_ids', $response);
        $this->assertEmpty($response['audience_ids']);
    }

    /**
     * Assert that an invalid container ID will fail the request
     *
     * @return void
     */
    public function test_audience_input_non_owner(): void {

        $generator = self::getDataGenerator();
        $user1 = $generator->create_user();
        $user2 = $generator->create_user();

        $this->setUser($user1);
        $workspace = $this->workspace_generator()->create_workspace();

        $this->setUser($user2);

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Invalid workspace');

        $this->call_mutation($workspace->id, [1234]);
    }

    /**
     * Assert that an invalid container ID will fail the request
     *
     * @return void
     */
    public function test_audience_input_owner_no_cap(): void {

        $generator = self::getDataGenerator();
        $user1 = $generator->create_user();

        $this->setUser($user1);
        $workspace = $this->workspace_generator()->create_workspace();

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Invalid workspace');

        $this->call_mutation($workspace->id, [1234]);
    }

    /**
     * Call the mutation with the provided input
     *
     * @param int|null $workspace_id
     * @param array $audience_ids
     * @return mixed|null
     */
    protected function call_mutation(?int $workspace_id, array $audience_ids) {
        $graphql_name = $this->get_graphql_name(add_audiences::class);
        return $this->resolve_graphql_mutation(
            $graphql_name,
            [
                'input' => compact('workspace_id', 'audience_ids'),
            ]
        );
    }

    /**
     * @return moodle_database
     */
    protected function db(): moodle_database {
        return $GLOBALS['DB'];
    }

    /**
     * Generates sample users mapped to specific audiences
     *
     * @return array
     */
    protected function make_users_and_audiences(): array {
        $generator = self::getDataGenerator();

        $cohorts_to_users = [
            'c1' => ['u1'],
            'c2' => ['u2'],
            'c3' => ['u3'],
            'c4' => ['u1', 'u2'],
            'c5' => ['u2', 'u3'],
        ];

        $users = [];
        $cohorts = [];

        foreach ($cohorts_to_users as $cohort_name => $user_names) {
            $cohort = $generator->create_cohort(
                [
                    'name' => $cohort_name,
                    'description' => ':' . join(':', $user_names) . ':'
                ]
            );
            $cohorts[$cohort_name] = $cohort;

            foreach ($user_names as $user_name) {
                $user = $users[$user_name] ?? null;
                if (empty($user)) {
                    $user = $generator->create_user(['username' => $user_name]);
                    $users[$user_name] = $user;
                }

                cohort_add_member($cohort->id, $user->id);
            }
        }

        return [$users, $cohorts, $cohorts_to_users];
    }

    /**
     * @return workspace_generator
     */
    protected function workspace_generator(): workspace_generator {
        /** @var workspace_generator $generator */
        $generator = self::getDataGenerator()->get_plugin_generator('container_workspace');
        return $generator;
    }

    /**
     * Runs specific tests for both admin & owner
     *
     * @param int $workspace_id
     * @param array $cohorts
     * @return void
     */
    private function assert_enrolment_tests(int $workspace_id, array $cohorts): void {
        // Confirm the workspace has no assigned cohorts
        $instances = $this->db()->get_records('enrol', ['courseid' => $workspace_id, 'enrol' => 'cohort']);
        $this->assertEmpty($instances);

        $keys = [$cohorts['c2']->id, $cohorts['c3']->id];

        $saved_ids = $this->call_mutation($workspace_id, $keys);
        $this->assertArrayHasKey('audience_ids', $saved_ids);
        $this->assertEqualsCanonicalizing($keys, $saved_ids['audience_ids']);

        $instances = $this->get_instances($workspace_id);
        $this->assertNotEmpty($instances);
        $this->assertCount(2, $instances);
        $this->assertEqualsCanonicalizing($keys, array_keys($instances));

        // Try and add it again
        $saved_ids = $this->call_mutation($workspace_id, [$cohorts['c2']->id]);
        $this->assertArrayHasKey('audience_ids', $saved_ids);
        $this->assertEmpty($saved_ids['audience_ids']);

        $instances = $this->get_instances($workspace_id);
        $this->assertNotEmpty($instances);
        $this->assertCount(2, $instances);
        $this->assertEqualsCanonicalizing($keys, array_keys($instances));

        // Add the last in
        $keys[] = $cohorts['c1']->id;

        $saved_ids = $this->call_mutation($workspace_id, [$cohorts['c1']->id]);
        $this->assertArrayHasKey('audience_ids', $saved_ids);
        $this->assertEqualsCanonicalizing([$cohorts['c1']->id], $saved_ids['audience_ids']);

        $instances = $this->get_instances($workspace_id);
        $this->assertNotEmpty($instances);
        $this->assertCount(3, $instances);
        $this->assertEqualsCanonicalizing($keys, array_keys($instances));
    }

    /**
     * Fetch the enrolment instances
     *
     * @param int $workspace_id
     * @return array
     */
    private function get_instances(int $workspace_id): array {
        return $this
            ->db()
            ->get_records(
                'enrol',
                [
                    'courseid' => $workspace_id,
                    'enrol' => 'cohort'
                ],
                '',
                'customint1'
            );
    }
}
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

use container_workspace\enrol\manager;
use container_workspace\local\workspace_helper;
use container_workspace\testing\generator as workspace_generator;
use container_workspace\webapi\resolver\mutation\remove_audience;
use core\entity\enrol;
use core\entity\user_enrolment;
use core_phpunit\testcase;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @group container_workspace
 * @group totara_engage
 */
class container_workspace_webapi_remove_audience_testcase extends testcase {

    use webapi_phpunit_helper;

    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();

        global $CFG;
        require_once($CFG->dirroot . '/cohort/lib.php');
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

        $this->call_mutation(123456789, 1234);
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

        $this->call_mutation($course->id, 1234);
    }

    /**
     * Assert that empty audience ID will fail the request
     *
     * @return void
     */
    public function test_audience_input_missing_id(): void {
        $this->setAdminUser();
        $workspace0 = $this->workspace_generator()->create_workspace();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('No audience_id provided');

        $this->call_mutation($workspace0->id, 0);
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

        $this->call_mutation($workspace->id, 1234);
    }

    /**
     * Assert that a missing capability will fail the request
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

        $this->call_mutation($workspace->id, 1234);
    }

    /**
     * @return void
     */
    public function test_audience_remove_with_owner(): void {
        $generator = self::getDataGenerator();

        $user = $generator->create_user();
        $this->setUser($user);

        $workspace = $this->workspace_generator()->create_workspace();

        $this->setAdminUser();

        // Owner needs to have the capability against the workspace
        $owner_role = current(get_archetype_roles('workspaceowner'));
        $context = $workspace->get_context();
        assign_capability(
            'moodle/cohort:view',
            CAP_ALLOW,
            $owner_role->id,
            $context->id
        );

        $cohort = $generator->create_cohort();
        $manager = manager::from_workspace($workspace);
        $manager->enrol_audiences([$cohort->id]);

        // Confirm the audience is part of the workspace
        $existing = $this->get_instances($workspace->get_id());
        $this->assertCount(1, $existing);
        $this->assertArrayHasKey($cohort->id, $existing);

        $this->setUser($user->id);

        // Now remove the audience
        $this->call_mutation($workspace->get_id(), $cohort->id);

        $existing = $this->get_instances($workspace->get_id());
        $this->assertEmpty($existing);
    }

    public function test_audience_removed(): void {
        $generator = self::getDataGenerator();
        $workspace_generator = $this->workspace_generator();

        $this->setAdminUser();

        $workspace = $workspace_generator->create_workspace();
        $cohort = $generator->create_cohort();

        $manager = manager::from_workspace($workspace);
        $manager->enrol_audiences([$cohort->id]);

        // Confirm the audience is part of the workspace
        $existing = $this->get_instances($workspace->get_id());
        $this->assertCount(1, $existing);
        $this->assertArrayHasKey($cohort->id, $existing);

        // Now remove the audience
        $this->call_mutation($workspace->get_id(), $cohort->id);

        $existing = $this->get_instances($workspace->get_id());
        $this->assertEmpty($existing);
    }

    /**
     * Assert that if the owner of a workspace only exists via an audience, that
     * the workspace record is updated when the audience is removed.
     *
     * @return void
     */
    public function test_workspace_owner_is_reset_when_audience_removed(): void {
        $generator = self::getDataGenerator();
        $workspace_generator = $this->workspace_generator();

        $this->setAdminUser();
        $user_one = $generator->create_user();
        $workspace = $workspace_generator->create_workspace();
        $cohort = $generator->create_cohort();

        // Enrol user_one inside the audience
        cohort_add_member($cohort->id, $user_one->id);

        // Add the audience to the workspace
        $manager = manager::from_workspace($workspace);
        $manager->enrol_audiences([$cohort->id]);
        $this->executeAdhocTasks();

        // Confirm the user_one is a member of the workspace
        $count = user_enrolment::repository()->as('ue')
            ->join([enrol::TABLE, 'e'], 'ue.enrolid', 'e.id')
            ->where('ue.userid', $user_one->id)
            ->where('e.courseid', $workspace->get_id())
            ->count();
        $this->assertSame(1, $count);

        // Confirm user_one is not the owner of the workspace
        $this->assertNotEquals($user_one->id, $workspace->get_user_id());

        // Make user_one the owner
        workspace_helper::update_workspace_primary_owner($workspace, $user_one->id);
        $this->executeAdhocTasks();
        $this->assertEquals($user_one->id, $workspace->get_user_id());

        // Remove the audience
        $this->call_mutation($workspace->get_id(), $cohort->id);
        $this->executeAdhocTasks();

        // Confirm user_one is no longer a member of the workspace
        $count = user_enrolment::repository()->as('ue')
            ->join([enrol::TABLE, 'e'], 'ue.enrolid', 'e.id')
            ->where('ue.userid', $user_one->id)
            ->where('e.courseid', $workspace->get_id())
            ->count();
        $this->assertSame(0, $count);

        $workspace->reload();

        // Confirm the workspace primary owner is empty
        $this->assertEmpty($workspace->get_user_id());
    }

    /**
     * Call the mutation with the provided input
     *
     * @param int $workspace_id
     * @param array $audience_ids
     * @return mixed|null
     */
    protected function call_mutation(int $workspace_id, int $audience_id) {
        $graphql_name = $this->get_graphql_name(remove_audience::class);
        return $this->resolve_graphql_mutation(
            $graphql_name,
            [
                'input' => compact('workspace_id', 'audience_id'),
            ]
        );
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
     * @return moodle_database
     */
    private function db(): moodle_database {
        return $GLOBALS['DB'];
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
<?php
/*
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package mod_perform
 * @category test
 */

use core\entity\tenant;
use core\entity\user;
use core\orm\entity\repository as entity_repository;
use core\orm\query\builder;
use mod_perform\dates\date_offset;
use mod_perform\dates\resolvers\dynamic\dynamic_source;
use mod_perform\dates\resolvers\dynamic\user_creation_date;
use mod_perform\dates\resolvers\dynamic\user_custom_field;
use mod_perform\entity\activity\activity;
use mod_perform\entity\activity\track as track_entity;
use mod_perform\entity\activity\track_assignment;
use mod_perform\entity\activity\track_user_assignment;
use mod_perform\event\track_user_assigned_bulk;
use mod_perform\event\track_user_unassigned;
use mod_perform\expand_task;
use mod_perform\models\activity\activity as activity_model;
use mod_perform\models\activity\track;
use mod_perform\models\activity\track_assignment_type;
use mod_perform\models\activity\track_status;
use mod_perform\state\activity\draft;
use mod_perform\user_groups\grouping;
use mod_perform\util;
use totara_core\dates\date_time_setting;
use totara_job\job_assignment;
use totara_tenant\local\util as tenant_util;

defined('MOODLE_INTERNAL') || die();

/**
 * @coversDefaultClass \mod_perform\expand_task
 *
 * @group perform
 */
class mod_perform_expand_task_testcase extends advanced_testcase {

    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();
        global $CFG;
        require_once($CFG->dirroot . '/cohort/lib.php');
    }

    /**
     * Date generator shortcut
     *
     * @return \core\testing\generator
     */
    protected function generator(): \core\testing\generator {
        return self::getDataGenerator();
    }

    protected function setUp(): void {
        parent::setUp();
        $this->setAdminUser();
    }

    protected function tearDown(): void {
        parent::tearDown();
    }

    public function test_expand_single_assignment(): void {
        $test_data = $this->prepare_assignments();

        $track1_id = $test_data->track1->id;

        $this->assert_track_has_no_user_assignments($track1_id, $test_data->user1->id);

        // No user added to cohort so nothing should happen
        $this->get_expand_task()->expand_single($test_data->assignment1->id);

        $this->assert_track_has_no_user_assignments($track1_id, $test_data->user1->id);

        // Add the users to the cohort
        $this->add_user_to_cohort($test_data->cohort1->id, $test_data->user1->id);

        // This should now result in a user assignment
        $this->get_expand_task()->expand_single($test_data->assignment1->id);

        $this->assert_track_has_user_assignments($track1_id, $test_data->user1->id);
        // The other user is not in a cohort yet
        $this->assert_track_has_no_user_assignments($track1_id, $test_data->user2->id);

        // Now add the other one
        $this->add_user_to_cohort($test_data->cohort2->id, $test_data->user2->id);

        $this->get_expand_task()->expand_single($test_data->assignment1->id);

        // Should have been expanded
        $this->assert_track_has_user_assignments($track1_id, $test_data->user1->id);
        $this->assert_track_has_no_user_assignments($track1_id, $test_data->user2->id);

        $this->add_user_to_cohort($test_data->cohort1->id, $test_data->user2->id);

        $this->get_expand_task()->expand_single($test_data->assignment1->id);

        // Should have been expanded
        $this->assert_track_has_user_assignments($track1_id, $test_data->user1->id);
        $this->assert_track_has_user_assignments($track1_id, $test_data->user2->id);
    }

    public function test_deleted_users_get_unassigned_cohort(): void {
        $test_data = $this->prepare_assignments();

        $track1_id = $test_data->track1->id;

        $this->assert_track_has_no_user_assignments($track1_id, $test_data->user1->id);

        // Add the users to the cohort
        $this->add_user_to_cohort($test_data->cohort1->id, $test_data->user1->id);

        // This should now result in a user assignment
        $this->get_expand_task()->expand_single($test_data->assignment1->id);

        $this->assert_track_has_user_assignments($track1_id, $test_data->user1->id);
        // The other user is not in a cohort yet
        $this->assert_track_has_no_user_assignments($track1_id, $test_data->user2->id);

        delete_user($test_data->user1);

        $this->assert_track_has_user_assignments($track1_id, $test_data->user1->id, true);

        // Make sure the user does not get readded
        $this->get_expand_task()->expand_all(true);

        $this->assert_track_has_user_assignments($track1_id, $test_data->user1->id, true);
    }

    public function test_deleted_users_get_unassigned_position(): void {
        $test_data = $this->prepare_assignments();

        /** @var \totara_hierarchy\testing\generator $hierarchy_generator */
        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $fw = $hierarchy_generator->create_pos_frame(['fullname' => 'FW 1']);
        $pos = $hierarchy_generator->create_pos(['frameworkid' => $fw->id]);

        $track1_id = $test_data->track1->id;

        $assignment = new track_assignment([
            'track_id' => $track1_id,
            'type' => track_assignment_type::ADMIN,
            'user_group_type' => grouping::POS,
            'user_group_id' => $pos->id,
            'created_by' => 0,
            'expand' => true,
        ]);
        $assignment->save();

        // Assign both users to the same job assignment
        job_assignment::create([
            'userid' => $test_data->user1->id,
            'idnumber' => 'job1',
            'positionid' => $pos->id
        ]);

        $this->assert_track_has_no_user_assignments($track1_id, $test_data->user1->id);

        // This should now result in a user assignment
        $this->get_expand_task()->expand_single($assignment->id);

        $this->assert_track_has_user_assignments($track1_id, $test_data->user1->id);
        // The other user is not in a cohort yet
        $this->assert_track_has_no_user_assignments($track1_id, $test_data->user2->id);

        delete_user($test_data->user1);

        $this->assert_track_has_user_assignments($track1_id, $test_data->user1->id, true);

        // Make sure the user does not get readded
        $this->get_expand_task()->expand_all(true);

        $this->assert_track_has_user_assignments($track1_id, $test_data->user1->id, true);
    }

    public function test_deleted_users_get_unassigned_organisation(): void {
        $test_data = $this->prepare_assignments();

        /** @var \totara_hierarchy\testing\generator $hierarchy_generator */
        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $fw = $hierarchy_generator->create_org_frame(['fullname' => 'FW 1']);
        $org = $hierarchy_generator->create_org(['frameworkid' => $fw->id]);

        $track1_id = $test_data->track1->id;

        $assignment = new track_assignment([
            'track_id' => $track1_id,
            'type' => track_assignment_type::ADMIN,
            'user_group_type' => grouping::ORG,
            'user_group_id' => $org->id,
            'created_by' => 0,
            'expand' => true,
        ]);
        $assignment->save();

        // Assign both users to the same job assignment
        job_assignment::create([
            'userid' => $test_data->user1->id,
            'idnumber' => 'job1',
            'organisationid' => $org->id
        ]);

        $this->assert_track_has_no_user_assignments($track1_id, $test_data->user1->id);

        // This should now result in a user assignment
        $this->get_expand_task()->expand_single($assignment->id);

        $this->assert_track_has_user_assignments($track1_id, $test_data->user1->id);
        // The other user is not in a cohort yet
        $this->assert_track_has_no_user_assignments($track1_id, $test_data->user2->id);

        delete_user($test_data->user1);

        $this->assert_track_has_user_assignments($track1_id, $test_data->user1->id, true);

        // Make sure the user does not get readded
        $this->get_expand_task()->expand_all(true);

        $this->assert_track_has_user_assignments($track1_id, $test_data->user1->id, true);
    }

    public function test_expand_single_audience_assignment_with_multi_tenancy_enabled(): void {
        $test_data = $this->prepare_assignments_for_multi_tenancy();

        $track1_id = $test_data->track1->id;

        $this->assert_track_has_no_user_assignments($track1_id, $test_data->user1->id);

        // No user added to cohort so nothing should happen
        $this->get_expand_task()->expand_single($test_data->assignment1->id);

        $this->assert_track_has_no_user_assignments($track1_id, $test_data->user1->id);

        // Add the users to the cohort
        $this->add_user_to_cohort($test_data->cohort1->id, $test_data->user1->id);

        // This should now result in a user assignment
        $this->get_expand_task()->expand_single($test_data->assignment1->id);

        $this->assert_track_has_user_assignments($track1_id, $test_data->user1->id);
        // The other user is not in a cohort yet
        $this->assert_track_has_no_user_assignments($track1_id, $test_data->user2->id);

        $this->add_user_to_cohort($test_data->cohort1->id, $test_data->user2->id);

        $this->get_expand_task()->expand_single($test_data->assignment1->id);

        $this->assert_track_has_user_assignments($track1_id, $test_data->user1->id);
        // The other user is in a different tenant so is not expanded
        $this->assert_track_has_no_user_assignments($track1_id, $test_data->user2->id);
    }

    public function test_expand_single_position_assignment_with_multi_tenancy_enabled(): void {
        $test_data = $this->prepare_assignments_for_multi_tenancy();

        /** @var \totara_hierarchy\testing\generator $hierarchy_generator */
        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $fw = $hierarchy_generator->create_pos_frame(['fullname' => 'FW 1']);
        $pos = $hierarchy_generator->create_pos(['frameworkid' => $fw->id]);

        $assignment = new track_assignment([
            'track_id' => $test_data->track1->id,
            'type' => track_assignment_type::ADMIN,
            'user_group_type' => grouping::POS,
            'user_group_id' => $pos->id,
            'created_by' => 0,
            'expand' => true,
        ]);
        $assignment->save();

        $track1_id = $test_data->track1->id;

        // No user added to cohort so nothing should happen
        $this->get_expand_task()->expand_single($assignment->id);

        $this->assert_track_has_no_user_assignments($track1_id, $test_data->user1->id);
        $this->assert_track_has_no_user_assignments($track1_id, $test_data->user2->id);

        // Assign both users to the same job assignment
        job_assignment::create([
            'userid' => $test_data->user1->id,
            'idnumber' => 'job1',
            'positionid' => $pos->id
        ]);

        job_assignment::create([
            'userid' => $test_data->user2->id,
            'idnumber' => 'job2',
            'positionid' => $pos->id
        ]);

        // This should now result in a user assignment
        $this->get_expand_task()->expand_single($assignment->id);

        // Only the first user is assigned as he's in the same tenant as the activity
        $this->assert_track_has_user_assignments($track1_id, $test_data->user1->id, false);
        $this->assert_track_has_no_user_assignments($track1_id, $test_data->user2->id);

        // Now migrate user 1 to tenant 2
        tenant_util::migrate_user_to_tenant($test_data->user1->id, $test_data->tenant2->id);

        $this->get_expand_task()->expand_single($assignment->id);

        // Now the user should have a deleted assignment
        $this->assert_track_has_user_assignments($track1_id, $test_data->user1->id, true);
        $this->assert_track_has_no_user_assignments($track1_id, $test_data->user1->id, false);
        $this->assert_track_has_no_user_assignments($track1_id, $test_data->user2->id);

        // Expand and check that it's still the same
        $assignment->expand = true;
        $assignment->save();

        $this->get_expand_task()->expand_single($assignment->id);

        // Now the user should have a deleted assignment
        $this->assert_track_has_user_assignments($track1_id, $test_data->user1->id, true);
        $this->assert_track_has_no_user_assignments($track1_id, $test_data->user1->id, false);
        $this->assert_track_has_no_user_assignments($track1_id, $test_data->user2->id);
    }

    public function test_expand_single_assignment_based_on_job_assignment(): void {
        $test_data = $this->prepare_assignments(track_entity::SUBJECT_INSTANCE_GENERATION_ONE_PER_JOB);

        $track1_id = $test_data->track1->id;

        $job_assignment_id = builder::table('job_assignment')->insert([
            'userid' => $test_data->user1->id,
            'timecreated' => time(),
            'timemodified' => time(),
            'usermodified' => time(),
            'positionassignmentdate' => true,
            'sortorder' => 0,
        ]);

        $this->assert_track_has_no_user_assignments($track1_id, $test_data->user1->id);

        // No user added to cohort so nothing should happen
        $this->get_expand_task()->expand_single($test_data->assignment1->id);

        $this->assert_track_has_no_user_assignments($track1_id, $test_data->user1->id);

        // Add the users to the cohort
        $this->add_user_to_cohort($test_data->cohort1->id, $test_data->user1->id);

        // This should now result in a user assignment
        $this->get_expand_task()->expand_single($test_data->assignment1->id);

        $this->assert_track_has_user_assignments($track1_id, $test_data->user1->id, false, $job_assignment_id);
        // The other user is not in a cohort yet
        $this->assert_track_has_no_user_assignments($track1_id, $test_data->user2->id);

        // Now add the other one
        $this->add_user_to_cohort($test_data->cohort2->id, $test_data->user2->id);

        $this->get_expand_task()->expand_single($test_data->assignment1->id);

        // Should have been expanded
        $this->assert_track_has_user_assignments($track1_id, $test_data->user1->id, false, $job_assignment_id);
    }

    public function test_job_assignment_is_used_without_multiple_job_assignments_enabled(): void {
        set_config('totara_job_allowmultiplejobs', 0);
        $test_data = $this->prepare_assignments(track_entity::SUBJECT_INSTANCE_GENERATION_ONE_PER_JOB);

        $track1_id = $test_data->track1->id;

        $job_assignment_id = builder::table('job_assignment')->insert([
            'userid' => $test_data->user1->id,
            'timecreated' => time(),
            'timemodified' => time(),
            'usermodified' => time(),
            'positionassignmentdate' => true,
            'sortorder' => 0,
        ]);

        $this->assert_track_has_no_user_assignments($track1_id, $test_data->user1->id);

        // No user added to cohort so nothing should happen
        $this->get_expand_task()->expand_single($test_data->assignment1->id);

        // Add the users to the cohort
        $this->add_user_to_cohort($test_data->cohort1->id, $test_data->user1->id);

        // Now add the other one
        $this->add_user_to_cohort($test_data->cohort2->id, $test_data->user2->id);

        $this->get_expand_task()->expand_single($test_data->assignment1->id);

        // Should have been expanded, but with he job assignment id
        $this->assert_track_has_user_assignments($track1_id, $test_data->user1->id, false, $job_assignment_id);
    }

    public function test_expand_single_assignment_based_on_job_with_no_job_assignments(): void {
        set_config('totara_job_allowmultiplejobs', 1);
        $test_data = $this->prepare_assignments(track_entity::SUBJECT_INSTANCE_GENERATION_ONE_PER_JOB);

        $track1_id = $test_data->track1->id;

        $this->assert_track_has_no_user_assignments($track1_id, $test_data->user1->id);

        // Add the users to the cohort
        $this->add_user_to_cohort($test_data->cohort1->id, $test_data->user1->id);

        // Now add the other one
        $this->add_user_to_cohort($test_data->cohort2->id, $test_data->user2->id);

        $this->get_expand_task()->expand_single($test_data->assignment1->id);

        // Neither user should have been expanded
        $this->assert_track_has_no_user_assignments($track1_id, $test_data->user1->id);
        $this->assert_track_has_no_user_assignments($track1_id, $test_data->user2->id);
    }

    public function test_expand_single_assignment_based_on_multiple_job_assignment(): void {
        set_config('totara_job_allowmultiplejobs', 1);
        $test_data = $this->prepare_assignments(track_entity::SUBJECT_INSTANCE_GENERATION_ONE_PER_JOB);

        $track1_id = $test_data->track1->id;

        $job_assignment_id1 = builder::table('job_assignment')->insert([
            'userid' => $test_data->user1->id,
            'timecreated' => time(),
            'timemodified' => time(),
            'usermodified' => time(),
            'positionassignmentdate' => true,
            'sortorder' => 0,
            'idnumber' => 1,
        ]);

        $job_assignment_id2 = builder::table('job_assignment')->insert([
            'userid' => $test_data->user1->id,
            'timecreated' => time(),
            'timemodified' => time(),
            'usermodified' => time(),
            'positionassignmentdate' => true,
            'sortorder' => 1,
            'idnumber' => 2,
        ]);

        $this->assert_track_has_no_user_assignments($track1_id, $test_data->user1->id);

        // No user added to cohort so nothing should happen
        $this->get_expand_task()->expand_single($test_data->assignment1->id);

        $this->assert_track_has_no_user_assignments($track1_id, $test_data->user1->id);

        // Add the users to the cohort
        $this->add_user_to_cohort($test_data->cohort1->id, $test_data->user1->id);

        // This should now result in a user assignment
        $this->get_expand_task()->expand_single($test_data->assignment1->id);

        $this->assert_track_has_user_assignments($track1_id, $test_data->user1->id, false, $job_assignment_id1);
        $this->assert_track_has_user_assignments($track1_id, $test_data->user1->id, false, $job_assignment_id2);
        // The other user is not in a cohort yet
        $this->assert_track_has_no_user_assignments($track1_id, $test_data->user2->id);

        // Now add the other one
        $this->add_user_to_cohort($test_data->cohort2->id, $test_data->user2->id);

        $this->get_expand_task()->expand_single($test_data->assignment1->id);

        // Should have been expanded
        $this->assert_track_has_user_assignments($track1_id, $test_data->user1->id, false, $job_assignment_id1);
        $this->assert_track_has_user_assignments($track1_id, $test_data->user1->id, false, $job_assignment_id2);
    }

    public function test_user_gets_unassigned(): void {
        $test_data = $this->prepare_assignments();

        $track1_id = $test_data->track1->id;

        $this->assert_track_has_no_user_assignments($track1_id, $test_data->user1->id);

        // Add the users to the cohort
        $this->add_user_to_cohort($test_data->cohort1->id, $test_data->user1->id);

        // This should now result in a user assignment
        $this->get_expand_task()->expand_single($test_data->assignment1->id);

        $this->assert_track_has_user_assignments($track1_id, $test_data->user1->id, false);

        $this->remove_user_from_cohort($test_data->cohort1->id, $test_data->user1->id);

        $this->get_expand_task()->expand_single($test_data->assignment1->id);

        // User is now marked as deleted
        $this->assert_track_has_user_assignments($track1_id, $test_data->user1->id, true);
    }

    public function test_user_assignment_gets_swapped_when_changing_generation_method(): void {
        set_config('totara_job_allowmultiplejobs', 1);
        $test_data = $this->prepare_assignments(track_entity::SUBJECT_INSTANCE_GENERATION_ONE_PER_JOB);

        $track1_id = $test_data->track1->id;

        $job_assignment_id1 = builder::table('job_assignment')->insert([
            'userid' => $test_data->user1->id,
            'timecreated' => time(),
            'timemodified' => time(),
            'usermodified' => time(),
            'positionassignmentdate' => true,
            'sortorder' => 0,
            'idnumber' => 1,
        ]);

        $this->assert_track_has_no_user_assignments($track1_id, $test_data->user1->id, false, $job_assignment_id1);
        $this->assert_track_has_no_user_assignments($track1_id, $test_data->user1->id, false, null);

        // Add the users to the cohort
        $this->add_user_to_cohort($test_data->cohort1->id, $test_data->user1->id);

        // This should now result in a user assignment
        $this->get_expand_task()->expand_single($test_data->assignment1->id);

        $this->assert_track_has_user_assignments($track1_id, $test_data->user1->id, false, $job_assignment_id1);
        $this->assert_track_has_no_user_assignments($track1_id, $test_data->user1->id, false, null);

        // Change the track to per subject generation
        $track1 = new track_entity($track1_id);
        $track1->subject_instance_generation = track_entity::SUBJECT_INSTANCE_GENERATION_ONE_PER_SUBJECT;
        $track1->save();

        // Trigger re-expansion
        $test_data->assignment1->expand = true;
        $test_data->assignment1->save();

        $this->get_expand_task()->expand_single($test_data->assignment1->id);

        // Assignment should now be swapped to be based on job assignment
        $this->assert_track_has_user_assignments($track1_id, $test_data->user1->id, false, null);
        $this->assert_track_has_no_user_assignments($track1_id, $test_data->user1->id, false, $job_assignment_id1);

        // Change the track BACK to per job generation
        $track1->subject_instance_generation = track_entity::SUBJECT_INSTANCE_GENERATION_ONE_PER_JOB;
        $track1->save();

        // Trigger re-expansion
        $test_data->assignment1->expand = true;
        $test_data->assignment1->save();

        $this->get_expand_task()->expand_single($test_data->assignment1->id);

        // Assignment should now be swapped to be based on job assignment
        $this->assert_track_has_user_assignments($track1_id, $test_data->user1->id, false, $job_assignment_id1);
        $this->assert_track_has_no_user_assignments($track1_id, $test_data->user1->id, false, null);
    }

    public function test_user_gets_unassigned_based_on_job_assignment(): void {
        set_config('totara_job_allowmultiplejobs', 1);
        $test_data = $this->prepare_assignments(track_entity::SUBJECT_INSTANCE_GENERATION_ONE_PER_JOB);

        $track1_id = $test_data->track1->id;

        $job_assignment_id1 = builder::table('job_assignment')->insert([
            'userid' => $test_data->user1->id,
            'timecreated' => time(),
            'timemodified' => time(),
            'usermodified' => time(),
            'positionassignmentdate' => true,
            'sortorder' => 0,
            'idnumber' => 1,
        ]);

        $this->assert_track_has_no_user_assignments($track1_id, $test_data->user1->id);

        // Add the users to the cohort
        $this->add_user_to_cohort($test_data->cohort1->id, $test_data->user1->id);

        // This should now result in a user assignment
        $this->get_expand_task()->expand_single($test_data->assignment1->id);

        $this->assert_track_has_user_assignments($track1_id, $test_data->user1->id, false, $job_assignment_id1);

        $this->remove_user_from_cohort($test_data->cohort1->id, $test_data->user1->id);

        $this->get_expand_task()->expand_single($test_data->assignment1->id);

        // User is now marked as deleted
        $this->assert_track_has_user_assignments($track1_id, $test_data->user1->id, true, $job_assignment_id1);
    }

    public function test_events_are_fired(): void {
        $test_data = $this->prepare_assignments();

        $track1_id = $test_data->track1->id;

        // Add the users to the cohort
        $this->add_user_to_cohort($test_data->cohort1->id, $test_data->user1->id);
        $this->add_user_to_cohort($test_data->cohort1->id, $test_data->user2->id);

        $sink = $this->redirectEvents();

        // This should now result in a user assignment
        $this->get_expand_task()->expand_single($test_data->assignment1->id);

        $events = $sink->get_events();
        $sink->close();

        $this->assertCount(1, $events);
        $this->assertContainsOnlyInstancesOf(track_user_assigned_bulk::class, $events);
        /** @var track_user_assigned_bulk $event */
        $event = $events[0];

        $this->assertEquals($track1_id, $event->objectid);
        $this->assertEqualsCanonicalizing([$test_data->user1->id, $test_data->user2->id], $event->get_user_ids());

        $this->remove_user_from_cohort($test_data->cohort1->id, $test_data->user1->id);
        $this->remove_user_from_cohort($test_data->cohort1->id, $test_data->user2->id);

        $sink = $this->redirectEvents();

        $this->get_expand_task()->expand_single($test_data->assignment1->id);

        $events = $sink->get_events();
        $sink->close();

        $this->assertCount(2, $events);
        $this->assertContainsOnlyInstancesOf(track_user_unassigned::class, $events);
        $this->assertEquals([$track1_id, $track1_id], array_column($events, 'objectid'));
        $this->assertEqualsCanonicalizing(
            [$test_data->user1->id, $test_data->user2->id],
            array_map(function (track_user_unassigned $event) {
                return $event->get_user_id();
            }, $events)
        );
    }

    public function test_user_assignment_gets_reactivated(): void {
        $test_data = $this->prepare_assignments();

        $track1_id = $test_data->track1->id;

        $this->assert_track_has_no_user_assignments($track1_id, $test_data->user1->id);

        // Add the users to the cohort
        $this->add_user_to_cohort($test_data->cohort1->id, $test_data->user1->id);

        // This should now result in a user assignment
        $this->get_expand_task()->expand_single($test_data->assignment1->id);

        $this->remove_user_from_cohort($test_data->cohort1->id, $test_data->user1->id);

        $this->get_expand_task()->expand_single($test_data->assignment1->id);

        // User is now marked as deleted
        $this->assert_track_has_user_assignments($track1_id, $test_data->user1->id, true);

        // Readd to group
        $this->add_user_to_cohort($test_data->cohort1->id, $test_data->user1->id);

        $this->get_expand_task()->expand_single($test_data->assignment1->id);

        $this->assert_track_has_user_assignments($track1_id, $test_data->user1->id, false);
    }

    public function test_user_assignment_gets_reactivated_based_on_job_assignment(): void {
        set_config('totara_job_allowmultiplejobs', 1);
        $test_data = $this->prepare_assignments(track_entity::SUBJECT_INSTANCE_GENERATION_ONE_PER_JOB);

        $track1_id = $test_data->track1->id;

        $job_assignment_id1 = builder::table('job_assignment')->insert([
            'userid' => $test_data->user1->id,
            'timecreated' => time(),
            'timemodified' => time(),
            'usermodified' => time(),
            'positionassignmentdate' => true,
            'sortorder' => 0,
            'idnumber' => 1,
        ]);

        $this->assert_track_has_no_user_assignments($track1_id, $test_data->user1->id, false, $job_assignment_id1);

        // Add the users to the cohort
        $this->add_user_to_cohort($test_data->cohort1->id, $test_data->user1->id);

        // This should now result in a user assignment
        $this->get_expand_task()->expand_single($test_data->assignment1->id);

        $this->remove_user_from_cohort($test_data->cohort1->id, $test_data->user1->id);

        $this->get_expand_task()->expand_single($test_data->assignment1->id);

        // User is now marked as deleted
        $this->assert_track_has_user_assignments($track1_id, $test_data->user1->id, true, $job_assignment_id1);

        // Readd to group
        $this->add_user_to_cohort($test_data->cohort1->id, $test_data->user1->id);

        $this->get_expand_task()->expand_single($test_data->assignment1->id);

        $this->assert_track_has_user_assignments($track1_id, $test_data->user1->id, false, $job_assignment_id1);
    }

    public function test_reactivated_user_assignment_gets_period_updated(): void {
        $test_data = $this->prepare_assignments();
        $track_id = $test_data->track1->id;
        $user_id = $test_data->user1->id;

        $now = date_time_setting::now_server_timezone();
        $test_data->track1->set_schedule_open_fixed($now);
        $test_data->track1->update();

        // Add user to the cohort - we expect a user assignment with period according to track schedule settings.
        $this->add_user_to_cohort($test_data->cohort1->id, $user_id);
        $this->get_expand_task()->expand_single($test_data->assignment1->id);
        $this->assert_user_assignment_period($track_id, $user_id, $now, null);

        // Remove user from cohort - period values should not be affected
        $this->remove_user_from_cohort($test_data->cohort1->id, $user_id);
        $this->get_expand_task()->expand_single($test_data->assignment1->id);
        $this->assert_user_assignment_period($track_id, $user_id, $now, null);

        // User assignment is now marked as deleted
        $this->assert_track_has_user_assignments($track_id, $user_id, true);

        // Change the schedule for the track
        $tomorrow = new date_time_setting(time() + 86400);
        $yesterday = new date_time_setting(time() - 86400);
        $test_data->track1->set_schedule_closed_fixed($yesterday, $tomorrow);
        $test_data->track1->update();

        // Re-add user to cohort - we expect the reactivated user assignment gets the updated period settings
        $this->add_user_to_cohort($test_data->cohort1->id, $user_id);
        $this->get_expand_task()->expand_single($test_data->assignment1->id);
        $this->assert_user_assignment_period($track_id, $user_id, $yesterday, $tomorrow);
    }

    private function assert_user_assignment_period(int $track_id, int $user_id, $start, $end): void {
        if ($start instanceof date_time_setting) {
            $start = $start->get_timestamp();
        }

        if ($end instanceof date_time_setting) {
            $end = $end->get_timestamp();
        }

        /** @var track_user_assignment $user_assignment */
        $user_assignment = track_user_assignment::repository()
            ->where('track_id', $track_id)
            ->where('subject_user_id', $user_id)
            ->one();
        $this->assertEquals($start, $user_assignment->period_start_date);
        $this->assertEquals($end, $user_assignment->period_end_date);
    }

    public function test_user_assignment_is_only_created_once(): void {
        $test_data = $this->prepare_assignments();

        $track1_id = $test_data->track1->id;

        // Add the user to two cohorts
        $this->add_user_to_cohort($test_data->cohort1->id, $test_data->user1->id);
        $this->add_user_to_cohort($test_data->cohort2->id, $test_data->user1->id);

        // This should now result in only one user assignment even if the user is in two cohorts
        $this->get_expand_task()->expand_multiple([$test_data->assignment1->id, $test_data->assignment2->id]);

        $this->assert_track_has_user_assignments($track1_id, $test_data->user1->id, false);
    }

    public function test_assign_multiple(): void {
        $test_data = $this->prepare_assignments();

        $track1_id = $test_data->track1->id;

        // Add the user to two cohorts
        $this->add_user_to_cohort($test_data->cohort1->id, $test_data->user1->id);
        $this->add_user_to_cohort($test_data->cohort2->id, $test_data->user2->id);

        $this->get_expand_task()->expand_multiple([$test_data->assignment1->id, $test_data->assignment2->id]);

        $this->assert_track_has_user_assignments($track1_id, $test_data->user1->id, false);
        $this->assert_track_has_user_assignments($track1_id, $test_data->user2->id, false);
    }

    public function test_assign_all(): void {
        $test_data = $this->prepare_assignments();

        $track1_id = $test_data->track1->id;

        // Add the user to two cohorts
        $this->add_user_to_cohort($test_data->cohort1->id, $test_data->user1->id);
        $this->add_user_to_cohort($test_data->cohort2->id, $test_data->user2->id);

        $this->get_expand_task()->expand_all();

        $this->assert_track_has_user_assignments($track1_id, $test_data->user1->id, false);
        $this->assert_track_has_user_assignments($track1_id, $test_data->user2->id, false);
    }

    public function test_assignments_of_draft_activity_are_not_expanded(): void {
        $test_data = $this->prepare_assignments();

        /** @var activity_model $activity */
        $activity = $test_data->activity1;

        /** @var activity $activity_entity */
        $activity_entity = activity::repository()->find($activity->get_id());
        $activity_entity->status = draft::get_code();
        $activity_entity->save();

        $track1_id = $test_data->track1->id;

        // Add the user to two cohorts
        $this->add_user_to_cohort($test_data->cohort1->id, $test_data->user1->id);
        $this->add_user_to_cohort($test_data->cohort2->id, $test_data->user2->id);

        $this->get_expand_task()->expand_all();

        $this->assert_track_has_no_user_assignments($track1_id, $test_data->user1->id, false);
        $this->assert_track_has_no_user_assignments($track1_id, $test_data->user2->id, false);
    }

    public function test_assignments_of_paused_track_are_not_expanded(): void {
        $test_data = $this->prepare_assignments();

        $track1_id = $test_data->track1->id;

        /** @var track_entity $track */
        $track = track_entity::repository()->find($track1_id);
        $track->status = track_status::PAUSED;
        $track->save();

        // Add the user to two cohorts
        $this->add_user_to_cohort($test_data->cohort1->id, $test_data->user1->id);
        $this->add_user_to_cohort($test_data->cohort2->id, $test_data->user2->id);

        $this->get_expand_task()->expand_all();

        $this->assert_track_has_no_user_assignments($track1_id, $test_data->user1->id, false);
        $this->assert_track_has_no_user_assignments($track1_id, $test_data->user2->id, false);
    }

    public function test_fixed_assignment_period_dates_are_populated(): void {
        $test_data = $this->prepare_assignments();

        $track1_id = $test_data->track1->id;

        /** @var track_entity $track */
        $track = track_entity::repository()->find($track1_id);
        $start_time = time() - 1000;
        $end_time = time() + 1000;
        $track->schedule_is_open = false;
        $track->schedule_is_fixed = true;
        $track->schedule_fixed_from = $start_time;
        $track->schedule_fixed_to = $end_time;
        $track->save();

        $this->add_user_to_cohort($test_data->cohort1->id, $test_data->user1->id);

        $this->get_expand_task()->expand_all();

        /** @var track_user_assignment $track_user_assignment */
        $track_user_assignment = track_user_assignment::repository()
            ->where('track_id', $track1_id)
            ->where('subject_user_id', $test_data->user1->id)
            ->one();
        $this->assertEquals($start_time, $track_user_assignment->period_start_date);
        $this->assertEquals($end_time, $track_user_assignment->period_end_date);

        // Change to open_fixed and expand for a new user.
        $track->schedule_is_open = true;
        $track->schedule_is_fixed = true;
        $track->save();

        $this->add_user_to_cohort($test_data->cohort1->id, $test_data->user2->id);

        $this->get_expand_task()->expand_all();
        /** @var track_user_assignment $track_user_assignment */
        $track_user_assignment = track_user_assignment::repository()
            ->where('track_id', $track1_id)
            ->where('subject_user_id', $test_data->user2->id)
            ->one();
        $this->assertEquals($start_time, $track_user_assignment->period_start_date);
        $this->assertEquals(null, $track_user_assignment->period_end_date);
    }

    public function test_dynamic_assignment_period_dates_are_populated(): void {
        $test_data = $this->prepare_assignments();

        $track1_id = $test_data->track1->id;

        $dynamic_source = (new user_creation_date())->get_options()->first();

        /** @var track_entity $track */
        $track = track_entity::repository()->find($track1_id);
        $track->schedule_is_open = false;
        $track->schedule_is_fixed = false;
        $track->schedule_dynamic_from = new date_offset(
            7,
            date_offset::UNIT_DAY,
            date_offset::DIRECTION_BEFORE
        );
        $track->schedule_dynamic_to = new date_offset(
            0,
            date_offset::UNIT_DAY,
            date_offset::DIRECTION_BEFORE
        );
        $track->schedule_dynamic_source = $dynamic_source;
        $track->schedule_use_anniversary = false;
        $track->save();

        $create_date = 1589932800;
        $seven_days_before_create_date = 1589328000;

        // Set the users created date.
        $user1 = new user($test_data->user1->id);
        $user1->timecreated = $create_date;
        $user1->save();

        $this->add_user_to_cohort($test_data->cohort1->id, $test_data->user1->id);

        $this->get_expand_task()->expand_all();

        /** @var track_user_assignment $track_user_assignment */
        $track_user_assignment = track_user_assignment::repository()
            ->where('track_id', $track1_id)
            ->where('subject_user_id', $test_data->user1->id)
            ->one();

        $this->assertEquals($seven_days_before_create_date, $track_user_assignment->period_start_date);

        // End dates are adjusted to "end of day".
        $this->assertEquals(
            $create_date + DAYSECS,
            $track_user_assignment->period_end_date
        );

        // Change to open and expand for a new user.
        $track->schedule_is_open = true;
        $track->schedule_dynamic_to = null;
        $track->save();

        // Set the users created date.
        $user2 = new user($test_data->user2->id);
        $user2->timecreated = $create_date;
        $user2->save();

        $this->add_user_to_cohort($test_data->cohort1->id, $test_data->user2->id);

        $this->get_expand_task()->expand_all();
        /** @var track_user_assignment $track_user_assignment */
        $track_user_assignment = track_user_assignment::repository()
            ->where('track_id', $track1_id)
            ->where('subject_user_id', $test_data->user2->id)
            ->one();

        $this->assertEquals($seven_days_before_create_date, $track_user_assignment->period_start_date);
        $this->assertEquals(null, $track_user_assignment->period_end_date);
    }

    public function test_dynamic_assignment_period_dates_are_populated_from_anniversary_date_resolution(): void {
        $test_data = $this->prepare_assignments();

        $track1_id = $test_data->track1->id;

        $dynamic_source = (new user_creation_date())->get_options()->first();

        /** @var track_entity $track */
        $track = track_entity::repository()->find($track1_id);
        $track->schedule_is_open = false;
        $track->schedule_is_fixed = false;
        $track->schedule_dynamic_from = new date_offset(
            0,
            date_offset::UNIT_DAY,
            date_offset::DIRECTION_BEFORE
        );
        $track->schedule_dynamic_to = new date_offset(
            0,
            date_offset::UNIT_DAY,
            date_offset::DIRECTION_BEFORE
        );
        $track->schedule_dynamic_source = $dynamic_source;
        $track->schedule_use_anniversary = true;
        $track->save();

        $create_date = (new DateTime('2000-01-01T00:00:00', new DateTimeZone('UTC')))->getTimestamp();

        // Set the users created date.
        $user1 = new user($test_data->user1->id);
        $user1->timecreated = $create_date;
        $user1->save();

        $this->add_user_to_cohort($test_data->cohort1->id, $test_data->user1->id);

        $this->get_expand_task()->expand_all();

        /** @var track_user_assignment $track_user_assignment */
        $track_user_assignment = track_user_assignment::repository()
            ->where('track_id', $track1_id)
            ->where('subject_user_id', $test_data->user1->id)
            ->one();

        $this->assert_anniversary_date($track_user_assignment->period_start_date, 1, 1);

        // End dates are adjusted to "end of day".
        $this->assert_anniversary_date($track_user_assignment->period_end_date, 2, 1);

        // Change to open and expand for a new user.
        $track->schedule_is_open = true;
        $track->schedule_dynamic_to = null;
        $track->save();

        // Set the users created date.
        $user2 = new user($test_data->user2->id);
        $user2->timecreated = $create_date;
        $user2->save();

        $this->add_user_to_cohort($test_data->cohort1->id, $test_data->user2->id);

        $this->get_expand_task()->expand_all();
        /** @var track_user_assignment $track_user_assignment */
        $track_user_assignment = track_user_assignment::repository()
            ->where('track_id', $track1_id)
            ->where('subject_user_id', $test_data->user2->id)
            ->one();

        $this->assert_anniversary_date($track_user_assignment->period_start_date, 1, 1);
        $this->assertEquals(null, $track_user_assignment->period_end_date);
    }

    public function test_null_dynamic_assignment_period_dates_are_populated(): void {
        $test_data = $this->prepare_assignments();

        // No user_info_data is inserted, this is how we get the null data.
        builder::get_db()->insert_record(
            'user_info_field',
            (object)['shortname' => 'datetime-custom-field-1', 'name' => 'time 2', 'categoryid' => 1, 'datatype' => 'datetime']
        );

        $track1_id = $test_data->track1->id;

        $dynamic_source = (new user_custom_field())->get_options()->find(function (dynamic_source $source) {
            return $source->get_option_key() === 'datetime-custom-field-1';
        });

        /** @var track_entity $track */
        $track = track_entity::repository()->find($track1_id);
        $track->schedule_is_open = false;
        $track->schedule_is_fixed = false;
        $track->schedule_dynamic_source = $dynamic_source;
        $track->schedule_dynamic_from = new date_offset(
            1,
            date_offset::UNIT_DAY,
            date_offset::DIRECTION_AFTER
        );
        $track->schedule_dynamic_to = new date_offset(
            2,
            date_offset::UNIT_DAY,
            date_offset::DIRECTION_AFTER
        );
        $track->save();

        $this->add_user_to_cohort($test_data->cohort1->id, $test_data->user1->id);

        $this->get_expand_task()->expand_all();

        /** @var track_user_assignment $track_user_assignment */
        $track_user_assignment = track_user_assignment::repository()
            ->where('track_id', $track1_id)
            ->where('subject_user_id', $test_data->user1->id)
            ->one();

        $this->assertNull($track_user_assignment->period_start_date);
        $this->assertNull($track_user_assignment->period_end_date);

        // Change to open_fixed and expand for a new user.
        $track->schedule_is_open = true;
        $track->schedule_is_fixed = false;
        $track->save();

        $this->add_user_to_cohort($test_data->cohort1->id, $test_data->user2->id);

        $this->get_expand_task()->expand_all();
        /** @var track_user_assignment $track_user_assignment */
        $track_user_assignment = track_user_assignment::repository()
            ->where('track_id', $track1_id)
            ->where('subject_user_id', $test_data->user2->id)
            ->one();

        $this->assertNull($track_user_assignment->period_start_date);
        $this->assertNull($track_user_assignment->period_end_date);
    }

    /**
     * Assert that the track does have any assignments, optionally can check individual user
     *
     * @param int $track_id
     * @param int|null $user_id
     * @param bool|null $deleted
     * @param null $job_assignment_id
     */
    private function assert_track_has_user_assignments(
        int $track_id,
        ?int $user_id = null,
        ?bool $deleted = null,
        $job_assignment_id = null
    ): void {
        $this->assertTrue(
            $this->track_has_user_assignments($track_id, $user_id, $deleted, $job_assignment_id),
            'Track should have user assignments'
        );
    }

    /**
     * Assert that the track does not have any assignments, optionally can check individual user
     *
     * @param int $track_id
     * @param int|null $user_id
     * @param bool|null $deleted
     * @param null $job_assignment_id
     */
    private function assert_track_has_no_user_assignments(
        int $track_id,
        ?int $user_id = null,
        ?bool $deleted = null,
        $job_assignment_id = null
    ): void {
        $this->assertFalse(
            $this->track_has_user_assignments($track_id, $user_id, $deleted, $job_assignment_id),
            'Track should not have user assignments'
        );
    }

    /**
     * Assert that a date is this year or next and that the day and month are particular values.
     *
     * @param int $date
     * @param int $expected_day
     * @param int $expected_month
     */
    private function assert_anniversary_date(
        int $date,
        int $expected_day,
        int $expected_month
    ): void {
        [$year, $month, $day] = explode(
            '-',
            (new DateTime("@{$date}"))->format('Y-m-d')
        );

        $this_year = (new DateTime())->format('Y');
        $next_year = (new DateTime())->modify('+1 year')->format('Y');

        $this->assertEquals($expected_day, (int) $day);
        $this->assertEquals($expected_month, (int) $month);
        $this->assertTrue(
            $year === $this_year || $year === $next_year,
            'Year was not this year or next'
        );
    }

    /**
     * Does the track and (optional) user combination have any assignments?
     *
     * @param int $track_id
     * @param int|null $user_id
     * @param bool|null $deleted
     * @param null $job_assignment_id
     * @return bool
     */
    private function track_has_user_assignments(
        int $track_id,
        ?int $user_id = null,
        ?bool $deleted = null,
        $job_assignment_id = null
    ): bool {
        $repo = track_user_assignment::repository()
            ->where('track_id', $track_id)
            ->when($user_id, function (entity_repository $repository) use ($user_id, $job_assignment_id) {
                $repository->where('subject_user_id', $user_id);
                $repository->where('job_assignment_id', $job_assignment_id);
            })
            ->when($deleted !== null, function (entity_repository $repository) use ($deleted) {
                $repository->where('deleted', $deleted);
            });

        // There can only be one assignment per track and user
        if ($user_id) {
            $result = $repo->one();
            return !empty($result);
        }

        // Otherwise there could be multiple
        return $repo->exists();
    }

    private function add_user_to_cohort(int $cohort_id, int $user_id): void {
        cohort_add_member($cohort_id, $user_id);
    }

    private function remove_user_from_cohort(int $cohort_id, int $user_id): void {
        cohort_remove_member($cohort_id, $user_id);
    }

    private function prepare_assignments(
        $subject_instance_generation = track_entity::SUBJECT_INSTANCE_GENERATION_ONE_PER_SUBJECT
    ) {
        $test_data = new class() {
            public $user1;
            public $user2;
            public $cohort1;
            public $cohort2;
            public $activity1;

            /** @var $track1 track */
            public $track1;

            /** @var $assignment1 track_assignment */
            public $assignment1;

            /** @var $assignment1 track_assignment */
            public $assignment2;
        };

        $test_data->user1 = $this->generator()->create_user();
        $test_data->user2 = $this->generator()->create_user();
        $test_data->cohort1 = $this->generator()->create_cohort();
        $test_data->cohort2 = $this->generator()->create_cohort();

        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = \mod_perform\testing\generator::instance();

        $test_data->activity1 = $perform_generator->create_activity_in_container();

        $test_data->track1 = $perform_generator
            ->create_activity_tracks($test_data->activity1)
            ->first();

        $track1 = new track_entity($test_data->track1->id);
        $track1->subject_instance_generation = $subject_instance_generation;
        $track1->save();

        $test_data->assignment1 = new track_assignment([
            'track_id' => $test_data->track1->id,
            'type' => track_assignment_type::ADMIN,
            'user_group_type' => grouping::COHORT,
            'user_group_id' => $test_data->cohort1->id,
            'created_by' => 0,
            'expand' => true,
        ]);
        $test_data->assignment1->save();

        $test_data->assignment2 = new track_assignment([
            'track_id' => $test_data->track1->id,
            'type' => track_assignment_type::ADMIN,
            'user_group_type' => grouping::COHORT,
            'user_group_id' => $test_data->cohort2->id,
            'created_by' => 0,
            'expand' => false
        ]);
        $test_data->assignment2->save();

        return $test_data;
    }

    private function prepare_assignments_for_multi_tenancy(
        $subject_instance_generation = track_entity::SUBJECT_INSTANCE_GENERATION_ONE_PER_SUBJECT
    ) {
        $test_data = new class() {
            public $tenant1, $tenant2;
            public $user1, $user2;
            public $cohort1, $cohort2;
            public $activity1;
            public $track1;
            public $assignment1, $assignment2;
        };

        $generator = $this->getDataGenerator();
        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = $generator->get_plugin_generator('mod_perform');

        $this->setAdminUser();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');

        $tenant_generator->enable_tenants();

        $tenant1 = $tenant_generator->create_tenant();
        $tenant2 = $tenant_generator->create_tenant();

        $test_data->tenant1 = new tenant($tenant1);
        $test_data->tenant2 = new tenant($tenant2);

        $tenant1_category_context = context_coursecat::instance($test_data->tenant1->categoryid);
        $tenant2_category_context = context_coursecat::instance($test_data->tenant2->categoryid);

        $test_data->user1 = $this->generator()->create_user(['tenantid' => $test_data->tenant1->id]);
        $test_data->user2 = $this->generator()->create_user(['tenantid' => $test_data->tenant2->id]);
        $test_data->cohort1 = $this->generator()->create_cohort(['contextid' => $tenant1_category_context->id]);
        $test_data->cohort2 = $this->generator()->create_cohort(['contextid' => $tenant2_category_context->id]);

        // Make sure we have the categories created
        $this->setUser($test_data->user1);
        $default_category_id1 = util::get_default_category_id();

        $this->setUser($test_data->user2);
        $default_category_id2 = util::get_default_category_id();

        $this->setAdminUser();

        // Activity is in tenant 1
        $test_data->activity1 = $perform_generator->create_activity_in_container([
            'category' => $default_category_id1
        ]);

        $test_data->track1 = $perform_generator
            ->create_activity_tracks($test_data->activity1)
            ->first();

        $track1 = new track_entity($test_data->track1->id);
        $track1->subject_instance_generation = $subject_instance_generation;
        $track1->save();

        $test_data->assignment1 = new track_assignment([
            'track_id' => $test_data->track1->id,
            'type' => track_assignment_type::ADMIN,
            'user_group_type' => grouping::COHORT,
            'user_group_id' => $test_data->cohort1->id,
            'created_by' => 0,
            'expand' => true,
        ]);
        $test_data->assignment1->save();

        $test_data->assignment2 = new track_assignment([
            'track_id' => $test_data->track1->id,
            'type' => track_assignment_type::ADMIN,
            'user_group_type' => grouping::COHORT,
            'user_group_id' => $test_data->cohort2->id,
            'created_by' => 0,
            'expand' => false
        ]);
        $test_data->assignment2->save();

        return $test_data;
    }

    /**
     * Returns a new instance of the expand task
     *
     * @return expand_task
     */
    private function get_expand_task(): expand_task {
        return expand_task::create();
    }

}

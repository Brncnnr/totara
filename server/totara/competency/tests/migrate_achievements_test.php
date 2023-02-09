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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_competency
 */

use totara_competency\entity\competency_achievement;
use totara_competency\migration_helper;
use totara_core\advanced_feature;

/**
 * @group totara_competency
 */
class totara_competency_migrate_achievements_testcase extends advanced_testcase {

    public function setUp(): void {
        parent::setUp();
    }

    private function add_comp_record($competency_id, $user_id, $proficiency, $timecreated, $timemodified) {
        global $DB;

        $comp_record = new stdClass();
        $comp_record->competencyid = $competency_id;
        $comp_record->userid = $user_id;
        $comp_record->proficiency = $proficiency;
        $comp_record->timecreated = $timecreated;
        $comp_record->timemodified = $timemodified;
        $comp_record->id = $DB->insert_record('comp_record', $comp_record);

        return $comp_record;
    }

    private function add_comp_record_history($competency_id, $user_id, $proficiency, $timemodified) {
        global $DB;

        $comp_record_history = new stdClass();
        $comp_record_history->competencyid = $competency_id;
        $comp_record_history->userid = $user_id;
        $comp_record_history->proficiency = $proficiency;
        $comp_record_history->timemodified = $timemodified;
        $comp_record_history->usermodified = 500;
        $comp_record_history->id = $DB->insert_record('comp_record_history', $comp_record_history);

        return $comp_record_history;
    }

    public function test_empty_no_comp_records() {
        global $DB;

        // This test is supposed to cover there being no records in these tables. So just make sure of that.
        $this->assertEquals(0, $DB->count_records('comp_record'));
        $this->assertEquals(0, $DB->count_records('comp_record_history'));

        migration_helper::queue_migration();
        migration_helper::migrate_achievements();

        // For the most part, we just need to make sure we've made it here without an exception.

        $this->assertEquals(0, $DB->count_records('totara_competency_achievement'));
        $this->assertEquals(0, $DB->count_records('totara_competency_assignments'));
    }

    public function test_single_current_comp_record_with_a_learning_plan_assignment() {
        global $DB;

        $this->setAdminUser();

        $comp_record = $this->add_comp_record(100, 200, null, 300, 400);

        // Now add the comp_record_history.
        $this->add_comp_record_history(
            $comp_record->competencyid,
            $comp_record->userid,
            null,
            // Timemodified doesn't have to be the same. We expect there will be records where this is the case.
            $comp_record->timemodified + 1
        );

        /** @var totara_plan_generator $plan_generator */
        $plan_generator = $this->getDataGenerator()->get_plugin_generator('totara_plan');

        // Create a plan and add competency.
        $plan1 = $plan_generator->create_learning_plan(['userid' => 200]);
        $plan_generator->add_learning_plan_competency($plan1->id, 100);

        $this->setCurrentTimeStart();

        // Now remove the configuration setting allowing us to run migration again
        migration_helper::queue_migration();
        migration_helper::migrate_achievements();

        $plan_values = $DB->get_records('dp_plan_competency_value');
        $this->assertCount(1, $plan_values);

        $achievements = $DB->get_records('totara_competency_achievement');
        $this->assertCount(1, $achievements);
    }

    public function test_single_current_comp_record() {
        global $DB;

        $comp_record = $this->add_comp_record(100, 200, null, 300, 400);

        // First off, try this without a comp_record_history.
        migration_helper::queue_migration();
        migration_helper::migrate_achievements();

        // The comp_record did not get migrated. This is because it is already supposed to have an
        // equivalent in comp_record_history.
        // This assertion just confirms that we do not allow for this invalid data state.
        $achievements = $DB->get_records('totara_competency_achievement');
        $this->assertCount(0, $achievements);

        $assignments = $DB->get_records('totara_competency_assignments');
        $this->assertCount(0, $assignments);

        // Now add the comp_record_history.
        $comp_record_history = $this->add_comp_record_history(
            $comp_record->competencyid,
            $comp_record->userid,
            null,
            // Timemodified doesn't have to be the same. We expect there will be records where this is the case.
            $comp_record->timemodified + 1
        );

        $this->setCurrentTimeStart();

        // We currently only allow running migration once.
        // Running migration again should not have any affect
        migration_helper::migrate_achievements();
        $achievements = $DB->get_records('totara_competency_achievement');
        $this->assertCount(0, $achievements);

        $assignments = $DB->get_records('totara_competency_assignments');
        $this->assertCount(0, $assignments);

        // Now remove the configuration setting allowing us to run migration again
        migration_helper::queue_migration();
        migration_helper::migrate_achievements();

        $plan_values = $DB->get_records('dp_plan_competency_value');
        $this->assertCount(0, $plan_values);

        $achievements = $DB->get_records('totara_competency_achievement');
        $this->assertCount(1, $achievements);
        $achievement = array_pop($achievements);
        $this->assertEquals($comp_record_history->userid, $achievement->user_id);
        $this->assertEquals($comp_record_history->competencyid, $achievement->competency_id);
        $this->assertNull($achievement->scale_value_id);
        $this->assertEquals(0, $achievement->proficient);
        $this->assertEquals(competency_achievement::ACTIVE_ASSIGNMENT, $achievement->status);
        $this->assertEquals($comp_record_history->timemodified, $achievement->time_created);
        $this->assertEquals($comp_record_history->timemodified, $achievement->time_status);
        $this->assertEquals($comp_record_history->timemodified, $achievement->time_scale_value);
        $this->assertNull($achievement->time_proficient);
        $this->assertTimeCurrent($achievement->last_aggregated);

        $assignments = $DB->get_records('totara_competency_assignments');
        $this->assertCount(1, $assignments);

        $assignment = array_pop($assignments);

        $this->assertEquals($comp_record_history->userid, $assignment->user_group_id); // In this case user group id is user id
        $this->assertEquals('user', $assignment->user_group_type); // User group type is user
        $this->assertEquals(0, $assignment->optional); // Can't remember what optional means
        $this->assertEquals(2, $assignment->status); // Assignment archived
        $this->assertEquals($comp_record_history->competencyid, $assignment->competency_id);
        $this->assertEquals($achievement->assignment_id, $assignment->id);
        $this->assertEquals(0, $assignment->created_by);
        $this->assertEquals($comp_record_history->timemodified, $assignment->created_at);
        $this->assertEquals($comp_record_history->timemodified, $assignment->updated_at);
    }

    public function test_history_record_only() {
        global $DB;

        $comp_record_history = $this->add_comp_record_history(100, 200, 10, 300);

        $this->setCurrentTimeStart();

        migration_helper::queue_migration();
        migration_helper::migrate_achievements();

        $achievements = $DB->get_records('totara_competency_achievement');
        $this->assertCount(1, $achievements);
        $achievement = array_pop($achievements);
        $this->assertEquals($comp_record_history->userid, $achievement->user_id);
        $this->assertEquals($comp_record_history->competencyid, $achievement->competency_id);
        $this->assertEquals(10, $achievement->scale_value_id);
        $this->assertEquals(0, $achievement->proficient);
        $this->assertEquals(competency_achievement::ACTIVE_ASSIGNMENT, $achievement->status);
        $this->assertEquals($comp_record_history->timemodified, $achievement->time_created);
        $this->assertEquals($comp_record_history->timemodified, $achievement->time_status);
        $this->assertEquals($comp_record_history->timemodified, $achievement->time_scale_value);
        $this->assertNull($achievement->time_proficient);
        $this->assertTimeCurrent($achievement->last_aggregated);

        $assignments = $DB->get_records('totara_competency_assignments');
        $this->assertCount(1, $assignments);

        $assignment = array_pop($assignments);

        $this->assertEquals($comp_record_history->userid, $assignment->user_group_id); // In this case user group id is user id
        $this->assertEquals('user', $assignment->user_group_type); // User group type is user
        $this->assertEquals(0, $assignment->optional); // Can't remember what optional means
        $this->assertEquals(2, $assignment->status); // Assignment archived
        $this->assertEquals($comp_record_history->competencyid, $assignment->competency_id);
        $this->assertEquals($achievement->assignment_id, $assignment->id);
        $this->assertEquals(0, $assignment->created_by);
        $this->assertEquals($comp_record_history->timemodified, $assignment->created_at);
        $this->assertEquals($comp_record_history->timemodified, $assignment->updated_at);
    }

    public function test_current_and_historic_comp_record() {
        global $DB;

        $comp_record = $this->add_comp_record(100, 200, 5, 300, 400);

        // The comp_record did not get migrated. This is because it is already supposed to have an
        // equivalent in comp_record_history.
        // This assertion just confirms that we do not allow for this invalid data state.
        $achievements = $DB->get_records('totara_competency_achievement');
        $this->assertCount(0, $achievements);

        // This is the comp_history_record representing the current state.
        $comp_record_history1 = $this->add_comp_record_history(
            $comp_record->competencyid,
            $comp_record->userid,
            $comp_record->proficiency,
            $comp_record->timemodified + 1
        );

        // This is the the historic comp_record_history.
        $comp_record_history2 = $this->add_comp_record_history(
            $comp_record->competencyid,
            $comp_record->userid,
            // It's not the same scale value as the one in comp_record.
            $comp_record->proficiency + 1,
            // It has the same timemodified as the current comp_record
            // And the other history record doesn't match, even though that one is the right one.
            // We're doing this because timemodified isn't reliable anyway.
            $comp_record->timemodified
        );

        $this->setCurrentTimeStart();

        migration_helper::queue_migration();
        migration_helper::migrate_achievements();

        $plan_values = $DB->get_records('dp_plan_competency_value');
        $this->assertCount(0, $plan_values);

        $achievements = $DB->get_records('totara_competency_achievement', null, 'time_created ASC');
        $this->assertCount(2, $achievements);

        // Let's check that only one assignment was created for the first record
        $assignments = $DB->get_records('totara_competency_assignments');
        $this->assertCount(1, $assignments);

        $assignment = array_pop($assignments);

        $achievement1 = array_pop($achievements);
        $this->assertEquals($comp_record_history1->userid, $achievement1->user_id);
        $this->assertEquals($comp_record_history1->competencyid, $achievement1->competency_id);
        $this->assertEquals($assignment->id, $achievement1->assignment_id);
        $this->assertEquals($comp_record_history1->proficiency, $achievement1->scale_value_id);
        $this->assertEquals(0, $achievement1->proficient);
        $this->assertEquals(competency_achievement::ACTIVE_ASSIGNMENT, $achievement1->status);
        $this->assertEquals($comp_record_history1->timemodified, $achievement1->time_created);
        $this->assertEquals($comp_record_history1->timemodified, $achievement1->time_status);
        $this->assertEquals($comp_record_history1->timemodified, $achievement1->time_scale_value);
        $this->assertNull($achievement1->time_proficient);
        $this->assertTimeCurrent($achievement1->last_aggregated);

        $achievement2 = array_pop($achievements);
        $this->assertEquals($comp_record_history2->userid, $achievement2->user_id);
        $this->assertEquals($comp_record_history2->competencyid, $achievement2->competency_id);
        $this->assertEquals($assignment->id, $achievement2->assignment_id);
        $this->assertEquals($comp_record_history2->proficiency, $achievement2->scale_value_id);
        $this->assertEquals(0, $achievement2->proficient);
        $this->assertEquals(competency_achievement::SUPERSEDED, $achievement2->status);
        $this->assertEquals($comp_record_history2->timemodified, $achievement2->time_created);
        $this->assertEquals($comp_record_history2->timemodified, $achievement2->time_status);
        $this->assertEquals($comp_record_history2->timemodified, $achievement2->time_scale_value);
        $this->assertNull($achievement2->time_proficient);
        $this->assertTimeCurrent($achievement2->last_aggregated);

        $this->assertEquals($comp_record_history1->userid, $assignment->user_group_id); // In this case user group id is user id
        $this->assertEquals('user', $assignment->user_group_type); // User group type is user
        $this->assertEquals(0, $assignment->optional); // Can't remember what optional means
        $this->assertEquals(2, $assignment->status); // Assignment archived
        $this->assertEquals($comp_record_history1->competencyid, $assignment->competency_id);
        $this->assertEquals($achievement1->assignment_id, $assignment->id);
        $this->assertEquals(0, $assignment->created_by);
        $this->assertEquals($comp_record_history1->timemodified, $assignment->created_at);
        $this->assertEquals($comp_record_history1->timemodified, $assignment->updated_at);
    }

    public function test_multiple_users_and_competencies() {
        global $DB;

        $this->setAdminUser();

        /** @var totara_competency_generator $competency_generator */
        $competency_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $talking_scale = $competency_generator->create_scale();
        $listening_scale = $competency_generator->create_scale();
        $talking_fw = $competency_generator->create_framework($talking_scale, 'Talking FW');
        $listening_fw = $competency_generator->create_framework($listening_scale, 'Listening FW');
        $competency1 = $competency_generator->create_competency('Talking', $talking_fw);
        $competency2 = $competency_generator->create_competency('Listening', $listening_fw);

        // Competency ids
        $talking = $competency1->id;
        $listening = $competency2->id;

        // User ids.
        $bob = $user1->id;
        $alice = $user2->id;
        $eve = $user3->id;

        $talking_proficient = $DB->get_record('comp_scale_values', ['id' => $talking_scale->minproficiencyid]);
        $talking_not_proficient = $DB->get_record(
            'comp_scale_values',
            ['scaleid' => $talking_scale->id, 'sortorder' => $talking_proficient->sortorder + 1]
        );
        $listening_proficient = $DB->get_record('comp_scale_values', ['id' => $listening_scale->minproficiencyid]);
        $listening_not_proficient = $DB->get_record(
            'comp_scale_values',
            ['scaleid' => $talking_scale->id, 'sortorder' => $listening_proficient->sortorder + 1]
        );

        $talking_bob = $this->add_comp_record($talking, $bob, $talking_proficient->id, 301, 401);
        $talking_alice = $this->add_comp_record($talking, $alice, $talking_proficient->id, 302, 402);
        $listening_bob = $this->add_comp_record($listening, $bob, $listening_proficient->id, 303, 403);
        $listening_alice = $this->add_comp_record($listening, $alice, $listening_proficient->id, 304, 404);

        $talking_bob_latest = $this->add_comp_record_history($talking, $bob, $talking_bob->proficiency, $talking_bob->timemodified);
        $talking_alice_latest = $this->add_comp_record_history($talking, $alice, $talking_alice->proficiency, $talking_alice->timemodified);
        $listening_bob_latest = $this->add_comp_record_history($listening, $bob, $listening_bob->proficiency, $listening_bob->timemodified);
        $listening_alice_latest = $this->add_comp_record_history($listening, $alice, $listening_alice->proficiency, $listening_alice->timemodified);

        $talking_bob_previous = $this->add_comp_record_history($talking, $bob, $talking_not_proficient->id, $talking_bob->timemodified - 10);
        $talking_alice_previous = $this->add_comp_record_history($talking, $alice, $talking_not_proficient->id, $talking_alice->timemodified - 10);
        // Bob has no previous history for listening.
        $listening_alice_previous = $this->add_comp_record_history($listening, $alice, $listening_not_proficient->id, $listening_alice->timemodified - 10);

        $talking_bob_oldest = $this->add_comp_record_history($talking, $bob, null, $talking_bob->timemodified - 20);
        $listening_alice_oldest = $this->add_comp_record_history($listening, $alice, $listening_proficient->id, $listening_alice->timemodified - 20);

        // Eve only has a history record, but no comp_record. This could be invalid data, but let's be aware of what happens with it.
        $listening_eve = $this->add_comp_record_history($listening, $eve, $listening_proficient->id, 500);

        /** @var totara_plan_generator $plan_generator */
        $plan_generator = $this->getDataGenerator()->get_plugin_generator('totara_plan');

        // Create a plan and add competency.
        $plan1 = $plan_generator->create_learning_plan(['userid' => $bob]);
        $plan_generator->add_learning_plan_competency($plan1->id, $talking);

        $plan2 = $plan_generator->create_learning_plan(['userid' => $alice]);
        $plan_generator->add_learning_plan_competency($plan2->id, $talking);

        migration_helper::queue_migration();
        migration_helper::migrate_achievements();

        // Only two users have competencies in the learning plan
        $plan_values = $DB->get_records('dp_plan_competency_value');
        $this->assertCount(2, $plan_values);

        // There should be 1 record for each of the history records added above.
        $this->assertEquals(10, $DB->count_records('totara_competency_achievement'));

        // Only 5 assignments should be created.
        $assignments = $DB->get_records('totara_competency_assignments');
        $this->assertCount(5, $assignments);

        /* Bob / Talking */

        $bob_talking_achievements = $DB->get_records('totara_competency_achievement', ['competency_id' => $talking, 'user_id' => $bob], 'time_created desc');
        $this->assertCount(3, $bob_talking_achievements);

        $achievement = array_shift($bob_talking_achievements);
        $this->assertEquals($talking_bob_latest->timemodified, $achievement->time_created);
        $this->assertEquals($talking_bob_latest->proficiency, $achievement->scale_value_id);
        $this->assertEquals(1, $achievement->proficient);
        $this->assertEquals(competency_achievement::ACTIVE_ASSIGNMENT, $achievement->status);

        // Let's check that it matches the assignment
        $assignment = $DB->get_record('totara_competency_assignments', ['id' => $achievement->assignment_id], '*', MUST_EXIST);

        $this->assertEquals($bob, $assignment->user_group_id); // In this case user group id is user id
        $this->assertEquals('user', $assignment->user_group_type); // User group type is user
        $this->assertEquals(0, $assignment->optional); // Can't remember what optional means
        $this->assertEquals(2, $assignment->status); // Assignment archived
        $this->assertEquals($achievement->competency_id, $assignment->competency_id);
        $this->assertEquals($achievement->assignment_id, $assignment->id);
        $this->assertEquals(0, $assignment->created_by);
        $this->assertEquals($achievement->time_created, $assignment->created_at);
        $this->assertEquals($achievement->time_created, $assignment->updated_at);

        $achievement = array_shift($bob_talking_achievements);
        $this->assertEquals($talking_bob_previous->timemodified, $achievement->time_created);
        $this->assertEquals($talking_bob_previous->proficiency, $achievement->scale_value_id);
        $this->assertEquals(0, $achievement->proficient);
        $this->assertEquals(competency_achievement::SUPERSEDED, $achievement->status);

        $this->assertEquals($achievement->assignment_id, $assignment->id);

        $achievement = array_shift($bob_talking_achievements);
        $this->assertEquals($talking_bob_oldest->timemodified, $achievement->time_created);
        $this->assertEquals($talking_bob_oldest->proficiency, $achievement->scale_value_id);
        $this->assertEquals(0, $achievement->proficient);
        $this->assertEquals(competency_achievement::SUPERSEDED, $achievement->status);

        $this->assertEquals($achievement->assignment_id, $assignment->id);

        /* Bob / Listening */

        $bob_listening_achievements = $DB->get_records('totara_competency_achievement', ['competency_id' => $listening, 'user_id' => $bob], 'time_created desc');
        $this->assertCount(1, $bob_listening_achievements);

        $achievement = array_shift($bob_listening_achievements);
        $this->assertEquals($listening_bob_latest->timemodified, $achievement->time_created);
        $this->assertEquals($listening_bob_latest->proficiency, $achievement->scale_value_id);
        $this->assertEquals(1, $achievement->proficient);
        $this->assertEquals(competency_achievement::ACTIVE_ASSIGNMENT, $achievement->status);

        // Let's check that it matches the assignment
        $assignment = $DB->get_record('totara_competency_assignments', ['id' => $achievement->assignment_id], '*', MUST_EXIST);

        $this->assertEquals($bob, $assignment->user_group_id); // In this case user group id is user id
        $this->assertEquals('user', $assignment->user_group_type); // User group type is user
        $this->assertEquals(0, $assignment->optional); // Can't remember what optional means
        $this->assertEquals(2, $assignment->status); // Assignment archived
        $this->assertEquals($achievement->competency_id, $assignment->competency_id);
        $this->assertEquals($achievement->assignment_id, $assignment->id);
        $this->assertEquals(0, $assignment->created_by);
        $this->assertEquals($achievement->time_created, $assignment->created_at);
        $this->assertEquals($achievement->time_created, $assignment->updated_at);

        /* Alice / Talking */

        $alice_talking_achievements = $DB->get_records('totara_competency_achievement', ['competency_id' => $talking, 'user_id' => $alice], 'time_created desc');
        $this->assertCount(2, $alice_talking_achievements);

        $achievement = array_shift($alice_talking_achievements);
        $this->assertEquals($talking_alice_latest->timemodified, $achievement->time_created);
        $this->assertEquals($talking_alice_latest->proficiency, $achievement->scale_value_id);
        $this->assertEquals(1, $achievement->proficient);
        $this->assertEquals(competency_achievement::ACTIVE_ASSIGNMENT, $achievement->status);

        // Let's check that it matches the assignment
        $assignment = $DB->get_record('totara_competency_assignments', ['id' => $achievement->assignment_id], '*', MUST_EXIST);

        $this->assertEquals($alice, $assignment->user_group_id); // In this case user group id is user id
        $this->assertEquals('user', $assignment->user_group_type); // User group type is user
        $this->assertEquals(0, $assignment->optional); // Can't remember what optional means
        $this->assertEquals(2, $assignment->status); // Assignment archived
        $this->assertEquals($achievement->competency_id, $assignment->competency_id);
        $this->assertEquals($achievement->assignment_id, $assignment->id);
        $this->assertEquals(0, $assignment->created_by);
        $this->assertEquals($achievement->time_created, $assignment->created_at);
        $this->assertEquals($achievement->time_created, $assignment->updated_at);

        $achievement = array_shift($alice_talking_achievements);
        $this->assertEquals($talking_alice_previous->timemodified, $achievement->time_created);
        $this->assertEquals($talking_alice_previous->proficiency, $achievement->scale_value_id);
        $this->assertEquals(0, $achievement->proficient);
        $this->assertEquals(competency_achievement::SUPERSEDED, $achievement->status);

        $this->assertEquals($achievement->assignment_id, $assignment->id);

        /* Alice / Listening */

        $alice_listening_achievements = $DB->get_records('totara_competency_achievement', ['competency_id' => $listening, 'user_id' => $alice], 'time_created desc');
        $this->assertCount(3, $alice_listening_achievements);
        $achievement = array_shift($alice_listening_achievements);
        $this->assertEquals($listening_alice_latest->timemodified, $achievement->time_created);
        //$this->assertEquals($listening_alice_latest->proficiency, $achievement->scale_value_id);
        $this->assertEquals(1, $achievement->proficient);
        $this->assertEquals(competency_achievement::ACTIVE_ASSIGNMENT, $achievement->status);

        // Let's check that it matches the assignment
        $assignment = $DB->get_record('totara_competency_assignments', ['id' => $achievement->assignment_id], '*', MUST_EXIST);

        $this->assertEquals($alice, $assignment->user_group_id); // In this case user group id is user id
        $this->assertEquals('user', $assignment->user_group_type); // User group type is user
        $this->assertEquals(0, $assignment->optional); // Can't remember what optional means
        $this->assertEquals(2, $assignment->status); // Assignment archived
        $this->assertEquals($achievement->competency_id, $assignment->competency_id);
        $this->assertEquals($achievement->assignment_id, $assignment->id);
        $this->assertEquals(0, $assignment->created_by);
        $this->assertEquals($achievement->time_created, $assignment->created_at);
        $this->assertEquals($achievement->time_created, $assignment->updated_at);

        $achievement = array_shift($alice_listening_achievements);
        $this->assertEquals($listening_alice_previous->timemodified, $achievement->time_created);
        $this->assertEquals($listening_alice_previous->proficiency, $achievement->scale_value_id);
        $this->assertEquals(0, $achievement->proficient);
        $this->assertEquals(competency_achievement::SUPERSEDED, $achievement->status);

        $this->assertEquals($achievement->assignment_id, $assignment->id);

        $achievement = array_shift($alice_listening_achievements);
        $this->assertEquals($listening_alice_oldest->timemodified, $achievement->time_created);
        $this->assertEquals($listening_alice_oldest->proficiency, $achievement->scale_value_id);
        $this->assertEquals(1, $achievement->proficient);
        $this->assertEquals(competency_achievement::SUPERSEDED, $achievement->status);

        $this->assertEquals($achievement->assignment_id, $assignment->id);

        /* Eve / Listening */

        $eve_listening_achievements = $DB->get_records('totara_competency_achievement', ['competency_id' => $listening, 'user_id' => $eve], 'time_created desc');
        $this->assertCount(1, $eve_listening_achievements);

        $achievement = array_shift($eve_listening_achievements);
        $this->assertEquals($listening_eve->timemodified, $achievement->time_created);
        $this->assertEquals($listening_eve->proficiency, $achievement->scale_value_id);
        $this->assertEquals(1, $achievement->proficient);
        $this->assertEquals(competency_achievement::ACTIVE_ASSIGNMENT, $achievement->status);

        // Let's check that it matches the assignment
        $assignment = $DB->get_record('totara_competency_assignments', ['id' => $achievement->assignment_id], '*', MUST_EXIST);

        $this->assertEquals($eve, $assignment->user_group_id); // In this case user group id is user id
        $this->assertEquals('user', $assignment->user_group_type); // User group type is user
        $this->assertEquals(0, $assignment->optional); // Can't remember what optional means
        $this->assertEquals(2, $assignment->status); // Assignment archived
        $this->assertEquals($achievement->competency_id, $assignment->competency_id);
        $this->assertEquals($achievement->assignment_id, $assignment->id);
        $this->assertEquals(0, $assignment->created_by);
        $this->assertEquals($achievement->time_created, $assignment->created_at);
        $this->assertEquals($achievement->time_created, $assignment->updated_at);
    }

    /**
     * Same as test_current_and_historic_comp_record. We're just disabling perform.
     *
     * There should be no difference when perform is disabled.
     */
    public function test_current_and_historic_comp_record_perform_disabled() {
        global $DB;

        advanced_feature::disable('competency_assignment');

        $this->assertTrue(advanced_feature::is_disabled('competency_assignment'));

        $comp_record = $this->add_comp_record(100, 200, 5, 300, 400);

        // The comp_record did not get migrated. This is because it is already supposed to have an
        // equivalent in comp_record_history.
        // This assertion just confirms that we do not allow for this invalid data state.
        $achievements = $DB->get_records('totara_competency_achievement');
        $this->assertCount(0, $achievements);

        // This is the comp_history_record representing the current state.
        $comp_record_history1 = $this->add_comp_record_history(
            $comp_record->competencyid,
            $comp_record->userid,
            $comp_record->proficiency,
            $comp_record->timemodified + 1
        );

        // This is the the historic comp_record_history.
        $comp_record_history2 = $this->add_comp_record_history(
            $comp_record->competencyid,
            $comp_record->userid,
            // It's not the same scale value as the one in comp_record.
            $comp_record->proficiency + 1,
            // It has the same timemodified as the current comp_record
            // And the other history record doesn't match, even though that one is the right one.
            // We're doing this because timemodified isn't reliable anyway.
            $comp_record->timemodified
        );

        $this->setCurrentTimeStart();

        migration_helper::queue_migration();
        migration_helper::migrate_achievements();

        $achievements = $DB->get_records('totara_competency_achievement', null, 'time_created ASC, id ASC');
        $this->assertCount(2, $achievements);

        // Let's check that only one assignment was created for the first record
        $assignments = $DB->get_records('totara_competency_assignments');
        $this->assertCount(1, $assignments);

        $assignment = array_pop($assignments);

        $achievement1 = array_pop($achievements);
        $this->assertEquals($comp_record_history1->userid, $achievement1->user_id);
        $this->assertEquals($comp_record_history1->competencyid, $achievement1->competency_id);
        $this->assertEquals($assignment->id, $achievement1->assignment_id);
        $this->assertEquals($comp_record_history1->proficiency, $achievement1->scale_value_id);
        $this->assertEquals(0, $achievement1->proficient);
        $this->assertEquals(competency_achievement::ACTIVE_ASSIGNMENT, $achievement1->status);
        $this->assertEquals($comp_record_history1->timemodified, $achievement1->time_created);
        $this->assertEquals($comp_record_history1->timemodified, $achievement1->time_status);
        $this->assertEquals($comp_record_history1->timemodified, $achievement1->time_scale_value);
        $this->assertNull($achievement1->time_proficient);
        $this->assertTimeCurrent($achievement1->last_aggregated);

        $achievement2 = array_pop($achievements);
        $this->assertEquals($comp_record_history2->userid, $achievement2->user_id);
        $this->assertEquals($comp_record_history2->competencyid, $achievement2->competency_id);
        $this->assertEquals($assignment->id, $achievement2->assignment_id);
        $this->assertEquals($comp_record_history2->proficiency, $achievement2->scale_value_id);
        $this->assertEquals(0, $achievement2->proficient);
        $this->assertEquals(competency_achievement::SUPERSEDED, $achievement2->status);
        $this->assertEquals($comp_record_history2->timemodified, $achievement2->time_created);
        $this->assertEquals($comp_record_history2->timemodified, $achievement2->time_status);
        $this->assertEquals($comp_record_history2->timemodified, $achievement2->time_scale_value);
        $this->assertNull($achievement2->time_proficient);
        $this->assertTimeCurrent($achievement2->last_aggregated);

        $this->assertEquals($comp_record_history1->userid, $assignment->user_group_id); // In this case user group id is user id
        $this->assertEquals('user', $assignment->user_group_type); // User group type is user
        $this->assertEquals(0, $assignment->optional); // Can't remember what optional means
        $this->assertEquals(2, $assignment->status); // Assignment archived
        $this->assertEquals($comp_record_history1->competencyid, $assignment->competency_id);
        $this->assertEquals($achievement1->assignment_id, $assignment->id);
        $this->assertEquals(0, $assignment->created_by);
        $this->assertEquals($comp_record_history1->timemodified, $assignment->created_at);
        $this->assertEquals($comp_record_history1->timemodified, $assignment->updated_at);
    }
}

<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @package totara_competency
 * @category test
 */

use core\orm\query\builder;
use totara_competency\assignment_create_exception;
use totara_competency\entity\assignment as assignment_entity;
use totara_competency\event\assignment_created;
use totara_competency\models\assignment as assignment_model;
use totara_competency\models\user_group\cohort as cohort_model;
use totara_competency\models\user_group\organisation as organisation_model;
use totara_competency\models\user_group\position as position_model;
use hierarchy_organisation\entity\organisation as organisation_entity;
use hierarchy_position\entity\position as position_entity;
use totara_competency\user_groups;
use totara_hierarchy\entity\competency;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__.'/assignment_model_base_testcase.php');

/**
 * @group totara_competency
 */
class totara_competency_assignment_model_create_testcase extends assignment_model_base_testcase {

    public function test_create_assignment_for_user() {
        $data = $this->create_data();

        $this->setUser($data->user1->id);

        $competency_id = $data->comp1->id;
        $type = assignment_entity::TYPE_ADMIN;
        $user_group_type = user_groups::USER;
        $user_group_id = $data->user1->id;
        $status = assignment_entity::STATUS_ACTIVE;

        $assignment = assignment_model::create($competency_id, $type, $user_group_type, $user_group_id, $status);

        $this->assertInstanceOf(assignment_model::class, $assignment);

        $this->assertEquals($competency_id, $assignment->get_field('competency_id'));
        $this->assertEquals($type, $assignment->get_field('type'));
        $this->assertEquals($user_group_type, $assignment->get_field('user_group_type'));
        $this->assertEquals($user_group_id, $assignment->get_field('user_group_id'));
        $this->assertEquals($status, $assignment->get_field('status'));
        $this->assertEquals($data->user1->id, $assignment->get_field('created_by'));
        $this->assertGreaterThan(0, $assignment->get_field('created_at'));
        $this->assertGreaterThan(0, $assignment->get_field('updated_at'));
        $this->assertTrue($assignment->should_expand());
        $this->assertEquals(null, $assignment->get_field('archived_at'));
    }

    public function test_create_assignment_event_is_fired() {
        $expected_data = $this->create_data();

        $this->setUser($expected_data->user1->id);

        $competency_id = $expected_data->comp1->id;
        $type = assignment_entity::TYPE_ADMIN;
        $user_group_type = user_groups::USER;
        $user_group_id = $expected_data->user1->id;
        $status = assignment_entity::STATUS_ACTIVE;

        $event_sink = $this->redirectEvents();

        $assignment = assignment_model::create($competency_id, $type, $user_group_type, $user_group_id, $status);
        $this->assertInstanceOf(assignment_model::class, $assignment);

        $events = $event_sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);
        $this->assertInstanceOf(assignment_created::class, $event);

        $actual_data = $event->get_data();
        $this->assertEquals($assignment->get_id(), $actual_data['objectid']);
        $this->assertEquals(
            [
                'type' => $type,
                'user_group_type' => $user_group_type,
                'user_group_id' => $user_group_id,
                'status' => $status,
            ],
            $actual_data['other']
        );
    }

    public function test_create_active_assignment_for_user() {
        $data = $this->create_data();

        $this->setUser($data->user1->id);

        $competency_id = $data->comp1->id;
        $type = assignment_entity::TYPE_ADMIN;
        $user_group_type = user_groups::USER;
        $user_group_id = $data->user1->id;
        $status = assignment_entity::STATUS_ACTIVE;

        $assignment = assignment_model::create($competency_id, $type, $user_group_type, $user_group_id, $status);
        $this->assertInstanceOf(assignment_model::class, $assignment);
        $this->assertEquals(assignment_entity::STATUS_ACTIVE, $assignment->get_status());
    }

    public function test_create_draft_assignment_for_user() {
        $data = $this->create_data();

        $this->setUser($data->user1->id);

        $competency_id = $data->comp1->id;
        $type = assignment_entity::TYPE_ADMIN;
        $user_group_type = user_groups::USER;
        $user_group_id = $data->user1->id;
        $status = assignment_entity::STATUS_DRAFT;

        $assignment = assignment_model::create($competency_id, $type, $user_group_type, $user_group_id, $status);
        $this->assertInstanceOf(assignment_model::class, $assignment);
        $this->assertEquals($status, $assignment->get_status());
        $this->assertFalse($assignment->should_expand());
    }

    public function test_cannot_create_archived_assignment_for_user() {
        $data = $this->create_data();

        $this->setUser($data->user1->id);

        $competency_id = $data->comp1->id;
        $type = assignment_entity::TYPE_ADMIN;
        $user_group_type = user_groups::USER;
        $user_group_id = $data->user1->id;
        $status = assignment_entity::STATUS_ARCHIVED;

        $this->expectException(assignment_create_exception::class);
        $this->expectExceptionMessage('Invalid assignment status supplied');

        assignment_model::create($competency_id, $type, $user_group_type, $user_group_id, $status);
    }

    public function test_cannot_create_with_invalid_status_given() {
        $data = $this->create_data();

        $this->setUser($data->user1->id);

        $competency_id = $data->comp1->id;
        $type = assignment_entity::TYPE_ADMIN;
        $user_group_type = user_groups::USER;
        $user_group_id = 1;
        $status = 999;

        $this->expectException(assignment_create_exception::class);
        $this->expectExceptionMessage('Invalid assignment status supplied');

        assignment_model::create($competency_id, $type, $user_group_type, $user_group_id, $status);
    }

    public function test_caanot_create_with_non_existent_competency() {
        $this->setAdminUser();

        $competency_id = 9999;
        $type = assignment_entity::TYPE_ADMIN;
        $user_group_type = user_groups::USER;
        $user_group_id = 1;
        $status = assignment_entity::STATUS_ACTIVE;

        $this->expectException(assignment_create_exception::class);
        $this->expectExceptionMessage('Non-existent or invisible competency id given');

        assignment_model::create($competency_id, $type, $user_group_type, $user_group_id, $status);
    }

    public function test_cannot_create_with_invisible_competency() {
        $this->setAdminUser();

        $data = $this->create_data();

        $comp = clone $data->comp1;
        $comp->visible = false;
        $comp->save();

        $competency_id = $comp->id;
        $type = assignment_entity::TYPE_ADMIN;
        $user_group_type = user_groups::USER;
        $user_group_id = 1;
        $status = assignment_entity::STATUS_ACTIVE;

        $this->expectException(assignment_create_exception::class);
        $this->expectExceptionMessage('Non-existent or invisible competency id given');

        assignment_model::create($competency_id, $type, $user_group_type, $user_group_id, $status);
    }

    public function test_cannot_create_with_non_existent_user_group() {
        $this->setAdminUser();

        $data = $this->create_data();

        $competency_id = $data->comp1->id;
        $type = assignment_entity::TYPE_ADMIN;
        $user_group_type = user_groups::USER;
        $user_group_id = 9999;
        $status = assignment_entity::STATUS_ACTIVE;

        $this->expectException(assignment_create_exception::class);
        $this->expectExceptionMessage('User group not found');

        assignment_model::create($competency_id, $type, $user_group_type, $user_group_id, $status);
    }

    public function test_cannot_create_with_non_existent_user_group_type() {
        $this->setAdminUser();

        $data = $this->create_data();

        $competency_id = $data->comp1->id;
        $type = assignment_entity::TYPE_ADMIN;
        $user_group_type = 'foobar';
        $user_group_id = 9999;
        $status = assignment_entity::STATUS_ACTIVE;

        $this->expectException(assignment_create_exception::class);
        $this->expectExceptionMessage('Invalid user group has been passed');

        assignment_model::create($competency_id, $type, $user_group_type, $user_group_id, $status);
    }

    public function test_cannot_create_with_invalid_type() {
        $this->setAdminUser();

        $data = $this->create_data();

        $competency_id = $data->comp1->id;
        $type = 'foobar';
        $user_group_type = user_groups::USER;
        $user_group_id = $data->user1->id;
        $status = assignment_entity::STATUS_ACTIVE;

        $this->expectException(assignment_create_exception::class);
        $this->expectExceptionMessage('Invalid assignment type supplied');

        assignment_model::create($competency_id, $type, $user_group_type, $user_group_id, $status);
    }

    public function test_can_create_with_other_type_if_assignable() {
        $this->setAdminUser();

        $data = $this->create_data();

        $competency_id = $data->comp1->id;
        $this->set_other_assignable($data->comp1->id);

        $type = assignment_entity::TYPE_OTHER;
        $user_group_type = user_groups::USER;
        $user_group_id = $data->user1->id;
        $status = assignment_entity::STATUS_ACTIVE;

        $assignment = assignment_model::create($competency_id, $type, $user_group_type, $user_group_id, $status);
        $this->assertInstanceOf(assignment_model::class, $assignment);
    }

    public function test_cannot_create_with_other_type_if_not_assignable() {
        $this->setAdminUser();

        $data = $this->create_data();

        $competency_id = $data->comp1->id;
        $type = assignment_entity::TYPE_OTHER;
        $user_group_type = user_groups::USER;
        $user_group_id = $data->user1->id;
        $status = assignment_entity::STATUS_ACTIVE;

        $this->expectException(assignment_create_exception::class);
        $this->expectExceptionMessage('Competency cannot be be assigned by given type');

        assignment_model::create($competency_id, $type, $user_group_type, $user_group_id, $status);
    }

    public function test_can_create_with_self_type_if_assignable() {
        $this->setAdminUser();

        $data = $this->create_data();

        $competency_id = $data->comp1->id;
        $this->set_self_assignable($data->comp1->id);

        $type = assignment_entity::TYPE_SELF;
        $user_group_type = user_groups::USER;
        $user_group_id = $data->user1->id;
        $status = assignment_entity::STATUS_ACTIVE;

        $assignment = assignment_model::create($competency_id, $type, $user_group_type, $user_group_id, $status);
        $this->assertInstanceOf(assignment_model::class, $assignment);
    }

    public function test_cannot_create_with_self_type_if_not_assignable() {
        $this->setAdminUser();

        $data = $this->create_data();

        $competency_id = $data->comp1->id;
        $type = assignment_entity::TYPE_SELF;
        $user_group_type = user_groups::USER;
        $user_group_id = $data->user1->id;
        $status = assignment_entity::STATUS_ACTIVE;

        $this->expectException(assignment_create_exception::class);
        $this->expectExceptionMessage('Competency cannot be be assigned by given type');

        assignment_model::create($competency_id, $type, $user_group_type, $user_group_id, $status);
    }

    public function test_cannot_create_for_deleted_user() {
        $this->setAdminUser();

        $data = $this->create_data();

        delete_user($data->user1);

        $competency_id = $data->comp1->id;
        $type = assignment_entity::TYPE_ADMIN;
        $user_group_type = user_groups::USER;
        $user_group_id = $data->user1->id;
        $status = assignment_entity::STATUS_ACTIVE;

        $this->expectException(assignment_create_exception::class);
        $this->expectExceptionMessage('User group not found');

        assignment_model::create($competency_id, $type, $user_group_type, $user_group_id, $status);
    }

    public function test_invalid_combination_of_type_and_user_group_type() {
        $data = $this->create_data();

        $this->setUser($data->user1->id);

        $competency_id = $data->comp1->id;
        $user_group_id = $data->user1->id;
        $status = assignment_entity::STATUS_ACTIVE;

        $user_only_types = [assignment_entity::TYPE_OTHER, assignment_entity::TYPE_SYSTEM, assignment_entity::TYPE_SELF];
        foreach ($user_only_types as $user_only_type) {
            $disallowed_user_types = [user_groups::POSITION, user_groups::COHORT, user_groups::ORGANISATION];
            foreach ($disallowed_user_types as $disallowed_type) {
                try {
                    assignment_model::create($competency_id, $user_only_type, $disallowed_type, $user_group_id, $status);
                    $this->fail('Expected fail due to invalid type and user_group_type combination');
                } catch (assignment_create_exception $e) {
                    $this->assertMatchesRegularExpression('/Invalid combination of type and user_group_type given/', $e->getMessage());
                }
            }
        }
    }

    public function test_duplicate_assignments_are_not_created() {
        $data = $this->create_data();

        $this->setAdminUser();

        $competency_id = $data->comp1->id;
        $type = assignment_entity::TYPE_ADMIN;
        $user_group_type = user_groups::USER;
        $user_group_id = $data->user1->id;
        $status = assignment_entity::STATUS_ACTIVE;

        $assignment = assignment_model::create($competency_id, $type, $user_group_type, $user_group_id, $status);
        $this->assertInstanceOf(assignment_model::class, $assignment);
        $this->assertEquals(1, assignment_entity::repository()->count());

        // Try again, no duplicate should be created
        $assignment = assignment_model::create($competency_id, $type, $user_group_type, $user_group_id, $status);
        $this->assertEmpty($assignment);
        $this->assertEquals(1, assignment_entity::repository()->count());

        // Now change the type which should result in a new assignment
        $type = assignment_entity::TYPE_OTHER;

        $this->set_other_assignable($competency_id);

        $assignment = assignment_model::create($competency_id, $type, $user_group_type, $user_group_id, $status);
        $this->assertInstanceOf(assignment_model::class, $assignment);
        $this->assertEquals(2, assignment_entity::repository()->count());

        // Try again, no duplicate should be created
        $assignment = assignment_model::create($competency_id, $type, $user_group_type, $user_group_id, $status);
        $this->assertEmpty($assignment);
        $this->assertEquals(2, assignment_entity::repository()->count());

        // Now change the creator which should result in a new assignment
        // as we want two users create other assignments
        $this->setUser($data->user2->id);

        // A new assignment created by another user should have been created
        $assignment = assignment_model::create($competency_id, $type, $user_group_type, $user_group_id, $status);
        $this->assertInstanceOf(assignment_model::class, $assignment);
        $this->assertEquals(3, assignment_entity::repository()->count());
    }

    public function test_create_position_assignment() {
        $data = $this->create_data();

        $this->setAdminUser();

        $hierarchy_generator = $this->generator()->hierarchy_generator();
        $fw = $hierarchy_generator->create_pos_frame([]);
        $pos = $hierarchy_generator->create_pos(['frameworkid' => $fw->id]);

        $competency_id = $data->comp1->id;
        $type = assignment_entity::TYPE_ADMIN;
        $user_group_type = user_groups::POSITION;
        $user_group_id = $pos->id;
        $status = assignment_entity::STATUS_ACTIVE;

        $assignment = assignment_model::create($competency_id, $type, $user_group_type, $user_group_id, $status);
        $this->assertInstanceOf(assignment_model::class, $assignment);

        $this->assertEquals($type, $assignment->get_field('type'));
        $this->assertEquals($user_group_type, $assignment->get_field('user_group_type'));
        $this->assertEquals($user_group_id, $assignment->get_field('user_group_id'));

        $user_group = $assignment->get_user_group();
        $this->assertInstanceOf(position_model::class, $user_group);
        $this->assertEquals(user_groups::POSITION, $user_group->get_type());
        $this->assertEquals($user_group_id, $user_group->get_id());
        $this->assertFalse($user_group->is_deleted());
    }

    public function test_cannot_create_assignment_for_hidden_position() {
        $data = $this->create_data();

        $this->setAdminUser();

        $hierarchy_generator = $this->generator()->hierarchy_generator();
        $fw = $hierarchy_generator->create_pos_frame([]);
        $pos = $hierarchy_generator->create_pos(['frameworkid' => $fw->id]);

        $pos = new position_entity($pos);
        $pos->visible = 0;
        $pos->save();

        $competency_id = $data->comp1->id;
        $type = assignment_entity::TYPE_ADMIN;
        $user_group_type = user_groups::POSITION;
        $user_group_id = $pos->id;
        $status = assignment_entity::STATUS_ACTIVE;

        $this->expectException(assignment_create_exception::class);
        $this->expectExceptionMessage('User group not found');

        assignment_model::create($competency_id, $type, $user_group_type, $user_group_id, $status);
    }

    public function test_create_organisation_assignment() {
        $data = $this->create_data();

        $this->setAdminUser();

        $hierarchy_generator = $this->generator()->hierarchy_generator();
        $fw = $hierarchy_generator->create_org_frame([]);
        $org = $hierarchy_generator->create_org(['frameworkid' => $fw->id]);

        $competency_id = $data->comp1->id;
        $type = assignment_entity::TYPE_ADMIN;
        $user_group_type = user_groups::ORGANISATION;
        $user_group_id = $org->id;
        $status = assignment_entity::STATUS_ACTIVE;

        $assignment = assignment_model::create($competency_id, $type, $user_group_type, $user_group_id, $status);
        $this->assertInstanceOf(assignment_model::class, $assignment);

        $this->assertEquals($type, $assignment->get_field('type'));
        $this->assertEquals($user_group_type, $assignment->get_field('user_group_type'));
        $this->assertEquals($user_group_id, $assignment->get_field('user_group_id'));

        $user_group = $assignment->get_user_group();
        $this->assertInstanceOf(organisation_model::class, $user_group);
        $this->assertEquals(user_groups::ORGANISATION, $user_group->get_type());
        $this->assertEquals($user_group_id, $user_group->get_id());
        $this->assertFalse($user_group->is_deleted());
    }

    public function test_cannot_create_assignment_for_hidden_organisation() {
        $data = $this->create_data();

        $this->setAdminUser();

        $hierarchy_generator = $this->generator()->hierarchy_generator();
        $fw = $hierarchy_generator->create_org_frame([]);
        $org = $hierarchy_generator->create_org(['frameworkid' => $fw->id]);

        $org = new organisation_entity($org);
        $org->visible = 0;
        $org->save();

        $competency_id = $data->comp1->id;
        $type = assignment_entity::TYPE_ADMIN;
        $user_group_type = user_groups::ORGANISATION;
        $user_group_id = $org->id;
        $status = assignment_entity::STATUS_ACTIVE;

        $this->expectException(assignment_create_exception::class);
        $this->expectExceptionMessage('User group not found');

        assignment_model::create($competency_id, $type, $user_group_type, $user_group_id, $status);
    }

    public function test_create_cohort_assignment() {
        $data = $this->create_data();

        $this->setAdminUser();

        $gen = $this->getDataGenerator();

        $cohort = $gen->create_cohort([
            'name' => 'Cohort 1 staff',
            'description' => 'We add here important people',
            'idnumber' => 'the_first',
        ]);

        $competency_id = $data->comp1->id;
        $type = assignment_entity::TYPE_ADMIN;
        $user_group_type = user_groups::COHORT;
        $user_group_id = $cohort->id;
        $status = assignment_entity::STATUS_ACTIVE;

        $assignment = assignment_model::create($competency_id, $type, $user_group_type, $user_group_id, $status);
        $this->assertInstanceOf(assignment_model::class, $assignment);

        $this->assertEquals($type, $assignment->get_field('type'));
        $this->assertEquals($user_group_type, $assignment->get_field('user_group_type'));
        $this->assertEquals($user_group_id, $assignment->get_field('user_group_id'));

        $user_group = $assignment->get_user_group();
        $this->assertInstanceOf(cohort_model::class, $user_group);
        $this->assertEquals(user_groups::COHORT, $user_group->get_type());
        $this->assertEquals($user_group_id, $user_group->get_id());
        $this->assertFalse($user_group->is_deleted());
    }

    private function set_self_assignable($competency_id) {
        builder::table('comp_assign_availability')
            ->insert([
                'comp_id' => $competency_id,
                'availability' => competency::ASSIGNMENT_CREATE_SELF
            ]);
    }

    private function set_other_assignable($competency_id) {
        builder::table('comp_assign_availability')
            ->insert([
                'comp_id' => $competency_id,
                'availability' => competency::ASSIGNMENT_CREATE_OTHER
            ]);
    }

}

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

use core\entity\user as user_entity;
use core\orm\entity\repository;
use core\orm\query\builder;
use totara_competency\admin_setting_unassign_behaviour;
use totara_competency\entity\assignment as assignment_entity;
use totara_competency\entity\competency_achievement;
use totara_competency\entity\competency_assignment_user;
use totara_competency\expand_task;
use totara_competency\models\assignment as assignment_model;
use totara_competency\models\profile\proficiency_value;
use totara_competency\models\user_group\user;
use totara_competency\user_groups;
use totara_hierarchy\entity\competency as competency_entity;
use totara_hierarchy\entity\scale;
use totara_job\job_assignment;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__.'/assignment_model_base_testcase.php');

/**
 * @group totara_competency
 */
class totara_competency_assignment_model_testcase extends assignment_model_base_testcase {

    public function test_load_assignment() {
        $data = $this->create_data();

        $this->setUser($data->user1->id);

        $assignment = $this->create_active_user_assignment($data->comp1->id, $data->user1->id);

        $expected_assignment = assignment_entity::repository()->find($assignment->get_id());

        $loaded_assignment = assignment_model::load_by_entity($expected_assignment);
        $this->assertEquals($assignment, $loaded_assignment);

        $loaded_assignment = assignment_model::load_by_id($assignment->get_id());
        $this->assertEquals($assignment, $loaded_assignment);
    }

    public function test_load_user_assignments() {
        $data = $this->create_data();

        $this->setUser($data->user1->id);

        $assignment = $this->create_active_user_assignment($data->comp1->id, $data->user1->id);

        // another one for a different user
        $assignment2 = $this->create_active_user_assignment($data->comp1->id, $data->user2->id);

        $this->assertEquals(0, $assignment->get_assigned_users()->count());
        $this->assertEquals(0, $assignment2->get_assigned_users()->count());

        $task = new expand_task($GLOBALS['DB']);
        $task->expand_all();

        // This is to force-reload the assigned users, otherwise only cached results will be returned
        $assignment->get_entity()->load_relation('assignment_users');
        /** @var competency_assignment_user $user */
        $users = $assignment->get_assigned_users();

        $this->assertEquals(1, $users->count());
        $user = $users->first();
        $this->assertEquals($assignment->get_id(), $user->assignment_id);
        $this->assertEquals($data->user1->id, $user->user_id);
        $this->assertEquals($data->comp1->id, $user->competency_id);

        // This is to force-reload the assigned users, otherwise only cached results will be returned
        $assignment2->get_entity()->load_relation('assignment_users');
        $users = $assignment2->get_assigned_users();
        $this->assertEquals(1, $users->count());
        $user = $users->first();
        $this->assertEquals($assignment2->get_id(), $user->assignment_id);
        $this->assertEquals($data->user2->id, $user->user_id);
        $this->assertEquals($data->comp1->id, $user->competency_id);
    }

    public function test_get_competency_from_assignment() {
        $data = $this->create_data();

        $this->setUser($data->user1->id);

        $assignment = $this->create_active_user_assignment($data->comp1->id, $data->user1->id);

        $comp = $assignment->get_competency();
        $this->assertInstanceOf(competency_entity::class, $comp);
        $this->assertEquals($data->comp1->id, $comp->id);
    }

    public function test_get_user_group() {
        $data = $this->create_data();

        $this->setUser($data->user1->id);

        $assignment = $this->create_active_user_assignment($data->comp1->id, $data->user1->id);

        $user_group = $assignment->get_user_group();
        $this->assertInstanceOf(user::class, $user_group);
        $this->assertEquals(user_groups::USER, $user_group->get_type());
        $this->assertEquals($data->user1->id, $user_group->get_id());
        $this->assertFalse($user_group->is_deleted());

        $this->assertEquals($user_group->get_name(), $assignment->get_user_group_name());
    }

    public function test_user_group_entity() {
        $data = $this->create_data();
        $this->setUser($data->user1->id);

        $assignment = $this->create_active_user_assignment($data->comp1->id, $data->user1->id);

        $user_group = $assignment->get_user_group();

        $user_entity = new user_entity($user_group->get_id());

        $assignment->set_user_group_entity($user_entity);

        $entity = $assignment->get_user_group_entity();

        $this->assertEquals($user_entity->id, $entity->id);
    }

    public function test_statuses() {
        $data = $this->create_data();

        $this->setUser($data->user1->id);

        $assignment = $this->create_active_user_assignment($data->comp1->id, $data->user1->id);

        /** @var assignment_entity $assignment_entity */
        $assignment_entity = assignment_entity::repository()->find($assignment->get_id());

        $this->assertEquals(assignment_entity::STATUS_ACTIVE, $assignment->get_status());
        $this->assertTrue($assignment->is_active());
        $this->assertFalse($assignment->is_draft());
        $this->assertFalse($assignment->is_archived());

        $assignment_entity->status = assignment_entity::STATUS_DRAFT;
        $assignment_entity->save();

        $assignment = assignment_model::load_by_id($assignment->get_id());
        $this->assertEquals(assignment_entity::STATUS_DRAFT, $assignment->get_status());
        $this->assertFalse($assignment->is_active());
        $this->assertTrue($assignment->is_draft());
        $this->assertFalse($assignment->is_archived());

        $assignment_entity->status = assignment_entity::STATUS_ARCHIVED;
        $assignment_entity->save();

        $assignment = assignment_model::load_by_id($assignment->get_id());
        $this->assertEquals(assignment_entity::STATUS_ARCHIVED, $assignment->get_status());
        $this->assertFalse($assignment->is_active());
        $this->assertFalse($assignment->is_draft());
        $this->assertTrue($assignment->is_archived());
    }

    public function test_min_proficiency_value_overrides(): void {
        $data = $this->create_data();

        $this->setUser($data->user1->id);

        $assignment = $this->create_active_user_assignment($data->comp1->id, $data->user1->id);

        /** @var assignment_entity $assignment_entity */
        $assignment_entity = assignment_entity::repository()->find($assignment->get_id());
        $assignment_model = assignment_model::load_by_entity($assignment_entity);

        $this->assertFalse($assignment_model->has_default_proficiency_value_override());
        $this->assertEquals(
            $assignment_entity->competency->scale->minproficiencyid,
            $assignment_model->get_min_value()->id
        );


        $assignment_entity->minproficiencyid = $assignment_entity->competency->scale->minproficiencyid;
        $assignment_entity->save();
        $assignment_model = assignment_model::load_by_id($assignment_entity->id);

        // Even if the override is set to the scale min, there is still an override present.
        $this->assertTrue($assignment_model->has_default_proficiency_value_override());
        $this->assertEquals(
            $assignment_entity->competency->scale->minproficiencyid,
            $assignment_model->get_min_value()->id
        );


        $assignment_entity->minproficiencyid = $assignment_entity->competency->scale->defaultid;
        $assignment_entity->save();
        $assignment_model = assignment_model::load_by_id($assignment_entity->id);

        $this->assertTrue($assignment_model->has_default_proficiency_value_override());
        $this->assertEquals(
            $assignment_entity->competency->scale->defaultid,
            $assignment_model->get_min_value()->id
        );
    }

    public function test_get_my_value(): void {
        $data = $this->create_data();

        self::setUser($data->user1->id);

        $assignment = $this->create_active_user_assignment($data->comp1->id, $data->user1->id);

        // Framework is created with the default scale.
        /** @var scale $scale */
        $scale = scale::repository()
            ->with(['min_proficient_value', 'values'])
            ->one();
        $competent = $scale->min_proficient_value;
        $not_competent = $scale->values->first();

        $achievement = new competency_achievement([
            'competency_id' => $assignment->competency_id,
            'user_id' => $data->user1->id,
            'assignment_id' => $assignment->id,
            'scale_value_id' => $competent->id,
            'proficient' => 1,
            'status' => 0,
            'time_created' => time(),
            'time_status' => time(),
            'time_proficient' => time(),
            'time_scale_value' => time(),
            'last_aggregated' => time(),
        ]);
        $achievement->save();

        /** @var assignment_entity $assignment_entity */
        $assignment_entity = assignment_entity::repository()
            ->with('current_achievement')
            ->where('id', $assignment->get_id())
            ->one();
        $assignment_model = assignment_model::load_by_entity($assignment_entity);
        $my_value = $assignment_model->get_my_value();

        self::assertInstanceOf(proficiency_value::class, $my_value);
        self::assertEquals('Competent', $my_value->name);
        self::assertTrue($my_value->proficient);
        self::assertEquals(100.0, $my_value->percentage);

        // Set the achievement to "not proficient". That should be reflected in the proficiency_value returned.
        $achievement->proficient = 0;
        $achievement->update();

        // Re-fetch entity.
        /** @var assignment_entity $assignment_entity */
        $assignment_entity = assignment_entity::repository()
            ->with('current_achievement')
            ->where('id', $assignment->get_id())
            ->one();
        $assignment_model = assignment_model::load_by_entity($assignment_entity);
        $my_value = $assignment_model->get_my_value();

        self::assertInstanceOf(proficiency_value::class, $my_value);
        self::assertEquals('Competent', $my_value->name);
        self::assertFalse($my_value->proficient);
        self::assertEquals(100.0, $my_value->percentage);
    }

    public function test_user_is_assigned_and_unassigned_at() {
        $this->setAdminUser();
        // Create user
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        // Make sure we keep the data
        set_config('unassign_behaviour', admin_setting_unassign_behaviour::KEEP, 'totara_competency');

        /** @var totara_hierarchy_generator $totara_hierarchy_generator */
        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $fw = $hierarchy_generator->create_pos_frame(['fullname' => 'Pos Framework']);
        // Create position 1
        $position1 = $hierarchy_generator->create_pos(['frameworkid' => $fw->id, 'fullname' => 'Position 1']);
        // Create position 2
        $position2 = $hierarchy_generator->create_pos(['frameworkid' => $fw->id, 'fullname' => 'Position 2']);
        // Create position 3
        $position3 = $hierarchy_generator->create_pos(['frameworkid' => $fw->id, 'fullname' => 'Position 3']);

        /** @var totara_competency_assignment_generator $assignment_generator */
        $assignment_generator = $this->getDataGenerator()
            ->get_plugin_generator('totara_competency')
            ->assignment_generator();

        // Create competency
        /** @var totara_hierarchy_generator $totara_hierarchy_generator */
        $totara_hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $compfw = $totara_hierarchy_generator->create_comp_frame([]);
        $comp = $totara_hierarchy_generator->create_comp(['frameworkid' => $compfw->id]);

        // Create position 1 assignment
        $pos_assignment1 = $assignment_generator->create_position_assignment(
            $comp->id,
            $position1->id,
            ['status' => assignment_entity::STATUS_ACTIVE]
        );
        // Create position 2 assignment
        $pos_assignment2 = $assignment_generator->create_position_assignment(
            $comp->id,
            $position2->id,
            ['status' => assignment_entity::STATUS_ACTIVE]
        );
        // Create position 3 assignment
        $pos_assignment3 = $assignment_generator->create_position_assignment(
            $comp->id,
            $position3->id,
            ['status' => assignment_entity::STATUS_ACTIVE]
        );

        $ja1 = job_assignment::create_default($user1->id, ['positionid' => $position1->id]);
        $ja2 = job_assignment::create_default($user1->id, ['positionid' => $position2->id]);
        $ja3 = job_assignment::create_default($user1->id, ['positionid' => $position3->id]);

        (new expand_task(builder::get_db()))->expand_all();

        $pos_assignment1 = assignment_model::load_by_id($pos_assignment1->id);
        $pos_assignment2 = assignment_model::load_by_id($pos_assignment2->id);
        $pos_assignment3 = assignment_model::load_by_id($pos_assignment3->id);

        $this->assertTrue($pos_assignment1->is_assigned($user1->id));
        $this->assertFalse($pos_assignment1->is_assigned($user2->id));
        $this->assertNull($pos_assignment1->get_unassigned_at($user1->id));
        $this->assertNull($pos_assignment1->get_unassigned_at($user2->id));

        $this->assertTrue($pos_assignment2->is_assigned($user1->id));
        $this->assertFalse($pos_assignment2->is_assigned($user2->id));
        $this->assertNull($pos_assignment2->get_unassigned_at($user1->id));
        $this->assertNull($pos_assignment2->get_unassigned_at($user2->id));

        $this->assertTrue($pos_assignment3->is_assigned($user1->id));
        $this->assertFalse($pos_assignment3->is_assigned($user2->id));
        $this->assertNull($pos_assignment3->get_unassigned_at($user1->id));
        $this->assertNull($pos_assignment3->get_unassigned_at($user2->id));

        // Now unassign user from position 1
        $ja1->update(['positionid' => null]);
        // And archive the second one
        $pos_assignment2->archive();

        (new expand_task(builder::get_db()))->expand_all();

        // Reload the assignments
        $pos_assignment1 = assignment_model::load_by_id($pos_assignment1->id);
        $pos_assignment2 = assignment_model::load_by_id($pos_assignment2->id);
        $pos_assignment3 = assignment_model::load_by_id($pos_assignment3->id);

        $this->assertFalse($pos_assignment1->is_assigned($user1->id));
        $this->assertFalse($pos_assignment1->is_assigned($user2->id));
        $this->assertNotNull($pos_assignment1->get_unassigned_at($user1->id));
        $this->assertNull($pos_assignment1->get_unassigned_at($user2->id));

        $this->assertFalse($pos_assignment2->is_assigned($user1->id));
        $this->assertFalse($pos_assignment2->is_assigned($user2->id));
        $this->assertNotNull($pos_assignment2->get_unassigned_at($user1->id));
        $this->assertNull($pos_assignment2->get_unassigned_at($user2->id));

        $this->assertTrue($pos_assignment3->is_assigned($user1->id));
        $this->assertFalse($pos_assignment3->is_assigned($user2->id));
        $this->assertNull($pos_assignment3->get_unassigned_at($user1->id));
        $this->assertNull($pos_assignment3->get_unassigned_at($user2->id));
    }

    public function test_user_is_assigned_and_unassigned_at_with_preloaded_user_assignment() {
        $this->setAdminUser();
        // Create user
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        // Make sure we keep the data
        set_config('unassign_behaviour', admin_setting_unassign_behaviour::KEEP, 'totara_competency');

        /** @var totara_hierarchy_generator $totara_hierarchy_generator */
        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $fw = $hierarchy_generator->create_pos_frame(['fullname' => 'Pos Framework']);
        // Create position 1
        $position1 = $hierarchy_generator->create_pos(['frameworkid' => $fw->id, 'fullname' => 'Position 1']);

        /** @var totara_competency_assignment_generator $assignment_generator */
        $assignment_generator = $this->getDataGenerator()
            ->get_plugin_generator('totara_competency')
            ->assignment_generator();

        // Create competency
        /** @var totara_hierarchy_generator $totara_hierarchy_generator */
        $totara_hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $compfw = $totara_hierarchy_generator->create_comp_frame([]);
        $comp = $totara_hierarchy_generator->create_comp(['frameworkid' => $compfw->id]);

        // Create position 1 assignment
        $pos_assignment1 = $assignment_generator->create_position_assignment(
            $comp->id,
            $position1->id,
            ['status' => assignment_entity::STATUS_ACTIVE]
        );

        $ja1 = job_assignment::create_default($user1->id, ['positionid' => $position1->id]);
        $ja2 = job_assignment::create_default($user2->id, ['positionid' => $position1->id]);

        (new expand_task(builder::get_db()))->expand_all();

        /** @var assignment_entity $pos_assignment1_entity */
        $pos_assignment1_entity = assignment_entity::repository()
            ->where('id', $pos_assignment1->id)
            ->with([
                'assignment_user' => function (repository $repository) use ($user1) {
                    $repository->where('user_id', $user1->id);
                }
            ])
            ->one();

        $pos_assignment1 = assignment_model::load_by_entity($pos_assignment1_entity);

        $this->assertTrue($pos_assignment1->is_assigned($user1->id));
        $this->assertNull($pos_assignment1->get_unassigned_at($user1->id));

        try {
            $pos_assignment1->is_assigned($user2->id);
            $this->fail('Exception should have been thrown');
        } catch (coding_exception $exception) {
            $this->assertStringContainsString(
                'The assignment had to be loaded with the assignment_user relation for the specific user',
                $exception->getMessage()
            );
        }

        try {
            $pos_assignment1->get_unassigned_at($user2->id);
            $this->fail('Exception should have been thrown');
        } catch (coding_exception $exception) {
            $this->assertStringContainsString(
                'The assignment had to be loaded with the assignment_user relation for the specific user',
                $exception->getMessage()
            );
        }
    }

}
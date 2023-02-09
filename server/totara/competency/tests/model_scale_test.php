<?php

/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @package totara_competency
 */

use core\collection;
use core_phpunit\testcase;
use totara_competency\entity\assignment as assignment_entity;
use totara_competency\entity\competency_achievement;
use totara_competency\models\assignment_specific_scale_value;
use totara_competency\min_proficiency_override_for_assignments;
use totara_competency\models\assignment_actions;
use totara_competency\models\scale;
use totara_competency\models\assignment as assignment_model;
use totara_competency\user_groups;
use totara_core\advanced_feature;
use totara_hierarchy\entity\scale as scale_entity;
use totara_hierarchy\entity\scale_value;
use totara_hierarchy\testing\generator as hierarchy_generator;
use totara_criteria\criterion;

/**
 * Class totara_competency_model_scale_testcase
 *
 * @coversDefaultClass \totara_competency\models\scale
 *
 * @group totara_competency
 */
class totara_competency_model_scale_testcase extends testcase {

    /**
     * @covers ::load_by_id
     * @covers ::load_by_id_with_values
     * @covers ::load_by_ids
     * @covers ::__construct
     */
    public function test_it_loads_scales_using_ids(): void {
        $data = $this->create_data();

        $expected = $data['scales'];

        $scales = scale::load_by_ids([$expected->item(0)->id, $expected->item(2)->id, 'bottom', 'bogus', -5], false);

        $this->assertEqualsCanonicalizing(['Scale 1', 'Scale 3'], $scales->pluck('name'));

        $this->assert_scale_is_good($scales, false);

        // Let's also check that it loaded values correctly
        $this->assert_scale_is_good(
            scale::load_by_ids([$expected->item(0)->id, $expected->item(2)->id], true),
            true
        );

        $this->assertEqualsCanonicalizing(
            (new scale_entity($expected->item(1)->id))->to_array(),
            scale::load_by_id($expected->item(1)->id)->to_array()
        );

        // Let's also check that it loaded values correctly
        $this->assert_scale_is_good(new collection([scale::load_by_id_with_values($expected->item(1)->id)]), true);
    }

    /**
     * @covers ::find_by_competency_id
     * @covers ::find_by_competency_ids
     * @covers ::sanitize_ids
     */
    public function test_it_loads_scales_using_competency_ids(): void {
        $data = $this->create_data();

        $expected = $data['scales'];
        $comps = $data['competencies'];

        $scales = scale::find_by_competency_ids(
            [
                'I am negative :(',
                '-2',
                -2,
                0,
                $comps->item(0)->id, // Scale 1
                $comps->item(1)->id, // Scale 1
                $comps->item(3)->id, // Scale 3
                $comps->item(4)->id, // Scale 3
                'I don\'t exist',
            ],
            false
        );

        $this->assertEqualsCanonicalizing(['Scale 1', 'Scale 3'], $scales->pluck('name'));
        $this->assert_scale_is_good($scales, false);

        // Let's check the same for when we have scale values loaded
        $this->assert_scale_is_good(scale::find_by_competency_ids([
            $comps->item(1)->id, // Scale 1
            $comps->item(3)->id, // Scale 3
        ], true), true);

        $this->assertEqualsCanonicalizing(
            (new scale_entity($expected->item(1)->id))->to_array(),
            scale::find_by_competency_id($comps->item(2)->id, false)->to_array()
        );

        // Let's check that it loads scale values correctly
        $this->assert_scale_is_good(new collection([scale::find_by_competency_id($comps->item(2)->id, true)]), true);
    }

    public function test_load_by_framework_id(): void {
        $data = $this->create_data();

        /** @var competency $comp1 */
        $comp1 = $data['competencies']->item(0); // Scale 1

        $scale = scale::find_by_framework_id($comp1->frameworkid);

        $this->assertNotNull($scale);
        $this->assertEquals('Scale 1', $scale->name);
    }

    public function test_if_scale_is_assigned(): void {
        $generator = $this->generator();

        $scale = $generator->create_scale('comp', ['name' => 'Scale 1']);

        $scale_model = scale::load_by_id_with_values($scale->id);

        $this->assertFalse($scale_model->is_assigned());

        // Now create a framework using the scale
        $generator->create_comp_frame(['scale' => $scale->id]);

        $this->assertTrue($scale_model->is_assigned());
    }

    public function test_if_scale_is_in_use_in_achievement_record(): void {
        $generator = $this->generator();

        $user = $this->getDataGenerator()->create_user();

        $scale = $generator->create_scale('comp', ['name' => 'Scale 1']);
        $framework = $generator->create_comp_frame(['scale' => $scale->id]);

        $comp = $generator->create_comp(['frameworkid' => $framework->id]);

        $scale_model = scale::load_by_id_with_values($scale->id);

        $this->assertFalse($scale_model->is_in_use());

        /** @var \totara_competency\testing\generator $comp_generator */
        $comp_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');

        $assignment = $comp_generator->assignment_generator()->create_user_assignment($comp->id, $user->id);

        // Creating a record with a null scale_value_id value
        $achievement = new competency_achievement();
        $achievement->user_id = $user->id;
        $achievement->competency_id = $comp->id;
        $achievement->assignment_id = $assignment->id;
        $achievement->scale_value_id = null;
        $achievement->proficient = 0;
        $achievement->status = competency_achievement::ACTIVE_ASSIGNMENT;
        $achievement->time_created = time();
        $achievement->time_proficient = time();
        $achievement->time_scale_value = time();
        $achievement->time_status = time();
        $achievement->save();

        $this->assertFalse($scale_model->is_in_use());

        $achievement->scale_value_id = $scale_model->minproficiencyid;
        $achievement->save();

        $this->assertTrue($scale_model->is_in_use());
    }

    public function test_if_scale_is_in_use_with_minimum_proficiency_override(): void {
        $generator = $this->generator();

        $scale = $generator->create_scale('comp', ['name' => 'Scale 1']);
        $framework = $generator->create_comp_frame(['scale' => $scale->id]);

        $comp = $generator->create_comp(['frameworkid' => $framework->id]);

        $scale_model = scale::load_by_id_with_values($scale->id);

        /** @var totara_competency\testing\generator $competency_generator */
        $competency_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');
        $assignment = $competency_generator->assignment_generator()->create_self_assignment($comp->id);
        $assignment_entity = new assignment_entity($assignment->id);

        $model = new assignment_actions();
        $model->activate([$assignment_entity->id]);

        // The scale is not in use, even though there is an active assignment (the assignment doesn't have an override yet).
        $this->assertFalse($scale_model->is_in_use());

        // Update the assignment with a minimum proficiency override.
        /** @var scale_value $new_min_scale_value */
        $new_min_scale_value = $assignment_entity->competency->scale->values->find(function (scale_value $scale_value) {
            return $scale_value->id !== $scale_value->scale->minproficiencyid;
        });
        (new min_proficiency_override_for_assignments(
            $new_min_scale_value->id,
            [$assignment_entity->id]
        ))->process();

        // The scale is now in use, because there is a minimum proficiency override on an active assignment.
        $this->assertTrue($scale_model->is_in_use());
    }

    public function test_if_scale_is_in_use_in_achievement_pathway(): void {
        $generator = $this->generator();

        $scale = $generator->create_scale('comp', ['name' => 'Scale 1']);
        $framework = $generator->create_comp_frame(['scale' => $scale->id]);

        $comp = $generator->create_comp(['frameworkid' => $framework->id]);

        $scale_model = scale::load_by_id_with_values($scale->id);

        /** @var totara_competency\testing\generator $competency_generator */
        $competency_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');
        $assignment = $competency_generator->assignment_generator()->create_self_assignment($comp->id);
        $assignment_entity = new assignment_entity($assignment->id);

        // The scale is not in use, even though there is an active assignment.
        $this->assertFalse($scale_model->is_in_use());

        // Add a scale based criteria group to an achievement pathway.
        $course = $this->getDataGenerator()->create_course();
        /** @var totara_criteria\testing\generator $crit_generator */
        $crit_generator = $this->getDataGenerator()->get_plugin_generator('totara_criteria');
        $cc1 = $crit_generator->create_coursecompletion([
            'aggregation' => criterion::AGGREGATE_ALL,
            'courseids' => [$course->id],
        ]);
        $competency_generator->create_criteria_group($comp->id, [$cc1], $assignment_entity->competency->scale->values->first()->id);

        // The scale is now in use.
        $this->assertTrue($scale_model->is_in_use());
    }

    public function test_if_scale_is_in_use_in_learning_plan(): void {
        $generator = $this->generator();

        $user = $this->getDataGenerator()->create_user();

        $scale = $generator->create_scale('comp', ['name' => 'Scale 1']);
        $framework = $generator->create_comp_frame(['scale' => $scale->id]);

        $comp = $generator->create_comp(['frameworkid' => $framework->id]);

        $scale_model = scale::load_by_id_with_values($scale->id);

        $this->assertFalse($scale_model->is_in_use());

        /** @var \totara_plan\testing\generator $plangenerator */
        $plangenerator = $this->getDataGenerator()->get_plugin_generator('totara_plan');

        $this->setAdminUser();

        $planrecord = $plangenerator->create_learning_plan(['userid' => $user->id]);

        $plangenerator->add_learning_plan_competency($planrecord->id, $comp->id);

        /** @var dp_competency_component $competency_component */
        $competency_component = (new development_plan($planrecord->id))->get_component('competency');
        $competency_component->set_value($comp->id, $user->id, $scale_model->minproficiencyid, new stdClass());

        $this->assertTrue($scale_model->is_in_use());

        // Check that a 0 value does not make it in use
        $competency_component->set_value($comp->id, $user->id, 0, new stdClass());

        $this->assertFalse($scale_model->is_in_use());
    }

    public function test_if_scale_is_in_use_with_learning_plan_disabled(): void {
        advanced_feature::disable('learningplans');

        $generator = $this->generator();

        $user = $this->getDataGenerator()->create_user();

        $scale = $generator->create_scale('comp', ['name' => 'Scale 1']);
        $framework = $generator->create_comp_frame(['scale' => $scale->id]);

        $comp = $generator->create_comp(['frameworkid' => $framework->id]);

        $scale_model = scale::load_by_id_with_values($scale->id);

        $this->assertFalse($scale_model->is_in_use());

        /** @var \totara_plan\testing\generator $plangenerator */
        $plangenerator = $this->getDataGenerator()->get_plugin_generator('totara_plan');

        $this->setAdminUser();

        $planrecord = $plangenerator->create_learning_plan(['userid' => $user->id]);

        $plangenerator->add_learning_plan_competency($planrecord->id, $comp->id);

        /** @var dp_competency_component $competency_component */
        $competency_component = (new development_plan($planrecord->id))->get_component('competency');
        $competency_component->set_value($comp->id, $user->id, $scale_model->minproficiencyid, new stdClass());

        // We don't count values in learning plans if it's disable
        $this->assertFalse($scale_model->is_in_use());
    }

    public function test_create_for_assignment(): void {
        $data = $this->create_data();

        $user = $this->getDataGenerator()->create_user();
        self::setUser($user);

        $assignment = $this->create_active_user_assignment($data['competencies']->first()->id, $user->id);

        /** @var assignment_entity $assignment_entity */
        $assignment_entity = assignment_entity::repository()->find($assignment->get_id());


        // No override.
        $assignment_specific_scale = scale::create_for_assignment($assignment_entity);
        self::assertContainsOnlyInstancesOf(scale_value::class,  $assignment_specific_scale->values);
        foreach ($assignment_specific_scale->values as $value) {
            self::assertNotInstanceOf(assignment_specific_scale_value::class, $value);
        }

        $proficient = $assignment_specific_scale->values->pluck('proficient');
        self::assertEquals([0, 0, 1], $proficient);


        // Override is the lowest scale value.
        $assignment_entity->minproficiencyid = $assignment_entity->competency->scale->values->first()->id;
        $assignment_entity->save();
        $assignment_entity = $assignment_entity::repository()->find($assignment_entity->id);

        $assignment_specific_scale = scale::create_for_assignment($assignment_entity);
        self::assertContainsOnlyInstancesOf(assignment_specific_scale_value::class,  $assignment_specific_scale->values);

        $proficient = $assignment_specific_scale->values->pluck('proficient');
        self::assertEquals([true, true, true], $proficient);


        // Override is the highest scale value.
        $assignment_entity->minproficiencyid = $assignment_entity->competency->scale->values->last()->id;
        $assignment_entity->save();
        $assignment_entity = $assignment_entity::repository()->find($assignment_entity->id);

        $assignment_specific_scale = scale::create_for_assignment($assignment_entity);
        self::assertContainsOnlyInstancesOf(assignment_specific_scale_value::class,  $assignment_specific_scale->values);

        $proficient = $assignment_specific_scale->values->pluck('proficient');
        self::assertEquals([false, false, true], $proficient);


        // Override is the middle scale value.
        $assignment_entity->minproficiencyid = $assignment_entity->competency->scale->values->all(false)[1]->id;
        $assignment_entity->save();
        $assignment_entity = $assignment_entity::repository()->find($assignment_entity->id);

        $assignment_specific_scale = scale::create_for_assignment($assignment_entity);
        self::assertContainsOnlyInstancesOf(assignment_specific_scale_value::class,  $assignment_specific_scale->values);

        $proficient = $assignment_specific_scale->values->pluck('proficient');
        self::assertEquals([false, true, true], $proficient);
    }

    public function test_create_for_assignment_underlying_scale_entity_is_not_shared(): void {
        $data = $this->create_data();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        self::setUser($user1);

        $assignment1 = $this->create_active_user_assignment($data['competencies']->first()->id, $user1->id);
        $assignment2 = $this->create_active_user_assignment($data['competencies']->first()->id, $user2->id);

        /** @var assignment_entity $assignment1_entity */
        /** @var assignment_entity $assignment2_entity */
        [$assignment1_entity, $assignment2_entity] = assignment_entity::repository()
            ->where('id', [$assignment1->get_id(), $assignment2->get_id()])
            ->with('competency.scale.values')
            ->order_by('id')
            ->get()
            ->all();

        self::assertSame($assignment1_entity->competency->scale, $assignment2_entity->competency->scale);

        /** @var scale_value $first_scale_value */
        $first_scale_value = $assignment2_entity->competency->scale->values->first();

        $assignment2_entity->minproficiencyid = $first_scale_value->id;
        $assignment2_entity->save();

        $assignment_specific_scale1 = scale::create_for_assignment($assignment1_entity);
        $assignment_specific_scale2 = scale::create_for_assignment($assignment2_entity);

        // If the scale entities are were shared scale models, then the values collection would be the same instance as well.
        self::assertNotSame($assignment_specific_scale1->values, $assignment_specific_scale2->values);
        self::assertNotEquals($assignment_specific_scale1->values->to_array(), $assignment_specific_scale2->values->to_array());
    }

    /**
     * Assert that given collection is a valid collection of scale models
     *
     * @param collection $scales Collection of scale models
     * @param bool $with_values Check whether it should have values loaded or not
     */
    protected function assert_scale_is_good(collection $scales, bool $with_values = false): void {
        $scales->map(function (scale $scale) use ($with_values) {
            $this->assertInstanceOf(scale::class, $scale);

            $exp = (new scale_entity($scale->get_id()))->to_array();

            $scale_array = $scale->to_array();

            if ($with_values) {
                $this->assertTrue(isset($scale_array['values']));

                $expected = scale_value::repository()
                    ->where('scaleid', $scale->id)
                    ->order_by('sortorder', 'desc')
                    ->get();

                $this->assertEqualsCanonicalizing($expected->pluck('name'), $scale->values->pluck('name'));

                $scale->values->map(function (scale_value $scale_value) use ($expected) {
                    $this->assertEqualsCanonicalizing($expected->item($scale_value->id)->to_array(), $scale_value->to_array());
                });

                unset($scale_array['values']);
            } else {
                $this->assertFalse(isset($scale_array['values']));
            }

            $this->assertEqualsCanonicalizing($exp, $scale_array);
        });
    }

    /**
     * Create testing data
     *
     * @return array
     */
    protected function create_data(): array {
        // Let's create 3 scales
        $scales = new collection();

        $scales->append($this->generator()->create_scale('comp', ['name' => 'Scale 1']));
        $scales->append($this->generator()->create_scale('comp', ['name' => 'Scale 2']));
        $scales->append($this->generator()->create_scale('comp', ['name' => 'Scale 3']));

        // Let's create 4 frameworks
        $frameworks = new collection();

        $frameworks->append($this->generator()->create_comp_frame(['scale' => $scales->item(0)->id]));
        $frameworks->append($this->generator()->create_comp_frame(['scale' => $scales->item(1)->id]));
        $frameworks->append($this->generator()->create_comp_frame(['scale' => $scales->item(2)->id]));
        $frameworks->append($this->generator()->create_comp_frame(['scale' => $scales->item(2)->id]));


        // Let's create 5 competencies
        $competencies = new collection();

        $competencies->append($this->generator()->create_comp(['frameworkid' => $frameworks->item(0)->id]));
        $competencies->append($this->generator()->create_comp(['frameworkid' => $frameworks->item(0)->id]));
        $competencies->append($this->generator()->create_comp(['frameworkid' => $frameworks->item(1)->id]));
        $competencies->append($this->generator()->create_comp(['frameworkid' => $frameworks->item(2)->id]));
        $competencies->append($this->generator()->create_comp(['frameworkid' => $frameworks->item(3)->id]));

        return [
            'scales' => $scales,
            'frameworks' => $frameworks,
            'competencies' => $competencies,
        ];
    }

    protected function generator(): hierarchy_generator {
        return hierarchy_generator::instance();
    }

    protected function create_active_user_assignment(int $competency_id, int $user_id): assignment_model {
        $type = assignment_entity::TYPE_ADMIN;
        $user_group_type = user_groups::USER;
        $status = assignment_entity::STATUS_ACTIVE;

        return assignment_model::create($competency_id, $type, $user_group_type, $user_id, $status);
    }

}

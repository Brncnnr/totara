<?php

/*
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package totara_competency
 * @subpackage test
 */

use core\orm\query\builder;
use core_phpunit\testcase;
use totara_competency\aggregation_users_table;
use totara_competency\entity\assignment as assignment_entity;
use totara_competency\event\assignment_min_proficiency_override_updated;
use totara_competency\expand_task;
use totara_competency\min_proficiency_override_for_assignments;
use totara_competency\models\assignment;
use totara_competency\testing\generator as competency_generator;
use totara_hierarchy\entity\scale_value;

/**
 * Class totara_competency_model_scale_testcase
 *
 * @coversDefaultClass \totara_competency\models\scale
 *
 * @group totara_competency
 */
class totara_competency_min_proficiency_override_for_assignments_testcase extends testcase {

    public function test_set_and_unset_single_assignment(): void {
        $assignment = competency_generator::instance()->assignment_generator()->create_self_assignment();
        $assignment_entity = new assignment_entity($assignment->id);

        /** @var scale_value $new_min_scale_value */
        $new_min_scale_value = $assignment_entity->competency->scale->values->find(function (scale_value $scale_value) {
            return $scale_value->id !== $scale_value->scale->minproficiencyid;
        });

        $original_min_scale_value = $assignment_entity->competency->scale->min_proficient_value;

        $sink = self::redirectEvents();

        $updated_assignments = (new min_proficiency_override_for_assignments(
            $new_min_scale_value->id,
            [$assignment_entity->id]
        ))->process();

        /** @var assignment $updated_assignment */
        $updated_assignment = $updated_assignments->first();

        self::assertEquals($updated_assignment->get_id(), $assignment_entity->id);
        self::assertTrue($updated_assignment->has_default_proficiency_value_override());
        self::assertEquals($updated_assignment->get_min_value()->id, $new_min_scale_value->id);

        $this->verify_events($sink, $updated_assignments->pluck('id'));
        $sink->clear();

        $updated_assignments = (new min_proficiency_override_for_assignments(
            null,
            [$assignment_entity->id]
        ))->process();

        /** @var assignment $updated_assignment */
        $updated_assignment = $updated_assignments->first();

        self::assertEquals($updated_assignment->get_id(), $assignment_entity->id);
        self::assertFalse($updated_assignment->has_default_proficiency_value_override());
        self::assertEquals($updated_assignment->get_min_value()->id, $original_min_scale_value->id);

        $this->verify_events($sink, $updated_assignments->pluck('id'));
        $sink->close();
    }

    public function test_set_and_unset_multi_assignments_same_competency(): void {
        $assignment1 = competency_generator::instance()->assignment_generator()->create_self_assignment();
        $assignment_entity1 = new assignment_entity($assignment1->id);

        // One and two share the same competency.
        $assignment2 = competency_generator::instance()->assignment_generator()->create_self_assignment();
        $assignment_entity2 = new assignment_entity($assignment2->id);
        $assignment_entity2->competency_id = $assignment1->competency_id;
        $assignment_entity2->save();

        /** @var scale_value $new_min_scale_value */
        $new_min_scale_value = $assignment_entity1->competency->scale->values->find(function (scale_value $scale_value) {
            return $scale_value->id !== $scale_value->scale->minproficiencyid;
        });

        $original_min_scale_value = $assignment_entity1->competency->scale->min_proficient_value;

        $sink = self::redirectEvents();

        $updated_assignments = (new min_proficiency_override_for_assignments(
            $new_min_scale_value->id,
            [$assignment_entity1->id, $assignment_entity2->id]
        ))->process();

        /** @var assignment $updated_assignment1 */
        $updated_assignment1 = $updated_assignments->first();
        /** @var assignment $updated_assignment2 */
        $updated_assignment2 = $updated_assignments->last();

        self::assertEquals($updated_assignment1->get_id(), $assignment_entity1->id);
        self::assertTrue($updated_assignment1->has_default_proficiency_value_override());
        self::assertEquals($updated_assignment1->get_min_value()->id, $new_min_scale_value->id);

        self::assertEquals($updated_assignment2->get_id(), $assignment_entity2->id);
        self::assertTrue($updated_assignment2->has_default_proficiency_value_override());
        self::assertEquals($updated_assignment2->get_min_value()->id, $new_min_scale_value->id);

        $this->verify_events($sink, $updated_assignments->pluck('id'));
        $sink->clear();

        $updated_assignments = (new min_proficiency_override_for_assignments(
            null,
            [$assignment_entity1->id, $assignment_entity2->id]
        ))->process();

        /** @var assignment $updated_assignment1 */
        $updated_assignment1 = $updated_assignments->first();
        /** @var assignment $updated_assignment2 */
        $updated_assignment2 = $updated_assignments->last();

        self::assertEquals($updated_assignment1->get_id(), $assignment_entity1->id);
        self::assertFalse($updated_assignment1->has_default_proficiency_value_override());
        self::assertEquals($updated_assignment1->get_min_value()->id, $original_min_scale_value->id);

        self::assertEquals($updated_assignment2->get_id(), $assignment_entity2->id);
        self::assertFalse($updated_assignment2->has_default_proficiency_value_override());
        self::assertEquals($updated_assignment2->get_min_value()->id, $original_min_scale_value->id);
    }

    public function test_set_and_unset_multi_assignments_different_competency(): void {
        $assignment1 = competency_generator::instance()->assignment_generator()->create_self_assignment();
        $assignment_entity1 = new assignment_entity($assignment1->id);

        // One and two share the same framework, different competencies.
        $assignment2 = competency_generator::instance()->assignment_generator()->create_self_assignment();
        $assignment_entity2 = new assignment_entity($assignment2->id);

        self::assertNotEquals($assignment_entity2->competency_id, $assignment1->competency_id);

        $assignment_entity2->competency->frameworkid = $assignment_entity1->competency->frameworkid;
        $assignment_entity2->competency->save();

        /** @var scale_value $new_min_scale_value */
        $new_min_scale_value = $assignment_entity1->competency->scale->values->find(function (scale_value $scale_value) {
            return $scale_value->id !== $scale_value->scale->minproficiencyid;
        });

        $original_min_scale_value = $assignment_entity1->competency->scale->min_proficient_value;

        $sink = self::redirectEvents();

        $updated_assignments = (new min_proficiency_override_for_assignments(
            $new_min_scale_value->id,
            [$assignment_entity1->id, $assignment_entity2->id]
        ))->process();

        /** @var assignment $updated_assignment1 */
        $updated_assignment1 = $updated_assignments->first();
        /** @var assignment $updated_assignment2 */
        $updated_assignment2 = $updated_assignments->last();

        self::assertEquals($updated_assignment1->get_id(), $assignment_entity1->id);
        self::assertTrue($updated_assignment1->has_default_proficiency_value_override());
        self::assertEquals($updated_assignment1->get_min_value()->id, $new_min_scale_value->id);

        self::assertEquals($updated_assignment2->get_id(), $assignment_entity2->id);
        self::assertTrue($updated_assignment2->has_default_proficiency_value_override());
        self::assertEquals($updated_assignment2->get_min_value()->id, $new_min_scale_value->id);

        $this->verify_events($sink, $updated_assignments->pluck('id'));
        $sink->clear();

        $updated_assignments = (new min_proficiency_override_for_assignments(
            null,
            [$assignment_entity1->id, $assignment_entity2->id]
        ))->process();

        /** @var assignment $updated_assignment1 */
        $updated_assignment1 = $updated_assignments->first();
        /** @var assignment $updated_assignment2 */
        $updated_assignment2 = $updated_assignments->last();

        self::assertEquals($updated_assignment1->get_id(), $assignment_entity1->id);
        self::assertFalse($updated_assignment1->has_default_proficiency_value_override());
        self::assertEquals($updated_assignment1->get_min_value()->id, $original_min_scale_value->id);

        self::assertEquals($updated_assignment2->get_id(), $assignment_entity2->id);
        self::assertFalse($updated_assignment2->has_default_proficiency_value_override());
        self::assertEquals($updated_assignment2->get_min_value()->id, $original_min_scale_value->id);

        $this->verify_events($sink, $updated_assignments->pluck('id'));
        $sink->close();
    }

    public function test_non_existent_assignments(): void {
        $assignment1 = competency_generator::instance()->assignment_generator()->create_self_assignment();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Assignments with ids (-100, -200) do not exist');

        (new min_proficiency_override_for_assignments(
            null,
            [$assignment1->id, -100, -200]
        ))->process();
    }

    public function test_scale_value_does_not_exist(): void {
        $assignment1 = competency_generator::instance()->assignment_generator()->create_self_assignment();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage(min_proficiency_override_for_assignments::SCALE_VALUE_DOES_NOT_EXIST);

        (new min_proficiency_override_for_assignments(
            -100,
            [$assignment1->id]
        ))->process();
    }

    public function test_scale_value_does_not_belong_to_a_framework(): void {
        $assignment1 = competency_generator::instance()->assignment_generator()->create_self_assignment();
        $scale = competency_generator::instance()->create_scale('No framework');

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage(min_proficiency_override_for_assignments::COMPETENCIES_DO_NOT_BELONG_TO_OVERRIDE_FRAMEWORK);

        (new min_proficiency_override_for_assignments(
            $scale->minproficiencyid,
            [$assignment1->id]
        ))->process();
    }

    public function test_assigned_users_are_queued_for_re_aggregation(): void {
        $user = self::getDataGenerator()->create_user();
        $fw = competency_generator::instance()->create_framework();
        $competency1 = competency_generator::instance()->create_competency('Comp1', $fw);
        $competency2 = competency_generator::instance()->create_competency('Comp2', $fw);

        // One and two share the same competency.
        $assignment1 = competency_generator::instance()->assignment_generator()->create_self_assignment($competency1->id, $user->id);
        $assignment2 = competency_generator::instance()->assignment_generator()->create_self_assignment($competency1->id, $user->id);

        // Three - different competency, same framework.
        $assignment3 = competency_generator::instance()->assignment_generator()->create_self_assignment($competency2->id, $user->id);

        /** @var scale_value $new_min_scale_value */
        $new_min_scale_value = $competency1->scale->values->find(function (scale_value $scale_value) {
            return $scale_value->id !== $scale_value->scale->minproficiencyid;
        });

        (new expand_task(builder::get_db()))->expand_all();
        self::redirectEvents();

        // Set new custom min proficiency for two and three
        (new min_proficiency_override_for_assignments(
            $new_min_scale_value->id,
            [$assignment2->id, $assignment3->id]
        ))->process();

        $this->verify_and_clear_queued_for_aggregation([
            ['competency_id' => $competency1->id, 'user_id' => $user->id],
            ['competency_id' => $competency2->id, 'user_id' => $user->id],
        ]);

        // Unset custom min proficiency for three
        (new min_proficiency_override_for_assignments(
            null,
            [$assignment3->id]
        ))->process();

        $this->verify_and_clear_queued_for_aggregation([
            ['competency_id' => $competency2->id, 'user_id' => $user->id],
        ]);
    }

    /**
     * @param \core_phpunit\event_sink $sink
     * @param int[] $expected_assignment_ids
     */
    private function verify_events(\core_phpunit\event_sink $sink, array $expected_assignment_ids): void {
        $events = $sink->get_events();

        self::assertCount(count($expected_assignment_ids), $events);

        foreach ($events as $event) {
            self::assertInstanceOf(assignment_min_proficiency_override_updated::class, $event);
            $idx = array_search($event->objectid, $expected_assignment_ids);
            if ($idx !== false) {
                unset($expected_assignment_ids[$idx]);
            }
        }
        self::assertEmpty($expected_assignment_ids);
    }

    private function verify_and_clear_queued_for_aggregation(array $expected_queued): void {
        $builder = builder::table((new aggregation_users_table())->get_table_name());
        $actual_queued = $builder->get();

        self::assertCount(count($expected_queued), $actual_queued);

        foreach ($actual_queued as $actual) {
            foreach ($expected_queued as $idx => $expected) {
                if ($expected['competency_id'] == $actual->competency_id && $expected['user_id'] == $actual->user_id) {
                    unset($expected_queued[$idx]);
                    break;
                }
            }
        }

        self::assertEmpty($expected_queued);

        $builder->delete();
    }

    public function test_reset_with_scale_value(): void {
        $assignment1 = competency_generator::instance()->assignment_generator()->create_self_assignment();
        $assignment_entity1 = new assignment_entity($assignment1->id);

        $assignment2 = competency_generator::instance()->assignment_generator()->create_self_assignment(
            $assignment_entity1->competency_id
        );
        $assignment_entity2 = new assignment_entity($assignment2->id);

        $assignment3 = competency_generator::instance()->assignment_generator()->create_self_assignment(
            $assignment_entity1->competency_id
        );
        $assignment_entity3 = new assignment_entity($assignment3->id);

        $default_min_scale_value_id = $assignment_entity1->competency->scale->minproficiencyid;

        // The first assignment has the override set to the scale default.
        (new min_proficiency_override_for_assignments(
            $default_min_scale_value_id,
            [$assignment_entity1->id]
        ))->process();

        // The second assignment has the override set to some other value.
        /** @var scale_value $new_min_scale_value */
        $other_scale_value = $assignment_entity1->competency->scale->values->find(function (scale_value $scale_value) {
            return $scale_value->id !== $scale_value->scale->minproficiencyid;
        });
        (new min_proficiency_override_for_assignments(
            $other_scale_value->id,
            [$assignment_entity2->id]
        ))->process();

        // There are three total.
        self::assertEquals(
            3,
            assignment_entity::repository()->count()
        );

        // One does not have an override.
        self::assertEquals(
            1,
            assignment_entity::repository()->where_null('minproficiencyid')->count()
        );

        // One has an override set to the default.
        self::assertEquals(
            1,
            assignment_entity::repository()->where('minproficiencyid', $default_min_scale_value_id)->count()
        );

        // Reset all assignments which have this scale value as an override back to default (null).
        min_proficiency_override_for_assignments::reset_with_scale_value($default_min_scale_value_id);

        // There are three total.
        self::assertEquals(
            3,
            assignment_entity::repository()->count()
        );

        // Two do not have an override.
        self::assertEquals(
            2,
            assignment_entity::repository()->where_null('minproficiencyid')->count()
        );

        // Zero have an override set to the default.
        self::assertEquals(
            0,
            assignment_entity::repository()->where('minproficiencyid', $default_min_scale_value_id)->count()
        );
    }
}

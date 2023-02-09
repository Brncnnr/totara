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
 * @author Fabian Derschatta <fabian.derschata@totaralearning.com>
 * @package totara_competency
 */

use core\collection;
use core\entity\user;
use totara_competency\entity\assignment;
use totara_competency\entity\competency_achievement;
use totara_competency\models\assignment as assignment_model;
use totara_competency\models\profile\competency_progress;
use totara_competency\models\profile\filter;
use totara_competency\models\profile\progress;
use totara_competency\models\profile\traits\assignment_key;
use totara_competency\models\profile\unassigned_competency_progress;

global $CFG;

/**
 * @group totara_competency
 */
require_once($CFG->dirroot . '/totara/competency/tests/totara_competency_testcase.php');

/**
 * Class totara_competency_model_scale_testcase
 *
 * @coversDefaultClass \totara_competency\models\scale
 */
class totara_competency_model_profile_progress_testcase extends totara_competency_testcase {

    use assignment_key;

    /**
     * @covers ::load_by_id_with_values
     * @covers ::load_by_ids
     * @covers ::__construct
     */
    public function test_it_loads_scales_using_ids(): void {
        $data = $this->create_sorting_testing_data(true);

        // Let's build data for a user
        /** @var user $user */
        $user = $data['users']->first();

        $progress = progress::for($user->id);

        $this->assertInstanceOf(progress::class, $progress);

        // Let's check that it has required objects

        // User
        $this->assertInstanceOf(stdClass::class, $progress->user);
        $this->assertEquals($user->to_array(), (array) $progress->user);

        // Individual progress items
        $this->assertInstanceOf(collection::class, $progress->items);

        $this->assertGreaterThan(0, count($progress->items));

        $assignments = $data['assignments'];

        foreach ($progress->items as $key => $item) {
            // assert correct assignments
            $user_group_id = $item->assignments->pluck('user_group_id')[0];
            $filtered_ass = $assignments->filter('user_group_id', $user_group_id);
            $this->assertEqualsCanonicalizing($filtered_ass->pluck('id'), $item->assignments->pluck('id'));

            // assert correct overall_progress
            $this->assertNotNull($item->overall_progress);

            $expected_proficient = $this->get_expected_proficient_value($user, $filtered_ass->first()->id);
            $this->assertEquals($expected_proficient, $item->overall_progress);

            // assert correct user group name
            $assignment_id = $filtered_ass->first()->id;
            $expected_assignment = assignment_model::load_by_id($assignment_id);
            $this->assertEquals($expected_assignment->get_progress_name(), $item->name);

            // assert correct graph
            $this->assertIsArray($item->graph);

            // assert correct key
            $this->assertEquals(self::build_key($expected_assignment->get_entity()), $key);
        }

        // Filters
        $this->assertIsArray($progress->filters);
        $this->assertGreaterThan(0, count($progress->filters));

        foreach ($progress->filters as $filter) {
            $this->assertInstanceOf(filter::class, $filter);
        }

        // Latest achievement
        $this->assertEquals($data['competencies']->first()->fullname, $progress->latest_achievement);
    }

    public function test_build_progress_items_from_assignments(): void {
        $fw = $this->generator()->create_framework();
        $comp1 = $this->generator()->create_competency(null, $fw);
        $comp2 = $this->generator()->create_competency(null, $fw);
        $comp3 = $this->generator()->create_competency(null, $fw);

        // Create users
        $user1 = $this->create_user();
        $user2 = $this->create_user();

        $ass_gen = $this->generator()->assignment_generator();

        $pos1 = $ass_gen->create_position_and_add_members([$user1->id, $user2->id]);
        $pos2 = $ass_gen->create_position_and_add_members([$user1->id, $user2->id]);

        $pos_ass1 = new assignment($this->generator()->assignment_generator()->create_position_assignment($comp1->id, $pos1->id));
        $pos_ass2 = new assignment($this->generator()->assignment_generator()->create_position_assignment($comp2->id, $pos1->id));
        $pos_ass3 = new assignment($this->generator()->assignment_generator()->create_position_assignment($comp1->id, $pos2->id));
        $pos_ass4 = new assignment($this->generator()->assignment_generator()->create_position_assignment($comp2->id, $pos2->id));

        $user_ass1 = new assignment($ass_gen->create_user_assignment($comp2->id, $user1->id));
        $user_ass2 = new assignment($ass_gen->create_user_assignment($comp1->id, $user1->id));

        $self_ass1 = new assignment($ass_gen->create_self_assignment($comp2->id, $user1->id));
        $self_ass2 = new assignment($ass_gen->create_self_assignment($comp1->id, $user1->id));
        $self_ass3 = new assignment($ass_gen->create_self_assignment($comp1->id, $user2->id));
        $self_ass4 = new assignment($ass_gen->create_self_assignment($comp1->id, $user2->id, ['status' => assignment::STATUS_ARCHIVED]));

        $assignments = new \core\orm\collection([
            $pos_ass1,
            $pos_ass2,
            $pos_ass3,
            $pos_ass4,
            $user_ass1,
            $user_ass2,
            $self_ass1,
            $self_ass2,
            $self_ass3,
            $self_ass4,
        ]);

        $collection = competency_progress::build_from_assignments($assignments);

        // Expecting 2 items as there are two competencies
        $this->assertCount(2, $collection);

        // Only those assignments were added which differ in user_group (type/id) and status
        $expected_assignemts[$comp1->id] = collection::new([
            $pos_ass1,
            $pos_ass3,
            $user_ass2,
            $self_ass2,
            $self_ass3,
            $self_ass4, // status is different so it should still shows up
        ]);

        // Only those assignments were added which differ in user_group (type/id) and status
        $expected_assignemts[$comp2->id] = collection::new([
            $pos_ass2,
            $pos_ass4,
            $user_ass1,
            $self_ass1,
        ]);

        // We use assert equals here because we expect these to be ordered by created date.
        /** @var competency_progress $item */
        foreach ($collection as $key => $item) {
            $this->assertEquals(
                $expected_assignemts[$key]->pluck('id'),
                $item->get_assignments()->map(function (assignment_model $assignment_model) {
                    return $assignment_model->get_entity();
                })->pluck('id')
            );
        }
    }

    /**
     * When building for a competency, we want a null object with the competency attached rather than literal null.
     */
    public function test_build_for_competency_no_assignments(): void {
        $competency_framework = $this->generator()->create_framework();
        $competency = $this->generator()->create_competency(null, $competency_framework);

        $user1 = $this->create_user();

        $progress = competency_progress::build_for_competency($user1, $competency->id);

        self::assertInstanceOf(unassigned_competency_progress::class, $progress);
        self::assertCount(0, $progress->assignments);
        self::assertEquals($competency->id, $progress->competency->id);

        // These are invalid competency ids but should return null instead.
        $progress = competency_progress::build_for_competency($user1, 0);
        self::assertNull($progress);

        $progress = competency_progress::build_for_competency($user1, 232);
        self::assertNull($progress);
    }

    /**
     * Get proficient value for a specific assignment
     *
     * we calculate overall progress in the real world,
     * in this test we only set one active assignment per group,
     * so its proficient value represents the overall proficient value
     * @see item::calculate_overall_progress()
     *
     * @param user $user
     * @param int $assignment_id
     * @return int
     */
    private function get_expected_proficient_value(user $user, int $assignment_id): int {
        $proficient = competency_achievement::repository()
                ->where('user_id', $user->id)
                ->where('assignment_id', $assignment_id)
                ->where('status', 0)
                ->get()
                ->first()->proficient ?? 0;

        return $proficient * 100;
    }

}

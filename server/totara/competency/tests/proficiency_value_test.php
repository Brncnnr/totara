<?php

use core\orm\query\builder;
use pathway_manual\models\roles\appraiser;
use totara_competency\entity\assignment;
use totara_competency\expand_task;
use totara_competency\models\profile\proficiency_value;
use totara_competency\task\competency_aggregation_queue;

/**
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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_competency
 */

/**
 * @group totara_competency
 */
class totara_competency_proficiency_value_testcase extends advanced_testcase {

    public function test_min_value() {
        $user = $this->getDataGenerator()->create_user();

        /** @var \totara_competency\testing\generator $competency_generator */
        $competency_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');
        $competency = $competency_generator->create_competency();
        $competency_generator->create_manual($competency);

        $assignment_generator = $competency_generator->assignment_generator();

        $assignment = $assignment_generator->create_user_assignment($competency->id, $user->id);

        /** @var assignment $assignment */
        $assignment = assignment::repository()->find($assignment->id);

        $expected_value = $competency->scale->min_proficient_value;
        $min_value = proficiency_value::min_value($assignment);

        $this->assertEquals($expected_value->id, $min_value->id);
        $this->assertEquals($expected_value->name, $min_value->name);
        $this->assertTrue($min_value->proficient);
        $this->assertEquals(100.0, $min_value->percentage);
    }

    public function test_current_achievement_not_loaded() {
        $user = $this->getDataGenerator()->create_user();

        /** @var \totara_competency\testing\generator $competency_generator */
        $competency_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');
        $competency = $competency_generator->create_competency();
        $competency_generator->create_manual($competency);

        $assignment_generator = $competency_generator->assignment_generator();

        $assignment = $assignment_generator->create_user_assignment($competency->id, $user->id);

        /** @var assignment $assignment */
        $assignment = assignment::repository()->find($assignment->id);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage(
            'You must preload "current_achievement" relation with a user filter included'
        );

        proficiency_value::my_value($assignment);
    }

    public function test_proficiency_value_achieved() {
        $rater_user = $this->getDataGenerator()->create_user();
        $user = $this->getDataGenerator()->create_user();

        /** @var \totara_competency\testing\generator $competency_generator */
        $competency_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');
        $competency = $competency_generator->create_competency();
        $competency_generator->create_manual($competency);

        $assignment_generator = $competency_generator->assignment_generator();

        $assignment = $assignment_generator->create_user_assignment($competency->id, $user->id);

        (new expand_task(builder::get_db()))->expand_all();

        /** @var assignment $assignment */
        $assignment = $this->load_assignment_with_achievement($assignment->id, $user);

        $value = proficiency_value::value_at_timestamp($assignment, $user->id, time());
        $this->assert_user_has_no_achievement($value);

        $value = proficiency_value::my_value($assignment);
        $this->assert_user_has_no_achievement($value);

        $scale_value1 = $competency->scale->values->first();
        $scale_value2 = $competency->scale->values->last();

        $time_pre_achievement = time();

        $this->waitForSecond();

        $competency_generator->create_manual_rating(
            $competency,
            $user,
            $rater_user,
            appraiser::class,
            $scale_value1
        );

        (new competency_aggregation_queue())->execute();

        $time_after_first_achievement = time();

        // The value with current timestamp should now have changed
        $value = proficiency_value::value_at_timestamp($assignment, $user->id, $time_after_first_achievement);
        $this->assertEquals($scale_value1->id, $value->id);
        $this->assertEquals($scale_value1->name, $value->name);
        $this->assertFalse($value->proficient);
        $this->assertEquals(33.0, $value->percentage);

        // Now the current value should match the one with the timestamp

        // Reload the assignment
        $assignment = $this->load_assignment_with_achievement($assignment->id, $user);

        $value = proficiency_value::my_value($assignment);
        $this->assertEquals($scale_value1->id, $value->id);
        $this->assertEquals($scale_value1->name, $value->name);
        $this->assertFalse($value->proficient);
        $this->assertEquals(33.0, $value->percentage);

        // Let's try an older one, it should still return no value
        $older_value = proficiency_value::value_at_timestamp($assignment, $user->id, $time_pre_achievement);
        $this->assert_user_has_no_achievement($older_value);

        $this->waitForSecond();

        $competency_generator->create_manual_rating(
            $competency,
            $user,
            $rater_user,
            appraiser::class,
            $scale_value2
        );

        (new competency_aggregation_queue())->execute();

        $time_after_second_achievement = time();

        // The value with current timestamp should now have changed
        $value = proficiency_value::value_at_timestamp($assignment, $user->id, $time_after_second_achievement);
        $this->assertEquals($scale_value2->id, $value->id);
        $this->assertEquals($scale_value2->name, $value->name);
        $this->assertTrue($value->proficient);
        $this->assertEquals(100.0, $value->percentage);

        // Now the current value should match the one with the timestamp

        // Reload the assignment
        $assignment = $this->load_assignment_with_achievement($assignment->id, $user);

        $value = proficiency_value::my_value($assignment);
        $this->assertEquals($scale_value2->id, $value->id);
        $this->assertEquals($scale_value2->name, $value->name);
        $this->assertTrue($value->proficient);
        $this->assertEquals(100.0, $value->percentage);

        // Let's try an older one, it should still return no value
        $older_value = proficiency_value::value_at_timestamp($assignment, $user->id, $time_pre_achievement);
        $this->assert_user_has_no_achievement($older_value);

        // Try the one we got first
        $value = proficiency_value::value_at_timestamp($assignment, $user->id, $time_after_first_achievement);
        $this->assertEquals($scale_value1->id, $value->id);
        $this->assertEquals($scale_value1->name, $value->name);
        $this->assertFalse($value->proficient);
        $this->assertEquals(33.0, $value->percentage);
    }

    public function test_empty_value() {
        $value = proficiency_value::empty_value();

        $this->assert_user_has_no_achievement($value);
    }

    /**
     * Assert that the given value points to 'no achievement'
     *
     * @param proficiency_value $value
     */
    private function assert_user_has_no_achievement(proficiency_value $value): void {
        $this->assertEquals(0, $value->id);
        $this->assertEquals(get_string('no_value_achieved', 'totara_competency'), $value->name);
        $this->assertFalse($value->proficient);
        $this->assertEquals(0, $value->percentage);
    }

    /**
     * Load the assignment with the current achievement of the given user
     *
     * @param int $assignment_id
     * @return assignment
     */
    private function load_assignment_with_achievement(int $assignment_id, stdClass $user): assignment {
        return assignment::repository()
            ->with(
                [
                    'current_achievement' => function (\core\orm\entity\repository $repository) use ($user) {
                        $repository->where('user_id', $user->id);
                    }
                ]
            )
            ->where('id', $assignment_id)
            ->one();
    }

}
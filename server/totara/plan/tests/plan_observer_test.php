<?php
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
 * @package totara_plan
 */

use core\orm\query\builder;
use core_phpunit\testcase;
use totara_plan\entity\record_of_learning as record_of_learning_entity;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for the plan observer
 */
class totara_plan_plan_observer_testcase extends testcase {

    public function test_adding_a_course_to_plan_ceates_record_of_learning_record() {
        $generator = $this->getDataGenerator();

        /** @var \totara_plan\testing\generator $plan_generator */
        $plan_generator = $generator->get_plugin_generator('totara_plan');

        $user1 = $generator->create_user();
        $user2 = $generator->create_user();

        $course = $generator->create_course();

        $this->assertEquals(0, record_of_learning_entity::repository()->count());

        $plan1 = $plan_generator->create_learning_plan(['userid' => $user1->id]);

        $this->setUser($user1);
        $plan_generator->add_learning_plan_course($plan1->id, $course->id);
        $this->setAdminUser();

        $this->assertEquals(1, record_of_learning_entity::repository()->count());

        $this->assertTrue(
            record_of_learning_entity::repository()
                ->where('userid', $user1->id)
                ->where('instanceid', $course->id)
                ->exists()
        );

        // Delete the plan assignments
        builder::table('dp_plan_course_assign')->delete();

        $this->setUser($user1);
        $plan_generator->add_learning_plan_course($plan1->id, $course->id);
        $this->setAdminUser();

        $this->assertEquals(1, record_of_learning_entity::repository()->count());

        $this->assertTrue(
            record_of_learning_entity::repository()
                ->where('userid', $user1->id)
                ->where('instanceid', $course->id)
                ->exists()
        );

        $plan2 = $plan_generator->create_learning_plan(['userid' => $user2->id]);

        $this->setUser($user2);
        $plan_generator->add_learning_plan_course($plan2->id, $course->id);
        $this->setAdminUser();

        $this->assertEquals(2, record_of_learning_entity::repository()->count());

        $this->assertTrue(
            record_of_learning_entity::repository()
                ->where('userid', $user2->id)
                ->where('instanceid', $course->id)
                ->exists()
        );
    }
}

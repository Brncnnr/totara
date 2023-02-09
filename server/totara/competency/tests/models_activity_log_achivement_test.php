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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @package totara_competency
 */

use core_phpunit\testcase;
use totara_competency\models\activity_log;
use totara_competency\entity\competency_achievement;
use totara_competency\entity\assignment;
use totara_hierarchy\entity\scale_value;

/**
 * @group totara_competency
 */
class totara_competency_models_activity_log_achievement_testcase extends testcase {

    public function test_no_value() {
        $assignment = new assignment();
        $assignment->competency_id = 100;
        $assignment->user_group_id = 300;
        $assignment->user_group_type = 'test';
        $assignment->created_by = 400;
        $assignment->save();

        $achievement = new competency_achievement();
        $achievement->time_created = time();
        $achievement->scale_value_id = null;
        $achievement->assignment_id = $assignment->id;

        $achievement = new competency_achievement();
        $achievement->user_id = 1;
        $achievement->competency_id = 100;
        $achievement->scale_value_id = null;
        $achievement->proficient = 0;
        $achievement->status = 0;
        $achievement->time_created = time();
        $achievement->time_status = time();
        $achievement->assignment_id = $assignment->id;
        $achievement->save();

        $entry = activity_log\competency_achieved_via::load_by_entity($achievement);
        $this->assertEquals('Rating value reset', $entry->get_description());
        $this->assertSame('', $entry->get_comment());
    }

    public function test_value_given() {
        $time = time();

        /** @var \totara_hierarchy\testing\generator $hierarchy_generator */
        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $hierarchy_generator->create_scale(
            'comp',
            [],
            [
                ['name' => 'Great', 'proficient' => 1, 'sortorder' => 1, 'default' => 0],
                ['name' => 'Good', 'proficient' => 1, 'sortorder' => 2, 'default' => 1],
                ['name' => 'Bad', 'proficient' => 0, 'sortorder' => 3, 'default' => 0],
            ]
        );

        $great = scale_value::repository()->where('name', '=', 'Great')->one();

        $assignment = new assignment();
        $assignment->competency_id = 100;
        $assignment->user_group_id = 300;
        $assignment->user_group_type = 'test';
        $assignment->created_by = 400;
        $assignment->save();

        $achievement = new competency_achievement();
        $achievement->user_id = 1;
        $achievement->time_created = $time;
        $achievement->time_status = $time;
        $achievement->proficient = true;
        $achievement->scale_value_id = $great->id;
        $achievement->assignment_id = $assignment->id;
        $achievement->competency_id = 100;
        $achievement->status = 0;
        $achievement->save();

        $entry = activity_log\competency_achievement::load_by_entity($achievement);

        $this->assertEquals('Rating: Great', $entry->get_description());
        $this->assertTrue($entry->get_proficient_status());
        $this->assertEquals($assignment->id, $entry->get_assignment()->get_id());
        $this->assertEquals($time, $entry->get_date());
        $this->assertSame('', $entry->get_comment());
    }
}

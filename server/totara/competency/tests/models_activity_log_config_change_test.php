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
use totara_competency\entity\configuration_change;
use totara_hierarchy\entity\scale_value;

/**
 * @group totara_competency
 */
class totara_competency_models_activity_log_config_change_testcase extends testcase {

    public function test_changed_aggregation() {
        $time = 100;
        $config_change = new configuration_change();
        $config_change->time_changed = $time;
        $config_change->change_type = configuration_change::CHANGED_AGGREGATION;
        $config_change->competency_id = 1;
        $config_change->save();

        $entry = activity_log\configuration_change::load_by_entity($config_change);

        $this->assertEquals('Overall rating calculation change', $entry->get_description());
        $this->assertNull($entry->get_assignment());
        $this->assertEquals($time, $entry->get_date());
        $this->assertNull($entry->get_proficient_status());
        $this->assertSame('', $entry->get_comment());
    }

    public function test_changed_criteria() {
        $time = 100;
        $config_change = new configuration_change();
        $config_change->time_changed = $time;
        $config_change->change_type = configuration_change::CHANGED_CRITERIA;
        $config_change->competency_id = 1;
        $config_change->save();

        $entry = activity_log\configuration_change::load_by_entity($config_change);

        $this->assertEquals('Criteria change', $entry->get_description());
        $this->assertNull($entry->get_assignment());
        $this->assertEquals($time, $entry->get_date());
        $this->assertNull($entry->get_proficient_status());
        $this->assertSame('', $entry->get_comment());
    }

    public function test_changed_min_proficiency() {
        $time = 100;
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

        $config_change = new configuration_change();
        $config_change->time_changed = $time;
        $config_change->change_type = configuration_change::CHANGED_MIN_PROFICIENCY;
        $config_change->related_info = json_encode(['new_min_proficiency_id' => $great->id]);
        $config_change->competency_id = 1;
        $config_change->save();

        $entry = activity_log\configuration_change::load_by_entity($config_change);

        $this->assertEquals('Minimum required proficiency value changed to \'Great\'', $entry->get_description());
        $this->assertNull($entry->get_assignment());
        $this->assertEquals($time, $entry->get_date());
        $this->assertNull($entry->get_proficient_status());
        $this->assertSame('', $entry->get_comment());
    }
}

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
 * @package totara_competency
 */

use core_phpunit\testcase;
use aggregation_first\first;
use aggregation_highest\highest;
use aggregation_latest_achieved\latest_achieved;
use core\hook\admin_setting_changed;
use totara_competency\achievement_configuration;
use totara_competency\admin_setting_legacy_aggregation_method;
use totara_competency\expand_task;
use totara_competency\task\competency_aggregation_queue;
use totara_core\advanced_feature;
use totara_hierarchy\entity\scale;

class totara_competency_settings_watcher_testcase extends testcase {

    public function test_settings_changed_ignored_on_perform() {
        global $DB;

        advanced_feature::enable('competency_assignment');

        $this->setAdminUser();

        /** @var \totara_competency\testing\generator $competency_generator */
        $competency_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');
        /** @var \totara_hierarchy\testing\generator $hierarchy_generator */
        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');

        $user = $this->getDataGenerator()->create_user();

        $scale = $hierarchy_generator->create_scale(
            'comp',
            ['name' => 'Test scale', 'description' => 'Test scale'],
            [
                5 => ['name' => 'No clue', 'proficient' => 0, 'sortorder' => 5, 'default' => 1],
                4 => ['name' => 'Learning', 'proficient' => 0, 'sortorder' => 4, 'default' => 0],
                3 => ['name' => 'Getting there', 'proficient' => 0, 'sortorder' => 3, 'default' => 0],
                2 => ['name' => 'Almost there', 'proficient' => 1, 'sortorder' => 2, 'default' => 0],
                1 => ['name' => 'Arrived', 'proficient' => 1, 'sortorder' => 1, 'default' => 0],
            ]
        );
        $scale = new scale($scale);
        $scalevalues = $scale->sorted_values_high_to_low->key_by('sortorder')->all(true);
        $fw = $competency_generator->create_framework($scale, 'Talking FW');
        $competency = $competency_generator->create_competency('Talking', $fw);

        // Now simulate a competency which is already configured with custom pathways
        $criteria_generator = $this->getDataGenerator()->get_plugin_generator('totara_criteria');

        $criterion = $criteria_generator->create_onactivate(['competency' => $competency->id]);
        $competency_generator->create_criteria_group(
            $competency,
            [$criterion],
            $scalevalues[5]->id
        );

        $configuration = new achievement_configuration($competency);
        $configuration->set_aggregation_type(first::aggregation_type());
        $configuration->save_aggregation();

        $assign_generator = $competency_generator->assignment_generator();
        $assign_generator->create_user_assignment($competency->id, $user->id);

        (new expand_task($DB))->expand_all();

        $task = new competency_aggregation_queue();
        $task->execute();

        $scale_aggregation_before = $DB->get_record('totara_competency_scale_aggregation', ['competency_id' => $competency->id]);
        $this->assertEquals(first::aggregation_type(), $scale_aggregation_before->type);
        $this->assertGreaterThan(0, $DB->count_records('totara_competency_achievement'));
        $this->assertEquals(0, $DB->count_records('totara_competency_aggregation_queue'));

        $hook = new admin_setting_changed('legacy_aggregation_method', admin_setting_legacy_aggregation_method::HIGHEST_ACHIEVEMENT, admin_setting_legacy_aggregation_method::LATEST_ACHIEVEMENT);
        $hook->execute();

        // The aggregation should not have been changed
        $scale_aggregation_after = $DB->get_record('totara_competency_scale_aggregation', ['competency_id' => $competency->id]);
        $this->assertEquals(first::aggregation_type(), $scale_aggregation_after->type);
        $this->assertEquals(0, $DB->count_records('totara_competency_aggregation_queue'));
    }

    public function test_settings_changed_without_achievements() {
        global $DB;

        advanced_feature::disable('competency_assignment');

        $this->setAdminUser();

        /** @var \totara_competency\testing\generator $competency_generator */
        $competency_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');

        $user = $this->getDataGenerator()->create_user();

        $scale = $competency_generator->create_scale();
        $fw = $competency_generator->create_framework($scale, 'Talking FW');
        $competency = $competency_generator->create_competency('Talking', $fw);

        $scale_aggregation_before = $DB->get_record('totara_competency_scale_aggregation', ['competency_id' => $competency->id]);
        $this->assertEquals(highest::aggregation_type(), $scale_aggregation_before->type);
        $this->assertEquals(0, $DB->count_records('totara_competency_aggregation_queue'));

        $hook = new admin_setting_changed('idontcare', admin_setting_legacy_aggregation_method::HIGHEST_ACHIEVEMENT, admin_setting_legacy_aggregation_method::LATEST_ACHIEVEMENT);
        $hook->execute();

        // Nothing changed
        $scale_aggregation_after = $DB->get_record('totara_competency_scale_aggregation', ['competency_id' => $competency->id]);
        $this->assertEquals(highest::aggregation_type(), $scale_aggregation_after->type);
        $this->assertEquals(0, $DB->count_records('totara_competency_aggregation_queue'));

        $hook = new admin_setting_changed('legacy_aggregation_method', admin_setting_legacy_aggregation_method::HIGHEST_ACHIEVEMENT, admin_setting_legacy_aggregation_method::LATEST_ACHIEVEMENT);
        $hook->execute();

        // The aggregation should now have been changed
        $scale_aggregation_after = $DB->get_record('totara_competency_scale_aggregation', ['competency_id' => $competency->id]);
        $this->assertEquals(latest_achieved::aggregation_type(), $scale_aggregation_after->type);
        $this->assertEquals(0, $DB->count_records('totara_competency_aggregation_queue'));
    }

    public function test_settings_changed_with_achievements() {
        global $DB;

        advanced_feature::disable('competency_assignment');

        $this->setAdminUser();

        /** @var \totara_competency\testing\generator $competency_generator */
        $competency_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');

        $user = $this->getDataGenerator()->create_user();

        $scale = $competency_generator->create_scale();
        $fw = $competency_generator->create_framework($scale, 'Talking FW');
        $competency = $competency_generator->create_competency('Talking', $fw);

        $competency_generator->create_learning_plan_with_competencies(
            $user->id,
            [$competency->id => $scale->min_proficient_value->id]
        );

        $task = new competency_aggregation_queue();
        $task->execute();

        $scale_aggregation_before = $DB->get_record('totara_competency_scale_aggregation', ['competency_id' => $competency->id]);
        $this->assertEquals(highest::aggregation_type(), $scale_aggregation_before->type);
        $this->assertEquals(0, $DB->count_records('totara_competency_aggregation_queue'));

        $hook = new admin_setting_changed('legacy_aggregation_method', admin_setting_legacy_aggregation_method::HIGHEST_ACHIEVEMENT, admin_setting_legacy_aggregation_method::LATEST_ACHIEVEMENT);
        $hook->execute();

        // The aggregation should now have been changed
        $scale_aggregation_after = $DB->get_record('totara_competency_scale_aggregation', ['competency_id' => $competency->id]);
        $this->assertEquals(latest_achieved::aggregation_type(), $scale_aggregation_after->type);
        // And we should have entries in the aggregation queue
        $this->assertEquals(1, $DB->count_records('totara_competency_aggregation_queue'));
    }

}
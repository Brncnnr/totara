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
use aggregation_latest_achieved\latest_achieved;
use totara_competency\achievement_configuration;
use totara_competency\admin_setting_legacy_aggregation_method;
use totara_competency\task\competency_aggregation_all;
use totara_competency\task\competency_aggregation_queue;
use totara_core\advanced_feature;
use totara_hierarchy\entity\scale;

class totara_competency_upgrade_testcase extends testcase {

    public function test_learn_upgrade_without_preexisting_data() {
        global $CFG, $DB;
        require_once $CFG->dirroot . '/totara/competency/db/upgradelib.php';

        // Let's simulate a new install where this config setting does not exist yet
        advanced_feature::disable('competency_assignment');
        unset_config('legacy_aggregation_method', 'totara_competency');

        $config_value = get_config('totara_competency', 'legacy_aggregation_method');
        $this->assertFalse($config_value);

        totara_competency_upgrade_update_aggregation_method_setting();

        $config_value = get_config('totara_competency', 'legacy_aggregation_method');
        $this->assertEquals(admin_setting_legacy_aggregation_method::HIGHEST_ACHIEVEMENT, $config_value);
    }

    public function test_perform_upgrade_without_preexisting_data() {
        global $CFG, $DB;
        require_once $CFG->dirroot . '/totara/competency/db/install.php';

        // Let's simulate a new install where this config setting does not exist yet
        advanced_feature::enable('competency_assignment');
        unset_config('legacy_aggregation_method', 'totara_competency');

        $config_value = get_config('totara_competency', 'legacy_aggregation_method');
        $this->assertFalse($config_value);

        totara_competency_upgrade_update_aggregation_method_setting();

        $config_value = get_config('totara_competency', 'legacy_aggregation_method');
        $this->assertEquals(admin_setting_legacy_aggregation_method::HIGHEST_ACHIEVEMENT, $config_value);
    }

    public function test_learn_upgrade_with_preexisting_competency_records() {
        global $CFG, $DB;
        require_once $CFG->dirroot . '/totara/competency/db/install.php';

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

        unset_config('legacy_aggregation_method', 'totara_competency');
        $DB->execute("UPDATE {totara_competency_scale_aggregation} SET type = '". latest_achieved::aggregation_type()."'");

        totara_competency_upgrade_update_aggregation_method_setting();

        $config_value = get_config('totara_competency', 'legacy_aggregation_method');
        $this->assertEquals(admin_setting_legacy_aggregation_method::LATEST_ACHIEVEMENT, $config_value);

        $scale_aggregations = $DB->get_records('totara_competency_scale_aggregation');
        foreach ($scale_aggregations as $scale_aggregation) {
            $this->assertEquals(latest_achieved::aggregation_type(), $scale_aggregation->type);
        }
    }

    public function test_perform_upgrade_with_preexisting_competency_records() {
        global $CFG, $DB;
        require_once $CFG->dirroot . '/totara/competency/db/install.php';

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

        $fw = $competency_generator->create_framework($scale, 'Test FW');
        $competency = $competency_generator->create_competency('Test', $fw);

        $record_time = time() - 1;
        $this->add_comp_record($competency->id, $user->id, 1, $record_time, $record_time);
        $this->add_comp_record_history($competency->id, $user->id, 1, $record_time);

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

        $task = new competency_aggregation_all();
        $task->execute();

        unset_config('legacy_aggregation_method', 'totara_competency');

        $scale_aggregation_before = $DB->get_record('totara_competency_scale_aggregation', ['competency_id' => $competency->id]);

        totara_competency_upgrade_update_aggregation_method_setting();

        $config_value = get_config('totara_competency', 'legacy_aggregation_method');
        $this->assertEquals(admin_setting_legacy_aggregation_method::HIGHEST_ACHIEVEMENT, $config_value);

        // The existing scale aggregation types should be untouched
        $scale_aggregation_after = $DB->get_record('totara_competency_scale_aggregation', ['competency_id' => $competency->id]);
        $this->assertEquals($scale_aggregation_before->type, $scale_aggregation_after->type);
    }

    private function add_comp_record(int $competency_id, int $user_id, int $proficiency, int $timecreated = null, int $timemodified = null): stdClass {
        global $DB;

        $comp_record = new stdClass();
        $comp_record->competencyid = $competency_id;
        $comp_record->userid = $user_id;
        $comp_record->proficiency = $proficiency;
        $comp_record->timecreated = $timecreated ?? time();
        $comp_record->timemodified = $timemodified ?? time();
        $comp_record->id = $DB->insert_record('comp_record', $comp_record);

        return $comp_record;
    }

    private function add_comp_record_history(int $competency_id, int $user_id, int $proficiency, int $timemodified = null): stdClass {
        global $DB;

        $comp_record_history = new stdClass();
        $comp_record_history->competencyid = $competency_id;
        $comp_record_history->userid = $user_id;
        $comp_record_history->proficiency = $proficiency;
        $comp_record_history->timemodified = $timemodified;
        $comp_record_history->usermodified = 500;
        $comp_record_history->id = $DB->insert_record('comp_record_history', $comp_record_history);

        return $comp_record_history;
    }

}
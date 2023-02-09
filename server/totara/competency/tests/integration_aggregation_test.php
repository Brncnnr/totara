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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_competency
 */

global $CFG;
require_once($CFG->dirroot . '/totara/competency/tests/integration_aggregation_base_test.php');

/**
 * This is an integration test with multiple users assigned to multiple competencies
 * It verifies over the competency / criteria boundaries to ensure the correct data is
 * created on all levels
 *
 * Test descriptions are defined in https://docs.google.com/spreadsheets/d/1rjnFZtI-ZJZCE8AmJjmiXtmU9S1_uIld_swteRyIKgA/edit#gid=0
 *
 * @group totara_competency
 */
class totara_competency_integration_aggregation_testcase extends totara_competency_integration_aggregation_base_testcase {

    /**
     * Test competency_aggregation_all task with no criteria
     * @dataProvider task_to_execute_data_provider
     */
    public function test_aggregation_all_task_no_criteria(string $task_to_execute) {
        $data = $this->setup_data();

        $this->verify_item_records([]);
        $this->verify_pathway_achievements([]);
        $this->verify_competency_achievements([]);

        (new $task_to_execute())->execute();
        $this->verify_item_records([]);
        $this->verify_pathway_achievements([]);
        $this->verify_competency_achievements([]);

        // Now assign users to the competencies and test again

        $to_assign = [];
        foreach ($data->users as $user) {
            foreach ($data->competencies as $competency) {
                $to_assign[] = ['user_id' => $user->id, 'competency_id' => $competency->id];
            }
        }
        $this->assign_users_to_competencies($to_assign);
        $this->waitForSecond();

        (new $task_to_execute())->execute();
        $this->verify_item_records([]);
        $this->verify_pathway_achievements([]);
        $this->verify_competency_achievements([]);
    }

}

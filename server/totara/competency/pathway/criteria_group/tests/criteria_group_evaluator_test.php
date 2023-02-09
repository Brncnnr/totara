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

use core_phpunit\testcase;
use pathway_criteria_group\criteria_group;
use pathway_criteria_group\criteria_group_evaluator;
use totara_competency\aggregation_users_table;
use totara_competency\entity\pathway_achievement;
use totara_competency\pathway_evaluator_user_source;
use totara_criteria\criterion;
use totara_criteria\evaluators\item_evaluator;
use totara_criteria\evaluators\item_evaluator_user_source;
use totara_hierarchy\entity\competency;
use totara_hierarchy\entity\scale_value;

/**
 * @group totara_competency
 */
class pathway_criteria_group_evaluator_testcase extends testcase {

    private function setup_data() {
        global $DB;

        $data = new class() {
            /** @var competency $competency*/
            public $competency;
            public $criteria;
            public $scale;
            public $scalevalues = [];
            /** @var aggregation_users_table $user_id_table */
            public $user_id_table;
        };

        $hierarchygenerator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');

        $data->scale = $hierarchygenerator->create_scale(
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
        $rows = $DB->get_records('comp_scale_values', ['scaleid' => $data->scale->id], 'sortorder');
        foreach ($rows as $row) {
            $data->scalevalues[$row->sortorder] = new scale_value($row->id);
        }

        $framework = $hierarchygenerator->create_comp_frame(['scale' => $data->scale->id]);
        $comp = $hierarchygenerator->create_comp(['frameworkid' => $framework->id]);
        $data->competency = new competency($comp->id);

        $criteria_generator = $this->getDataGenerator()->get_plugin_generator('totara_criteria');

        $data->criteria = [];
        for ($i = 1; $i <= 5; $i++) {
            $data->criteria[$i] = $criteria_generator->create_test_criterion('test_cge_criterion');
        }

        $data->user_id_table = new aggregation_users_table();

        return $data;
    }


    /***************************************************
     * Aggregation takes a number of factors into consideration.
     * We mock the criteria behaviour through the test criterion test_cge_criterion with evaluator test_cge_criterion_combined.
     *
     * The following factors are taken into consideration
     *   - Have the user's completion of any criteria changed (simulated here via the mock criterion class directly updating has_changed)
     *   - Is there an existing current pathway_achievement record for this user
     *   - Does the newly achieved value differ from the existing achieved value found the the pathway_achievement record (if it exist)
     */

    /**
     * Data provider for test_aggregate
     */
    public function data_provider_test_aggregate() {
        return [
            // 1 criterion. 1 user. Criterion_met didn't change. No change in achievement
            [
                'pathway_scale_value' => 3,
                'criteria' => [
                    1 => [
                        'users' => [
                            1 => [
                                'user_id' => 1,
                                'existing_achievement' => [
                                    'status' => pathway_achievement::STATUS_CURRENT,
                                    'scale_value' => 2,
                                ],
                                'criteria_met' => false,
                                'criteria_met_has_changed' => false,
                            ],
                        ],
                    ],
                ],
                'expected' => [
                    1 => [
                        [
                            'status' => pathway_achievement::STATUS_CURRENT,
                            'scale_value' => 2,
                        ]
                    ],
                ],
            ],

            // 1 criterion. 1 user. Criterion_met changed. User didn't satisfy criteria. Changed achievement
            [
                'pathway_scale_value' => 3,
                'criteria' => [
                    1 => [
                        'users' => [
                            1 => [
                                'user_id' => 1,
                                'existing_achievement' => [
                                    'status' => pathway_achievement::STATUS_CURRENT,
                                    'scale_value' => 2,
                                ],
                                'criteria_met' => false,
                                'criteria_met_has_changed' => true,
                            ],
                        ],
                    ],
                ],
                'expected' => [
                    1 => [
                        [
                            'status' => pathway_achievement::STATUS_ARCHIVED,
                            'scale_value' => 2,
                        ],
                        [
                            'status' => pathway_achievement::STATUS_CURRENT,
                            'scale_value' => null,
                        ],
                    ],
                ],
            ],

            // 1 criterion. 1 user. Criterion_met changed. User satisfies criteria. Changed achievement
            [
                'pathway_scale_value' => 3,
                'criteria' => [
                    1 => [
                        'users' => [
                            1 => [
                                'user_id' => 1,
                                'existing_achievement' => [
                                    'status' => pathway_achievement::STATUS_CURRENT,
                                    'scale_value' => 2,
                                ],
                                'criteria_met' => true,
                                'criteria_met_has_changed' => true,
                            ],
                        ],
                    ],
                ],
                'expected' => [
                    1 => [
                        [
                            'status' => pathway_achievement::STATUS_ARCHIVED,
                            'scale_value' => 2,
                        ],
                        [
                            'status' => pathway_achievement::STATUS_CURRENT,
                            'scale_value' => 3,
                        ],
                    ],
                ],
            ],

            // 2 criteria. 1 user. New achievement. Some criterion_met changed. Some criteria satisfied. Changed achievement
            [
                'pathway_scale_value' => 3,
                'criteria' => [
                    1 => [
                        'users' => [
                            1 => [
                                'user_id' => 1,
                                'criteria_met' => true,
                                'criteria_met_has_changed' => false,
                            ],
                        ],
                    ],
                    2 => [
                        'users' => [
                            1 => [
                                'user_id' => 1,
                                'criteria_met' => false,
                                'criteria_met_has_changed' => true,
                            ],
                        ],
                    ],
                ],
                'expected' => [
                    1 => [
                        [
                            'status' => pathway_achievement::STATUS_CURRENT,
                            'scale_value' => null,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Test aggregate using a table with assigned users
     *
     * @dataProvider data_provider_test_aggregate
     */
    public function test_aggregate_from_table($pathway_scale_value_key, $criteria, $expected) {
        // Setting up the data
        $data = $this->setup_data();
        /** @var \totara_competency\testing\generator $competency_generator */
        $competency_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');

        $cg_criteria = [];
        foreach (array_keys($criteria) as $key) {
            $cg_criteria[] = $data->criteria[$key];
        }

        /** @var criteria_group $cg */
        $cg = $competency_generator->create_criteria_group($data->competency,
            $cg_criteria,
            $data->scalevalues[$pathway_scale_value_key]->id,
            1
        );
        // Pathways make use of an operation key in the user_id_table,
        // Setting the same value in tests
        $operation_value = $cg->get_path_type() . '__' . $cg->get_id();
        $data->user_id_table->set_update_operation_value($operation_value);

        $assigned_users = [];
        $criteria_has_met_user_ids = [];
        $criteria_updated_user_ids = [];

        foreach ($criteria as $criterion_key => $criterion_data) {
            $criterion = $data->criteria[$criterion_key];

            foreach ($criterion_data['users'] as $user) {
                if (!in_array($user['user_id'], $assigned_users)) {
                    $assigned_users[] = $user['user_id'];
                }

                if (isset($user['existing_achievement'])) {
                    $this->create_achievement_record($cg->get_id(),
                        $user['user_id'],
                        $user['existing_achievement']['status'],
                        $data->scalevalues[$user['existing_achievement']['scale_value']]->id
                    );
                }

                if (!empty($user['criteria_met'])) {
                    $criterion->add_criterion_met_user_id($user['user_id']);
                }

                if (!empty($user['criteria_met_has_changed'])) {
                    $criterion->add_updated_user_id($user['user_id']);
                }
            }

            $criteria_has_met_user_ids[$criterion->get_id()] = $criterion->get_criterion_met_user_ids();
            $criteria_updated_user_ids[$criterion->get_id()] = $criterion->get_updated_user_ids();
        }

        $this->create_userid_table_records($data->user_id_table, $data->competency->id, $assigned_users);

        // We 'pass' the users who have met the criteria via a static attribute in the class
        test_cge_criterion::set_criteria_has_met_user_ids($criteria_has_met_user_ids);
        // We 'pass' the users to be marked as updated via a static attribute in the class
        test_cge_criterion_evaluator::set_criteria_updated_user_ids($criteria_updated_user_ids);

        // End of setting up the data

        $user_source = new pathway_evaluator_user_source($data->user_id_table, true);
        $evaluator = new criteria_group_evaluator($cg, $user_source);
        $this->waitForSecond();
        $evaluator->aggregate(time());
        $this->validate_achievement_records($expected, $data->scalevalues);
    }



    /**
     * Helper function to create a test pathway_achievement record
     *
     * @param int $pathway_id
     * @param int $user_id
     * @param ?int $status
     * @param ?int $scale_value_id
     * @param ?int date_achieved
     * @param bool $do_truncate
     */
    private function create_achievement_record(int $pathway_id, int $user_id, int $status = null,
                                               ?int $scale_value_id = null, ?int $date_achieved = null,
                                               bool $do_truncate = false) {
        global $DB;

        if ($do_truncate) {
            $DB->delete_records('totara_competency_pathway_achievement');
        }

        $instance = new pathway_achievement();
        $instance->pathway_id = $pathway_id;
        $instance->user_id = $user_id;
        $instance->status = $status ?? pathway_achievement::STATUS_CURRENT;
        if (!is_null($scale_value_id)) {
            $instance->scale_value_id = $scale_value_id;
        }
        $instance->date_achieved = $date_achieved ?? strtotime("-1 week");
        $instance->save();
    }

    /**
     * Helper function to validate totara_competency_pathway_achievement contains the expected rows
     *
     * @param $expected
     */
    private function validate_achievement_records($expected, $scalevalues) {
        global $DB;

        $actual_rows = $DB->get_records('totara_competency_pathway_achievement');
        foreach ($actual_rows as $actual_row) {
            $this->assertTrue(isset($expected[$actual_row->user_id]));

            $actual_fnd = false;
            $expected_rows = $expected[$actual_row->user_id];

            foreach ($expected_rows as $key => $expected_row) {
                $match = true;
                foreach ($expected_row as $columnname => $expected_value) {
                    if ($columnname == 'scale_value') {
                        $columnname = 'scale_value_id';
                        $expected_value = !is_null($expected_value) ? $scalevalues[$expected_value]->id : null;
                    }
                    if ($expected_value != $actual_row->{$columnname}) {
                        $match = false;
                        break;
                    }
                }
                // Row found - now remove from the expected rows and compare next actual
                if ($match) {
                    $actual_fnd = true;
                    unset($expected[$actual_row->user_id][$key]);
                    break;
                }
            }

            $this->assertTrue($actual_fnd);
        }

        // Ensure we found all expected
        foreach ($expected as $user_id => $expected_rows) {
            $this->assertSame(0, count($expected_rows));
        }
    }

    /**
     * Helper function to create rows in the user_id table
     *
     * @param aggregation_users_table $user_id_table
     * @param int $competency_id
     * @param array $assigned_users
     */
    private function create_userid_table_records(
        aggregation_users_table $user_id_table,
        int $competency_id, array $assigned_users
    ) {
        global $DB;

        if (empty($assigned_users)) {
            return;
        }

        $tablename = $user_id_table->get_table_name();
        $temp_user_records = [];
        foreach ($assigned_users as $user_id) {
            $temp_user_records[] = $user_id_table->get_insert_record($user_id, $competency_id);
        }
        $DB->insert_records($tablename, $temp_user_records);
    }

}


class test_cge_criterion extends criterion {

    // The test criterion make use of a static variable containing the 'criterion_met_user_ids for all criteria
    // The similar private attribute is used during setup of the data, while the static variable is required
    // during aggregation as aggregation is done on a newly instantiated criterion and 'criterion_met_user_ids' is not
    // persisted to the database

    /** @var array $criteria_criterion_met_user_ids User ids to consider to have met all criteria for each criterion */
    private static $criteria_has_met_user_ids = [];

    public static function set_criteria_has_met_user_ids(array $criteria_has_met_user_ids) {
        self::$criteria_has_met_user_ids = $criteria_has_met_user_ids;
    }


    /** @var array $criterion_met_user_ids Ids of users to consider to have met the criteria  */
    private $criterion_met_user_ids = [];
    /** @var array $updated_user_ids User ids to mark as having updates */
    private $updated_user_ids = [];

    public function get_items_type() {
        return 'test_cge_criterion';
    }

    protected function get_display_class(): string {
        return 'test_cge_criterion';
    }

    public static function item_evaluator(): string {
        return test_cge_criterion_evaluator::class;
    }

    public function update_items(): criterion {
        $this->set_item_ids([1]);
        return $this;
    }

    public function add_criterion_met_user_id(int $user_id) {
        if (!in_array($user_id, $this->criterion_met_user_ids)) {
            $this->criterion_met_user_ids[] = $user_id;
        }
    }

    public function get_criterion_met_user_ids(): array {
        return $this->criterion_met_user_ids;
    }

    public function add_updated_user_id(int $user_id) {
        if (!in_array($user_id, $this->updated_user_ids)) {
            $this->updated_user_ids[] = $user_id;
        }
    }

    public function get_updated_user_ids(): array {
        return $this->updated_user_ids;
    }

    public function aggregate(int $user_id): bool {
        return in_array($user_id, self::$criteria_has_met_user_ids[$this->get_id()]);
    }
}

class_alias('test_cge_criterion', 'criteria_test_cge_criterion\\test_cge_criterion');

class test_cge_criterion_evaluator extends item_evaluator {
    /** @var array $criteria_updated_user_ids User ids to mark as having updates for each criterion */
    private static $criteria_updated_user_ids = [];

    public static function set_criteria_updated_user_ids(array $criteria_updated_user_ids) {
        self::$criteria_updated_user_ids = $criteria_updated_user_ids;
    }

    /**
     * Overriding this function to simplify the test
     *
     * @param criterion $criterion
     */
    public function update_completion(criterion $criterion) {
        global $DB;

        $updated_user_ids = self::$criteria_updated_user_ids[$criterion->get_id()];

        if (empty($updated_user_ids) || !$this->user_source instanceof item_evaluator_user_source) {
            // Nothing to do
            return;
        }

        /** @var aggregation_users_table $temp_table */
        $temp_table = $this->user_source->get_source();
        $temp_table_name = $temp_table->get_table_name();
        $temp_user_id_column = $temp_table->get_user_id_column();
        [$temp_set_sql, $temp_set_params] = $temp_table->get_set_has_changed_sql_with_params(1);
        [$temp_wh, $temp_wh_params] = $temp_table->get_filter_sql_with_params('', true, null);
        if (!empty($temp_wh)) {
            $temp_wh .= ' AND ';
        }
        [$users_in_sql, $users_in_params] = $DB->get_in_or_equal($updated_user_ids, SQL_PARAMS_NAMED);

        $sql =
            "UPDATE {" . $temp_table_name . "}
                SET {$temp_set_sql}
              WHERE {$temp_wh}
                    {$temp_user_id_column} {$users_in_sql}";

        $params = array_merge($temp_set_params, $temp_wh_params, $users_in_params);

        $DB->execute($sql, $params);
    }

    protected function update_criterion_completion(criterion $criterion, int $now) {
    }
}

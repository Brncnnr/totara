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
 * @author Vernon Denny <vernon.denny@totaralearning.com>
 * @package totara_cohort
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/totara/cohort/lib.php');

// Make constants available.
require_once($CFG->dirroot . '/totara/cohort/classes/rules/ui/none_min_max_exactly.php');
use core\entity\cohort_member;
use core_phpunit\testcase;
use totara_cohort\rules\ui\none_min_max_exactly as ui;


class totara_cohort_indirect_reports_testcase extends testcase {

    private $users = [];
    private $cohort = null;
    private $ruleset = 0;
    private $cohort_generator = null;

    const TEST_COUNT_USERS = 20;

    protected function tearDown(): void {
        $this->users = null;
        $this->cohort = null;
        $this->ruleset = null;
        $this->cohort_generator = null;
        parent::tearDown();
    }

    public function setUp(): void {
        parent::setup();

        // Set totara_cohort generator.
        $this->cohort_generator = \totara_cohort\testing\generator::instance();
    }


    /**
    Test data: Job assignment hierarchies:
    ------------------------------------

     * Tree.
    Usr3 (manager: user id 3 == root)
     |
     |
     |---Usr4
     |    |
     |    |---Usr6
     |    |
     |    |---Usr7
     |         |__________________
     |         |        |        |
     |       Usr8      Usr9     Usr10
     |
     |
     |---Usr5
          |
          |---Usr11
               |
               |
               |
              Usr12
               |
               |
           ____|____
           |       |
          Usr13   Usr14


     * Sapling.
    Usr11
     |_____
          |
         Usr15
          |______
                |
               Usr16


     * Sapling.
    Usr3
     |_____
          |
         Usr17

    */

    /**
     * Evaluates if the user has indirect reports according to various filtering options.
     *
     * @dataProvider data_indirect_reports
     */
    public function test_indirect_reports_rules($params, $listofvalues, $cohortmembers) {
        $this->setAdminUser();

        // Create users.
        for ($i = 3; $i < self::TEST_COUNT_USERS; $i++) {
            $this->users[$i] = $this->getDataGenerator()->create_user()->id;
        }
        $this->assertEquals((self::TEST_COUNT_USERS - 3), count($this->users));

        // Create job assignments and assign managers to users in the tree formation given above (user id points to manager user id).
        $trees = [];
        $trees[] = [
            // Root.
            $this->users[3] => 0,
            // Branch heads.
            $this->users[4] => $this->users[3],
            $this->users[5] => $this->users[3],
            // Left branch.
            $this->users[6] => $this->users[4],
            $this->users[7] => $this->users[4],
            $this->users[8] => $this->users[7],
            $this->users[9] => $this->users[7],
            $this->users[10] => $this->users[7],
            // Right branch.
            $this->users[11] => $this->users[5],
            $this->users[12] => $this->users[11],
            $this->users[13] => $this->users[12],
            $this->users[14] => $this->users[12],
        ];

        // Additional straight-line job assignment hierarchy.
        $trees[] = [
            $this->users[11] => 0,
            $this->users[15] => $this->users[11],
            $this->users[16] => $this->users[15],
        ];

        $trees[] = [
            $this->users[3] => 0,
            $this->users[17] => $this->users[3]
        ];

        // Construct the hierarchies in job assignments table.
        $jaids = [];
        foreach ($trees as $index => $tree) {
            $jaids[$index] = [];
            foreach ($tree as $userid => $managerid) {
                // Create job assignment for user.
                $suffix = (string) $index . (string) $userid;
                $userja = \totara_job\job_assignment::create([
                    'userid' => $userid,
                    'fullname' => 'user' . $suffix,
                    'shortname' => 'user' . $suffix,
                    'idnumber' => 'id' . $suffix,
                    'managerjaid' => null,
                ]);

                // Store job assignment id for easy retrieval.
                $jaids[$index][$userid] = (int) $userja->id;

                // Set users manager as needed.
                if ($managerid > 0) {
                    \totara_job\job_assignment::get_with_id($jaids[$index][$userid])->update(['managerjaid' => $jaids[$index][$managerid]]);
                }
            }
        }
        $jaids = null;

        // Create and apply indirect reports rule using test data parameters.
        $this->cohort = $this->cohort_generator->create_cohort(['cohorttype' => cohort::TYPE_DYNAMIC]);
        $this->ruleset = cohort_rule_create_ruleset($this->cohort->draftcollectionid);

        // Create dynamic cohort.
        $this->cohort_generator->create_cohort_rule_params(
            $this->ruleset,
            'alljobassign',
            'hasindirectreports',
            $params,
            $listofvalues,
            'listofvalues'
        );

        // Calculate cohort membership.
        cohort_rules_approve_changes($this->cohort);

        // Test the results.
        $expected = array_map(
            function (int $index): int {
                return $this->users[$index];
            },
            $cohortmembers
        );

        $members = cohort_member::repository()
            ->where('cohortid', $this->cohort->id)
            ->get()
            ->pluck('userid');

        $this->assertEqualsCanonicalizing($expected, $members);
    }

    /**
     * Data provider for the indirect reports rule.
     */
    public function data_indirect_reports() {
        $data = [
            'Minimum 9 indirect reports --> expecting 1' => [
                ['equal' => ui::COHORT_RULES_OP_MIN], [9], [3]
            ],

            'Minimum 10 indirect reports --> expecting 0' => [
                ['equal' => ui::COHORT_RULES_OP_MIN], [10], []
            ],

            'Maximum 3 indirect reports --> expecting 3' => [
                ['equal' => ui::COHORT_RULES_OP_MAX], [3], [4, 5, 11]
            ],

            'Maximum 9 indirect reports --> expecting 4' => [
                ['equal' => ui::COHORT_RULES_OP_MAX], [9], [3, 4, 5, 11]
            ],

            'Exactly 3 indirect reports --> expecting 3' => [
                ['equal' => ui::COHORT_RULES_OP_EXACT], [3], [4, 5, 11]
            ],

            'Exactly 2 indirect reports --> expecting 0' => [
                ['equal' => ui::COHORT_RULES_OP_EXACT], [2], []
            ],

            // Note the 'no indirect report' rule means 'no indirect reports AT ALL'. So for this rule
            // to apply, the manager must have zero indirect reports however many direct reports he has.
            'No indirect reports --> expecting 10' => [
                ['equal' => ui::COHORT_RULES_OP_NONE],
                [0],
                [6, 7, 8, 9, 10, 12, 13, 14, 15, 16, 17]
            ]
        ];

        return $data;
    }
}

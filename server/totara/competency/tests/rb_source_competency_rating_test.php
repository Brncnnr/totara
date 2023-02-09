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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package totara_competency
 */

use core_phpunit\testcase;
use pathway_manual\models\roles\appraiser;
use pathway_manual\models\roles\manager;
use pathway_manual\models\roles\self_role;
use totara_competency\entity\assignment;
use totara_competency\models\assignment as assignment_model;
use totara_competency\models\assignment_actions;
use totara_competency\user_groups;
use totara_hierarchy\entity\scale_value;
use totara_job\job_assignment;

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_reportbuilder
 * @group totara_competency
 */
class totara_reportbuilder_rb_source_competency_rating_testcase extends testcase {
    use totara_reportbuilder\phpunit\report_testing;

    public function test_report() {
        self::setAdminUser();

        $generator = self::getDataGenerator();
        /** @var \totara_hierarchy\testing\generator $hierarchy_generator */
        $hierarchy_generator = $generator->get_plugin_generator('totara_hierarchy');
        /** @var \totara_competency\testing\generator $comp_generator */
        $comp_generator = $generator->get_plugin_generator('totara_competency');
        $user1 = $generator->create_user(['lastname' => 'user1']);
        $user2 = $generator->create_user(['lastname' => 'user2']);
        $user3 = $generator->create_user(['lastname' => 'user3']);
        $user4 = $generator->create_user(['lastname' => 'user4']);
        $cohort1 = $generator->create_cohort();
        $cohort2 = $generator->create_cohort();

        $scale = $comp_generator->create_scale('scale1', 'scale1_description', [
            ['name' => 'scale_value1', 'proficient' => false, 'default' => true, 'sortorder' => 1],
            ['name' => 'scale_value2', 'proficient' => true, 'default' => false, 'sortorder' => 2],
        ]);

        $fw = $comp_generator->create_framework($scale);
        $comp1 = $comp_generator->create_competency('comp1', $fw);
        $comp2 = $comp_generator->create_competency('comp2', $fw);
        $comp3 = $comp_generator->create_competency('comp3', $fw);

        // Create two manual pathways for comp1, so we can verify it doesn't create duplicate rows in the report.
        $comp_generator->create_manual($comp1, [manager::class, appraiser::class, self_role::class]);
        $comp_generator->create_manual($comp1, [manager::class, appraiser::class]);

        // One manual pathway for comp2. No manual pathway for comp3.
        $comp_generator->create_manual($comp2, [manager::class, appraiser::class]);
        $comp_generator->create_manual($comp3, [self_role::class]);

        $fw = $hierarchy_generator->create_pos_frame(['fullname' => 'Position framework']);
        $pos1 = $hierarchy_generator->create_pos(['frameworkid' => $fw->id, 'fullname' => 'Position 1']);
        $pos2 = $hierarchy_generator->create_pos(['frameworkid' => $fw->id, 'fullname' => 'Position 2']);
        $pos3 = $hierarchy_generator->create_pos(['frameworkid' => $fw->id, 'fullname' => 'Position 3']);

        self::assign_position($user1, $pos1);
        self::assign_position($user2, $pos1);
        self::assign_position($user3, $pos2);
        self::assign_position($user4, $pos3);

        $rid = $this->create_report('competency_rating', 'Test rating report');
        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);
        $this->add_column($report, 'user', 'lastname', null, null, null, 0);
        $this->add_column($report, 'competency', 'fullname', null, null, null, 0);
        $this->add_column($report, 'rating', 'rating', null, null, null, 0);
        $this->add_column($report, 'rating', 'rolename', null, null, null, 0);
        $this->add_column($report, 'rating', 'rating_time', null, null, null, 0);

        // No assignments present, report should be empty.
        self::assert_report($rid, []);

        $actions = new assignment_actions();
        $competencies = [$comp1->id, $comp2->id];
        $actions->create_from_competencies(
            $competencies,
            [user_groups::POSITION => [$pos1->id]],
            assignment::TYPE_ADMIN, assignment::STATUS_DRAFT
        );
        $actions->create_from_competencies(
            $competencies,
            [user_groups::POSITION => [$pos2->id]],
            assignment::TYPE_ADMIN,
            assignment::STATUS_ACTIVE
        );
        $assignments_to_archive = $actions->create_from_competencies(
            $competencies,
            [user_groups::POSITION => [$pos3->id]],
            assignment::TYPE_ADMIN,
            assignment::STATUS_ACTIVE
        );

        $expected_result = [
            ['user3', 'comp1', 'manager', ''],
            ['user3', 'comp1', 'appraiser', ''],
            ['user3', 'comp1', 'self', ''],
            ['user3', 'comp2', 'manager', ''],
            ['user3', 'comp2', 'appraiser', ''],
            ['user4', 'comp1', 'manager', ''],
            ['user4', 'comp1', 'appraiser', ''],
            ['user4', 'comp1', 'self', ''],
            ['user4', 'comp2', 'manager', ''],
            ['user4', 'comp2', 'appraiser', ''],
        ];
        self::assert_report($rid, $expected_result);

        // Archive assignments to position 3. It should not change the result.
        /** @var assignment_model $assignment */
        foreach ($assignments_to_archive as $assignment) {
            $assignment->archive();
        }
        self::assert_report($rid, $expected_result);

        // Add self-rating for user3
        /** @var scale_value $scale_value1 */
        $scale_value1 = scale_value::repository()->where('name', 'scale_value1')->one();
        $comp_generator->create_manual_rating(
            $comp1,
            $user3->id,
            $user3->id,
            self_role::class,
            $scale_value1->id,
            'Rating Two'
        );

        $expected_result = [
            ['user3', 'comp1', 'manager', ''],
            ['user3', 'comp1', 'appraiser', ''],
            ['user3', 'comp1', 'self', 'scale_value1'],
            ['user3', 'comp2', 'manager', ''],
            ['user3', 'comp2', 'appraiser', ''],
            ['user4', 'comp1', 'manager', ''],
            ['user4', 'comp1', 'appraiser', ''],
            ['user4', 'comp1', 'self', ''],
            ['user4', 'comp2', 'manager', ''],
            ['user4', 'comp2', 'appraiser', ''],
        ];
        self::assert_report($rid, $expected_result);

        // Add another self-rating for user3. This should lead to an additional row.
        /** @var scale_value $scale_value2 */
        $scale_value2 = scale_value::repository()->where('name', 'scale_value2')->one();
        $comp_generator->create_manual_rating(
            $comp1,
            $user3->id,
            $user3->id,
            self_role::class,
            $scale_value2->id,
            'Rating Two'
        );
        $expected_result[] = ['user3', 'comp1', 'self', 'scale_value2'];
        self::assert_report($rid, $expected_result);

        // Add an audience assignment for the same users for the same competencies. This should not change the result.
        cohort_add_member($cohort1->id, $user3->id);
        cohort_add_member($cohort1->id, $user4->id);
        $actions->create_from_competencies(
            $competencies,
            [user_groups::COHORT => [$cohort1->id]],
            assignment::TYPE_ADMIN,
            assignment::STATUS_ACTIVE
        );
        self::assert_report($rid, $expected_result);

        // Add an audience assignment for new user/competency.
        cohort_add_member($cohort2->id, $user1->id);
        $actions->create_from_competencies(
            [$comp3->id],
            [user_groups::COHORT => [$cohort2->id]],
            assignment::TYPE_ADMIN,
            assignment::STATUS_ACTIVE
        );
        $expected_result[] = ['user1', 'comp3', 'self', ''];
        self::assert_report($rid, $expected_result);

        // Add an organisation assignment.
        $fw = $hierarchy_generator->create_org_frame(['fullname' => 'Orga framework']);
        $org1 = $hierarchy_generator->create_org(['frameworkid' => $fw->id, 'fullname' => 'Organisation 1']);
        $job_data = [
            'userid' => $user2->id,
            'idnumber' => 'org1',
            'fullname' => 'org1 job',
            'organisationid' => $org1->id
        ];
        job_assignment::create($job_data);
        $actions->create_from_competencies(
            [$comp3->id],
            [user_groups::ORGANISATION => [$org1->id]],
            assignment::TYPE_ADMIN,
            assignment::STATUS_ACTIVE
        );
        $expected_result[] = ['user2', 'comp3', 'self', ''];
        self::assert_report($rid, $expected_result);

        // Add an individual user assignment.
        $actions->create_from_competencies(
            [$comp3->id],
            [user_groups::USER => [$user4->id]],
            assignment::TYPE_ADMIN,
            assignment::STATUS_ACTIVE
        );
        $expected_result[] = ['user4', 'comp3', 'self', ''];
        self::assert_report($rid, $expected_result);
    }

    /**
     * @param int $report_id
     * @param array $expected_records
     */
    private static function assert_report(int $report_id, array $expected_records): void {
        global $DB;

        $report = reportbuilder::create($report_id);
        [$sql, $params] = $report->build_query();
        $records = $DB->get_records_sql($sql, $params);
        $actual_records = [];
        foreach ($records as $record) {
            $record = (array)$record;
            $actual_records[] = [
                $record['user_lastname'],
                $record['competency_fullname'],
                $record['rating_rolename'],
                $record['rating_rating'] ?? '',
            ];
        }
        self::assertEqualsCanonicalizing($expected_records, $actual_records);
    }

    /**
     * @param stdClass $user
     * @param stdClass $position
     */
    private static function assign_position(stdClass $user, stdClass $position): void {
        job_assignment::create([
            'userid' => $user->id,
            'idnumber' => 'dev1',
            'fullname' => 'Developer',
            'positionid' => $position->id
        ]);
    }
}
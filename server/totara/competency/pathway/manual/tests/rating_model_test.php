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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package pathway_manual
 * @category test
 */

use pathway_manual\entity\rating as rating_entity;
use pathway_manual\models\rating as rating_model;
use pathway_manual\models\roles\appraiser;
use pathway_manual\models\roles\manager;
use pathway_manual\models\roles\self_role;
use totara_job\job_assignment;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/pathway_manual_base_testcase.php');

/**
 * @group totara_competency
 */
class pathway_manual_rating_model_testcase extends pathway_manual_base_testcase {

    public function test_create_with_missing_capability_rating_self() {
        global $DB;
        $this->setUser($this->user1->id);

        $user_role_id = $DB->get_record('role', ['shortname' => 'user'])->id;
        unassign_capability('totara/competency:rate_own_competencies', $user_role_id);

        $this->expectException(required_capability_exception::class);
        rating_model::for_user_and_role($this->user1->id, self_role::class)->create(
            $this->competency1->id,
            $this->get_scale_value_id('11'),
            'comment text'
        );
    }

    public function test_create_with_missing_capability_rating_other() {
        global $DB;
        $this->setUser($this->user1->id);

        $user_role_id = $DB->get_record('role', ['shortname' => 'user'])->id;
        unassign_capability('totara/competency:rate_other_competencies', $user_role_id);

        $this->expectException(required_capability_exception::class);
        rating_model::for_user_and_role($this->user2->id, manager::class)->create(
            $this->competency1->id,
            $this->get_scale_value_id('11'),
            'comment text'
        );
    }

    public function test_create_with_bad_role() {
        global $DB;
        $this->setUser($this->user1->id);

        $user_role_id = $DB->get_record('role', ['shortname' => 'user'])->id;
        assign_capability(
            'totara/competency:rate_other_competencies',
            CAP_ALLOW,
            $user_role_id,
            context_user::instance($this->user2->id)
        );

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessageMatches('|Invalid role specified: \'non-existent-role\'|');
        rating_model::for_user_and_role($this->user2->id, 'non-existent-role')->create(
            $this->competency1->id,
            $this->get_scale_value_id('11'),
            'comment text'
        );
    }

    public function test_create_with_missing_role() {
        global $DB;
        $this->setUser($this->user1->id);

        $user_role_id = $DB->get_record('role', ['shortname' => 'user'])->id;
        $manager_ja = job_assignment::create_default($this->user1->id);
        job_assignment::create(['userid' => $this->user2->id, 'managerjaid' => $manager_ja->id, 'idnumber' => 1]);
        assign_capability(
            'totara/competency:rate_other_competencies',
            CAP_ALLOW,
            $user_role_id,
            context_user::instance($this->user2->id)
        );

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessageMatches('|The following competencies: [\d]+ do not have the manager role enabled.|');
        rating_model::for_user_and_role($this->user2->id, manager::class)->create(
            $this->competency1->id,
            $this->get_scale_value_id('11'),
            'comment text'
        );
    }

    /**
     * Just test that role is validated for competency when calling create().
     * Validation method itself is tested more thoroughly below.
     */
    public function test_create_with_role_not_enabled_for_competency() {
        $this->setUser($this->user2->id);
        $this->set_as_rating_manager($this->user1->id, $this->user2->id);
        $this->generator->create_manual($this->competency1, [self_role::class]);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessageMatches('|The following competencies: [\d]+ do not have the manager role enabled|');
        rating_model::for_user_and_role($this->user1->id, manager::class)->create(
            $this->competency1->id,
            $this->get_scale_value_id('11'),
            'comment text'
        );
    }

    /**
     * Just test that scale values are validated when calling create().
     * Validation method itself is tested more thoroughly below.
     */
    public function test_create_with_invalid_scale_value() {
        $this->setUser($this->user2->id);
        $this->set_as_rating_manager($this->user1->id, $this->user2->id);
        $this->generator->create_manual($this->competency1, [manager::class]);

        // Try to set scale value from scale2.
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessageMatches('|Invalid scale value|');
        rating_model::for_user_and_role($this->user1->id, manager::class)->create(
            $this->competency1->id,
            $this->get_scale_value_id('22'),
            'comment text'
        );
    }

    public function test_create_successful() {
        global $DB;

        $this->setUser($this->user2->id);
        $this->set_as_rating_manager($this->user1->id, $this->user2->id);
        $this->generator->create_manual($this->competency1, [manager::class]);
        $scale_value_id = $this->get_scale_value_id('11');

        $this->assertFalse($DB->record_exists('pathway_manual_rating', ['competency_id' => $this->competency1->id]));
        rating_model::for_user_and_role($this->user1->id, manager::class)->create(
            $this->competency1->id,
            $scale_value_id,
            'comment text'
        );
        $record = $DB->get_record('pathway_manual_rating', [
            'competency_id' => $this->competency1->id,
            'user_id' => $this->user1->id,
            'scale_value_id' => $scale_value_id,
            'assigned_by' => $this->user2->id,
            'assigned_by_role' => manager::get_name(),
        ], '*', MUST_EXIST);
        $this->assertEquals('comment text', $record->comment);

        // Test null value for scale value as well.
        rating_model::for_user_and_role($this->user1->id, manager::class)->create(
            $this->competency1->id,
            null,
            'comment text 2'
        );
        $record = $DB->get_record('pathway_manual_rating', [
            'competency_id' => $this->competency1->id,
            'user_id' => $this->user1->id,
            'scale_value_id' => null,
            'assigned_by' => $this->user2->id,
            'assigned_by_role' => manager::get_name(),
        ], '*', MUST_EXIST);
        $this->assertEquals('comment text 2', $record->comment);
    }

    public function test_create_multiple() {
        global $DB;

        $this->setUser($this->user2->id);
        $this->set_as_rating_manager($this->user1->id, $this->user2->id);

        $this->generator->create_manual($this->competency1, [manager::class]);
        $this->generator->create_manual($this->competency2, [manager::class]);
        $scale_value_id_1 = $this->get_scale_value_id('11');
        $scale_value_id_2 = $this->get_scale_value_id('22');

        $ratings = [
            [
                'competency_id' => $this->competency1->id,
                'scale_value_id' => $scale_value_id_1,
                'comment' => 'Test comment 1',
            ],
            [
                'competency_id' => $this->competency2->id,
                'scale_value_id' => $scale_value_id_2,
                'comment' => 'Test comment 2',
            ],
        ];

        $this->assertFalse($DB->record_exists('totara_competency_aggregation_queue', []));

        rating_model::for_user_and_role($this->user1->id, manager::class)->create_multiple($ratings);

        $record = $DB->get_record('pathway_manual_rating', [
            'competency_id' => $this->competency1->id,
            'user_id' => $this->user1->id,
            'scale_value_id' => $scale_value_id_1,
            'assigned_by' => $this->user2->id,
            'assigned_by_role' => manager::get_name(),
        ], '*', MUST_EXIST);
        $this->assertEquals('Test comment 1', $record->comment);

        $record = $DB->get_record('pathway_manual_rating', [
            'competency_id' => $this->competency2->id,
            'user_id' => $this->user1->id,
            'scale_value_id' => $scale_value_id_2,
            'assigned_by' => $this->user2->id,
            'assigned_by_role' => manager::get_name(),
        ], '*', MUST_EXIST);
        $this->assertEquals('Test comment 2', $record->comment);

        // Check that records were inserted into aggregation queue.
        $this->assertCount(2, $DB->get_records('totara_competency_aggregation_queue'));
        $this->assertTrue($DB->record_exists(
            'totara_competency_aggregation_queue',
            ['user_id' => $this->user1->id, 'competency_id' => $this->competency1->id]
        ));
        $this->assertTrue($DB->record_exists(
            'totara_competency_aggregation_queue',
            ['user_id' => $this->user1->id, 'competency_id' => $this->competency2->id]
        ));
    }

    public function test_validate_role_for_competencies() {
        $this->generator->create_manual($this->competency1, [manager::class, appraiser::class]);
        $this->generator->create_manual($this->competency2, [manager::class, appraiser::class, self_role::class]);

        $this->assertTrue(
            rating_model::for_user_and_role($this->user1->id, manager::class)
                ->validate_role_for_competencies([$this->competency1->id, $this->competency2->id])
        );

        $this->assertTrue(
            rating_model::for_user_and_role($this->user1->id, appraiser::class)
                ->validate_role_for_competencies([$this->competency1->id, $this->competency2->id])
        );

        $this->assertTrue(
            rating_model::for_user_and_role($this->user1->id, self_role::class)
                ->validate_role_for_competencies([$this->competency2->id])
        );

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessageMatches('|The following competencies: [\d]+ do not have the self role enabled|');
        rating_model::for_user_and_role($this->user1->id, self_role::class)
            ->validate_role_for_competencies([$this->competency1->id, $this->competency2->id]);
    }

    public function test_validate_scale_values_for_competencies() {
        $this->assertTrue(rating_model::validate_scale_values_for_competencies([]));
        $this->assertTrue(rating_model::validate_scale_values_for_competencies([
            $this->competency1->id => $this->get_scale_value_id('11'),
            $this->competency2->id => $this->get_scale_value_id('21'),
        ]));
        $this->assertTrue(rating_model::validate_scale_values_for_competencies([
            $this->competency1->id => null,
            $this->competency2->id => $this->get_scale_value_id('21'),
        ]));

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessageMatches('|Invalid scale value|');
        rating_model::validate_scale_values_for_competencies([
            $this->competency1->id => $this->get_scale_value_id('11'),
            $this->competency2->id => $this->get_scale_value_id('12'),
        ]);
    }

    public function test_validate_scale_values_for_competencies_with_invalid_competency() {
        global $DB;
        $non_existent_competency_id = 99999;
        while ($DB->record_exists('comp', ['id' => $non_existent_competency_id])) {
            $non_existent_competency_id ++;
        }

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Non-existent or invisible competency id given.');
        rating_model::validate_scale_values_for_competencies([
            $this->competency1->id => $this->get_scale_value_id('11'),
            $non_existent_competency_id => $this->get_scale_value_id('21'),
        ]);
    }

    public function test_empty_comments_are_not_saved() {
        $this->setUser($this->user2->id);
        $this->set_as_rating_manager($this->user1->id, $this->user2->id);
        $this->generator->create_manual($this->competency1, [manager::class]);

        $scale_value = $this->competency1->scale->values->first();

        $rating_model = rating_model::for_user_and_role($this->user1->id, manager::class);

        // Create empty string comment, which should not be saved.
        $rating_model->create($this->competency1->id, $scale_value->id, '');
        /** @var rating_entity $rating */
        $rating = rating_entity::repository()
            ->order_by('id', 'desc')
            ->first();
        $this->assertNull($rating->comment);

        // Create another empty string comment, which should not be saved.
        $rating_model->create($this->competency1->id, $scale_value->id, '   ');
        $rating = rating_entity::repository()
            ->order_by('id', 'desc')
            ->first();
        $this->assertNull($rating->comment);

        // Create a comment which does have text, which should be saved.
        $rating_model->create($this->competency1->id, $scale_value->id, '  Hello!  ');
        $rating = rating_entity::repository()
            ->order_by('id', 'desc')
            ->first();
        $this->assertEquals('Hello!', $rating->comment);
    }

    public function test_can_see_raters_of_staff_members(): void {
        // Create the user who is rated
        $user = self::getDataGenerator()->create_user();
        self::setUser($this->user1->id);

        // Create a manager.
        $manager_ja = job_assignment::create_default($this->user2->id);

        // Create temp manager.
        $tempmanager_ja = job_assignment::create_default($this->user1->id);
        job_assignment::create_default(
            $user->id,
            [
                'managerjaid' => $manager_ja->id,
                'tempmanagerjaid' => $tempmanager_ja->id,
                'tempmanagerexpirydate' => time() + DAYSECS
            ]
        );

        // Rate the user by job manager.
        $this->generator->create_manual_rating($this->competency1, $user,  $this->user2->id, manager::class);
        self::assertTrue(rating_model::can_see_raters_of_staff_members($this->user1->id, $this->user2->id));
    }

}

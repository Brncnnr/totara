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
 * @package pathway_manual
 */

use pathway_manual\achievement_detail;
use pathway_manual\models\roles\appraiser;
use pathway_manual\models\roles\manager;
use pathway_manual\models\roles\self_role;
use pathway_manual\userdata\manual_rating_other;
use totara_competency\aggregation_task;
use totara_competency\aggregation_users_table;
use totara_competency\entity\pathway_achievement;
use totara_job\job_assignment;
use totara_userdata\userdata\target_user;

require_once(__DIR__ . '/pathway_manual_base_testcase.php');

/**
 * @group totara_competency
 */
class pathway_manual_achievement_detail_testcase extends pathway_manual_base_testcase {

    private $manual;

    protected function setUp(): void {
        parent::setUp();
        $this->manual = $this->generator->create_manual($this->competency1);
    }

    protected function tearDown(): void {
        parent::tearDown();
        $this->manual = null;
    }

    public function test_get_achieved_via_strings_empty() {
        $detail = new achievement_detail();
        $this->assertSame([], $detail->get_achieved_via_strings());
    }

    /**
     * Return the correct string for a user rating themselves
     */
    public function test_get_achieved_via_string_self() {
        $this->generator->create_manual_rating(
            $this->competency1,
            $this->user1->id,
            $this->user1->id,
            self_role::class,
            $this->scale1->values->first()
        );

        $this->validate_and_run_aggregation_task();

        $this->assert_achieved_via_string(
            $this->user1->id,
            get_string('activity_log_rating_by', 'pathway_manual', [
                'name' => $this->user1->fullname,
                'role' => self_role::get_display_name()
            ])
        );
    }

    /**
     * Return the correct string for a manager rating a user
     */
    public function test_get_achieved_via_string_manager() {
        $managerja = job_assignment::create_default($this->user2->id);
        job_assignment::create([
            'userid' => $this->user1->id,
            'idnumber' => 'a',
            'managerjaid' => $managerja->id
        ]);

        $this->generator->create_manual_rating(
            $this->competency1,
            $this->user1->id,
            $this->user2->id,
            manager::class,
            $this->scale1->values->first()
        );
        $this->validate_and_run_aggregation_task();

        $this->assert_achieved_via_string(
            $this->user1->id,
            get_string('activity_log_rating_by', 'pathway_manual', [
                'name' => $this->user2->fullname,
                'role' => manager::get_display_name()
            ])
        );
    }

    /**
     * Return the correct string for an appraiser rating a user
     */
    public function test_get_achieved_via_string_appraiser() {
        job_assignment::create([
            'userid' => $this->user1->id,
            'idnumber' => 'a',
            'appraiserid' => $this->user2->id,
        ]);
        $this->generator->create_manual_rating(
            $this->competency1,
            $this->user1->id,
            $this->user2->id,
            appraiser::class,
            $this->scale1->values->first()
        );
        $this->validate_and_run_aggregation_task();

        $this->assert_achieved_via_string(
            $this->user1->id,
            get_string('activity_log_rating_by', 'pathway_manual', [
                'name' => $this->user2->fullname,
                'role' => appraiser::get_display_name()
            ])
        );
    }

    /**
     * Return the correct string for when rater details have been purged
     */
    public function test_get_achieved_via_string_rater_purged() {
        job_assignment::create([
            'userid' => $this->user1->id,
            'idnumber' => 'a',
            'appraiserid' => $this->user2->id,
        ]);
        $this->generator->create_manual_rating(
            $this->competency1,
            $this->user1->id,
            $this->user2->id,
            appraiser::class,
            $this->scale1->values->first()
        );
        manual_rating_other::execute_purge(new target_user((object) $this->user2->to_array()), context_system::instance());

        $expected_string = get_string('activity_log_rating_by_removed', 'pathway_manual', 'an appraiser');
        $this->validate_and_run_aggregation_task();
        $this->assert_achieved_via_string($this->user1->id, $expected_string);
    }

    /**
     * Return the correct string for when the rater user has been deleted
     */
    public function test_get_achieved_via_string_rater_deleted() {
        $managerja = job_assignment::create_default($this->user2->id);
        job_assignment::create([
            'userid' => $this->user1->id,
            'idnumber' => 'a',
            'managerjaid' => $managerja->id
        ]);
        $this->generator->create_manual_rating(
            $this->competency1,
            $this->user1->id,
            $this->user2->id,
            manager::class,
            $this->scale1->values->first()
        );
        delete_user((object) $this->user2->to_array());
        $this->validate_and_run_aggregation_task();

        $expected_string = get_string('activity_log_rating_by_removed', 'pathway_manual', 'a manager');
        $this->assert_achieved_via_string($this->user1->id, $expected_string);
    }

    public function test_get_comment() {
        $detail = new achievement_detail();
        $this->assertSame('', $detail->get_comment());

        $managerja = job_assignment::create_default($this->user2->id);
        job_assignment::create([
            'userid' => $this->user1->id,
            'idnumber' => 'a',
            'managerjaid' => $managerja->id
        ]);

        $this->generator->create_manual_rating(
            $this->competency1,
            $this->user1->id,
            $this->user2->id,
            manager::class,
            $this->scale1->values->first(),
            'Test manual rating comment'
        );
        $this->validate_and_run_aggregation_task();

        $achievement = pathway_achievement::get_current($this->manual, $this->user1->id);
        $detail->set_related_info(json_decode($achievement->related_info, true));
        $this->assertSame('Test manual rating comment', $detail->get_comment());
    }

    private function assert_achieved_via_string(int $user, string $expected_string) {
        $achievement = pathway_achievement::get_current($this->manual, $user);
        $detail = new achievement_detail();
        $detail->set_related_info(json_decode($achievement->related_info, true));
        $this->assertSame([$expected_string], $detail->get_achieved_via_strings());
    }

    private function validate_and_run_aggregation_task() {
        global $DB;

        $table = new aggregation_users_table();
        $this->assertTrue($DB->record_exists($table->get_table_name(), [$table->get_process_key_column() => null]));

        (new aggregation_task($table, false))->execute();
    }

}

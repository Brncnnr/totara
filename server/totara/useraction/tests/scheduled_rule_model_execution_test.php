<?php
/**
 * This file is part of Totara Talent Experience Platform
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 */

use core\entity\user;
use core\orm\collection;
use core_phpunit\testcase;
use totara_useraction\action\delete_user;
use totara_useraction\entity\scheduled_rule_history;
use totara_useraction\filter\duration;
use totara_useraction\filter\status;
use totara_useraction\filter\applies_to;
use totara_useraction\model\scheduled_rule;
use totara_useraction\model\scheduled_rule\execution_data;
use totara_useraction\model\scheduled_rule_history as scheduled_rule_history_model;

/**
 * Integration test covering executing an active scheduled rule.
 *
 * @group totara_useraction
 */
class totara_useraction_scheduled_rule_model_execution_testcase extends testcase {

    private $active_user_ids;

    private $suspended_user_ids;

    private $cohorts;

    /**
     * @return void
     */
    public function test_delete_user_on_all_users_suspended_5_years_ago(): void {
        $duration_filter = duration::create_from_input([
            'source' => duration::ENUM_SUSPENDED,
            'unit' => duration::ENUM_UNIT_YEARS,
            'value' => 3,
        ]);

        $scheduled_rule = scheduled_rule::create(
            "Test rule",
            totara_useraction\action\delete_user::class,
            new status(status::STATUS_SUSPENDED),
            $duration_filter,
            new applies_to(true, collection::new([])),
            null,
            null,
            true
        );

        // Execute the rule 3 years in the future.
        $timestamp = time() + duration::unit_to_seconds(5, duration::UNIT_YEARS);
        $execution_data = execution_data::instance(['timestamp' => $timestamp]);
        $scheduled_rule->execute($execution_data);

        // Assert action
        $this->assert_users_deleted($scheduled_rule, $timestamp);
    }

    /**
     * @return void
     */
    public function test_delete_user_on_users_in_audiences_suspended_3_days_ago(): void {
        $duration_filter = duration::create_from_input([
            'source' => duration::ENUM_SUSPENDED,
            'unit' => duration::ENUM_UNIT_DAYS,
            'value' => 3,
        ]);

        $scheduled_rule = scheduled_rule::create(
            "Test rule",
            totara_useraction\action\delete_user::class,
            new status(status::STATUS_SUSPENDED),
            $duration_filter,
            new applies_to(false, collection::new($this->cohorts)),
            null,
            null,
            true
        );

        // Execute the rule 3 days in the future.
        $timestamp = time() + duration::unit_to_seconds(3, duration::UNIT_DAYS);
        $execution_data = execution_data::instance(['timestamp' => $timestamp]);
        $scheduled_rule->execute($execution_data);

        // Assert action
        $this->assert_users_deleted($scheduled_rule, $timestamp);
    }

    /**
     * @return void
     */
    public function test_delete_user_on_rule_without_audiences(): void {
        $duration_filter = duration::create_from_input([
            'source' => duration::ENUM_SUSPENDED,
            'unit' => duration::ENUM_UNIT_YEARS,
            'value' => 1,
        ]);

        $scheduled_rule = scheduled_rule::create(
            "Test rule",
            totara_useraction\action\delete_user::class,
            new status(status::STATUS_SUSPENDED),
            $duration_filter,
            new applies_to(false, collection::new($this->cohorts)),
            null,
            null,
            true
        );

        // delete audiences
        foreach ($this->cohorts as $cohort) {
            cohort_delete_cohort($cohort);
        }

        // Execute the rule 1 year in the future.
        $timestamp = time() + duration::unit_to_seconds(1, duration::UNIT_YEARS);
        $execution_data = execution_data::instance(['timestamp' => $timestamp]);
        $scheduled_rule->execute($execution_data);

        // assert no users deleted.
        $deleted_users = user::repository()->where('deleted', 1)->get()->pluck('id');
        $this->assertEmpty($deleted_users);

        // Assert no history records created.
        $history_records = scheduled_rule_history::repository()->get()->to_array();
        $this->assertEmpty($history_records);
    }

    /**
     * @param scheduled_rule $scheduled_rule
     * @param int $timestamp
     *
     * @return void
     */
    private function assert_users_deleted(scheduled_rule $scheduled_rule, int $timestamp): void {
        // assert users deleted.
        $deleted_users = user::repository()->where('deleted', 1)->get()->pluck('id');
        $this->assertEqualsCanonicalizing($this->suspended_user_ids, $deleted_users);

        // assert history records
        /** @var scheduled_rule_history_model[] $history_records */
        $history_records = scheduled_rule_history::repository()->get()->map_to(scheduled_rule_history_model::class);
        $this->assertCount(count($this->suspended_user_ids), $history_records);
        foreach ($history_records as $history_record) {
            $this->assertEquals($scheduled_rule->id, $history_record->scheduled_rule_id);
            $this->assertContains($history_record->user_id, $this->suspended_user_ids);
            $this->assertNotContains($history_record->user_id, $this->active_user_ids);
            $this->assertInstanceOf(delete_user::class, $history_record->action);
            $this->assertTrue($history_record->success);

            // Created timestamp is auto-generated.
            $this->assertNotEmpty($history_record->created);
        }
    }

    /**
     * @return void
     */
    protected function setUp(): void {
        // Active users.
        $active_user_ids = [];
        for ($i = 0; $i <= 6; $i++) {
            $active_user_ids[] = $this->getDataGenerator()->create_user()->id;
        }
        $this->active_user_ids = $active_user_ids;

        // Suspended users.
        $suspended_user_ids = [];

        for ($i = 0; $i <= 6; $i++) {
            $user_id = $this->getDataGenerator()->create_user()->id;
            $suspended_user_ids[] = $user_id;
            user_suspend_user($user_id);
        }
        $this->suspended_user_ids = $suspended_user_ids;

        // Create audience and add users into the audience.
        $cohort_1 = $this->getDataGenerator()->create_cohort();
        $cohort_2 = $this->getDataGenerator()->create_cohort();
        $this->cohorts = [$cohort_1, $cohort_2];

        foreach ($active_user_ids as $user_id) {
            cohort_add_member($cohort_1->id, $user_id);
            cohort_add_member($cohort_2->id, $user_id);
        }

        foreach ($suspended_user_ids as $user_id) {
            cohort_add_member($cohort_1->id, $user_id);
            cohort_add_member($cohort_2->id, $user_id);
        }
    }

    /**
     * @return void
     */
    protected function tearDown(): void {
        $this->suspended_user_ids = $this->active_user_ids = $this->cohorts = null;
        parent::tearDown();
    }
}


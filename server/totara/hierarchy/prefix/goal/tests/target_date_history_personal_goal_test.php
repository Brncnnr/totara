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
 * @package hierarchy_goal
 */

use core_phpunit\testcase;
use hierarchy_goal\entity\goal_item_target_date_history;
use hierarchy_goal\event\personal_created;
use hierarchy_goal\event\personal_updated;
use totara_hierarchy\testing\generator as hierarchy_generator;

global $CFG;
require_once($CFG->dirroot . '/totara/hierarchy/prefix/goal/lib.php');

/**
 * @group hierarchy_goal
 * @group totara_hierarchy
 * @group totara_goal
 */
class hierarchy_goal_target_date_history_personal_goal_testcase extends testcase {

    public function target_date_data_provider(): array {
        $future_date = time() + DAYSECS;
        return [
            ['Make sure it works with an actual date' => $future_date],
            ['Make sure it works when target date is set to null' => null],
            ['Make sure it works when target date is set to zero' => 0],
        ];
    }

    /**
     * @dataProvider target_date_data_provider
     * @param int|null $target_date
     */
    public function test_history_is_recorded_on_personal_created_event(?int $target_date): void {
        $generator = self::getDataGenerator();
        $user = $generator->create_user();
        self::setUser($user);

        // Create goal without triggering event.
        $goal = $this->create_personal_goal($user->id, $target_date);
        self::assertEquals(0, goal_item_target_date_history::repository()->count());

        $now = time();
        personal_created::create_from_instance($goal)->trigger();

        /** @var goal_item_target_date_history $target_date_history */
        $target_date_history = goal_item_target_date_history::repository()->one(true);
        self::assertEquals(goal::SCOPE_PERSONAL, $target_date_history->scope);
        self::assertEquals($goal->id, $target_date_history->itemid);
        self::assertEquals(is_null($target_date) ? 0 : $target_date, $target_date_history->targetdate);
        self::assertEquals($user->id, $target_date_history->usermodified);
        self::assertGreaterThanOrEqual($now, $target_date_history->timemodified);
    }

    public function test_history_is_recorded_on_personal_updated_event(): void {
        $generator = self::getDataGenerator();
        $user = $generator->create_user();
        self::setUser($user);

        $now = time();
        $future_target_date = $now + DAYSECS;

        // Create goal without triggering event.
        $goal = $this->create_personal_goal($user->id, $future_target_date);
        self::assertEquals(0, goal_item_target_date_history::repository()->count());

        // Trigger event.
        personal_updated::create_from_instance($goal)->trigger();
        self::assertEquals(1, goal_item_target_date_history::repository()->count());
        $this->assert_history_order($goal->id, [$future_target_date]);
    }

    public function target_date_combinations_data_provider(): array {
        $now = time();
        $past_date = $now - DAYSECS;
        $future_date = $now + DAYSECS;

        return [
            'Date is not recorded when it does not change' => [
                [$future_date, $future_date, 0, 0, null, null, $past_date, $past_date],
                [$future_date, 0, $past_date],
            ],
            'Null is NOT different from zero' => [
                [0, null, 0, null],
                [0],
            ],
        ];
    }

    /**
     * @dataProvider target_date_combinations_data_provider
     * @param array $target_dates
     * @param array $expected_records
     */
    public function test_target_date_combinations(array $target_dates, array $expected_records): void {
        $generator = self::getDataGenerator();
        $user = $generator->create_user();
        self::setUser($user);

        $now = time();
        $future_target_date = $now + DAYSECS;

        // Create goal without triggering event.
        $goal = $this->create_personal_goal($user->id, $future_target_date);
        self::assertEquals(0, goal_item_target_date_history::repository()->count());

        foreach ($target_dates as $target_date) {
            $goal->targetdate = $target_date;
            personal_updated::create_from_instance($goal)->trigger();
        }

        self::assertEquals(count($expected_records), goal_item_target_date_history::repository()->count());
        $this->assert_history_order($goal->id, $expected_records);
    }

    /**
     * @param int $goal_id
     * @param array $expected_target_dates
     * @throws coding_exception
     */
    private function assert_history_order(int $goal_id, array $expected_target_dates): void {
        /** @var goal_item_target_date_history[] $entities */
        $target_dates = goal_item_target_date_history::repository()
            ->where('scope', goal::SCOPE_PERSONAL)
            ->where('itemid', $goal_id)
            ->order_by('id')
            ->get()
            ->to_array();

        self::assertEquals($expected_target_dates, array_column($target_dates, 'targetdate'));
    }

    /**
     * @param int $userid
     * @param int|null $target_date
     * @return stdClass
     */
    private function create_personal_goal(int $userid, ?int $target_date): stdClass {
        $generator = self::getDataGenerator();
        /** @var hierarchy_generator $hierarchy_generator */
        $hierarchy_generator = $generator->get_plugin_generator('totara_hierarchy');
        return $hierarchy_generator->create_personal_goal(
            $userid,
            ['name' => 'goal1', 'targetdate' => $target_date]
        );
    }
}
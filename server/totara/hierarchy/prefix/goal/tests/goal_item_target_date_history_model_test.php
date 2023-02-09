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
use hierarchy_goal\entity\goal_item_target_date_history as goal_item_target_date_history_entity;
use hierarchy_goal\models\goal_item_target_date_history;
use totara_hierarchy\testing\generator as hierarchy_generator;

global $CFG;
require_once($CFG->dirroot . '/totara/hierarchy/prefix/goal/lib.php');

/**
 * @group hierarchy_goal
 */
class hierarchy_goal_goal_item_target_date_history_model_testcase extends testcase {

    public function test_create_with_invalid_scope(): void {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Invalid goal scope: -1');
        goal_item_target_date_history::create(-1, 1, time());
    }

    public function test_create_with_invalid_item_id_company(): void {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Invalid goal item id: -1');
        goal_item_target_date_history::create(goal::SCOPE_COMPANY, -1, time());
    }

    public function test_create_with_invalid_item_id_personal(): void {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Invalid goal item id: -1');
        goal_item_target_date_history::create(goal::SCOPE_PERSONAL, -1, time());
    }

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
    public function test_create_successful_for_personal_goal(?int $target_date): void {
        global $DB;

        $user = self::getDataGenerator()->create_user();
        self::setUser($user);
        $now = time();
        $goal_id = $DB->insert_record('goal_personal',
            (object)[
                'userid' => $user->id,
                'assigntype' => GOAL_ASSIGNMENT_ADMIN,
                'timecreated' => $now,
                'timemodified' => $now,
                'usercreated' => $user->id,
                'usermodified' => $user->id,
                'scalevalueid' => null
            ]
        );

        self::assertEquals(0, goal_item_target_date_history_entity::repository()->count());
        $goal_item_target_date_history = goal_item_target_date_history::create(goal::SCOPE_PERSONAL, $goal_id, $target_date);

        self::assertEquals(1, goal_item_target_date_history_entity::repository()->count());
        self::assertEquals(goal::SCOPE_PERSONAL, $goal_item_target_date_history->scope);
        self::assertEquals($goal_id, $goal_item_target_date_history->itemid);
        self::assertEquals($target_date, $goal_item_target_date_history->targetdate);
        self::assertEquals($user->id, $goal_item_target_date_history->usermodified);
        self::assertGreaterThanOrEqual($now, $goal_item_target_date_history->timemodified);
    }

    /**
     * @dataProvider target_date_data_provider
     * @param int|null $target_date
     */
    public function test_create_successful_for_company_goal(?int $target_date): void {
        self::setAdminUser();
        $generator = self::getDataGenerator();
        /** @var hierarchy_generator $hierarchy_generator */
        $hierarchy_generator = $generator->get_plugin_generator('totara_hierarchy');
        $framework = $hierarchy_generator->create_goal_frame(['name' => 'frame1']);

        // Create the goal without triggering events.
        $now = time();
        $goal = $hierarchy_generator->create_goal(['fullname' => 'goal1', 'frameworkid' => $framework->id, 'targetdate' => $target_date], false);

        self::assertEquals(0, goal_item_target_date_history_entity::repository()->count());
        $goal_item_target_date_history = goal_item_target_date_history::create(goal::SCOPE_COMPANY, $goal->id, $target_date);

        self::assertEquals(1, goal_item_target_date_history_entity::repository()->count());
        self::assertEquals(goal::SCOPE_COMPANY, $goal_item_target_date_history->scope);
        self::assertEquals($goal->id, $goal_item_target_date_history->itemid);
        self::assertEquals($target_date, $goal_item_target_date_history->targetdate);
        self::assertEquals(get_admin()->id, $goal_item_target_date_history->usermodified);
        self::assertGreaterThanOrEqual($now, $goal_item_target_date_history->timemodified);
    }
}
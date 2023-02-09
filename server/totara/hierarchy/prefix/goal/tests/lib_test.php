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
use totara_hierarchy\testing\generator as hierarchy_generator;

global $CFG;
require_once($CFG->dirroot . '/totara/hierarchy/prefix/goal/lib.php');

/**
 * @group hierarchy_goal
 */
class hierarchy_goal_lib_testcase extends testcase {

    public function test_delete_hierarchy_item_deletes_target_date_history(): void {
        self::setAdminUser();

        $generator = self::getDataGenerator();
        /** @var hierarchy_generator $hierarchy_generator */
        $hierarchy_generator = $generator->get_plugin_generator('totara_hierarchy');
        $framework = $hierarchy_generator->create_goal_frame(['name' => 'frame1']);
        $goal1 = $hierarchy_generator->create_goal(
            ['fullname' => 'goal1', 'frameworkid' => $framework->id, 'targetdate' => time()]
        );
        $goal2 = $hierarchy_generator->create_goal(
            ['fullname' => 'goal2', 'frameworkid' => $framework->id, 'targetdate' => time()]
        );

        self::assertEquals(2, goal_item_target_date_history::repository()->count());
        self::assertTrue(goal_item_target_date_history::repository()
            ->where('scope', goal::SCOPE_COMPANY)
            ->where('itemid', $goal1->id)
            ->exists()
        );
        self::assertTrue(goal_item_target_date_history::repository()
            ->where('scope', goal::SCOPE_COMPANY)
            ->where('itemid', $goal2->id)
            ->exists()
        );

        $hierarchy = new goal();
        $hierarchy->delete_hierarchy_item($goal1->id);

        self::assertEquals(1, goal_item_target_date_history::repository()->count());
        self::assertTrue(goal_item_target_date_history::repository()
            ->where('scope', goal::SCOPE_COMPANY)
            ->where('itemid', $goal2->id)
            ->exists()
        );

    }
}
<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @author Samantha Jayasinghe <samantha.jayasinghe@totaralearning.com>
 * @package mod_perform
 */

/**
 * @group perform
 */

use core\orm\query\builder;
use mod_perform\data_providers\activity\activity;
use mod_perform\models\activity\activity as activity_model;

class mod_perform_activity_data_provider_testcase extends advanced_testcase {

    public function test_fetch() {
        $this->setAdminUser();

        $data = $this->create_test_data();

        $data_provider = new activity();
        $performs = $data_provider->fetch()->get()->all();

        $this->assertCount(2, $performs);
        $this->assertEqualsCanonicalizing(
            [$data->activity1->name, $data->activity2->name],
            [$performs[0]->name, $performs[1]->name]
        );
    }

    public function test_fetch_excludes_hidden_courses() {
        global $CFG;
        require_once($CFG->dirroot . '/course/lib.php');

        $user = $this->getDataGenerator()->create_user();

        $this->setUser($user);

        $data = $this->create_test_data();

        /** @var activity_model $activity2 */
        $activity2 = $data->activity2;

        builder::table('course')
            ->where('id', $activity2->course)
            ->update([
                'visible' => 0,
                'visibleold' => 0
            ]);

        $data_provider = new activity();
        $performs = $data_provider->fetch()->get()->all();

        $this->assertCount(1, $performs);
        $this->assertEqualsCanonicalizing(
            [$data->activity1->name],
            [$performs[0]->name]
        );
    }

    public function test_fetch_filter_capabilities() {
        $data_generator = $this->getDataGenerator();
        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = $data_generator->get_plugin_generator('mod_perform');
        $data_provider = new activity();

        $user1 = $data_generator->create_user();
        $user2 = $data_generator->create_user();

        $this->setUser($user1);
        $activity_user1_1 = $perform_generator->create_activity_in_container(['activity_name' => 'User1 One']);
        $activity_user1_2 = $perform_generator->create_activity_in_container(['activity_name' => 'User1 Two']);

        $this->setUser($user2);
        $activity_user2_1 = $perform_generator->create_activity_in_container(['activity_name' => 'User2 One']);

        $activities = $data_provider->fetch()->get()->all();
        $this->assertCount(1, $activities);
        $this->assertEquals('User2 One', $activities[0]->name);

        $this->setUser($user1);
        $activities = $data_provider->fetch()->get()->all();
        $this->assertCount(2, $activities);
        $this->assertEqualsCanonicalizing(
            ['User1 One', 'User1 Two'],
            [$activities[0]->name, $activities[1]->name]
        );

        // Make sure that assigning the view participation reperting capability will result in the activity being returned
        $role = builder::table('role')->where('shortname', 'user')->one();
        assign_capability('mod/perform:view_participation_reporting', CAP_ALLOW, $role->id, $activity_user1_2->get_context_id());

        $this->setUser($user2);

        $activities = $data_provider->fetch()->get()->all();
        $this->assertCount(2, $activities);
        $this->assertEqualsCanonicalizing(
            ['User2 One', 'User1 Two'],
            [$activities[0]->name, $activities[1]->name]
        );
    }

    public function test_filter_by_id() {
        $this->setAdminUser();

        $data = $this->create_test_data();

        $activities = (new activity())->add_filters(['id' => $data->activity1->id])->fetch()->get()->all();

        $this->assertCount(1, $activities);
        $this->assertEquals($data->activity1->id, $activities[0]->id);

        $activities = (new activity())->add_filters(['id' => - 1])->fetch()->get()->all();
        $this->assertCount(0, $activities);
    }

    private function create_test_data(): stdClass {
        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = \mod_perform\testing\generator::instance();

        $data = new stdClass();

        $data->activity1 = $perform_generator->create_activity_in_container(['activity_name' => 'Mid year performance']);
        $data->activity2 = $perform_generator->create_activity_in_container(['activity_name' => 'End year performance']);

        return $data;
    }
}
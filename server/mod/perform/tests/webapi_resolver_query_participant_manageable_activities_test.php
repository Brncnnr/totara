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
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package mod_perform
 */

use core\entity\user;
use core\orm\query\builder;
use mod_perform\models\activity\activity;
use totara_core\advanced_feature;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @coversDefaultClass \mod_perform\webapi\resolver\query\participant_manageable_activities
 *
 * @group perform
 */
class mod_perform_webapi_resolver_participant_manageable_activities_testcase extends advanced_testcase {
    private const QUERY = 'mod_perform_participant_manageable_activities';

    use webapi_phpunit_helper;

    public function test_ajax_query(): void {
        self::setAdminUser();

        $names = ['Mid year performance', 'End year performance'];
        $this->create_test_data($names);

        $result = $this->parsed_graphql_operation(self::QUERY, []);
        $this->assert_webapi_operation_successful($result);

        $activities = $this->get_webapi_operation_data($result);
        $names[] = 'hidden-activity'; // Admin can see "hidden" activities.
        $this->assertCount(count($names), $activities, 'wrong count');
    }

    public function test_failed_ajax_query(): void {
        $feature = 'performance_activities';
        advanced_feature::disable($feature);
        $result = $this->parsed_graphql_operation(self::QUERY, []);
        $this->assert_webapi_operation_failed($result, 'Feature performance_activities is not available.');
        advanced_feature::enable($feature);
    }

    private function create_test_data(array $activity_names): void {
        $user = user::logged_in();

        self::setAdminUser();

        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = \mod_perform\testing\generator::instance();

        $activity_names[] = 'hidden-activity';

        foreach ($activity_names as $name) {
            $perform_generator->create_activity_in_container(['activity_name' => $name]);
        }

        /** @var activity $hidden_activity */
        $hidden_activity = mod_perform\entity\activity\activity::repository()
            ->where('name', 'hidden-activity')
            ->order_by('id')
            ->first(true);

        // Set "hidden-activity" to be hidden, as all queries under test should
        // be applying filter_by_visible  filter.
        builder::table('course')
            ->where('id', $hidden_activity->course)
            ->update([
                'visible' => 0,
                'visibleold' => 0
            ]);

        if ($user) {
            self::setUser($user->id);
        }
    }
}

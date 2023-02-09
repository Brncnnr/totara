<?php
/**
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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package mod_perform
 * @category test
 */

use mod_perform\models\activity\activity_setting;
use totara_core\advanced_feature;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @see \mod_perform\webapi\resolver\mutation\update_activity_workflow_settings::resolve()
 * @group perform
 */
class mod_perform_webapi_resolver_mutation_update_activity_workflow_settings_testcase extends advanced_testcase {
    private const MUTATION = 'mod_perform_update_activity_workflow_settings';

    use webapi_phpunit_helper;

    public function test_change_settings(): void {
        self::setAdminUser();

        $perform_generator = \mod_perform\testing\generator::instance();
        $activity = $perform_generator->create_activity_in_container();

        $settings = $activity->settings;
        $this->assertEquals(0, $settings->get()->count(), 'wrong settings count');
        $this->assertFalse(
            (bool)$settings->lookup(activity_setting::CLOSE_ON_COMPLETION),
            'wrong close on completion setting value'
        );
        $this->assertFalse(
            (bool)$settings->lookup(activity_setting::CLOSE_ON_DUE_DATE),
            'wrong close on due date setting value'
        );

        $result = $this->resolve_graphql_mutation(self::MUTATION, [
            'input' => [
                'activity_id' => $activity->id,
                activity_setting::CLOSE_ON_COMPLETION => false,
                activity_setting::CLOSE_ON_DUE_DATE => false,
            ],
        ])->settings;

        $this->assertEquals(2, $result->get()->count(), 'wrong settings count');
        $this->assertFalse(
            (bool)$result->lookup(activity_setting::CLOSE_ON_COMPLETION, true),
            'wrong close on completion setting value'
        );
        $this->assertFalse(
            (bool)$result->lookup(activity_setting::CLOSE_ON_DUE_DATE, true),
            'wrong close on due date setting value'
        );

        $result = $this->resolve_graphql_mutation(self::MUTATION, [
            'input' => [
                'activity_id' => $activity->id,
                activity_setting::CLOSE_ON_COMPLETION => false,
                activity_setting::CLOSE_ON_DUE_DATE => true,
            ],
        ])->settings;
        $this->assertEquals(2, $result->get()->count(), 'wrong settings count');
        $this->assertFalse(
            (bool)$result->lookup(activity_setting::CLOSE_ON_COMPLETION, false),
            'wrong close on completion setting value'
        );
        $this->assertTrue(
            (bool)$result->lookup(activity_setting::CLOSE_ON_DUE_DATE, false),
            'wrong close on due date setting value'
        );

        $result = $this->resolve_graphql_mutation(self::MUTATION, [
            'input' => [
                'activity_id' => $activity->id,
                activity_setting::CLOSE_ON_COMPLETION => true,
                activity_setting::CLOSE_ON_DUE_DATE => false,
            ],
        ])->settings;
        $this->assertEquals(2, $result->get()->count(), 'wrong settings count');
        $this->assertTrue(
            (bool)$result->lookup(activity_setting::CLOSE_ON_COMPLETION, false),
            'wrong close on completion setting value'
        );
        $this->assertFalse(
            (bool)$result->lookup(activity_setting::CLOSE_ON_DUE_DATE, false),
            'wrong close on due date setting value'
        );
    }

    public function test_successful_ajax_call(): void {
        self::setAdminUser();

        $perform_generator = \mod_perform\testing\generator::instance();
        $activity = $perform_generator->create_activity_in_container();

        $this->assertEquals(0, $activity->settings->get()->count(), 'wrong settings count');

        $result = $this->parsed_graphql_operation(self::MUTATION, [
            'input' => [
                'activity_id' => $activity->id,
                activity_setting::CLOSE_ON_COMPLETION => true,
                activity_setting::CLOSE_ON_DUE_DATE => true,
            ],
        ]);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);
        $settings = $result['settings'];
        $this->assertEquals(
            '1', // true
            $settings[activity_setting::CLOSE_ON_COMPLETION],
            'wrong close on completion setting value'
        );
        $this->assertEquals(
            '1', // true
            $settings[activity_setting::CLOSE_ON_DUE_DATE],
            'wrong close on due date setting value'
        );
    }

    public function test_failed_ajax_call(): void {
        self::setAdminUser();

        $perform_generator = \mod_perform\testing\generator::instance();
        $activity = $perform_generator->create_activity_in_container();

        $args = [
            'input' => [
                'activity_id' => $activity->id,
                activity_setting::CLOSE_ON_COMPLETION => true,
                activity_setting::CLOSE_ON_DUE_DATE => true,
            ],
        ];

        $feature = 'performance_activities';
        advanced_feature::disable($feature);
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed($result, 'Feature performance_activities is not available.');
        advanced_feature::enable($feature);

        $result = $this->parsed_graphql_operation(self::MUTATION, []);
        $this->assert_webapi_operation_failed($result, 'Variable "$input" of required type "workflow_settings!" was not provided.');

        $activity_id = 999;
        $args['input']['activity_id'] = $activity_id;
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed($result, "Invalid activity");

        self::setGuestUser();
        $args['input']['activity_id'] = $activity->id;
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed($result, 'Invalid activity');
    }
}
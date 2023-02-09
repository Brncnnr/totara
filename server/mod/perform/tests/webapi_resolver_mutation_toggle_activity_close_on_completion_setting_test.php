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
 * @coversDefaultClass \mod_perform\webapi\resolver\mutation\toggle_activity_close_on_completion_setting
 *
 * @group perform
 */
class mod_perform_webapi_mutation_toggle_activity_close_on_completion_setting_testcase extends advanced_testcase {
    private const MUTATION = 'mod_perform_toggle_activity_close_on_completion_setting';

    use webapi_phpunit_helper;

    /**
     * @covers ::resolve
     */
    public function test_change_close_on_completion(): void {
        [$activity, $args] = $this->setup_env(false);

        $settings = $activity->settings;
        $this->assertEquals(0, $settings->get()->count(), 'wrong settings count');
        $this->assertFalse(
            (bool)$settings->lookup(activity_setting::CLOSE_ON_COMPLETION),
            'wrong setting value'
        );

        $result = $this->resolve_graphql_mutation(self::MUTATION, $args)->settings;
        $this->assertEquals(1, $result->get()->count(), 'wrong settings count');
        $this->assertFalse(
            (bool)$result->lookup(activity_setting::CLOSE_ON_COMPLETION, true),
            'wrong setting value'
        );

        $args['input']['setting'] = true;
        $result = $this->resolve_graphql_mutation(self::MUTATION, $args)->settings;
        $this->assertEquals(1, $result->get()->count(), 'wrong settings count');
        $this->assertTrue(
            (bool)$result->lookup(activity_setting::CLOSE_ON_COMPLETION, false),
            'wrong setting value'
        );
    }

    /**
     * @covers ::resolve
     */
    public function test_successful_ajax_call(): void {
        [$activity, $args] = $this->setup_env(true);
        $this->assertEquals(0, $activity->settings->get()->count(), 'wrong settings count');

        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);
        $settings = $result['settings'];
        $this->assertEquals(
            (string)true,
            (string)$settings['close_on_completion'],
            'wrong setting value'
        );
    }

    /**
     * @covers ::resolve
     */
    public function test_failed_ajax_call(): void {
        [$activity, $args] = $this->setup_env();

        $feature = 'performance_activities';
        advanced_feature::disable($feature);
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed($result, 'Feature performance_activities is not available.');
        advanced_feature::enable($feature);

        $result = $this->parsed_graphql_operation(self::MUTATION, []);
        $this->assert_webapi_operation_failed($result, 'Variable "$input" of required type "toggle_activity_setting!" was not provided.');

        $activity_id = 999;
        $args['input']['activity_id'] = $activity_id;
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed($result, "Invalid activity");

        self::setGuestUser();
        $args['input']['activity_id'] = $activity->id;
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed($result, 'Invalid activity');
    }

    /**
     * Generates test data.
     *
     * @param bool $setting the new multisection setting.
     *
     * @return array an (activity, graphql arguments, graphql context) tuple.
     */
    private function setup_env(bool $setting = false): array {
        $this->setAdminUser();

        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = \mod_perform\testing\generator::instance();
        $activity = $perform_generator->create_activity_in_container();

        $activity_details = [
            'activity_id' => $activity->id,
            'setting' => $setting
        ];
        $args = ['input' => $activity_details];

        return [$activity, $args];
    }
}

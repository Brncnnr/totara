<?php
/**
 * This file is part of Totara Perform
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package mod_perform
 * @category test
 */

use core_phpunit\testcase;
use mod_perform\models\activity\subject_instance;
use mod_perform\state\subject_instance\closed as subject_availability_close;
use mod_perform\state\subject_instance\open as subject_availability_open;
use mod_perform\state\participant_instance\open as participant_availability_open;
use mod_perform\state\participant_instance\closed as participant_availability_close;
use mod_perform\testing\generator;
use totara_core\advanced_feature;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @coversDefaultClass \mod_perform\webapi\resolver\mutation\close_activity_subject_instances
 *
 * @group perform
 */
class mod_perform_webapi_mutation_close_activity_subject_instances_testcase extends testcase {
    private const MUTATION = 'mod_perform_close_activity_subject_instances';

    use webapi_phpunit_helper;

    public function test_close_subject_instances(): void {
        [$args, $sids] = $this->setup_env();

        [$si_open, $si_close, $pi_open, $pi_close] = $this->availability_statuses();
        $this->assert_availability($sids, $si_open, $pi_open);

        $result = $this->resolve_graphql_mutation(self::MUTATION, $args);
        $this->assertTrue($result);

        // Closing only happens when the adhoc task runs.
        $this->assert_availability($sids, $si_open, $pi_open);
        $this->executeAdhocTasks();
        $this->assert_availability($sids, $si_close, $pi_close);
    }

    public function test_successful_ajax_call(): void {
        [$args, $sids] = $this->setup_env();

        [$si_open, $si_close, $pi_open, $pi_close] = $this->availability_statuses();
        $this->assert_availability($sids, $si_open, $pi_open);

        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_successful($result);

        // Closing only happens when the adhoc task runs.
        $this->assert_availability($sids, $si_open, $pi_open);
        $this->executeAdhocTasks();
        $this->assert_availability($sids, $si_close, $pi_close);
    }

    /**
     * @covers ::resolve
     */
    public function test_failed_ajax_call(): void {
        [$args, ] = $this->setup_env();

        $feature = 'performance_activities';
        advanced_feature::disable($feature);
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed(
            $result, 'Feature performance_activities is not available.'
        );
        advanced_feature::enable($feature);

        $result = $this->parsed_graphql_operation(self::MUTATION, []);
        $this->assert_webapi_operation_failed(
            $result,
            'Variable "$input" of required type "mod_perform_close_activity_subject_instances_input!" was not provided.'
        );

        self::setGuestUser();
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed(
            $result, 'You do not have permission to manage participation'
        );

        $this->setAdminUser();
        $args['input']['activity_id'] = 0;
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed(
            $result, 'Invalid parameter value detected (invalid activity id)'
        );

        $activity_id = 999;
        $args['input']['activity_id'] = $activity_id;
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed($result, 'Invalid activity');
    }

    private function setup_env(): array {
        $this->setAdminUser();

        $perform_generator = generator::instance();
        $activity_id = $perform_generator->create_activity_in_container()->id;
        $args = [
            'input' => ['activity_id' => $activity_id]
        ];

        $core_generator = $this->getDataGenerator();
        $si_data = [
            'activity_id' => $activity_id,
            'other_participant_id' => $core_generator->create_user()->id,
            'third_participant_username' => $core_generator->create_user()->username,
            'subject_is_participating' => true,
            'include_questions' => false
        ];

        $sids = [];
        for ($i = 0; $i < 5; $i++) {
            $data = array_merge(
                ['subject_user_id' => $core_generator->create_user()->id],
                $si_data
            );

            $sids[] = $perform_generator->create_subject_instance($data)->id;
        }

        return [$args, $sids];
    }

    private function assert_availability(
        array $sids,
        string $expected_si_status,
        string $expected_pi_status
    ): void {
        foreach ($sids as $sid) {
            $si = subject_instance::load_by_id($sid);
            $this->assertEquals($expected_si_status, $si->availability_status);

            foreach ($si->participant_instances as $pi) {
                $this->assertEquals($expected_pi_status, $pi->availability_status);
            }
        }
    }

    private function availability_statuses(): array {
        return [
            subject_availability_open::get_name(),
            subject_availability_close::get_name(),
            participant_availability_open::get_name(),
            participant_availability_close::get_name()
        ];
    }




}

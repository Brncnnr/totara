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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package mod_perform
 */

use mod_perform\constants;
use mod_perform\entity\activity\participant_instance as participant_instance_entity;
use mod_perform\models\activity\activity;
use mod_perform\models\activity\participant_source;
use mod_perform\models\activity\subject_instance;
use totara_core\advanced_feature;
use totara_webapi\phpunit\webapi_phpunit_helper;

require_once(__DIR__ . '/subject_instance_testcase.php');

/**
 * @coversDefaultClass \mod_perform\webapi\resolver\query\subject_instance_for_participant
 *
 * @group perform
 */
class mod_perform_webapi_resolver_query_subject_instance_for_external_participant_testcase extends advanced_testcase {
    private const QUERY = 'mod_perform_subject_instance_for_external_participant';
    private const QUERY_NOSESSION = self::QUERY.'_nosession';

    use webapi_phpunit_helper;

    public function test_resolve_subject_instance() {
        $this->setup_data();

        // Make sure you are not logged in
        $this->setUser(null);

        // Get the first external participant instance
        /** @var participant_instance_entity $external_participant_instance */
        $external_participant_instance = participant_instance_entity::repository()
            ->where('participant_source', participant_source::EXTERNAL)
            ->order_by('id')
            ->first();

        /** @var participant_instance_entity $external_participant_instance2 */
        $external_participant_instance2 = participant_instance_entity::repository()
            ->where('participant_source', participant_source::EXTERNAL)
            ->where('id', '<>', $external_participant_instance->id)
            ->order_by('id')
            ->first();

        // This should resolve now as it has a valid token
        $result = $this->resolve_graphql_query(
            self::QUERY,
            [
                'subject_instance_id' => $external_participant_instance->subject_instance_id,
                'token' => $external_participant_instance->external_participant->token
            ]
        );
        $this->assertNotNull($result);
        $this->assertInstanceOf(subject_instance::class, $result);
        $this->assertEquals($external_participant_instance->subject_instance_id, $result->id);

        // Use an invalid token
        $result = $this->resolve_graphql_query(
            self::QUERY,
            [
                'subject_instance_id' => $external_participant_instance->subject_instance_id,
                'token' => 'idontexist'
            ]
        );
        $this->assertNull($result);

        // Use an empty token
        $result = $this->resolve_graphql_query(
            self::QUERY,
            [
                'subject_instance_id' => $external_participant_instance->subject_instance_id,
                'token' => ''
            ]
        );
        $this->assertNull($result);

        // Use valid token but from a different instance
        $result = $this->resolve_graphql_query(
            self::QUERY,
            [
                'subject_instance_id' => $external_participant_instance->subject_instance_id,
                'token' => $external_participant_instance2->external_participant->token
            ]
        );
        $this->assertNull($result);


        // Get an internal participant instance
        /** @var participant_instance_entity $internal_participant_instance */
        $internal_participant_instance = participant_instance_entity::repository()
            ->where('participant_source', participant_source::INTERNAL)
            ->where('subject_instance_id', '<>', $external_participant_instance->subject_instance_id)
            ->order_by('id')
            ->first();

        // Check that this internal one won't resolve
        $result = $this->resolve_graphql_query(
            self::QUERY,
            [
                'subject_instance_id' => $internal_participant_instance->subject_instance_id,
                'token' => $external_participant_instance->external_participant->token
            ]
        );
        $this->assertNull($result);
    }

    public function test_resolve_subject_instance_as_logged_in_user() {
        $this->setup_data();

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        // Even if someone is logged in the resolving with token should still work
        // Get the first external participant instance
        /** @var participant_instance_entity $external_participant_instance */
        $external_participant_instance = participant_instance_entity::repository()
            ->where('participant_source', participant_source::EXTERNAL)
            ->order_by('id')
            ->first();

        // This should resolve now as it has a valid token
        $result = $this->resolve_graphql_query(
            self::QUERY,
            [
                'subject_instance_id' => $external_participant_instance->subject_instance_id,
                'token' => $external_participant_instance->external_participant->token
            ]
        );
        $this->assertNull($result);
    }

    public function test_resolve_closed_subject_instance() {
        $this->setup_data();

        // Even if someone is logged in the resolving with token should still work
        // Get the first external participant instance
        /** @var participant_instance_entity $external_participant_instance */
        $external_participant_instance = participant_instance_entity::repository()
            ->where('participant_source', participant_source::EXTERNAL)
            ->order_by('id')
            ->first();

        $subject_instance = subject_instance::load_by_entity($external_participant_instance->subject_instance);
        $subject_instance->manually_close();

        // Make sure you are not logged in
        $this->setUser(null);

        // This should resolve now as it has a valid token
        $result = $this->resolve_graphql_query(
            self::QUERY,
            [
                'subject_instance_id' => $external_participant_instance->subject_instance_id,
                'token' => $external_participant_instance->external_participant->token
            ]
        );
        $this->assertNull($result);
    }

    public function test_query_successful(): void {
        $this->setup_data();

        /** @var participant_instance_entity $external_participant_instance */
        $external_participant_instance = participant_instance_entity::repository()
            ->where('participant_source', participant_source::EXTERNAL)
            ->order_by('id')
            ->first();

        $subject_user = $external_participant_instance->subject_instance->subject_user;

        // Make sure we are not logged in
        $this->setUser(null);

        $args = [
            'subject_instance_id' => $external_participant_instance->subject_instance_id,
            'token' => $external_participant_instance->external_participant->token
        ];

        $result = $this->parsed_graphql_operation(self::QUERY_NOSESSION, $args);
        $this->assert_webapi_operation_successful($result);
        $actual = $this->get_webapi_operation_data($result);

        $expected_subject_instance = subject_instance::load_by_entity($external_participant_instance->subject_instance);

        $expected = [
            'id' => (string) $expected_subject_instance->id,
            'progress_status' => $expected_subject_instance->get_progress_status(),
            'instance_count' => 1,
            'activity' => [
                'name' => $expected_subject_instance->get_activity()->name,
                'settings' => [
                    'close_on_completion' => false,
                    'multisection' => false,
                    'visibility_condition' => [
                        'participant_description' => null,
                        'view_only_participant_description' => 'Responses are displayed as soon as a participant has submitted.'
                    ],
                ],
                'anonymous_responses' => false,
            ],
            'subject_user' => [
                'id' => $subject_user->id,
                'fullname' => $subject_user->fullname,
                'profileimageurlsmall' => self::get_default_image_url()->out(true),
                'profileimagealt' => $subject_user->fullname,
            ]
        ];

        self::assertEquals($expected, $this->strip_expected_dates($actual));
    }

    public function test_failed_ajax_query(): void {
        $this->setup_data();

        $user = $this->getDataGenerator()->create_user();

        /** @var participant_instance_entity $external_participant_instance */
        $external_participant_instance = participant_instance_entity::repository()
            ->where('participant_source', participant_source::EXTERNAL)
            ->order_by('id')
            ->first();

        // Make sure we are not logged in
        $this->setUser(null);

        $args = [
            'subject_instance_id' => $external_participant_instance->subject_instance_id,
            'token' => $external_participant_instance->external_participant->token
        ];

        $feature = 'performance_activities';
        advanced_feature::disable($feature);
        $result = $this->parsed_graphql_operation(self::QUERY_NOSESSION, $args);
        $this->assert_webapi_operation_failed($result, 'Feature performance_activities is not available.');
        advanced_feature::enable($feature);

        $result = $this->parsed_graphql_operation(self::QUERY_NOSESSION, []);
        $this->assert_webapi_operation_failed(
            $result,
            'Variable "$subject_instance_id" of required type "core_id!" was not provided.'
        );

        $result = $this->parsed_graphql_operation(
            self::QUERY_NOSESSION,
            ['subject_instance_id' => $external_participant_instance->subject_instance_id]
        );
        $this->assert_webapi_operation_failed(
            $result,
            'Variable "$token" of required type "String!" was not provided.'
        );

        $this->setUser($user);

        $args = [
            'subject_instance_id' => $external_participant_instance->subject_instance_id,
            'token' => $external_participant_instance->external_participant->token
        ];

        [$result, $errors] = $this->parsed_graphql_operation(self::QUERY_NOSESSION, $args);
        $this->assertNull($result);
        $this->assertNull($errors);
    }

    private function setup_data() {
        $this->setAdminUser();
        $generator = $this->generator();

        $configuration = \mod_perform\testing\activity_generator_configuration::new()
            ->enable_creation_of_manual_participants()
            ->set_relationships_per_section(
                [
                    constants::RELATIONSHIP_EXTERNAL,
                    constants::RELATIONSHIP_SUBJECT,
                    constants::RELATIONSHIP_MANAGER
                ]
            );

        $activities = $generator->create_full_activities($configuration);
        /** @var activity $activity */
        $activities->first();
    }

    /**
     * @return \mod_perform\testing\generator
     */
    protected function generator(): \mod_perform\testing\generator {
        return \mod_perform\testing\generator::instance();
    }

    private static function get_default_image_url(): moodle_url {
        global $PAGE;
        $renderer = $PAGE->get_renderer('core');
        return $renderer->image_url('u/f2');
    }

    protected function strip_expected_dates(array $actual_result): array {
        $this->assertArrayHasKey(
            'created_at',
            $actual_result,
            'Result is expected to contain created_at'
        );

        $month_and_year = (new DateTime())->format('F Y');
        $this->assertStringContainsString(
            $month_and_year,
            $actual_result['created_at'],
            'Expected created at to at least be the current month and year'
        );

        unset($actual_result['created_at']);

        return $actual_result;
    }

}
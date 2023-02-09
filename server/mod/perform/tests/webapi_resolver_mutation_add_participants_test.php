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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package mod_perform
 */

use core\orm\query\builder;
use mod_perform\constants;
use mod_perform\entity\activity\activity as activity_entity;
use mod_perform\entity\activity\participant_instance;
use mod_perform\entity\activity\subject_instance;
use mod_perform\state\activity\draft;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @group perform
 */
class mod_perform_webapi_resolver_mutation_add_participants_testcase extends advanced_testcase {

    private const MUTATION = 'mod_perform_add_participants';

    use webapi_phpunit_helper;

    /**
     * @return \mod_perform\testing\generator
     */
    protected function generator() {
        return \mod_perform\testing\generator::instance();
    }

    protected function setUp(): void {
        parent::setUp();
        $this->setAdminUser();
    }

    public function test_not_possible_on_draft_activity() {
        $user1 = $this->getDataGenerator()->create_user();
        $data = $this->generate_test_data();
        $appraiser_relationship = $this->generator()->get_core_relationship(constants::RELATIONSHIP_APPRAISER);

        activity_entity::repository()
            ->where('id', $data->activity1->id)
            ->update(['status' => draft::get_code()]);

        $args = [
            'input' => [
                'subject_instance_ids' => $data->activity1_subject_instance_ids,
                'participants' => [
                    'core_relationship_id' => $appraiser_relationship->id,
                    'participant_id' => $user1->id,
                ]
            ]
        ];

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Can only add participants to an active activity.');
        $this->resolve_graphql_mutation(self::MUTATION, $args);
    }

    public function test_subject_instances_must_belong_to_same_activity() {
        $user1 = $this->getDataGenerator()->create_user();
        $data = $this->generate_test_data();
        $appraiser_relationship = $this->generator()->get_core_relationship(constants::RELATIONSHIP_APPRAISER);

        $args = [
            'input' => [
                'subject_instance_ids' => array_merge(
                    $data->activity1_subject_instance_ids,
                    $data->activity2_subject_instance_ids
                ),
                'participants' => [
                    'core_relationship_id' => $appraiser_relationship->id,
                    'participant_id' => $user1->id,
                ]
            ]
        ];

        $this->expectException(invalid_parameter_exception::class);
        $this->expectExceptionMessage('All subject instances must belong to the same activity');
        $this->resolve_graphql_mutation(self::MUTATION, $args);
    }

    public function test_successful_result_with_capability_manage_subject_user_participation() {
        $acting_user = $this->getDataGenerator()->create_user();
        $participant_user = $this->getDataGenerator()->create_user();
        $data = $this->generate_test_data();
        $user_role = builder::get_db()->get_record('role', ['shortname' => 'user']);
        foreach ([$data->subject_user1_id, $data->subject_user2_id] as $user_id) {
            assign_capability(
                'mod/perform:manage_subject_user_participation',
                CAP_ALLOW,
                $user_role->id,
                context_user::instance($user_id)->id,
                true
            );
        }
        $this->assert_successful_result($data, $acting_user, $participant_user);
    }

    public function test_successful_result_with_capability_manage_all_participation() {
        $acting_user = $this->getDataGenerator()->create_user();
        $participant_user = $this->getDataGenerator()->create_user();
        $data = $this->generate_test_data();
        $user_role = builder::get_db()->get_record('role', ['shortname' => 'user']);
        assign_capability(
            'mod/perform:manage_all_participation',
            CAP_ALLOW,
            $user_role->id,
            context_user::instance($acting_user->id)->id,
            true
        );
        $this->assert_successful_result($data, $acting_user, $participant_user);
    }

    public function test_missing_capabilities() {
        $this->setAdminUser();
        $user1 = $this->getDataGenerator()->create_user();
        $participant_user = $this->getDataGenerator()->create_user();
        $data = $this->generate_test_data();

        self::setUser($user1);
        $appraiser_relationship = $this->generator()->get_core_relationship(constants::RELATIONSHIP_APPRAISER);
        $args = [
            'input' => [
                'subject_instance_ids' => $data->activity1_subject_instance_ids,
                'participants' => [
                    [
                        'core_relationship_id' => $appraiser_relationship->id,
                        'participant_id' => $participant_user->id,
                    ]
                ]
            ]
        ];
        $this->expectException(required_capability_exception::class);
        $this->expectExceptionMessage('Sorry, but you do not currently have permissions to do that (Manage participation)');
        $this->resolve_graphql_mutation(self::MUTATION, $args);
    }

    private function assert_successful_result($data, $acting_user, $participant_user) {
        $this->assertEquals(0, participant_instance::repository()->where('participant_id', $participant_user->id)->count());

        self::setUser($acting_user);
        $appraiser_relationship = $this->generator()->get_core_relationship(constants::RELATIONSHIP_APPRAISER);
        $args = [
            'input' => [
                'subject_instance_ids' => $data->activity1_subject_instance_ids,
                'participants' => [
                    [
                        'core_relationship_id' => $appraiser_relationship->id,
                        'participant_id' => $participant_user->id,
                    ]
                ]
            ]
        ];
        [$result, $error] = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assertNull($error);
        $result_instances = $result['participant_instances'];

        $new_instances = participant_instance::repository()->where('participant_id', $participant_user->id)->get()->all();
        $this->assertCount(2, $new_instances);
        $expected_result = [
            [
                'id' => $new_instances[0]->id,
                'participant_id' => $participant_user->id,
                'core_relationship' => [
                    'id' => $appraiser_relationship->id
                ],
            ],
            [
                'id' => $new_instances[1]->id,
                'participant_id' => $participant_user->id,
                'core_relationship' => [
                    'id' => $appraiser_relationship->id
                ],
            ],
        ];

        $this->assertEqualsCanonicalizing($expected_result, $result_instances);
    }

    /**
     * @return stdClass
     */
    private function generate_test_data(): stdClass {
        $generator = $this->generator();

        // Create 2 activities with 2 users each.
        $config = \mod_perform\testing\activity_generator_configuration::new()
            ->set_number_of_activities(2)
            ->set_number_of_users_per_user_group_type(2)
            ->set_relationships_per_section([constants::RELATIONSHIP_SUBJECT, constants::RELATIONSHIP_APPRAISER]);

        [$activity1, $activity2] = $generator->create_full_activities($config)->all();

        $activity1_subject_instances = subject_instance::repository()
            ->filter_by_activity_id($activity1->id)
            ->get();
        $activity2_subject_instances = subject_instance::repository()
            ->filter_by_activity_id($activity2->id)
            ->get();
        [$subject_user1_id, $subject_user2_id] = $activity1_subject_instances->pluck('subject_user_id');

        return (object)[
            'activity1' => $activity1,
            'activity2' => $activity2,
            'activity1_subject_instance_ids' => $activity1_subject_instances->pluck('id'),
            'activity2_subject_instance_ids' => $activity2_subject_instances->pluck('id'),
            'subject_user1_id' => $subject_user1_id,
            'subject_user2_id' => $subject_user2_id,
        ];
    }
}
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
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package mod_perform
 */

use core\collection;
use core\date_format;
use core\entity\user;
use core_phpunit\testcase;
use core\webapi\formatter\field\date_field_formatter;
use mod_perform\constants;
use mod_perform\entity\activity\activity as activity_entity;
use mod_perform\entity\activity\external_participant;
use mod_perform\entity\activity\participant_instance as participant_instance_entity;
use mod_perform\entity\activity\subject_instance as subject_instance_entity;
use mod_perform\expand_task;
use mod_perform\models\activity\activity_setting;
use mod_perform\models\activity\participant_instance as participant_instance_model;
use mod_perform\models\activity\participant_source;
use mod_perform\models\activity\subject_instance as subject_instance_model;
use mod_perform\models\response\participant_section as participant_section_model;
use mod_perform\models\response\subject_sections;
use mod_perform\state\activity\active;
use mod_perform\state\activity\draft;
use mod_perform\state\participant_instance\in_progress as participant_instance_in_progress;
use mod_perform\state\participant_instance\not_started as participant_instance_not_started;
use mod_perform\state\participant_instance\not_submitted as participant_instance_not_submitted;
use mod_perform\state\participant_instance\open as participant_instance_open;
use mod_perform\state\participant_section\in_progress as participant_section_in_progress;
use mod_perform\state\participant_section\not_started as participant_section_not_started;
use mod_perform\state\participant_section\not_submitted as participant_section_not_submitted;
use mod_perform\state\participant_section\open as participant_section_open;
use mod_perform\state\subject_instance\in_progress as subject_instance_in_progress;
use mod_perform\state\subject_instance\not_submitted as subject_instance_not_submitted;
use mod_perform\state\subject_instance\open as subject_instance_open;
use mod_perform\task\service\subject_instance_creation;
use mod_perform\testing\activity_generator_configuration;
use mod_perform\testing\generator as perform_generator;
use totara_core\advanced_feature;
use totara_job\job_assignment;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @group perform
 */
class mod_perform_webapi_resolver_query_subject_instances_testcase extends testcase {

    private const QUERY = 'mod_perform_my_subject_instances';

    use webapi_phpunit_helper;

    public function test_query_successful_with_single_section(): void {
        self::setAdminUser();
        /** @var perform_generator $perform_generator */
        $perform_generator = perform_generator::instance();
        $activity = $perform_generator->create_full_activities()->first();
        /** @var participant_instance_entity $participant_instance */
        $participant_instance = participant_instance_entity::repository()
            ->order_by('id')
            ->get()
            ->first();
        /** @var subject_instance_model $subject_instance */
        $subject_instance = subject_instance_model::load_by_id($participant_instance->subject_instance_id);

        $subject_relationship = $perform_generator->get_core_relationship(constants::RELATIONSHIP_SUBJECT);

        $participant_id = $participant_instance->participant_id;
        self::setUser($participant_id);

        $args = [
            'filters' => [
                'about_role' => $subject_relationship->id
            ]
        ];

        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);
        $this->assertEquals($result['total'], 1);
        $this->assertEquals($result['completed_count'], 0);
        $this->assertEquals($result['overdue_count'], 0);

        $actual = $result['items'];
        $this->assertCount(1, $actual, 'wrong subject count');

        $subject_user = new user($subject_instance->subject_user->id);
        $subject_user_profile_image_url = (new user_picture($subject_user->to_record(), 0))->get_url($GLOBALS['PAGE'])->out(false);

        $subject = $actual[0];
        $expected_subject = [
            'id' => (string) $subject_instance->id,
            'progress_status' => $subject_instance->get_progress_status(),
            'availability_status' => $subject_instance->get_availability_status(),
            'created_at' => (new date_field_formatter(date_format::FORMAT_DATE, $subject_instance->get_context()))
                ->format($subject_instance->created_at),
            'due_on' => null,
            'activity' => [
                'name' => $activity->name,
                'settings' => [
                    activity_setting::MULTISECTION => false
                ],
                'type' => [
                    'display_name' => $activity->type->display_name
                ],
                'anonymous_responses' => false,
                'id' => $activity->id,
            ],
            'subject_user' => [
                'fullname' => $subject_instance->subject_user->fullname,
                'profileimageurlsmall' => $subject_user_profile_image_url,
                'profileimagealt' => $subject_instance->subject_user->fullname,
            ],
            'job_assignment' => null,
            'participant_instances' => [
                [
                    'progress_status' => participant_instance_not_started::get_name(),
                    'core_relationship' => [
                        'id' => $subject_relationship->id,
                        'name' => $subject_relationship->get_name(),
                    ],
                    'participant_id' => $participant_id,
                    'id' => (string) $participant_instance->id,
                    'availability_status' => participant_instance_open::get_name(),
                    'is_overdue' => false,
                    'is_for_current_user' => true
                ]
            ]
        ];
        $this->assertEquals($expected_subject, $subject['subject']);

        $participant = new user($participant_id);
        $profile_image_url = (new user_picture($participant->to_record(), 0))->get_url($GLOBALS['PAGE'])->out(false);

        $section = $activity->sections->first();
        $expected_section = [
            'section' => [
                'id' => $section->id,
                'display_title' => $section->display_title,
                'sort_order' => 1,
            ],
            'participant_sections' => [
                [
                    'id' => $participant_instance->participant_sections->first()->id,
                    'participant_instance' => [
                        'progress_status' => participant_instance_not_started::get_name(),
                        'participant_id' => $participant_id,
                        'participant' => [
                            'fullname' => $participant->fullname,
                            'profileimageurlsmall' => $profile_image_url,
                            'profileimagealt' => $participant->fullname,
                        ],
                        'core_relationship' => [
                            'id' => $subject_relationship->id,
                            'name' => $subject_relationship->get_name(),
                        ],
                        'is_for_current_user' => true,
                    ],
                    'progress_status' => participant_section_not_started::get_name(),
                    'availability_status' => participant_section_open::get_name(),
                    'is_overdue' => false,
                    'can_answer' => true,
                ],
            ],
            'can_participate' => true,
        ];

        $this->assertCount(1, $subject["sections"], 'wrong sections count');
        $this->assertEquals($expected_section, $subject['sections'][0]);
    }

    public function delete_or_suspend_data_provider(): array {
        return [
            'Deleted user should be hidden' => [
                static function (stdClass $user) {
                    delete_user($user);
                }
            ],
            'Suspended user should be hidden with "hide_suspended_users" switched on' => [
                static function (stdClass $user) {
                    set_config('perform_hide_suspended_users', 1);
                    user_suspend_user($user->id);
                }
            ],
            'Suspended user should NOT be hidden with "hide_suspended_users" switched off' => [
                static function (stdClass $user) {
                    set_config('perform_hide_suspended_users', 0);
                    user_suspend_user($user->id);
                },
                false
            ],
            'The setting "perform_close_suspended_user_instances" should not have any effect' => [
                static function (stdClass $user) {
                    set_config('perform_close_suspended_user_instances', 1);
                    user_suspend_user($user->id);
                },
                false
            ],
        ];
    }

    /**
     * @dataProvider delete_or_suspend_data_provider
     * @param closure $delete_or_suspend
     * @param bool $should_be_hidden
     * @return void
     */
    public function test_query_with_deleted_or_suspended_participant(
        closure $delete_or_suspend,
        bool $should_be_hidden = true
    ): void {
        self::setAdminUser();
        $perform_generator = perform_generator::instance();

        $configuration = activity_generator_configuration::new()
            ->set_number_of_activities(1)
            ->set_number_of_tracks_per_activity(1)
            ->set_cohort_assignments_per_activity(1)
            ->set_number_of_users_per_user_group_type(1)
            ->enable_appraiser_for_each_subject_user()
            ->set_relationships_per_section(
                [
                    constants::RELATIONSHIP_SUBJECT,
                    constants::RELATIONSHIP_MANAGER,
                    constants::RELATIONSHIP_APPRAISER
                ]
            );

        $perform_generator->create_full_activities($configuration)->first();

        /** @var participant_instance_entity $participant_instance */
        $participant_instance = participant_instance_entity::repository()
            ->order_by('id')
            ->get()
            ->first();

        $participant_id = $participant_instance->participant_id;
        self::setUser($participant_id);

        $subject_relationship = $perform_generator->get_core_relationship(constants::RELATIONSHIP_SUBJECT);
        $args = [
            'filters' => [
                'about_role' => $subject_relationship->id
            ]
        ];

        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_successful($result);

        $actual = ($this->get_webapi_operation_data($result))['items'];

        // The user has two participant sections, one for the subject and one for the appraiser
        self::assertCount(2, $actual[0]['sections'][0]['participant_sections']);

        $job = job_assignment::get_first($participant_id);

        /** @var user $appraiser */
        $appraiser = user::repository()->find_or_fail($job->appraiserid);

        // Now delete or suspend the appraiser
        $delete_or_suspend($appraiser->to_record());

        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_successful($result);

        $actual = ($this->get_webapi_operation_data($result))['items'];

        if ($should_be_hidden) {
            // The deleted participant is now gone from the result
            self::assertCount(1, $actual[0]['sections'][0]['participant_sections']);
        } else {
            self::assertCount(2, $actual[0]['sections'][0]['participant_sections']);
        }
    }

    /**
     * @dataProvider delete_or_suspend_data_provider
     * @param closure $delete_or_suspend
     * @param bool $should_be_hidden
     * @return void
     */
    public function test_query_with_deleted_or_suspended_subject(closure $delete_or_suspend, bool $should_be_hidden = true): void {
        self::setAdminUser();
        $perform_generator = perform_generator::instance();

        $configuration = activity_generator_configuration::new()
            ->set_number_of_activities(1)
            ->set_number_of_tracks_per_activity(1)
            ->set_cohort_assignments_per_activity(1)
            ->set_number_of_users_per_user_group_type(1)
            ->enable_appraiser_for_each_subject_user()
            ->set_relationships_per_section(
                [
                    constants::RELATIONSHIP_SUBJECT,
                    constants::RELATIONSHIP_MANAGER,
                    constants::RELATIONSHIP_APPRAISER
                ]
            );

        $perform_generator->create_full_activities($configuration)->first();

        /** @var participant_instance_entity $participant_instance */
        $participant_instance = participant_instance_entity::repository()
            ->order_by('id')
            ->get()
            ->first();

        $participant_id = $participant_instance->participant_id;

        $job = job_assignment::get_first($participant_id);

        /** @var user $appraiser */
        $appraiser = user::repository()->find_or_fail($job->appraiserid);

        self::setUser($appraiser->to_record());

        $appraiser_relationship = $perform_generator->get_core_relationship(constants::RELATIONSHIP_APPRAISER);
        $args = [
            'filters' => [
                'about_role' => $appraiser_relationship->id
            ]
        ];

        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_successful($result);

        $actual = ($this->get_webapi_operation_data($result))['items'];

        // The appraiser sees the subject users instance
        self::assertNotEmpty($actual);

        // Now delete or suspend the appraiser
        $delete_or_suspend(user::repository()->find_or_fail($participant_id)->to_record());

        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_successful($result);

        $actual = ($this->get_webapi_operation_data($result))['items'];

        if ($should_be_hidden) {
            // The instance is not visible anymore
            self::assertEmpty($actual);
        } else {
            self::assertNotEmpty($actual);
        }
    }

    public function test_query_successful_with_single_section_and_external_participant(): void {
        self::setAdminUser();
        /** @var perform_generator $perform_generator */
        $perform_generator = perform_generator::instance();

        $configuration = activity_generator_configuration::new()
            ->enable_creation_of_manual_participants()
            ->set_relationships_per_section([constants::RELATIONSHIP_SUBJECT, constants::RELATIONSHIP_EXTERNAL]);

        $activity = $perform_generator->create_full_activities($configuration)->first();
        /** @var participant_instance_entity $participant_instance */
        // Get the internal user
        $participant_instance = participant_instance_entity::repository()
            ->where('participant_source', participant_source::INTERNAL)
            ->order_by('id')
            ->get()
            ->first();

        /** @var subject_instance_model $subject_instance */
        $subject_instance = subject_instance_model::load_by_id($participant_instance->subject_instance_id);

        $subject_relationship = $perform_generator->get_core_relationship(constants::RELATIONSHIP_SUBJECT);
        $external_relationship = $perform_generator->get_core_relationship(constants::RELATIONSHIP_EXTERNAL);

        /** @var external_participant $external_participant */
        $external_participant = external_participant::repository()
            ->order_by('id')
            ->first();

        $external_participant_instance = $external_participant->participant_instance;

        $participant_id = $participant_instance->participant_id;
        self::setUser($participant_id);

        $args = [
            'filters' => [
                'about_role' => $subject_relationship->id
            ]
        ];

        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_successful($result);

        $actual = ($this->get_webapi_operation_data($result))['items'];
        $this->assertCount(1, $actual, 'wrong subject count');

        $subject_user = new user($subject_instance->subject_user->id);
        $subject_user_profile_image_url = (new user_picture($subject_user->to_record(), 0))->get_url($GLOBALS['PAGE'])->out(false);

        $subject = $actual[0];
        $expected_subject = [
            'id' => (string) $subject_instance->id,
            'progress_status' => $subject_instance->get_progress_status(),
            'availability_status' => $subject_instance->get_availability_status(),
            'created_at' => (new date_field_formatter(date_format::FORMAT_DATE, $subject_instance->get_context()))
                ->format($subject_instance->created_at),
            'due_on' => null,
            'activity' => [
                'name' => $activity->name,
                'settings' => [
                    activity_setting::MULTISECTION => false
                ],
                'type' => [
                    'display_name' => $activity->type->display_name
                ],
                'anonymous_responses' => false,
                'id' => $activity->id,
            ],
            'subject_user' => [
                'fullname' => $subject_instance->subject_user->fullname,
                'profileimageurlsmall' => $subject_user_profile_image_url,
                'profileimagealt' => $subject_instance->subject_user->fullname,
            ],
            'job_assignment' => null,
            'participant_instances' => [
                [
                    'progress_status' => participant_instance_not_started::get_name(),
                    'core_relationship' => [
                        'id' => $subject_relationship->id,
                        'name' => $subject_relationship->get_name(),
                    ],
                    'participant_id' => $participant_id,
                    'id' => (string) $participant_instance->id,
                    'availability_status' => participant_instance_open::get_name(),
                    'is_overdue' => false,
                    'is_for_current_user' => true
                ],
                [
                    'progress_status' => participant_instance_not_started::get_name(),
                    'core_relationship' => [
                        'id' => $external_relationship->id,
                        'name' => $external_relationship->get_name(),
                    ],
                    'participant_id' => $external_participant->id,
                    'id' => (string) $external_participant_instance->id,
                    'availability_status' => participant_instance_open::get_name(),
                    'is_overdue' => false,
                    'is_for_current_user' => false
                ]
            ]
        ];
        $this->assertEquals($expected_subject, $subject['subject']);

        $participant = new user($participant_id);
        $profile_image_url = (new user_picture($participant->to_record(), 0))->get_url($GLOBALS['PAGE'])->out(false);

        global $PAGE;
        $renderer = $PAGE->get_renderer('core');
        $default_image_url = $renderer->image_url('u/f2');

        $section = $activity->sections->first();
        $expected_section = [
            'section' => [
                'id' => $section->id,
                'display_title' => $section->display_title,
                'sort_order' => 1,
            ],
            'participant_sections' => [
                [
                    'id' => $participant_instance->participant_sections->first()->id,
                    'participant_instance' => [
                        'progress_status' => participant_instance_not_started::get_name(),
                        'participant_id' => $participant_id,
                        'participant' => [
                            'fullname' => $participant->fullname,
                            'profileimageurlsmall' => $profile_image_url,
                            'profileimagealt' => $participant->fullname,
                        ],
                        'core_relationship' => [
                            'id' => $subject_relationship->id,
                            'name' => $subject_relationship->get_name(),
                        ],
                        'is_for_current_user' => true,
                    ],
                    'progress_status' => participant_section_not_started::get_name(),
                    'availability_status' => participant_section_open::get_name(),
                    'is_overdue' => false,
                    'can_answer' => true,
                ],
                [
                    'id' => $external_participant_instance->participant_sections->first()->id,
                    'participant_instance' => [
                        'progress_status' => participant_instance_not_started::get_name(),
                        'participant_id' => $external_participant->id,
                        'participant' => [
                            'fullname' => $external_participant->name,
                            'profileimageurlsmall' => $default_image_url,
                            'profileimagealt' => $external_participant->name,
                        ],
                        'core_relationship' => [
                            'id' => $external_relationship->id,
                            'name' => $external_relationship->get_name(),
                        ],
                        'is_for_current_user' => false,
                    ],
                    'progress_status' => participant_section_not_started::get_name(),
                    'availability_status' => participant_section_open::get_name(),
                    'is_overdue' => false,
                    'can_answer' => true,
                ],
            ],
            'can_participate' => true,
        ];

        $this->assertCount(1, $subject["sections"], 'wrong sections count');
        $this->assertEquals($expected_section, $subject['sections'][0]);
    }

    public function test_query_successful_with_single_section_anonymous_responses(): void {
        self::setAdminUser();
        /** @var perform_generator $perform_generator */
        $perform_generator = perform_generator::instance();

        $configuration = activity_generator_configuration::new()
            ->set_number_of_activities(1)
            ->set_number_of_tracks_per_activity(1)
            ->set_cohort_assignments_per_activity(1)
            ->set_number_of_users_per_user_group_type(1)
            ->enable_appraiser_for_each_subject_user()
            ->enable_anonymous_responses()
            ->set_relationships_per_section(
                [
                    constants::RELATIONSHIP_SUBJECT,
                    constants::RELATIONSHIP_MANAGER,
                    constants::RELATIONSHIP_APPRAISER
                ]
            );

        $activity = $perform_generator->create_full_activities($configuration)->first();

        $subject_core_relationship_id = $perform_generator->get_core_relationship(constants::RELATIONSHIP_SUBJECT)->id;

        /** @var participant_instance_entity $subject_participant_instance */
        $subject_participant_instance = participant_instance_entity::repository()
            ->where('core_relationship_id', $subject_core_relationship_id)
            ->one();

        $subject_instance = subject_instance_model::load_by_id($subject_participant_instance->subject_instance_id);

        $subject_relationship = $perform_generator->get_core_relationship(constants::RELATIONSHIP_SUBJECT);

        $participant_id = $subject_participant_instance->participant_id;
        self::setUser($participant_id);

        $appraiser_core_relationship_id = $perform_generator->get_core_relationship(constants::RELATIONSHIP_APPRAISER)->id;
        /** @var participant_instance_entity $appraiser_participant_instance */
        $appraiser_participant_instance = participant_instance_entity::repository()
            ->where('core_relationship_id', $appraiser_core_relationship_id)
            ->one();

        $args = [
            'filters' => [
                'about_role' => $subject_relationship->id
            ]
        ];

        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_successful($result);

        $actual = ($this->get_webapi_operation_data($result))['items'];
        $this->assertCount(1, $actual, 'wrong subject count');

        $subject_user = new user($subject_instance->subject_user->id);
        $subject_user_profile_image_url = (new user_picture($subject_user->to_record(), 0))->get_url($GLOBALS['PAGE'])->out(false);

        $subject = $actual[0];
        $expected_subject = [
            'id' => (string) $subject_instance->id,
            'progress_status' => $subject_instance->get_progress_status(),
            'availability_status' => $subject_instance->get_availability_status(),
            'created_at' => (new date_field_formatter(date_format::FORMAT_DATE, $subject_instance->get_context()))
                ->format($subject_instance->created_at),
            'due_on' => null,
            'activity' => [
                'name' => $activity->name,
                'settings' => [
                    activity_setting::MULTISECTION => false
                ],
                'type' => [
                    'display_name' => $activity->type->display_name
                ],
                'anonymous_responses' => true,
                'id' => $activity->id,
            ],
            'subject_user' => [
                'fullname' => $subject_instance->subject_user->fullname,
                'profileimageurlsmall' => $subject_user_profile_image_url,
                'profileimagealt' => $subject_instance->subject_user->fullname,
            ],
            'job_assignment' => null,
            'participant_instances' => [
                [
                    'progress_status' => participant_instance_not_started::get_name(),
                    'core_relationship' => [
                        'id' => $subject_relationship->id,
                        'name' => $subject_relationship->get_name(),
                    ],
                    'participant_id' => $participant_id,
                    'id' => (string) $subject_participant_instance->id,
                    'availability_status' => participant_instance_open::get_name(),
                    'is_overdue' => false,
                    'is_for_current_user' => true
                ],
                [
                    'progress_status' => participant_instance_not_started::get_name(),
                    'core_relationship' => null,
                    'participant_id' => null,
                    'id' => (string) $appraiser_participant_instance->id,
                    'availability_status' => participant_instance_open::get_name(),
                    'is_overdue' => false,
                    'is_for_current_user' => false
                ],
            ]
        ];
        $this->assertEquals($expected_subject, $subject['subject']);

        $participant = new user($participant_id);
        $profile_image_url = (new user_picture($participant->to_record(), 0))->get_url($GLOBALS['PAGE'])->out(false);

        $section = $activity->sections->first();
        $expected_section = [
            'section' => [
                'id' => $section->id,
                'display_title' => $section->display_title,
                'sort_order' => 1,
            ],
            'participant_sections' => [
                [
                    'id' => $subject_participant_instance->participant_sections->first()->id,
                    'participant_instance' => [
                        'progress_status' => participant_instance_not_started::get_name(),
                        'participant_id' => $participant_id,
                        'participant' => [
                            'fullname' => $participant->fullname,
                            'profileimageurlsmall' => $profile_image_url,
                            'profileimagealt' => $participant->fullname,
                        ],
                        'core_relationship' => [
                            'id' => $subject_relationship->id,
                            'name' => $subject_relationship->get_name(),
                        ],
                        'is_for_current_user' => true,
                    ],
                    'progress_status' => participant_section_not_started::get_name(),
                    'availability_status' => participant_section_open::get_name(),
                    'is_overdue' => false,
                    'can_answer' => true,
                ],
                [
                    'id' => $appraiser_participant_instance->participant_sections->first()->id,
                    'participant_instance' => [
                        'progress_status' => participant_instance_not_started::get_name(),
                        'participant_id' => null,
                        'participant' => null,
                        'core_relationship' => null,
                        'is_for_current_user' => false,
                    ],
                    'progress_status' => participant_section_not_started::get_name(),
                    'availability_status' => participant_section_open::get_name(),
                    'is_overdue' => false,
                    'can_answer' => true,
                ],
            ],
            'can_participate' => true,
        ];

        $this->assertCount(1, $subject["sections"], 'wrong sections count');
        $this->assertEquals($expected_section, $subject['sections'][0]);
    }

    public function test_get_subject_instances_with_multiple_sections(): void {
        $this->setAdminUser();

        /** @var perform_generator $perform_generator */
        $perform_generator = perform_generator::instance();

        $activity1 = $perform_generator->create_activity_in_container([
            'activity_status' => draft::get_code(),
            'create_track' => true,
            'create_section' => false
        ]);
        // Activate multisection
        $activity1->toggle_multisection_setting(true);

        // Now activate this activity, directly in the database to avoid state change checks
        /** @var activity_entity $activity_entity */
        $activity_entity = activity_entity::repository()->find($activity1->id);
        $activity_entity->status = active::get_code();
        $activity_entity->save();

        // Create sections, deliberately create in different order to test sort order
        $section3 = $perform_generator->create_section($activity1, ['title' => 'Section 3']);
        $section1 = $perform_generator->create_section($activity1, ['title' => 'Section 1']);
        $section2 = $perform_generator->create_section($activity1, ['title' => 'Section 2']);

        $perform_generator->create_section_relationship($section1, ['relationship' => constants::RELATIONSHIP_SUBJECT]);
        $perform_generator->create_section_relationship($section1, ['relationship' => constants::RELATIONSHIP_MANAGER]);

        // This section should only be answered by the subject
        $perform_generator->create_section_relationship($section2, ['relationship' => constants::RELATIONSHIP_SUBJECT]);

        // This section should only be answered by the manager
        $perform_generator->create_section_relationship($section3, ['relationship' => constants::RELATIONSHIP_MANAGER]);

        $element = $perform_generator->create_element();
        $perform_generator->create_section_element($section1, $element);

        $element = $perform_generator->create_element();
        $perform_generator->create_section_element($section2, $element);

        $element = $perform_generator->create_element();
        $perform_generator->create_section_element($section3, $element);

        $cohort1 = $this->getDataGenerator()->create_cohort();

        /** @var \totara_job\testing\generator $job_generator */
        $job_generator = $this->getDataGenerator()->get_plugin_generator('totara_job');

        $user1 = $this->getDataGenerator()->create_user();
        // This user has user1 as the manager
        [$user2,] = $job_generator->create_user_and_job([], $user1->id);
        $user3 = $this->getDataGenerator()->create_user();

        // Add two users to the cohort
        cohort_add_member($cohort1->id, $user2->id);
        cohort_add_member($cohort1->id, $user3->id);

        $perform_generator->create_track_assignments_with_existing_groups($activity1->tracks->first(), [$cohort1->id]);

        // Make sure we have the instances
        expand_task::create()->expand_all();
        (new subject_instance_creation())->generate_instances();

        // Newest subject instances at the top of the list
        $subject_instances = subject_instance_entity::repository()
            ->filter_by_activity_id($activity1->id)
            ->where('subject_user_id', $user2->id)
            ->order_by('created_at', 'desc')
            ->order_by('id', 'desc')
            ->get()
            ->map_to(subject_instance_model::class);

        $this->assertCount(1, $subject_instances);

        /** @var subject_instance_model $subject_instance */
        $subject_instance = $subject_instances->first();

        $participant_instances = $subject_instance->participant_instances;
        $this->assertCount(2, $participant_instances);

        $subject_relationship = $perform_generator->get_core_relationship(constants::RELATIONSHIP_SUBJECT);
        $manager_relationship = $perform_generator->get_core_relationship(constants::RELATIONSHIP_MANAGER);

        /** @var participant_instance_model $participant_instance_for_subject */
        $participant_instance_for_subject = $subject_instance->get_participant_instances()
            ->find('core_relationship_id', $subject_relationship->id);
        /** @var participant_section_model $participant_section1_subject */
        $participant_section1_subject = $participant_instance_for_subject->participant_sections->find('section_id', $section1->id);
        /** @var participant_section_model $participant_section2_subject */
        $participant_section2_subject = $participant_instance_for_subject->participant_sections->find('section_id', $section2->id);

        // Now put this section into progress
        $participant_section2_subject->on_participant_access();

        /** @var participant_instance_model $participant_instance_for_manager */
        $participant_instance_for_manager = $subject_instance->get_participant_instances()
            ->find('core_relationship_id', $manager_relationship->id);
        /** @var participant_section_model $participant_section3_manager */
        $participant_section3_manager = $participant_instance_for_manager->participant_sections->find('section_id', $section3->id);
        /** @var participant_section_model $participant_section1_manager */
        $participant_section1_manager = $participant_instance_for_manager->participant_sections->find('section_id', $section1->id);

        $subject_user = new user($user2->id);
        $subject_user_profile_image_url = (new user_picture($user2, 0))->get_url($GLOBALS['PAGE'])->out(false);

        $manager_user = new user($user1->id);
        $manager_user_profile_image_url = (new user_picture($user1, 0))->get_url($GLOBALS['PAGE'])->out(false);

        // Now fetch the users own instances

        $this->setUser($user2);

        $args = [
            'filters' => [
                'about_role' => $subject_relationship->id
            ]
        ];

        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);
        $this->assertEquals($result['total'], 1);
        $this->assertEquals($result['completed_count'], 0);
        $this->assertEquals($result['overdue_count'], 0);

        $actual = $result['items'];
        $this->assertCount(1, $actual, 'wrong subject count');

        $subject = $actual[0];

        $expected_subject = function (
            subject_instance_model $subject_instance,
            collection $participant_instances,
            int $user_id
        ): array {
            $expected_relationships = [constants::RELATIONSHIP_SUBJECT, constants::RELATIONSHIP_MANAGER];

            $expected_participant_instances = [];
            foreach ($participant_instances as $participant_instance) {
                // We expect to have one manager and subject, so make sure the database rows
                // match what we expect so that the later assert is correct.
                $core_relationship = $participant_instance->get_core_relationship();
                $this->assertContains($core_relationship->idnumber, $expected_relationships);
                unset($expected_relationships[array_search($core_relationship->idnumber, $expected_relationships)]);

                // Only the subjects participant instance got started
                $state = $core_relationship->idnumber === constants::RELATIONSHIP_MANAGER
                    ? participant_instance_not_started::get_name()
                    : participant_instance_in_progress::get_name();

                $expected_participant_instances[] = [
                    'progress_status' => $state,
                    'core_relationship' => [
                        'id' => $core_relationship->id,
                        'name' => $core_relationship->name,
                    ],
                    'participant_id' => $participant_instance->participant_id,
                    'id' => (string) $participant_instance->id,
                    'availability_status' => participant_instance_open::get_name(),
                    'is_overdue' => false,
                    'is_for_current_user' => ($participant_instance->participant_id == $user_id)
                ];
            }

            $this->assertEmpty($expected_relationships);

            $subject_user = new user($subject_instance->subject_user->id);
            $subject_user_profile_image_url = (new user_picture($subject_user->to_record(), 0))->get_url($GLOBALS['PAGE'])->out(false);

            $expected_subject = [
                'id' => $subject_instance->id,
                'progress_status' => subject_instance_in_progress::get_name(),
                'availability_status' => subject_instance_open::get_name(),
                'created_at' => (new date_field_formatter(date_format::FORMAT_DATE, $subject_instance->get_context()))
                    ->format($subject_instance->created_at),
                'due_on' => null,
                'activity' => [
                    'name' => $subject_instance->activity->name,
                    'settings' => [
                        activity_setting::MULTISECTION => true
                    ],
                    'type' => [
                        'display_name' => $subject_instance->activity->type->display_name
                    ],
                    'anonymous_responses' => false,
                    'id' => $subject_instance->activity->id,
                ],
                'subject_user' => [
                    'fullname' => $subject_instance->subject_user->fullname,
                    'profileimageurlsmall' => $subject_user_profile_image_url,
                    'profileimagealt' => $subject_instance->subject_user->fullname,
                ],
                'job_assignment' => null,
                'participant_instances' => $expected_participant_instances
            ];

            return $expected_subject;
        };

        $this->assertEquals($expected_subject($subject_instance, $participant_instances, $user2->id), $subject['subject']);

        $expected_sections = [
            [
                'section' => [
                    'id' => $section3->id,
                    'display_title' => $section3->display_title,
                    'sort_order' => $section3->sort_order,
                ],
                'participant_sections' => [
                    [
                        'id' => $participant_section3_manager->id,
                        'participant_instance' => [
                            'progress_status' => participant_instance_not_started::get_name(),
                            'participant_id' => $manager_user->id,
                            'participant' => [
                                'fullname' => $manager_user->fullname,
                                'profileimageurlsmall' => $manager_user_profile_image_url,
                                'profileimagealt' => $manager_user->fullname,
                            ],
                            'core_relationship' => [
                                'id' => $manager_relationship->id,
                                'name' => $manager_relationship->get_name(),
                            ],
                            'is_for_current_user' => false,
                        ],
                        'progress_status' => participant_section_not_started::get_name(),
                        'availability_status' => participant_section_open::get_name(),
                        'is_overdue' => false,
                        'can_answer' => true,
                    ],
                ],
                'can_participate' => false,
            ],
            [
                'section' => [
                    'id' => $section1->id,
                    'display_title' => $section1->display_title,
                    'sort_order' => $section1->sort_order,
                ],
                'participant_sections' => [
                    [
                        'id' => $participant_section1_subject->id,
                        'participant_instance' => [
                            'progress_status' => participant_instance_in_progress::get_name(),
                            'participant_id' => $subject_user->id,
                            'participant' => [
                                'fullname' => $subject_user->fullname,
                                'profileimageurlsmall' => $subject_user_profile_image_url,
                                'profileimagealt' => $subject_user->fullname,
                            ],
                            'core_relationship' => [
                                'id' => $subject_relationship->id,
                                'name' => $subject_relationship->get_name(),
                            ],
                            'is_for_current_user' => true,
                        ],
                        'progress_status' => participant_section_not_started::get_name(),
                        'availability_status' => participant_section_open::get_name(),
                        'is_overdue' => false,
                        'can_answer' => true,
                    ],
                    [
                        'id' => $participant_section1_manager->id,
                        'participant_instance' => [
                            'progress_status' => participant_instance_not_started::get_name(),
                            'participant_id' => $manager_user->id,
                            'participant' => [
                                'fullname' => $manager_user->fullname,
                                'profileimageurlsmall' => $manager_user_profile_image_url,
                                'profileimagealt' => $manager_user->fullname,
                            ],
                            'core_relationship' => [
                                'id' => $manager_relationship->id,
                                'name' => $manager_relationship->get_name(),
                            ],
                            'is_for_current_user' => false,
                        ],
                        'progress_status' => participant_section_not_started::get_name(),
                        'availability_status' => participant_section_open::get_name(),
                        'is_overdue' => false,
                        'can_answer' => true,
                    ],
                ],
                'can_participate' => true,
            ],
            [
                'section' => [
                    'id' => $section2->id,
                    'display_title' => $section2->display_title,
                    'sort_order' => $section2->sort_order,
                ],
                'participant_sections' => [
                    [
                        'id' => $participant_section2_subject->id,
                        'participant_instance' => [
                            'progress_status' => participant_instance_in_progress::get_name(),
                            'participant_id' => $subject_user->id,
                            'participant' => [
                                'fullname' => $subject_user->fullname,
                                'profileimageurlsmall' => $subject_user_profile_image_url,
                                'profileimagealt' => $subject_user->fullname,
                            ],
                            'core_relationship' => [
                                'id' => $subject_relationship->id,
                                'name' => $subject_relationship->get_name(),
                            ],
                            'is_for_current_user' => true,
                        ],
                        'progress_status' => participant_section_in_progress::get_name(),
                        'availability_status' => participant_section_open::get_name(),
                        'is_overdue' => false,
                        'can_answer' => true,
                    ],
                ],
                'can_participate' => true,
            ],
        ];

        $this->assertCount(3, $subject['sections'], 'wrong sections count');
        $this->assertEquals($expected_sections, $subject['sections']);

        // Now run a query on others which should return empty result

        $args = [
            'filters' => [
                'about_role' => $manager_relationship->id
            ]
        ];

        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_successful($result);

        $actual = ($this->get_webapi_operation_data($result))['items'];
        $this->assertEmpty($actual, 'wrong subject count');

        // Now as the manager look at what I get back for others

        $this->setUser($user1);

        $args = [
            'filters' => [
                'about_role' => $manager_relationship->id
            ]
        ];

        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_successful($result);

        $actual = ($this->get_webapi_operation_data($result))['items'];
        $this->assertCount(1, $actual, 'wrong subject count');

        $subject = $actual[0];
        // The subject instance data should be the same as for the other user
        $this->assertEquals($expected_subject($subject_instance, $participant_instances, $user1->id), $subject['subject']);

        $expected_sections = [
            [
                'section' => [
                    'id' => $section3->id,
                    'display_title' => $section3->display_title,
                    'sort_order' => $section3->sort_order,
                ],
                'participant_sections' => [
                    [
                        'id' => $participant_section3_manager->id,
                        'participant_instance' => [
                            'progress_status' => participant_instance_not_started::get_name(),
                            'participant_id' => $manager_user->id,
                            'participant' => [
                                'fullname' => $manager_user->fullname,
                                'profileimageurlsmall' => $manager_user_profile_image_url,
                                'profileimagealt' => $manager_user->fullname,
                            ],
                            'core_relationship' => [
                                'id' => $manager_relationship->id,
                                'name' => $manager_relationship->get_name(),
                            ],
                            'is_for_current_user' => true,
                        ],
                        'progress_status' => participant_section_not_started::get_name(),
                        'availability_status' => participant_section_open::get_name(),
                        'is_overdue' => false,
                        'can_answer' => true,
                    ],
                ],
                'can_participate' => true,
            ],
            [
                'section' => [
                    'id' => $section1->id,
                    'display_title' => $section1->display_title,
                    'sort_order' => $section1->sort_order,
                ],
                'participant_sections' => [
                    [
                        'id' => $participant_section1_subject->id,
                        'participant_instance' => [
                            'progress_status' => participant_instance_in_progress::get_name(),
                            'participant_id' => $subject_user->id,
                            'participant' => [
                                'fullname' => $subject_user->fullname,
                                'profileimageurlsmall' => $subject_user_profile_image_url,
                                'profileimagealt' => $subject_user->fullname,
                            ],
                            'core_relationship' => [
                                'id' => $subject_relationship->id,
                                'name' => $subject_relationship->get_name(),
                            ],
                            'is_for_current_user' => false,
                        ],
                        'progress_status' => participant_section_not_started::get_name(),
                        'availability_status' => participant_section_open::get_name(),
                        'is_overdue' => false,
                        'can_answer' => true,
                    ],
                    [
                        'id' => $participant_section1_manager->id,
                        'participant_instance' => [
                            'progress_status' => participant_instance_not_started::get_name(),
                            'participant_id' => $manager_user->id,
                            'participant' => [
                                'fullname' => $manager_user->fullname,
                                'profileimageurlsmall' => $manager_user_profile_image_url,
                                'profileimagealt' => $manager_user->fullname,
                            ],
                            'core_relationship' => [
                                'id' => $manager_relationship->id,
                                'name' => $manager_relationship->get_name(),
                            ],
                            'is_for_current_user' => true,
                        ],
                        'progress_status' => participant_section_not_started::get_name(),
                        'availability_status' => participant_section_open::get_name(),
                        'is_overdue' => false,
                        'can_answer' => true,
                    ],
                ],
                'can_participate' => true,
            ],
            [
                'section' => [
                    'id' => $section2->id,
                    'display_title' => $section2->display_title,
                    'sort_order' => $section2->sort_order,
                ],
                'participant_sections' => [
                    [
                        'id' => $participant_section2_subject->id,
                        'participant_instance' => [
                            'progress_status' => participant_instance_in_progress::get_name(),
                            'participant_id' => $subject_user->id,
                            'participant' => [
                                'fullname' => $subject_user->fullname,
                                'profileimageurlsmall' => $subject_user_profile_image_url,
                                'profileimagealt' => $subject_user->fullname,
                            ],
                            'core_relationship' => [
                                'id' => $subject_relationship->id,
                                'name' => $subject_relationship->get_name(),
                            ],
                            'is_for_current_user' => false,
                        ],
                        'progress_status' => participant_section_in_progress::get_name(),
                        'availability_status' => participant_section_open::get_name(),
                        'is_overdue' => false,
                        'can_answer' => true,
                    ],
                ],
                'can_participate' => false,
            ],
        ];

        $this->assertCount(3, $subject['sections'], 'wrong sections count');
        $this->assertEquals($expected_sections, $subject['sections']);
    }

    public function test_when_manually_closed() {
        $this->setAdminUser();

        // Set up an activity with a single participant, the subject.
        $configuration = activity_generator_configuration::new()
            ->set_number_of_activities(1)
            ->set_number_of_sections_per_activity(1)
            ->set_relationships_per_section(['subject'])
            ->set_number_of_users_per_user_group_type(1)
            ->set_number_of_elements_per_section(0);

        /** @var perform_generator $generator */
        $generator = perform_generator::instance();
        $generator->create_full_activities($configuration);

        // Mark the subject instance manually close.
        /** @var subject_instance_entity $subject_instance */
        $subject_instance_entity = subject_instance_entity::repository()->get()->first();
        $subject_instance_model = subject_instance_model::load_by_entity($subject_instance_entity);
        $subject_instance_model->manually_close();

        // The query uses the current user, so set it to the subject/participant.
        self::setUser($subject_instance_model->subject_user_id);

        // Retrieve the data.
        $subject_relationship = $generator->get_core_relationship(constants::RELATIONSHIP_SUBJECT);
        $args = [
            'filters' => [
                'about_role' => $subject_relationship->id
            ]
        ];
        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_successful($result);

        // Check the progress status of the subject instance.
        $actual = ($this->get_webapi_operation_data($result))['items'];

        // Subject instance progress.
        $this->assertEquals(
            subject_instance_not_submitted::get_name(),
            $actual[0]['subject']['progress_status']
        );

        // Participant instance progress.
        $this->assertEquals(
            participant_instance_not_submitted::get_name(),
            $actual[0]['subject']['participant_instances'][0]['progress_status']
        );

        // Participant section progress.
        $this->assertEquals(
            participant_section_not_submitted::get_name(),
            $actual[0]['sections'][0]['participant_sections'][0]['participant_instance']['progress_status']
        );
    }

    public function test_query_invalid_filter(): void {
        $this->setAdminUser();

        $args = [
            'filters' => [
                'not_real_filter' => 1,
            ],
        ];

        $expected_error_message =
            'Variable "$filters" got invalid value {"not_real_filter":1}; ';
        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_failed($result, $expected_error_message);
    }

    // Basic check that the resolver processes the sort_by parameter. Sorting itself is tested in the data provider test.
    public function test_query_valid_sort_by(): void {
        self::setAdminUser();

        $subject_user = self::getDataGenerator()->create_user();

        $generator = perform_generator::instance();
        $generator->create_subject_instance([
            'activity_name' => 'AAA activity',
            'subject_user_id' => $subject_user->id,
            'subject_is_participating' => true,
        ]);
        $generator->create_subject_instance([
            'activity_name' => 'ZZZ activity',
            'subject_user_id' => $subject_user->id,
            'subject_is_participating' => true,
        ]);

        self::setUser($subject_user);

        // Without sort_by should default to "most recent first".
        $subject_relationship = $generator->get_core_relationship(constants::RELATIONSHIP_SUBJECT);
        $args = [
            'filters' => [
                'about_role' => $subject_relationship->id
            ],
            'options' => [
                'sort_by' => null,
            ],
        ];
        $result = $this->resolve_graphql_query(self::QUERY, $args)->items->all();
        /** @var subject_sections $subject_section_1 */
        $subject_section_1 = $result[0];
        self::assertEquals('ZZZ activity', $subject_section_1->get_subject_instance()->get_activity()->name);
        /** @var subject_sections $subject_section_2 */
        $subject_section_2 = $result[1];
        self::assertEquals('AAA activity', $subject_section_2->get_subject_instance()->get_activity()->name);

        // Sort by activity_name should reverse the order.
        $args = [
            'filters' => [
                'about_role' => $subject_relationship->id
            ],
            'options' => [
                'sort_by' => 'activity_name',
            ],
        ];
        $result = $this->resolve_graphql_query(self::QUERY, $args)->items->all();
        /** @var subject_sections $subject_section_1 */
        $subject_section_1 = $result[0];
        self::assertEquals('AAA activity', $subject_section_1->get_subject_instance()->get_activity()->name);
        /** @var subject_sections $subject_section_2 */
        $subject_section_2 = $result[1];
        self::assertEquals('ZZZ activity', $subject_section_2->get_subject_instance()->get_activity()->name);
    }

    public function test_query_invalid_sort_by(): void {
        self::setAdminUser();

        $manager_relationship = perform_generator::instance()->get_core_relationship(constants::RELATIONSHIP_MANAGER);
        $args = [
            'filters' => [
                'about_role' => $manager_relationship->id
            ],
            'options' => [
                'sort_by' => 'invalid',
            ],
        ];

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Sorting by 'invalid' is not supported");
        $this->resolve_graphql_query(self::QUERY, $args);
    }

    public function test_failed_ajax_query(): void {
        $manager_relationship = perform_generator::instance()->get_core_relationship(constants::RELATIONSHIP_MANAGER);
        $args = [
            'filters' => [
                'about_role' => $manager_relationship->id
            ]
        ];

        $feature = 'performance_activities';
        advanced_feature::disable($feature);
        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_failed($result, 'Feature performance_activities is not available.');
        advanced_feature::enable($feature);

        $this->setUser();
        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_failed($result, 'not logged in');
    }

    public function test_overdue(): void {
        $this->setAdminUser();

        $subject = self::getDataGenerator()->create_user();

        $generator = perform_generator::instance();
        $activity = $generator->create_activity_in_container();
        $entity = $generator->create_subject_instance([
            'activity_id' => $activity->id,
            'subject_is_participating' => true,
            'include_questions' => false,
            'subject_user_id' => $subject->id
        ]);

        $entity->due_date = (new DateTimeImmutable('-1 days'))->getTimestamp();
        $entity->save();

        $beginning_of_due_day = usergetmidnight($entity->due_date);
        $end_of_due_day = (new DateTimeImmutable("@$beginning_of_due_day"))
            ->setTime(23, 59, 59)
            ->getTimestamp();

        self::setUser($subject);
        $subject_relationship = $generator->get_core_relationship(constants::RELATIONSHIP_SUBJECT);
        $args = [
            'filters' => ['about_role' => $subject_relationship->id]
        ];
        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_successful($result);

        $items = ($this->get_webapi_operation_data($result))['items'];
        $this->assertCount(1, $items);

        $due_on = $items[0]['subject']['due_on'] ?? null;
        $this->assertNotNull($due_on);

        $date_formatter = new date_field_formatter(
            date_format::FORMAT_DATE,
            subject_instance_model::load_by_entity($entity)->get_context()
        );
        $this->assertEquals($date_formatter->format($end_of_due_day), $due_on['due_date']);

        $this->assertTrue($due_on['is_overdue']);
        $this->assertEquals(1, $due_on['units_to_due_date']);
        $this->assertEquals(get_string('day'), $due_on['units_to_due_date_type']);
    }
}

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
 * @package mod_perform
 */

use core\entity\user;
use mod_perform\entity\activity\subject_static_instance;
use totara_core\advanced_feature;
use totara_job\job_assignment;
use totara_webapi\phpunit\webapi_phpunit_helper;

require_once(__DIR__ . '/subject_instance_testcase.php');

/**
 * @coversDefaultClass \mod_perform\webapi\resolver\query\subject_instance_for_participant
 *
 * @group perform
 */
class mod_perform_webapi_resolver_query_subject_instance_for_participant_testcase extends mod_perform_subject_instance_testcase {
    private const QUERY = 'mod_perform_subject_instance_for_participant';

    use webapi_phpunit_helper;

    public function test_query_successful(): void {
        $args = [
            'subject_instance_id' => self::$about_user_and_participating->get_id()
        ];

        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_successful($result);
        $actual = $this->get_webapi_operation_data($result);

        $profile_image_small_url = (new user_picture(
            self::$about_user_and_participating->subject_user->get_user()->to_record(),
            0
        ))->get_url($GLOBALS['PAGE'])->out(false);

        $profile_image_url = (new user_picture(
            self::$about_user_and_participating->subject_user->get_user()->to_record(),
            1
        ))->get_url($GLOBALS['PAGE'])->out(false);

        $url = new moodle_url('/user/profile.php');
        $profile_url = $url->out();

        $expected = [
            'id' => (string) self::$about_user_and_participating->id,
            'progress_status' => self::$about_user_and_participating->get_progress_status(),
            'instance_count' => 1,
            'job_assignment' => null,
            'due_on' => null,
            'activity' => [
                'id' => self::$about_user_and_participating->get_activity()->id,
                'name' => self::$about_user_and_participating->get_activity()->name,
                'type' => [
                    'display_name' => self::$about_user_and_participating->get_activity()->type->display_name,
                ],
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
                'id' => self::$about_user_and_participating->subject_user->id,
                'fullname' => self::$about_user_and_participating->subject_user->fullname,
                'profileimageurlsmall' => $profile_image_small_url,
                'profileimagealt' => self::$about_user_and_participating->subject_user->fullname,
                'card_display' => [
                    'profile_picture_alt' => self::$about_user_and_participating->subject_user->fullname,
                    'profile_url' => $profile_url,
                    'profile_picture_url' => $profile_image_url,
                    'display_fields' => [
                        [
                            'associate_url' => $profile_url,
                            'value' => self::$about_user_and_participating->subject_user->fullname,
                            'label' => 'Full name',
                            'is_custom' => false,
                        ],
                        [
                            'associate_url' => null,
                            'value' => '',
                            'label' => 'Department',
                            'is_custom' => false,
                        ],
                        [
                            'associate_url' => null,
                            'value' => null,
                            'label' => null,
                            'is_custom' => false,
                        ],
                        [
                            'associate_url' => null,
                            'value' => null,
                            'label' => null,
                            'is_custom' => false,
                        ],
                    ],
                ],
            ],
            'static_instances' => [],
        ];

        self::assertEquals($expected, $this->strip_expected_dates($actual));
    }

    public function delete_or_suspend_data_provider(): array {
        return [
            'Deleted user should be hidden' => [
                static function (user $user) {
                    delete_user($user->to_record());
                }
            ],
            'Suspended user should be hidden with "hide_suspended_users" switched on' => [
                static function (user $user) {
                    set_config('perform_hide_suspended_users', 1);
                    user_suspend_user($user->id);
                }
            ],
            'Suspended user should NOT be hidden with "hide_suspended_users" switched off' => [
                static function (user $user) {
                    set_config('perform_hide_suspended_users', 0);
                    user_suspend_user($user->id);
                },
                false
            ],
            'The setting "perform_close_suspended_user_instances" should not have any effect' => [
                static function (user $user) {
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
    public function test_subject_got_deleted_or_suspended(closure $delete_or_suspend, bool $should_be_hidden = true): void {
        $subject_instance = self::$about_someone_else_and_participating;

        $args = [
            'subject_instance_id' => $subject_instance->id,
        ];

        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_successful($result);
        $actual = $this->get_webapi_operation_data($result);

        self::assertNotEmpty($actual);

        $subject_user = $subject_instance->subject_user->get_user();

        $delete_or_suspend($subject_user);

        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_successful($result);
        $actual = $this->get_webapi_operation_data($result);

        if ($should_be_hidden) {
            self::assertEmpty($actual);
        } else {
            self::assertNotEmpty($actual);
        }
    }

    public function test_subject_static_instance_manager_is_resolved(): void {
        self::setAdminUser();

        /** @var \mod_perform\testing\generator $generator */
        $generator = \mod_perform\testing\generator::instance();

        $subject_user = self::getDataGenerator()->create_user();
        $manager_user = self::getDataGenerator()->create_user();

        $subject_instance = $generator->create_subject_instance([
            'subject_is_participating' => true,
            'subject_user_id' => $subject_user->id,
            'other_participant_id' => null,
            'include_questions' => false,
        ]);

        $manager_ja = job_assignment::create([
            'userid' => $manager_user->id,
            'fullname' => 'manager_user_ja',
            'shortname' => 'manager_user_ja',
            'idnumber' => 'manager_user_ja',
            'managerjaid' => null,
        ]);

        $main_user_ja = job_assignment::create([
            'userid' => $manager_user->id,
            'fullname' => 'main_user_ja',
            'shortname' => 'main_user_ja',
            'idnumber' => 'main_user_ja',
            'managerjaid' => $manager_ja->id,
        ]);

        $static_instance = new subject_static_instance();
        $static_instance->subject_instance_id = $subject_instance->id;
        $static_instance->job_assignment_id = $main_user_ja->id;
        $static_instance->manager_job_assignment_id = $manager_ja->id;
        $static_instance->save();

        self::setUser($subject_user->id);

        $args = [
            'subject_instance_id' => $subject_instance->id,
        ];

        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_successful($result);
        $actual = $this->get_webapi_operation_data($result);

        self::assertCount(1, $actual['static_instances']);

        $actual_manager_ja = $actual['static_instances'][0]['managerja'];
        self::assertEquals($actual_manager_ja['id'], $manager_ja->id);
        self::assertEquals($actual_manager_ja['fullname'], $manager_ja->fullname);

        // Event should fire to null out the manager_ja reference.
        job_assignment::delete($manager_ja);

        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_successful($result);
        $actual = $this->get_webapi_operation_data($result);
        self::assertNull($actual['static_instances'][0]['managerja']);

        // Event should fire to delete the subject_static_instance record.
        $main_user_ja = job_assignment::get_with_id($main_user_ja->id); // Refresh manager reference.
        job_assignment::delete($main_user_ja);

        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_successful($result);
        $actual = $this->get_webapi_operation_data($result);
        self::assertEmpty($actual['static_instances']);
    }

    public function test_get_as_participation_manager(): void {
        $subject_instance = self::$about_user_but_not_participating;
        $args = ['subject_instance_id' => $subject_instance->id];

        $manager = self::getDataGenerator()->create_user();
        $employee = self::$about_user_but_not_participating->subject_user;

        self::setUser($manager);

        $context = $this->create_webapi_context(self::QUERY);
        $context->set_relevant_context($subject_instance->get_context());

        $returned_subject_instance = $this->resolve_graphql_query(self::QUERY, $args);
        self::assertNull($returned_subject_instance);

        $this->setup_manager_employee_job_assignment($manager, $employee);

        $returned_subject_instance = $this->resolve_graphql_query(self::QUERY, $args);
        $this->assertEquals(self::$about_user_but_not_participating->id, $returned_subject_instance->id);
    }

    public function test_get_as_reporting_user(): void {
        $subject_instance = self::$about_user_but_not_participating;
        $args = ['subject_instance_id' => $subject_instance->id];

        $reporter = self::getDataGenerator()->create_user();
        $employee = self::$about_user_but_not_participating->subject_user;

        self::setUser($reporter);

        $context = $this->create_webapi_context(self::QUERY);
        $context->set_relevant_context($subject_instance->get_context());

        $returned_subject_instance = $this->resolve_graphql_query(self::QUERY, $args);
        self::assertNull($returned_subject_instance);

        // Grant the reporting capability to the reporter in the context of the employee.
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability('mod/perform:report_on_subject_responses', CAP_ALLOW, $roleid, context_system::instance());
        $emplyee_context = \context_user::instance($employee->id);
        role_assign($roleid, $reporter->id, $emplyee_context);

        $returned_subject_instance = $this->resolve_graphql_query(self::QUERY, $args);
        $this->assertEquals(self::$about_user_but_not_participating->id, $returned_subject_instance->id);
    }

    public function test_failed_ajax_query(): void {
        $args = [
            'subject_instance_id' => self::$about_user_and_participating->get_id()
        ];

        $feature = 'performance_activities';
        advanced_feature::disable($feature);
        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_failed($result, 'Feature performance_activities is not available.');
        advanced_feature::enable($feature);

        $result = $this->parsed_graphql_operation(self::QUERY, []);
        $this->assert_webapi_operation_failed($result, 'subject_instance_id');

        $result = $this->parsed_graphql_operation(self::QUERY, ['subject_instance_id' => 0]);
        $this->assert_webapi_operation_failed($result, 'Argument "subject_instance_id" of non-null type "core_id!" must not be null.');

        $this->setUser();
        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_failed($result, 'not logged in');
    }

}
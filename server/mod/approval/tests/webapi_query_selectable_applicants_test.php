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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_approval
 */

use core_phpunit\testcase;
use core\orm\query\builder;
use container_approval\approval as approval_container;
use mod_approval\testing\approval_workflow_test_setup;
use mod_approval\model\workflow\workflow;
use totara_job\job_assignment;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @group approval_workflow
 */
class mod_approval_webapi_query_selectable_applicants_testcase extends testcase {

    private const QUERY = 'mod_approval_selectable_applicants';

    use webapi_phpunit_helper;
    use approval_workflow_test_setup;

    public function test_query_without_login() {
        $data = $this->generate_data();
        $args['input'] = [
            'workflow_id' => $data['workflow']->id,
            'pagination' => [
                'limit' => 5,
                'cursor' => null,
            ],
        ];
        $this->setUser(0);
        try {
            $this->resolve_graphql_query(self::QUERY, $args);
            $this->fail('Expected exception not thrown');
        } catch (require_login_exception $e) {
            $this->assertStringContainsString('You are not logged in', $e->getMessage());
        }

        // test as guest.
        $this->setGuestUser();
        $this->expectException('require_login_exception');
        $this->resolve_graphql_query(self::QUERY, $args);
    }
    public function test_query_as_guest() {
        $data = $this->generate_data();
        $args['input'] = [
            'workflow_id' => $data['workflow']->id,
            'pagination' => [
                'limit' => 5,
                'cursor' => null,
            ],
        ];

        $this->setGuestUser();
        $this->expectException('require_login_exception');
        $this->resolve_graphql_query(self::QUERY, $args);
    }

    public function test_ajax_query_failed() {
        $data = $this->generate_data();
        $args['input'] = [
            'workflow_id' => $data['workflow']->id,
            'pagination' => [
                'limit' => 5,
                'cursor' => null,
            ],
        ];
        $this->setUser(0);
        $this->expectException('require_login_exception');
        $this->resolve_graphql_query(self::QUERY, $args);
    }

    public function test_with_workflow_id_limits_to_users_in_assignment() {
        $data = $this->generate_data();

        $args['input'] = [
            'workflow_id' => $data['workflow']->id,
            'pagination' => [
                'limit' => 10,
                'cursor' => null,
            ],
        ];
        $this->setAdminUser();
        $result = $this->resolve_graphql_query(self::QUERY, $args);
        $this->assertCount(2, $result['items']);
    }

    public function test_without_workflow_id_limits_to_all_users() {
        $this->generate_data();

        $args['input'] = [
            'pagination' => [
                'limit' => 10,
                'cursor' => null,
            ],
        ];
        $this->setAdminUser();
        $result = $this->resolve_graphql_query(self::QUERY, $args);
        $this->assertCount(5, $result['items']);
    }

    public function test_capability_required_to_query_selectable_applicants() {
        global $CFG;
        require_once($CFG->libdir . '/coursecatlib.php');

        $context = approval_container::get_default_category_context();
        $workflow_manager_user = self::getDataGenerator()->create_user();
        $workflow_manager_role = builder::table('role')->where('shortname', 'approvalworkflowmanager')->one();
        role_assign($workflow_manager_role->id, $workflow_manager_user->id, $context);

        $data = $this->generate_data();
        $args['input'] = [
            'workflow_id' => $data['workflow']->id,
            'pagination' => [
                'limit' => 5,
                'cursor' => null,
            ],
        ];
        $use_cases = [
            [
                'capabilities' => [
                    'mod/approval:create_application_any' => CAP_PREVENT,
                    'mod/approval:create_application_user' => CAP_PREVENT,
                ],
                'can_query' => false,
            ],
            [
                'capabilities' => [
                    'mod/approval:create_application_any' => CAP_ALLOW,
                    'mod/approval:create_application_user' => CAP_PREVENT,
                ],
                'can_query' => true,
            ],
            [
                'capabilities' => [
                    'mod/approval:create_application_any' => CAP_PREVENT,
                    'mod/approval:create_application_user' => CAP_ALLOW,
                ],
                'can_query' => true,
            ],
            [
                'capabilities' => [
                    'mod/approval:create_application_any' => CAP_ALLOW,
                    'mod/approval:create_application_user' => CAP_ALLOW,
                ],
                'can_query' => true,
            ],
        ];

        $this->setUser($workflow_manager_user);
        foreach ($use_cases as $use_case) {
            foreach ($use_case['capabilities'] as $capability => $permission) {
                assign_capability($capability, $permission, $workflow_manager_role->id, $context, true);
            }

            $result = $this->parsed_graphql_operation(self::QUERY, $args);
            $use_case['can_query']
                ? $this->assert_webapi_operation_successful($result)
                : $this->assert_webapi_operation_failed($result, 'Cannot create applications on behalf');
        }
    }

    private function generate_data(): array {
        list($workflow, $framework) = $this->create_workflow_and_assignment();

        // generate site-wide users.
        $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->create_user();

        // generate users assigned to organisation.
        $this->create_users_with_job_assignment_in_organisation(2, $framework->agency->id);

        return [
            'workflow' => workflow::load_by_entity($workflow),
        ];
    }

    private function create_users_with_job_assignment_in_organisation(int $number_of_users, int $organisation_id) {
        $users = [];
        for ($i = 0; $i < $number_of_users; $i++) {
            $users[] = $this->getDataGenerator()->create_user();
        }

        foreach ($users as $user) {
            job_assignment::create_default(
                $user->id,
                [
                    'organisationid' => $organisation_id,
                ]
            );
        }
    }
}

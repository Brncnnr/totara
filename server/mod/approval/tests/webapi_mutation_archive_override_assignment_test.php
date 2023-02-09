<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 */

use core_phpunit\testcase;
use mod_approval\exception\access_denied_exception;
use mod_approval\exception\model_exception;
use mod_approval\model\assignment\assignment;
use mod_approval\model\assignment\assignment_type\organisation as organisation;
use mod_approval\model\workflow\workflow;
use mod_approval\testing\generator as approval_generator;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @group approval_workflow
 */
class mod_approval_webapi_mutation_archive_override_assignment_testcase extends testcase {

    private const MUTATION = 'mod_approval_archive_override_assignment';

    use webapi_phpunit_helper;

    public function test_execute_query_successful() {
        $this->setAdminUser();
        $data = $this->generate_data();
        $args = [
            'input' => [
                'assignment_id' => $data['override']->id,
            ]
        ];
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_successful($result);
        $result = $this->get_webapi_operation_data($result);
        $this->assertTrue($result['success']);
    }

    public function test_archive_default_assignment() {
        $this->setAdminUser();
        $data = $this->generate_data();
        $args = [
            'input' => [
                'assignment_id' => $data['default']->id,
            ]
        ];

        $this->expectException(model_exception::class);
        $this->expectExceptionMessage('Can not archive default assignment');
        $this->resolve_graphql_mutation(self::MUTATION, $args);
    }

    public function test_archive_without_manage_workflow_assignment_override_capability() {
        $this->setAdminUser();
        $data = $this->generate_data();

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user->id);
        $args = [
            'input' => [
                'assignment_id' => $data['override']->id,
            ]
        ];

        $this->expectException(access_denied_exception::class);
        $this->expectExceptionMessage('Cannot archive assignment for the given workflow');
        $this->resolve_graphql_mutation(self::MUTATION, $args);
    }

    public function test_archive_without_logged_in_user() {
        $data = $this->generate_data();
        $args = [
            'input' => [
                'assignment_id' => $data['default']->id,
            ]
        ];

        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed($result);
    }

    /**
     * Generates the setup used by these tests.
     *
     * @return array of workflow (model), organisation framework, workflow_stage ID
     */
    private function generate_data(): array {
        $workflow_entity = approval_generator::instance()->create_simple_request_workflow('Simple', 'Default Simple Workflow');
        $workflow = workflow::load_by_entity($workflow_entity);

        // Generate a simple organisation hierarchy
        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $framework = $hierarchy_generator->create_framework('organisation');
        $framework->agency = $hierarchy_generator->create_org(
            [
                'frameworkid' => $framework->id,
                'fullname' => 'Agency',
                'idnumber' => '001',
                'shortname' => 'org'
            ]
        );
        $framework->other_agency = $hierarchy_generator->create_org(
            [
                'frameworkid' => $framework->id,
                'fullname' => 'Other Agency',
                'idnumber' => '002',
                'shortname' => 'other_org'
            ]
        );

        $default_assignment = assignment::create($workflow->course_id, organisation::get_code(), $framework->agency->id, true);
        $override_assignment = assignment::create($workflow->course_id, organisation::get_code(), $framework->other_agency->id, false);

        return [
            'default' => $default_assignment,
            'override' => $override_assignment,
        ];
    }
}

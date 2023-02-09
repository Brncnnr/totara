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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 */

use core_phpunit\testcase;
use mod_approval\entity\workflow\workflow_type;
use mod_approval\testing\approval_workflow_test_setup;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @group approval_workflow
 */
class mod_approval_webapi_query_load_workflow_types_testcase extends testcase {

    use approval_workflow_test_setup;
    use webapi_phpunit_helper;

    private const QUERY = 'mod_approval_load_workflow_types';

    public function test_query_requires_logged_in_user() {
        $this->generate_data();
        $this->setGuestUser();
        $this->expectException(require_login_exception::class);
        $this->resolve_graphql_query(self::QUERY, $this->get_args());
    }

    public function test_query_as_user() {
        $this->generate_data();
        $user1 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);

        $result = $this->parsed_graphql_operation(self::QUERY, $this->get_args());
        $this->assert_webapi_operation_successful($result);
        $query_data = reset($result);
        $workflow_types = $query_data['workflow_types'];

        $this->assertCount(2, $workflow_types);
        $start = 6;
        foreach ($workflow_types as $workflow_type) {
            $this->assertEquals("Workflow $start", $workflow_type['label']);
            $start++;
        }
    }

    public function test_query_success() {
        $this->generate_data();
        $this->setAdminUser();

        $result = $this->parsed_graphql_operation(self::QUERY, $this->get_args());
        $this->assert_webapi_operation_successful($result);
        $query_data = reset($result);
        $workflow_types = $query_data['workflow_types'];

        $this->assertCount(7, $workflow_types);
        $start = 1;
        foreach ($workflow_types as $workflow_type) {
            $this->assertEquals("Workflow $start", $workflow_type['label']);
            $start++;
        }
    }

    public function test_query_failure() {
        $this->generate_data();
        $this->setGuestUser();

        $result = $this->parsed_graphql_operation(self::QUERY, $this->get_args());
        $this->assert_webapi_operation_failed($result);
    }

    private function generate_data(): void {
        // Create 5 empty workflow_types (no workflows)
        for ($i = 1; $i <= 5; $i++) {
            $workflow_type = new workflow_type();
            $workflow_type->name = "Workflow $i";
            $workflow_type->active = true;
            $workflow_type->save();
        }
        // Create 2 more with active workflows
        for ($i = 6; $i <= 7; $i++) {
            $workflow = $this->create_workflow_and_assignment("Workflow $i");
        }
    }

    private function get_args(): array {
        return [
            'input' => [
                'require_active_workflow' => false,
            ],
        ];
    }
}

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
 * @package mod_approval
 */

use container_approval\approval as approval_container;
use container_approval\approval as container_approval;
use core\entity\user as user_entity;
use core\orm\query\builder;
use core_phpunit\testcase;
use mod_approval\controllers\workflow\dashboard;
use mod_approval\exception\access_denied_exception;
use mod_approval\model\assignment\assignment_type;
use mod_approval\model\status;
use mod_approval\testing\approval_workflow_test_setup;

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\controllers\workflow\dashboard
 */
class mod_approval_controller_manage_approval_workflow_testcase extends testcase {
    use approval_workflow_test_setup;

    /**
     * Gets the approval workflow generator instance
     *
     * @return \mod_approval\testing\generator
     */
    protected function generator(): \mod_approval\testing\generator {
        return \mod_approval\testing\generator::instance();
    }

    public function test_context(): void {
        $this->setAdminUser();
        self::assertSame(approval_container::get_default_category_context(), (new dashboard())->get_context());
    }

    public function test_manage_approval_workflow(): void {
        $this->setAdminUser();
        $dashboard = (new dashboard())->action();
        //
        self::assertEquals(
            [
                'context-id' => approval_container::get_default_category_context()->id,
                'can-create-workflow' => true,
                'filter-options' => [
                    'assignment_types' => [
                        [
                            'label' => 'All',
                            'enum' => null
                        ],
                        [
                            'label' => get_string('model_assignment_type_organisation', 'mod_approval'),
                            'enum' => strtoupper(assignment_type\organisation::get_enum()),
                        ],
                        [
                            'label' => get_string('model_assignment_type_position', 'mod_approval'),
                            'enum' => strtoupper(assignment_type\position::get_enum()),
                        ],
                        [
                            'label' => get_string('model_assignment_type_cohort', 'mod_approval'),
                            'enum' => strtoupper(assignment_type\cohort::get_enum()),
                        ],
                    ],
                    'status' => status::get_list(),
                    'workflow_types' => [],
                ],
            ],
            $dashboard->get_data()
        );

        $user = new user_entity($this->getDataGenerator()->create_user()->id);
        $this->setUser($user);
        try {
            (new dashboard())->action();
            $this->fail("Permission check should have failed but didn't");
        } catch (access_denied_exception $exception) {
            $this->assertEquals('Access denied to manage the workflows', $exception->getMessage());
        }

        // Check can-create-workflow when the user has the capability.
        $user_role = builder::table('role')->where('shortname', 'user')->one();
        assign_capability('mod/approval:create_workflow', CAP_ALLOW, $user_role->id, context_system::instance(), true);
        assign_capability('mod/approval:manage_workflows', CAP_ALLOW, $user_role->id, container_approval::get_default_category_context(), true);
        $dashboard = (new dashboard())->action();
        self::assertEquals(
            [
                'context-id' => approval_container::get_default_category_context()->id,
                'can-create-workflow' => true,
                'filter-options' => [
                    'assignment_types' => [
                        [
                            'label' => 'All',
                            'enum' => null
                        ],
                        [
                            'label' => get_string('model_assignment_type_organisation', 'mod_approval'),
                            'enum' => strtoupper(assignment_type\organisation::get_enum()),
                        ],
                        [
                            'label' => get_string('model_assignment_type_position', 'mod_approval'),
                            'enum' => strtoupper(assignment_type\position::get_enum()),
                        ],
                        [
                            'label' => get_string('model_assignment_type_cohort', 'mod_approval'),
                            'enum' => strtoupper(assignment_type\cohort::get_enum()),
                        ],
                    ],
                    'status' => status::get_list(),
                    'workflow_types' => [],
                ],
            ],
            $dashboard->get_data()
        );
    }
}

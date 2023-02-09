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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 */

use core\orm\query\builder;
use mod_approval\exception\access_denied_exception;
use mod_approval\model\assignment\assignment_type;
use mod_approval\model\form\form;
use mod_approval\model\form\form_version;
use mod_approval\model\workflow\stage_type\approvals;
use mod_approval\model\workflow\stage_type\form_submission;
use mod_approval\model\workflow\workflow;
use mod_approval\model\workflow\workflow_stage;
use mod_approval\model\workflow\workflow_stage_interaction;
use mod_approval\model\workflow\workflow_type;
use mod_approval\testing\approval_workflow_test_setup;
use totara_webapi\phpunit\webapi_phpunit_helper;

require_once(__DIR__ . '/testcase.php');

/**
 * @group approval_workflow
 * @covers \mod_approval\webapi\resolver\mutation\workflow_stage_interaction_configure_transition
*/
class mod_approval_webapi_mutation_workflow_stage_interaction_configure_transition_testcase extends mod_approval_testcase {

    use webapi_phpunit_helper;
    use approval_workflow_test_setup;

    private const MUTATION = 'mod_approval_workflow_stage_interaction_configure_transition';

    /**
     * @var workflow_stage_interaction
     */
    private $workflow_stage_interaction;

    public function setUp(): void {
        $this->setAdminUser();
        $form = form::create('simple', 'test form');
        $json_schema = file_get_contents(__DIR__ . "/fixtures/schema/formview_management.json");
        form_version::create($form, 'test form version', $json_schema);
        $workflow = workflow::create(
            workflow_type::create('test workflow type'),
            $form,
            'Test workflow',
            '',
            assignment_type\cohort::get_code(),
            $this->getDataGenerator()->create_cohort()->id
        );

        workflow_stage::create($workflow->latest_version, 'Start', form_submission::get_enum());
        $stage2 = workflow_stage::create($workflow->latest_version, 'Next', approvals::get_enum());
        $this->workflow_stage_interaction = $stage2->interactions->first();
        parent::setUp();
    }

    protected function tearDown(): void {
        $this->workflow_stage_interaction = null;
        $this->next_stage = null;
        parent::tearDown();
    }

    private function get_default_transition_args(string $transition = 'previous'): array {
        return [
            'input' => [
                'workflow_stage_interaction_id' => $this->workflow_stage_interaction->id,
                'workflow_stage_interaction_transition_id' => $this->workflow_stage_interaction->default_transition->id,
                'transition' => $transition
            ]
        ];
    }

    private function grant_manage_transitions_capability(int $user_id) {
        $site_manager_role = builder::table('role')->where('shortname', 'manager')->one();
        role_assign($site_manager_role->id, $user_id, context_system::instance());
    }

    public function test_requires_logged_in_user() {
        $this->setGuestUser();
        $this->expectException(require_login_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            $this->get_default_transition_args()
        );
    }

    public function test_requires_has_manage_transitions_capability() {
        $user = $this->create_user();
        $this->setUser($user);
        $this->expectException(access_denied_exception::class);
        $this->resolve_graphql_mutation(
            self::MUTATION,
            $this->get_default_transition_args()
        );
    }

    public function test_without_interaction_id() {
        $user = $this->create_user();
        $this->setUser($user);
        $parsed_mutation = $this->parsed_graphql_operation(self::MUTATION, []);
        $this->assert_webapi_operation_failed($parsed_mutation);
    }

    public function test_success() {
        $user = $this->create_user();
        $this->setUser($user);
        $this->grant_manage_transitions_capability($user->id);

        $original_transition = $this->workflow_stage_interaction->default_transition;

        $result = $this->parsed_graphql_operation(
            self::MUTATION,
            $this->get_default_transition_args()
        );

        $this->assert_webapi_operation_successful($result);
        $result = $this->get_webapi_operation_data($result);
        $this->assertNotEmpty($result['transition']);
        $result_transition = $result['transition'];
        $this->assertEquals($original_transition->id, $result_transition['id']);
        $this->assertEquals('PREVIOUS', $result_transition['transition']);
        $this->assertEquals('mod_approval_workflow_stage_interaction_transition', $result_transition['__typename']);
    }
}


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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_approval
 */

use core\date_format;
use core\format;
use mod_approval\entity\workflow\workflow as workflow_entity;
use mod_approval\entity\assignment\assignment as assignment_entity;
use mod_approval\model\application\application;
use mod_approval\model\application\application_state;
use mod_approval\model\assignment\approver_type\user;
use mod_approval\model\assignment\assignment_type;
use mod_approval\model\workflow\workflow_version;
use mod_approval\testing\application_generator_object;
use mod_approval\testing\assignment_generator_object;
use mod_approval\testing\approval_workflow_test_setup;
use mod_approval\testing\assignment_approver_generator_object;
use totara_webapi\phpunit\webapi_phpunit_helper;

require_once(__DIR__ . '/testcase.php');

/**
 * @coversDefaultClass \mod_approval\webapi\resolver\type\application
 *
 * @group approval_workflow
 */
class mod_approval_webapi_type_application_testcase extends mod_approval_testcase {

    use webapi_phpunit_helper;
    use approval_workflow_test_setup;

    private const TYPE = 'mod_approval_application';

    /**
     * Gets the approval workflow generator instance
     *
     * @return \mod_approval\testing\generator
     */
    protected function generator(): \mod_approval\testing\generator {
        return \mod_approval\testing\generator::instance();
    }

    /**
     * @covers ::resolve
     */
    public function test_invalid_input(): void {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Expected application model');

        $this->resolve_graphql_type(self::TYPE, 'id', new stdClass());
    }

    /**
     * @covers ::resolve
     */
    public function test_invalid_field(): void {
        list($workflow, , $assignment) = $this->create_workflow_and_assignment();
        $user1 = $this->getDataGenerator()->create_user();
        $application = $this->create_application($workflow, $assignment, $user1);
        $cm = get_coursemodule_from_instance('approval', $assignment->id);
        $context = context_module::instance($cm->id);

        $field = 'unknown';

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessageMatches("/$field/");

        $this->resolve_graphql_type(self::TYPE, $field, $application, [], $context);
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve(): void {
        list($workflow, , $assignment) = $this->create_workflow_and_assignment();
        $workflow_version = workflow_version::load_latest_by_workflow_id($workflow->id);
        $approver = $this->getDataGenerator()->create_user(['firstname' => 'Sammy', 'lastname' => 'Sam', 'middlename' => '']);
        $stage_1 = $workflow_version->stages->first();
        $stage_2 = $workflow_version->get_next_stage($stage_1->id);
        $this->generator()->create_assignment_approver(
            new assignment_approver_generator_object(
                $assignment->id,
                $stage_2->approval_levels->first()->id,
                user::TYPE_IDENTIFIER,
                $approver->id
            )
        );

        $user1 = $this->getDataGenerator()->create_user(['firstname' => 'Bobby', 'lastname' => 'Bob', 'middlename' => '']);
        $application = $this->create_application($workflow, $assignment, $user1);
        $context = $application->get_context();

        $value = $this->resolve_graphql_type(self::TYPE, 'id', $application, [], $context);
        $this->assertEquals($application->id, $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'workflow_type', $application, [], $context);
        $this->assertEquals($workflow->workflow_type->name, $value);

        $value = $this->resolve_graphql_type(
            self::TYPE,
            'created',
            $application,
            ['format' => date_format::FORMAT_TIMESTAMP],
            $context
        );
        $this->assertEquals($application->created, $value);

        $value = $this->resolve_graphql_type(
            self::TYPE,
            'submitted',
            $application,
            ['format' => date_format::FORMAT_TIMESTAMP],
            $context
        );
        $this->assertEquals($application->submitted, $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'overall_progress', $application, [], $context);
        $this->assertEquals('DRAFT', $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'overall_progress_label', $application, [], $context);
        $this->assertEquals('Draft', $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'title', $application, ['format' => format::FORMAT_PLAIN], $context);
        $this->assertEquals($application->title, $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'user', $application, [], $context);
        $this->assertEquals('Bobby Bob', $value->fullname);

        $value = $this->resolve_graphql_type(self::TYPE, 'current_state', $application, [], $context);
        $this->assertInstanceOf(application_state::class, $value);
        $this->assertNull($value->get_approval_level_id());

        $value = $this->resolve_graphql_type(self::TYPE, 'approver_users', $application, [], $context);
        $this->assertCount(0, $value);

        $application->set_current_state(new application_state(
            $stage_2->id,
            false,
            $stage_2->approval_levels->first()->id
        ));
        $value = $this->resolve_graphql_type(self::TYPE, 'approver_users', $application, [], $context);
        $this->assertCount(1, $value);
        $this->assertArrayHasKey($approver->id, $value);
    }

    /**
     * Creates a workflow, and organization framework, and an assignment for the top-level organization.
     *
     * @return array
     */
    private function create_workflow_and_assignment(): array {
        $this->setAdminUser();

        $workflow = $this->generator()->create_simple_request_workflow();

        $framework = $this->generate_org_hierarchy();

        $assignment_go = new assignment_generator_object(
            $workflow->course_id,
            assignment_type\organisation::get_code(),
            $framework->agency->id
        );
        $assignment_go->is_default = true;
        $assignment = $this->generator()->create_assignment($assignment_go);

        return [$workflow, $framework, $assignment];
    }

    /**
     * Generates a test application for a user
     *
     * @param workflow_entity $workflow
     * @param assignment_entity $assignment
     * @param stdClass $applicant
     * @return application
     */
    private function create_application(
        workflow_entity $workflow,
        assignment_entity $assignment,
        stdClass $applicant
    ): application {
        $workflow_version = $workflow->versions()->one();
        $application_go = new application_generator_object(
            $workflow_version->id,
            $workflow->form->versions()->one()->id,
            $assignment->id
        );
        $application_go->user_id = $applicant->id;
        $application_entity = $this->generator()->create_application($application_go);
        return new application($application_entity);
    }
}

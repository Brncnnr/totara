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

use container_approval\approval as workflow_container;
use core\webapi\execution_context;
use core_phpunit\testcase;
use mod_approval\model\assignment\assignment_type\organisation as organisation_assignment_type;
use mod_approval\testing\assignment_generator_object;
use mod_approval\testing\approval_workflow_test_setup;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @coversDefaultClass \mod_approval\webapi\resolver\type\new_application_menu_item
 *
 * @group approval_workflow
 */
class mod_approval_webapi_type_new_application_menu_item_testcase extends testcase {

    use webapi_phpunit_helper;
    use approval_workflow_test_setup;

    private const TYPE = 'mod_approval_new_application_menu_item';

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
        $context = workflow_container::get_default_category_context();
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessageMatches("/new_application_menu_item/");

        $this->resolve_graphql_type(self::TYPE, 'assignment_id', new stdClass(), [], $context);
    }

    /**
     * @covers ::resolve
     */
    public function test_invalid_field(): void {
        list($workflow, $framework, $assignment) = $this->create_workflow_and_assignment();
        $cm = get_coursemodule_from_instance('approval', $assignment->id);
        $context = context_module::instance($cm->id);

        // Add a user and assign to program_a
        $user1 = $this->getDataGenerator()->create_user();

        $ja = \totara_job\job_assignment::create([
            'userid' => $user1->id,
            'idnumber' => '001',
            'organisationid' => $framework->agency->subagency_a->program_a->id,
            'fullname' => 'Test Job Assignment'
        ]);
        $new_application_menu_item = new \mod_approval\webapi\schema_object\new_application_menu_item($assignment->id, $workflow->workflow_type->name, $ja->fullname);

        $field = 'unknown';

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessageMatches("/$field/");

        $this->resolve_graphql_type(self::TYPE, $field, $new_application_menu_item, [], $context);
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve(): void {
        list($workflow, $framework, $assignment) = $this->create_workflow_and_assignment();
        $cm = get_coursemodule_from_instance('approval', $assignment->id);
        $context = context_module::instance($cm->id);

        // Add a user and assign to program_a
        $user1 = $this->getDataGenerator()->create_user();

        $ja = \totara_job\job_assignment::create([
            'userid' => $user1->id,
            'idnumber' => '001',
            'organisationid' => $framework->agency->subagency_a->program_a->id,
            'fullname' => 'Test Job Assignment'
        ]);

        $testcases = [
            'assignment_id' => ['assignment_id', null, $assignment->id],
            'workflow_type' => ['workflow_type', null, $workflow->workflow_type->name],
            'job_assignment' => ['job_assignment', null, $ja->fullname],
        ];

        $new_application_menu_item = new \mod_approval\webapi\schema_object\new_application_menu_item($assignment->id, $workflow->workflow_type->name, $ja->fullname);

        foreach ($testcases as $id => $testcase) {
            [$field, $format, $expected] = $testcase;
            $args = $format ? ['format' => $format] : [];

            $value = $this->resolve_graphql_type(self::TYPE, $field, $new_application_menu_item, $args, $context);
            $this->assertEquals($expected, $value, "[$id] wrong value");
        }
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

        $assignment_go = new assignment_generator_object($workflow->course_id, organisation_assignment_type::get_code(), $framework->agency->id);
        $assignment_go->is_default = true;
        $assignment = $this->generator()->create_assignment($assignment_go);

        return [$workflow, $framework, $assignment];
    }

    /**
     * Creates a graphql execution context.
     *
     * @param context totara context to pass to the execution context.
     *
     * @return execution_context the context.
     */
    private function get_webapi_context(context $context): execution_context {
        $ec = execution_context::create('dev', null);
        $ec->set_relevant_context($context);

        return $ec;
    }
}

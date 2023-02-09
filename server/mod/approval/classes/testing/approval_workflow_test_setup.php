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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\testing;

use core\entity\user as user_entity;
use core\testing\generator;
use mod_approval\data_provider\application\capability_map\capability_map_controller;
use mod_approval\data_provider\application\role_map\role_map_controller;
use mod_approval\entity\assignment\assignment as assignment_entity;
use mod_approval\entity\workflow\workflow as workflow_entity;
use mod_approval\entity\workflow\workflow_stage as workflow_stage_entity;
use mod_approval\model\application\action\submit;
use mod_approval\model\application\application;
use mod_approval\model\assignment\assignment_type;
use mod_approval\model\status;
use mod_approval\model\workflow\workflow_stage;
use mod_approval\model\workflow\workflow_version;
use stdClass;

/**
 * Trait approval_workflow_test_setup provides testcase classes with easy access to methods for generating test scenarios.
 *
 * @package mod_approval\testing
 */
trait approval_workflow_test_setup {

    /**
     * Gets the approval workflow generator instance
     *
     * @return \mod_approval\testing\generator
     */
    protected function generator(): \mod_approval\testing\generator {
        return \mod_approval\testing\generator::instance();
    }

    /**
     * Generates a simple, three-level organisation hierarchy.
     *
     * $framework->agency = $agency;
     * $framework->agency->subagency_a = $subagency_a;
     * $framework->agency->subagency_a->program_a = $program_a;
     * $framework->agency->subagency_a->program_b = $program_b;
     * $framework->agency->subagency_b = $subagency_b;
     *
     * @return stdClass
     */
    protected function generate_org_hierarchy(): stdClass {
        set_config('showhierarchyshortnames', true);
        $hierarchy_generator = generator::instance()->get_plugin_generator('totara_hierarchy');
        $framework = $hierarchy_generator->create_framework('organisation');

        $agency = $hierarchy_generator->create_org(
            [
                'frameworkid' => $framework->id,
                'fullname' => 'Agency',
                'idnumber' => '001',
                'shortname' => 'org'
            ]
        );

        $subagency_a = $hierarchy_generator->create_org(
            [
                'frameworkid' => $framework->id,
                'fullname' => 'Sub-agency A',
                'idnumber' => '002',
                'shortname' => 'org_a',
                'parentid' => $agency->id
            ]
        );

        $program_a = $hierarchy_generator->create_org(
            [
                'frameworkid' => $framework->id,
                'fullname' => 'Sub-agency A Program A',
                'idnumber' => '004',
                'shortname' => 'org_a_prog_a',
                'parentid' => $subagency_a->id
            ]
        );

        $program_b = $hierarchy_generator->create_org(
            [
                'frameworkid' => $framework->id,
                'fullname' => 'Sub-agency A Program B',
                'idnumber' => '005',
                'shortname' => 'org_a_prog_b',
                'parentid' => $subagency_a->id
            ]
        );

        $subagency_b = $hierarchy_generator->create_org(
            [
                'frameworkid' => $framework->id,
                'fullname' => 'Sub-agency B',
                'idnumber' => '003',
                'shortname' => 'org_b',
                'parentid' => $agency->id
            ]
        );

        $framework->agency = $agency;
        $framework->agency->subagency_a = $subagency_a;
        $framework->agency->subagency_a->program_a = $program_a;
        $framework->agency->subagency_a->program_b = $program_b;
        $framework->agency->subagency_b = $subagency_b;
        return $framework;
    }

    /**
     * Generates a four-level, two-branch position hierarchy.
     *
     * framework->division
     * framework->division->position_a
     * framework->division->position_a->grade_a
     * framework->division->position_a->grade_a->region_a
     * framework->division->position_a->grade_a->region_b
     * framework->division->position_a->grade_b
     * framework->division->position_a->grade_b->region_a
     * framework->division->position_a->grade_b->region_b
     * framework->division->position_b
     * framework->division->position_b->grade_a
     * framework->division->position_b->grade_a->region_a
     * framework->division->position_b->grade_a->region_b
     * framework->division->position_b->grade_b
     * framework->division->position_b->grade_b->region_a
     * framework->division->position_b->grade_b->region_b
     *
     * @return stdClass
     */
    protected function generate_pos_hierarchy(): stdClass {
        set_config('showhierarchyshortnames', true);
        $hierarchy_generator = generator::instance()->get_plugin_generator('totara_hierarchy');
        $framework = $hierarchy_generator->create_framework('position');

        $framework->division = $hierarchy_generator->create_pos(
            [
                'frameworkid' => $framework->id,
                'fullname' => 'Division',
                'idnumber' => '001',
                'shortname' => 'division'
            ]
        );

        // Position.
        foreach (['a', 'b'] as $poskey) {
            $posvar = 'position_' . $poskey;
            $posname = 'Position '.strtoupper($poskey);
            $framework->division->{$posvar} = $hierarchy_generator->create_pos(
                [
                    'frameworkid' => $framework->id,
                    'fullname' => $posname,
                    'idnumber' => '001' . $poskey,
                    'shortname' => $posvar,
                    'parentid' => $framework->division->id,
                ]
            );
            // Grade.
            foreach (['a', 'b'] as $gradekey) {
                $gradevar = 'grade_' . $gradekey;
                $gradename = $posname. ' ' . 'Grade '.strtoupper($gradekey);
                $framework->division->{$posvar}->{$gradevar} = $hierarchy_generator->create_pos(
                    [
                        'frameworkid' => $framework->id,
                        'fullname' => $gradename,
                        'idnumber' => '001' . $poskey . $gradekey,
                        'shortname' => $posvar . '-' . $gradevar,
                        'parentid' => $framework->division->{$posvar}->id,
                    ]
                );
                // Region.
                foreach (['a', 'b'] as $regionkey) {
                    $regionvar = 'region_' . $regionkey;
                    $regionname = $gradename . ' ' . 'Region '.strtoupper($regionkey);
                    $framework->division->{$posvar}->{$gradevar}->{$regionvar} = $hierarchy_generator->create_pos(
                        [
                            'frameworkid' => $framework->id,
                            'fullname' => $regionname,
                            'idnumber' => '001' . $poskey . $gradekey . $regionkey,
                            'shortname' => $posvar . '-' . $gradevar . '-' . $regionvar,
                            'parentid' => $framework->division->{$posvar}->{$gradevar}->id,
                        ]
                    );
                }
            }
        }

        return $framework;
    }

    /**
     * Creates a workflow, and organization framework, and an assignment for the top-level organization.
     *
     * @param string $workflow_type_name
     * @param bool $create_override_assignments
     * @param bool $publish_workflow
     * @return array [workflow_entity, organisation framework, assignment_entity]
     */
    protected function create_workflow_and_assignment(
        string $workflow_type_name = 'Testing',
        bool $create_override_assignments = false,
        bool $publish_workflow = true
    ): array {
        $framework = $this->generate_org_hierarchy();

        // In tests:
        // list($workflow, $framework, $assignment) = $this->create_workflow_and_assignment();
        return $this->create_workflow_and_assignment_on_framework($framework, $workflow_type_name, $create_override_assignments, $publish_workflow);
    }

    /**
     * Creates a workflow and assignments, on a pre-existing framework.
     *
     * @param stdClass $framework - must match the framework generated by approval_workflow_test_setup::generate_org_hierarchy
     * @param string $workflow_type_name
     * @param bool $create_override_assignments
     * @param bool $publish_workflow
     * @return array [workflow_entity, organisation framework, assignment_entity]
     */
    protected function create_workflow_and_assignment_on_framework(
        stdClass $framework,
        string $workflow_type_name = 'Testing',
        bool $create_override_assignments = false,
        bool $publish_workflow = true
    ): array {
        $this->setAdminUser();

        $workflow = $this->generator()->create_simple_request_workflow($workflow_type_name, 'Simple Request Workflow', $publish_workflow);

        // create default assignment.
        $assignment_go = new assignment_generator_object(
            $workflow->course_id,
            assignment_type\organisation::get_code(),
            $framework->agency->id
        );
        $assignment_go->is_default = true;
        $assignment_go->status = status::ACTIVE;
        $default_assignment = $this->generator()->create_assignment($assignment_go);
        $override_assignments = [];

        if ($create_override_assignments) {
            $sub_agencies = [
                $framework->agency->subagency_a,
                $framework->agency->subagency_a->program_a,
                $framework->agency->subagency_a->program_b,
                $framework->agency->subagency_b,
            ];
            $override_assignments = [];
            foreach ($sub_agencies as $sub_agency) {
                $assignment_go = new assignment_generator_object(
                    $workflow->course_id,
                    assignment_type\organisation::get_code(),
                    $sub_agency->id
                );
                $assignment_go->name = $sub_agency->fullname . ' Assignment';
                $assignment_go->is_default = false;
                $assignment_go->status = status::ACTIVE;
                $override_assignments[] = $this->generator()->create_assignment($assignment_go);
            }
        }

        return [$workflow, $framework, $default_assignment, $override_assignments];
    }

    /**
     * Generate a workflow_stage via entity so that it does not have any of its default children.
     *
     * @param int $workflow_version_id
     * @param string $name
     * @param int $stage_type_code
     * @param int $sortorder
     * @return workflow_stage
     */
    protected function create_stage_via_entity(int $workflow_version_id, string $name, int $stage_type_code, int $sortorder): workflow_stage {
        $stage_entity = new workflow_stage_entity();
        $stage_entity->workflow_version_id = $workflow_version_id;
        $stage_entity->name = $name;
        $stage_entity->type_code = $stage_type_code;
        $stage_entity->active = 1;
        $stage_entity->sortorder = $sortorder;
        $stage_entity->save();
        return workflow_stage::load_by_entity($stage_entity);
    }

    /**
     * Generates a test application for a user
     *
     * @param workflow_entity $workflow
     * @param assignment_entity $assignment
     * @param user_entity $applicant
     * @param int|null $job_assignment_id
     * @return application
     */
    protected function create_application(
        workflow_entity $workflow,
        assignment_entity $assignment,
        user_entity $applicant,
        ?int $job_assignment_id = null
    ): application {
        $workflow_version = $workflow->versions()->one();
        $application_go = new application_generator_object(
            $workflow_version->id,
            $workflow->form->versions()->one()->id,
            $assignment->id,
        );
        $application_go->user_id = $applicant->id;
        $application_go->job_assignment_id = $job_assignment_id;
        $application_entity = $this->generator()->create_application($application_go);
        return new application($application_entity);
    }

    /**
     * Generates a submitted test application for a user
     *
     * @param workflow_entity $workflow
     * @param assignment_entity $assignment
     * @param user_entity $applicant
     * @param int|null $job_assignment_id
     * @return application
     */
    protected function create_submitted_application(
        workflow_entity $workflow,
        assignment_entity $assignment,
        user_entity $applicant,
        ?int $job_assignment_id = null
    ): application {
        $workflow_version = $workflow->versions()->one();
        $application_go = new application_generator_object(
            $workflow_version->id,
            $workflow->form->versions()->one()->id,
            $assignment->id
        );
        $application_go->user_id = $applicant->id;
        $application_go->job_assignment_id = $job_assignment_id;
        $application_entity = $this->generator()->create_application($application_go);
        $application = new application($application_entity);
        $new_state = $application->current_state->get_stage()->state_manager->get_new_state(new submit(), $application);
        $application->set_current_state($new_state);
        return $application;
    }

    /**
     * Sets user as logged-in user and generates application dashboard capability maps.
     *
     * @param stdClass|user_entity $user_id
     */
    protected function set_user_with_capability_maps($user): void {
        role_map_controller::regenerate_all_maps();
        capability_map_controller::regenerate_all_maps($user->id);
        $this->setUser($user);
    }

    /**
     * Converts accented Latin characters to ASCII, hopefully ignores everything else.
     * Hacky and not for production use, but ok for use in accent-insensitive comparison of generated names.
     *
     * See \mod_approval_data_provider_selectable_applicants_for_workflow_testcase::test_local_unaccent_name_method()
     * for test coverage.
     *
     * @param $input
     * @return false|string
     */
    protected function unaccent_name(string $input) {
        $output = iconv('UTF-8', 'ASCII//TRANSLIT', $input);
        // Check that all characters were correctly transliterated, or maybe input had a '?'.
        if (strpos($output, '?') === false) {
            return $output;
        } else {
            return $input;
        }
    }
}
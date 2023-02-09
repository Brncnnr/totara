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
 * @package approvalform_simple
 */

namespace approvalform_simple;

use core\entity\cohort;
use core\entity\user;
use core\json_editor\helper\document_helper;
use core\json_editor\node\{mention, paragraph, text};
use core\session\manager as session_manager;
use core\testing\generator as core_generator;
use mod_approval\entity\workflow\workflow;
use mod_approval\event\stage_started as stage_started_event;
use mod_approval\exception\validation_exception;
use mod_approval\model\application\action\approve;
use mod_approval\model\application\action\reject;
use mod_approval\model\application\action\submit;
use mod_approval\model\application\activity\stage_started as stage_started_activity;
use mod_approval\model\application\application;
use mod_approval\model\application\application_activity;
use mod_approval\model\application\application_submission;
use mod_approval\model\assignment\approver_type\relationship as relationship_approver_type;
use mod_approval\model\assignment\approver_type\user as user_approver_type;
use mod_approval\model\assignment\assignment_type;
use mod_approval\model\form\form_data;
use mod_approval\model\status;
use mod_approval\model\workflow\stage_feature\formviews;
use mod_approval\model\workflow\stage_type\approvals;
use mod_approval\model\workflow\stage_type\finished;
use mod_approval\model\workflow\stage_type\form_submission;
use mod_approval\model\workflow\workflow as workflow_model;
use mod_approval\model\workflow\workflow_version as workflow_version_model;
use mod_approval\model\workflow\workflow_stage as workflow_stage_model;
use mod_approval\testing\application_generator_object;
use mod_approval\testing\assignment_approver_generator_object;
use mod_approval\testing\assignment_generator_object;
use mod_approval\testing\generator as mod_approval_generator;
use mod_approval\testing\workflow_generator_object;
use totara_cohort\testing\generator as totara_cohort_generator;
use totara_comment\comment;
use totara_comment\comment_helper;
use totara_core\advanced_feature;
use totara_core\entity\relationship;
use totara_job\entity\job_assignment;

/**
 * Installer class, for installing default Simple Workflow, demo assignments, and demo applications.
 */
class installer {

    private $usernames = [
        'ablake' => 'Anthony Blake',
        'lcameron' => 'Leonard Cameron',
        'sellison' => 'Sarah Ellison',

    ];

    /**
     * Gets the generator instance
     *
     * @return mod_approval_generator
     */
    private function generator(): mod_approval_generator {
        return mod_approval_generator::instance();
    }

    /**
     * @return totara_cohort_generator
     */
    private function cohort_generator(): totara_cohort_generator {
        return totara_cohort_generator::instance();
    }

    /**
     * Get stages for default workflow
     *
     * @return string[]
     */
    public static function get_default_stages(): array {
        return [
            'stage1' => [
                'name' => 'Request',
                'type' => form_submission::get_enum(),
            ],
            'stage2' => [
                'name' => 'Approval',
                'type' => approvals::get_enum(),
            ],
            'stage3' => [
                'name' => 'Followup',
                'type' => form_submission::get_enum(),
            ],
            'stage4' => [
                'name' => 'Verify',
                'type' => approvals::get_enum(),
            ],
            'stage5' => [
                'name' => 'End',
                'type' => finished::get_enum(),
            ]

        ];
    }

    /**
     * Creates or loads demo cohort.
     *
     * @return cohort
     */
    public function install_demo_cohort(): cohort {
        $cohort = cohort::repository()->where('name', '=', 'Simple workflow demo')->one();
        if (empty($cohort)) {
            $cohort_obj = $this->cohort_generator()->create_cohort(['name' => 'Simple workflow demo', 'idnumber' => 'simpledemo']);
            $cohort = new cohort($cohort_obj->id);
        }
        return $cohort;
    }

    /**
     * Creates a demo simple workflow with a default assignment.
     *
     * @param cohort $cohort Cohort to use for default assignment
     * @param string $type_name Workflow_type name
     * @return workflow
     */
    public function install_demo_workflow(cohort $cohort, string $type_name = 'Simple'): workflow {
        global $CFG;

        advanced_feature::enable('approval_workflows');

        $generator = $this->generator();

        // Create a workflow_type
        $workflow_type = $generator->create_workflow_type($type_name);

        // Create a form and form_version
        $form_version = $generator->create_form_and_version(
            'simple',
            'Simple Request Form',
            $CFG->dirroot . '/mod/approval/form/simple/form.json'
        );
        $form = $form_version->form;
        // Do this again in case the form_version already existed.
        $form_version->json_schema = file_get_contents($CFG->dirroot . '/mod/approval/form/simple/form.json');
        $schema = json_decode($form_version->json_schema);
        if (empty($schema->version)) {
            throw new validation_exception();
        }
        $form_version->version = $schema->version;
        $form_version->status = status::ACTIVE;
        $form_version->save();

        // Create a workflow and workflow_version
        $workflow_go = new workflow_generator_object($workflow_type->id, $form->id, $form_version->id);
        $workflow_go->name = "Default Simple Workflow";
        $workflow_go->status = status::DRAFT;
        $workflow_version = $generator->create_workflow_and_version($workflow_go);
        $workflow = $workflow_version->workflow;

        // Create Stages
        $stages = installer::get_default_stages();
        foreach ($stages as $stage => $stage_details) {
            ${$stage} = $generator->create_workflow_stage($workflow_version->id, $stage_details['name'], $stage_details['type']);
        }

        // Configure formviews & approval levels
        // Request

        $stage1 = workflow_stage_model::load_by_id($stage1->id);
        $stage1->configure_formview([
            [
                'field_key' => 'request',
                'visibility' => formviews::EDITABLE_AND_REQUIRED,
            ],
            [
                'field_key' => 'notes',
                'visibility' => formviews::EDITABLE,
            ],
            [
                'field_key' => 'complete',
                'visibility' => formviews::HIDDEN,
            ],
        ]);

        // Approvals
        $stage2 = workflow_stage_model::load_by_id($stage2->id);
        $stage2->configure_formview([
            [
                'field_key' => 'request',
                'visibility' => formviews::EDITABLE_AND_REQUIRED,
            ],
            [
                'field_key' => 'notes',
                'visibility' => formviews::EDITABLE,
            ],
            [
                'field_key' => 'complete',
                'visibility' => formviews::HIDDEN,
            ],
        ]);

        // Create 2 approval levels
        $level2_1 = $stage2->approval_levels->first();
        $level2_2 = $generator->create_approval_level($stage2->id, 'Level 2', 2);

        // Followup
        $stage3 = workflow_stage_model::load_by_id($stage3->id);
        $stage3->configure_formview([
            [
                'field_key' => 'request',
                'visibility' => formviews::READ_ONLY,
            ],
            [
                'field_key' => 'complete',
                'visibility' => formviews::EDITABLE_AND_REQUIRED,
            ],
            [
                'field_key' => 'notes',
                'visibility' => formviews::EDITABLE,
            ],
        ]);

        // Verify followup
        $stage4 = workflow_stage_model::load_by_id($stage4->id);
        $stage4->configure_formview([
            [
                'field_key' => 'request',
                'visibility' => formviews::READ_ONLY,
            ],
            [
                'field_key' => 'complete',
                'visibility' => formviews::EDITABLE_AND_REQUIRED,
            ],
            [
                'field_key' => 'notes',
                'visibility' => formviews::EDITABLE,
            ],
        ]);

        // Delete default approval level
        $stage4->approval_levels->first()->delete();

        // Create 1 approval level
        $level4_1 = $generator->create_approval_level($stage4->id, 'Verification', 1);

        // Create default assignment
        $assignment_go = new assignment_generator_object($workflow->course_id, assignment_type\cohort::get_code(), $cohort->id);
        $assignment_go->id_number = '001';
        $assignment_go->is_default = true;
        $agency = $generator->create_assignment($assignment_go);

        // Create assignment approver manager for stage 1 level 1
        $manager = relationship::repository()->where('idnumber', '=', 'manager')->one();
        $approver_go = new assignment_approver_generator_object(
            $agency->id,
            $level2_1->id,
            relationship_approver_type::TYPE_IDENTIFIER,
            $manager->id
        );
        $generator->create_assignment_approver($approver_go);

        // Create approver for $level2_2 and also $level4_1
        $data = $this->get_user_data($this->usernames);
        $approver1_2 = $this->load_or_create_user($data);
        $approver_go = new assignment_approver_generator_object(
            $agency->id,
            $level2_2->id,
            user_approver_type::TYPE_IDENTIFIER,
            $approver1_2->id
        );
        $assignment_approver1_2 = $generator->create_assignment_approver($approver_go);
        $approver_go = new assignment_approver_generator_object(
            $agency->id,
            $level4_1->id,
            user_approver_type::TYPE_IDENTIFIER,
            $approver1_2->id
        );
        $assignment_approver3_1 = $generator->create_assignment_approver($approver_go);

        workflow_model::load_by_entity($workflow)->publish(workflow_version_model::load_by_entity($workflow_version));

        return $workflow;
    }

    public function install_demo_assignment(cohort $cohort): array {
        // Create manager
        $data = $this->get_user_data($this->usernames);
        $manager = $this->load_or_create_user($data);
        $data = [
            'userid' => $manager->id,
            'idnumber' => 'manager_' . $cohort->idnumber,
            'fullname' => $cohort->name . ' Manager',
        ];
        $manager_ja = $this->load_or_create_job_assignment($data);

        // Create applicant
        $data = $this->get_user_data($this->usernames);
        $applicant = $this->load_or_create_user($data);
        $data = [
            'userid' => $applicant->id,
            'idnumber' => 'applicant_' . $cohort->idnumber,
            'fullname' => $cohort->name . ' Applicant',
            'managerjaid' => $manager_ja->id,
        ];
        $applicant_ja = $this->load_or_create_job_assignment($data);

        // Assign applicant to cohort
        $this->cohort_generator()->cohort_assign_users($cohort->id, [$applicant->id]);

        return [$applicant, $applicant_ja];
    }

    public function install_demo_applications(
        workflow $workflow,
        user $applicant,
        job_assignment $ja,
        int $draft = 7,
        int $submitted = 4
    ) {
        $workflow_version = $workflow->versions->first();
        $form_version = $workflow_version->form_version;
        $assignment = $workflow->default_assignment;
        $application_go = new application_generator_object($workflow_version->id, $form_version->id, $assignment->id);
        $application_go->user_id = $applicant->id;
        $application_go->job_assignment_id = $ja->id;
        /** @var application[] $applications */
        $applications = [];
        for ($i = 0; $i < $draft; $i++) {
            $application = $this->generator()->create_application($application_go);
            $application = application::load_by_id($application->id);
            $applications[] = $application;

            $stage_started = stage_started_event::create_from_application($application);
            $stage_started->trigger();

            application_activity::create(
                $application,
                null,
                stage_started_activity::class
            );
        }
        // Submit applications
        $this->set_user($applicant);
        for ($i = 0; $i < $submitted; $i++) {
            $form_data = form_data::from_json('{"request":"Pizza party"}');
            $submission = application_submission::create_or_update($applications[$i], $applications[$i]->user->id, $form_data);
            $submission->publish(user::logged_in()->id);
            submit::execute($applications[$i], user::logged_in()->id);
        }

        /** @var user $approver */
        $approver = $applications[0]->get_approver_users()->first();
        $this->set_user($approver);

        // Two applications are APPROVED
        approve::execute($applications[0], $approver->id);
        approve::execute($applications[1], $approver->id);

        // One application is REJECTED
        reject::execute($applications[2], $approver->id);

        // Create some comments and activities
        $comment = $this->post_comment(
            $applications[0],
            $approver,
            [text::create_json_node_from_text('This is a first comment')]
        );
        $this->post_reply(
            $comment,
            $applicant,
            [text::create_json_node_from_text('This is a first reply')]
        );
        $comment = $this->post_comment(
            $applications[1],
            $applicant,
            [text::create_json_node_from_text('This is a second comment')]
        );
        $this->post_reply(
            $comment,
            $approver,
            [
                paragraph::create_json_node_with_content_nodes([mention::create_raw_node($applicant->id)]),
                paragraph::create_json_node_with_content_nodes([text::create_json_node_from_text('This is a second reply')]),
            ]
        );

        /** @var user $approver */
        $approver = $applications[1]->get_approver_users()->first();
        $this->set_user($approver);
        approve::execute($applications[1], $approver->id);
    }

    /**
     * @param user $user
     */
    private function set_user(user $user): void {
        session_manager::set_user($user->to_record());
    }

    /**
     * @param application $application
     * @param user $actor
     * @param array $content content of JSON document
     * @return comment
     */
    private function post_comment(application $application, user $actor, array $content): comment {
        $document = [
            'type' => 'doc',
            'content' => $content,
        ];
        return comment_helper::create_comment(
            'mod_approval',
            'comment',
            $application->id,
            document_helper::json_encode_document($document),
            FORMAT_JSON_EDITOR,
            null,
            $actor->id
        );
    }

    /**
     * @param comment $comment
     * @param user $actor
     * @param array $content content of JSON document
     * @return comment
     */
    private function post_reply(comment $comment, user $actor, array $content): comment {
        $document = [
            'type' => 'doc',
            'content' => $content,
        ];
        return comment_helper::create_reply(
            $comment->get_id(),
            document_helper::json_encode_document($document),
            null,
            FORMAT_JSON_EDITOR,
            $actor->id
        );
    }

    /**
     * Gets the next user from an array of 'username' => 'Firstname Lastname' pairs.
     *
     * @param $usernames
     * @return array
     */
    private function get_user_data(&$usernames): array {
        list($firstname, $lastname) = explode(' ', current($usernames));
        $data = ['username' => key($usernames), 'firstname' => $firstname, 'lastname' => $lastname, 'password' => 'simple'];
        next($usernames);
        return $data;
    }

    /**
     * Loads (by username) or creates a user.
     *
     * @param $data
     * @return user
     */
    private function load_or_create_user($data): user {
        $core_generator = core_generator::instance();
        $user = user::repository()->where('username', '=', $data['username'])->one();
        if (is_null($user)) {
            $rec = $core_generator->create_user($data);
            $user = new user($rec->id);
        }
        return $user;
    }

    /**
     * Loads (by idnumber) or creates a new job assignment.
     *
     * @param $data
     * @return job_assignment
     */
    private function load_or_create_job_assignment($data): job_assignment {
        $ja = job_assignment::repository()->where('idnumber', '=', $data['idnumber'])->one();
        if (is_null($ja)) {
            $rec = \totara_job\job_assignment::create($data);
            $ja = new job_assignment($rec->id);
        }
        return $ja;
    }
}
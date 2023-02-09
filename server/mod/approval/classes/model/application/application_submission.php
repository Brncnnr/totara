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
 * @author Angela Kuznetsova <angela.kuznetsova@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\model\application;

use coding_exception;
use core\entity\user;
use core\orm\entity\model;
use core\orm\query\builder;
use mod_approval\entity\application\application_action as application_action_entity;
use mod_approval\entity\application\application_submission as application_submission_entity;
use mod_approval\model\form\approvalform_base;
use mod_approval\model\form\form_data;
use mod_approval\model\model_trait;
use mod_approval\model\workflow\workflow_stage;


/**
 * Approval workflow application submission model
 *
 * Properties:
 * @property-read int $id Database record ID
 * @property-read int $application_id Parent application ID
 * @property-read int $user_id User who submitted the application
 * @property-read int $workflow_stage_id Related workflow_stage ID
 * @property-read int $created Creation timestamp
 * @property-read int $updated Last-modified timestamp; same as created if not modified
 * @property-read int|null $submitted Last submitted timestamp, or null
 * @property-read bool $superseded Whether this submission has been superseded
 * @property-read string $form_data JSON blob of form field state at the time of the submission
 * @property-read form_data $form_data_parsed Parsed form data
 *
 * Relationships:
 * @property-read application $application Parent application
 * @property-read user $user Related user entity
 * @property-read workflow_stage $workflow_stage Related workflow_stage
 *
 * Methods:
 * @method static self load_by_id(int $id)
 * @method static self load_by_entity(application_submission_entity $entity)
 * @package mod_approval\models\application
 */
final class application_submission extends model {

    use model_trait;

    /** @var application_submission_entity */
    protected $entity;

    /** @var string[] */
    protected $entity_attribute_whitelist = [
        'id',
        'application_id',
        'user_id',
        'workflow_stage_id',
        'created',
        'updated',
        'superseded',
        'form_data',
        'submitted',
    ];

    /** @var string[] */
    protected $model_accessor_whitelist = [
        'application',
        'workflow_stage',
        'form_data_parsed',
        'user',
    ];

    /**
     * @inheritDoc
     * @codeCoverageIgnore
     */
    protected static function get_entity_class(): string {
        return application_submission_entity::class;
    }

    /**
     * Get the parent application
     *
     * @return application
     */
    public function get_application(): application {
        return application::load_by_entity($this->entity->application);
    }

    /**
     * Create or update a draft.
     *
     * @param application $application Parent application
     * @param int $submitter_id User who is creating or updating the submission
     * @param form_data $form_data Form data
     * @return self
     */
    public static function create_or_update(
        application $application,
        int $submitter_id,
        form_data $form_data
    ): self {
        return builder::get_db()->transaction(function () use ($application, $submitter_id, $form_data) {
            // Prepares fields for submission and fires action to allow form plugins observe form data.
            $approval_form = approvalform_base::from_plugin_name($application->form_version->form->plugin_name);
            $form_data = $form_data->filter_field_keys($application->current_stage);
            $form_data->prepare_fields_for_submission($application->get_interactor($submitter_id), $approval_form);
            $approval_form->observe_form_data_for_application($application, $form_data);

            $entity = self::fetch_or_create($application, $submitter_id);
            $entity->form_data = $form_data->to_json();
            $entity->save();

            return self::load_by_entity($entity);
        });
    }

    /**
     * Retrieves a submission for an application stage
     *
     * The submission will be the only non-superseded submission for the application stage. Any previous
     * submissions will be marked superseded.
     *
     * Over time, an example scenario might look like this (0 = not superseded, 1 = superseded):
     * UserA fills in the form with "Apple": Sub1(UserA, Apple, 0)
     * UserA fills in the form with "Avocado": Sub1(UserA, Avocado, 0)
     * UserB fills in the form with "Banana": Sub1(UserA, Avocado, 1), Sub2(UserB, Banana, 0)
     * UserA fills in the form with "Cherry": Sub1(UserA, Avocado, 1), Sub2(UserB, Banana, 1), Sub3(UserA, Cherry, 0)
     * UserA fills in the form with "Coconut": Sub1(UserA, Avocado, 1), Sub2(UserB, Banana, 1), Sub3(UserA, Coconut, 0)
     *
     * Note that each application stage is managed separately, and each will have exactly one non-superseded
     * submission (if it has any submissions at all). Therefore, an application may have several non-superseded
     * submissions, but each will be for a different stage.
     *
     * @param application $application
     * @param int $user_id
     * @return application_submission_entity
     */
    private static function fetch_or_create(application $application, int $user_id): application_submission_entity {
        // If the non-superseded submission for this application stage belongs to the user then load it.
        /** @var application_submission_entity|null */
        $entity = application_submission_entity::repository()
            ->filter_by_updateable($application->id, $application->current_state->get_stage_id(), $user_id)
            ->one();
        if ($entity === null) {
            // There MAY be an existing non-superseded submission. Mark all of them superseded - there can be only one!
            application_submission_entity::repository()
                ->where('application_id', '=', $application->id)
                ->where('workflow_stage_id', '=', $application->current_state->get_stage_id())
                ->where('superseded', '=', 0)
                ->update(['superseded' => 1]);

            // The user is not updating their previous non-superseded submission, so make a new submission.
            $entity = new application_submission_entity();
            $entity->application_id = $application->id;
            $entity->user_id = $user_id;
            $entity->workflow_stage_id = $application->get_current_state()->get_stage_id();
            $entity->submitted = null;
            $entity->superseded = false;
        }
        return $entity;
    }

    /**
     * Clone this submission.
     *
     * @param application $destination
     * @return self
     * @internal must only be called from the application model
     */
    public function clone(application $destination): self {
        // Fetch the latest instance in case 'this' is stale.
        $source = self::load_by_id($this->entity->id);
        $entity = new application_submission_entity();
        $entity->application_id = $destination->id;
        $entity->user_id = $source->user->id;
        $entity->workflow_stage_id = $destination->current_state->get_stage_id();
        $entity->submitted = null;
        $entity->superseded = false;
        $entity->form_data = form_data::from_json($source->form_data)->clone_form_data($this->application, $destination)->to_json();
        $entity->save();
        return self::load_by_entity($entity);
    }

    /**
     * Get the current workflow stage for this application
     *
     * @return workflow_stage
     */
    public function get_workflow_stage(): workflow_stage {
        return workflow_stage::load_by_entity($this->entity->workflow_stage);
    }

    /**
     * Get the applicator or approver.
     *
     * @return user
     */
    public function get_user(): user {
        return $this->entity->user;
    }

    /**
     * Get the form data.
     *
     * @return form_data
     */
    public function get_form_data_parsed(): form_data {
        return form_data::from_instance($this);
    }

    /**
     * Delete the record.
     *
     * Does not check that it is okay to delete this submission - this should be checked externally.
     *
     * @return self
     */
    public function delete(): self {
        $this->entity->delete();
        return $this;
    }

    /**
     * Mark this submission as published.
     *
     * This just touches the submission and doesn't do anything to the parent application.
     *
     * @param int $submitter_id The user who is marking the submission published
     */
    public function publish(int $submitter_id): void {
        if ($this->is_published()) {
            throw new coding_exception('Cannot publish submission because it is already published');
        }

        $this->form_data_parsed->check_readiness($this->application->current_state->get_stage());

        $this->entity->submitted = time();
        $this->entity->user_id = $submitter_id;
        $this->entity->save();
    }

    /**
     * @return boolean
     */
    public function is_published(): bool {
        return $this->entity->submitted !== null;
    }

    /**
     * @return bool
     */
    public function is_first_submission(): bool {
        return $this->application->submitter_id == $this->entity->user_id
            && $this->application->submitted == $this->entity->updated;
    }

    /**
     * Mark the existing submission(s) superseded, and create a new one using the last submission's form data.
     *
     * The new submission is credited to the user who triggered the function.
     *
     * @param application $application
     * @param workflow_stage $stage
     * @param int $actor_id
     */
    public static function supersede_submissions_for_stage(application $application, workflow_stage $stage, int $actor_id): void {
        $last_submission = $application->last_submission;

        application_submission_entity::repository()
            ->where('application_id', '=', $application->id)
            ->where('workflow_stage_id', '=', $stage->id)
            ->update(['superseded' => 1]);

        // Clone the last applicant submission as a new submission, and credit it to the actor
        // who caused the reset.
        application_submission::create_or_update(
            $application,
            $actor_id,
            $last_submission->form_data_parsed
        );
    }
}

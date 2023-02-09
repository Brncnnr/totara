<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\models\response;

use coding_exception;
use context_module;
use core\collection;
use core\orm\entity\model;
use core\orm\query\builder;
use mod_perform\controllers\activity\view_external_participant_activity;
use mod_perform\controllers\activity\view_user_activity;
use mod_perform\entity\activity\element_response;
use mod_perform\entity\activity\participant_section as participant_section_entity;
use mod_perform\entity\activity\section_relationship;
use mod_perform\event\participant_section_saved_as_draft;
use mod_perform\models\activity\participant_instance;
use mod_perform\models\activity\participant_source;
use mod_perform\models\activity\section;
use mod_perform\models\activity\section_element;
use mod_perform\state\participant_instance\closed as participant_instance_closed;
use mod_perform\state\participant_section\in_progress;
use mod_perform\state\participant_section\closed;
use mod_perform\state\participant_section\complete;
use mod_perform\state\participant_section\not_started;
use mod_perform\state\participant_section\open;
use mod_perform\state\participant_section\participant_section_availability;
use mod_perform\state\participant_section\participant_section_progress;
use mod_perform\state\state;
use mod_perform\state\state_aware;
use moodle_exception;
use moodle_url;
use totara_core\relationship\relationship;

/**
 * Class participant_section
 *
 * @property-read int $id ID
 * @property-read int $section_id
 * @property-read int $participant_instance_id
 * @property-read int $progress
 * @property-read int $availability
 * @property-read int $created_at
 * @property-read int $updated_at
 * @property-read string $availability_status
 * @property-read string $progress_status
 * @property-read participant_instance $participant_instance
 * @property-read section $section
 * @property-read moodle_url $participation_url
 * @property-read collection|section_element_response[] $section_element_responses
 * @property-read collection|participant_instance[] answerable_participant_instances
 *
 * @package mod_perform\models\activity
 */
class participant_section extends model implements section_response_interface {

    use state_aware;

    /**
     * @var participant_section_entity
     */
    protected $entity;

    /**
     * @var null|collection|section_element_response[]
     */
    protected $section_element_responses = null;

    protected $entity_attribute_whitelist = [
        'id',
        'progress',
        'participant_instance_id',
        'availability',
        'created_at',
        'updated_at',
        'participant_instance',
    ];

    protected $model_accessor_whitelist = [
        'section',
        'section_id',
        'section_element_responses',
        'can_answer',
        'progress_status',
        'participant_instance',
        'availability_status',
        'answerable_participant_instances',
        'is_overdue',
        'responses_are_visible_to',
        'participation_url',
        'can_answer',
    ];

    /**
     * participant_section constructor.
     *
     * @param participant_section_entity $entity
     * @throws coding_exception
     */
    public function __construct(participant_section_entity $entity) {
        parent::__construct($entity);
    }

    /**
     * @inheritDoc
     */
    protected static function get_entity_class(): string {
        return participant_section_entity::class;
    }

    public function get_section(): section {
        return section::load_by_entity($this->entity->section);
    }

    public function get_section_id(): int {
        // Done with accessor rather than attribute whitelist so we can satisfy the section_response interface.
        return $this->entity->section_id;
    }

    public function get_participant_instance(): participant_instance {
        return participant_instance::load_by_entity($this->entity->participant_instance);
    }

    /**
     * Get all participant instances (same end user, different relationship),
     * that this section can be answered by the linked participant instance.
     *
     * @return collection|participant_instance
     */
    public function get_answerable_participant_instances(): collection {
        return $this->entity->get_answerable_participant_instances()->map_to(participant_instance::class);
    }

    /**
     * Get a list of all relationships that can view this participant section responses.
     *
     * @return collection|relationship[]
     */
    public function get_responses_are_visible_to(): collection {
        return $this->entity->section->section_relationships
            ->filter(
                function (section_relationship $section_relationship) {
                    return $section_relationship->can_view;
                }
            )->map(
                function (section_relationship $section_relationship) {
                    return new relationship($section_relationship->core_relationship);
                }
            );
    }

    /**
     * Can the owner (user) of this participant section view other's responses.
     *
     * @return bool
     */
    public function can_view_others_responses(): bool {
        $core_relationship_id = (int) $this->entity->participant_instance->core_relationship_id;

        return $this->get_responses_are_visible_to()->has('id', $core_relationship_id);
    }

    /**
     * Checks if participant relationship has can_answer permissions on the section.
     *
     * @return bool
     */
    public function get_can_answer(): bool {
        $core_relationship_id = (int) $this->entity->participant_instance->core_relationship_id;

        return $this->entity->section->section_relationships->has(
            function (section_relationship $relationship) use ($core_relationship_id) {
                return (int) $relationship->core_relationship_id === $core_relationship_id
                    && (bool) $relationship->can_answer;
            }
        );
    }

    public function get_context(): context_module {
        return $this->get_participant_instance()->get_subject_instance()->get_context();
    }

    /**
     * @param collection|section_element_response[] $section_element_responses
     * @return self
     */
    public function set_section_element_responses(collection $section_element_responses): self {
        $this->section_element_responses = $section_element_responses;
        return $this;
    }

    /**
     * Returns the collection of section element responses that have been set in this object, otherwise retrieves
     * the current set of element responses from the DB and turns them into section element responses
     *
     * @return collection|section_element_response[]
     */
    public function get_section_element_responses(): collection {
        if (is_null($this->section_element_responses)) {
            $element_responses = element_response::repository()
                ->join('perform_section_element', 'section_element_id', 'id')
                ->where('perform_section_element.section_id', $this->section_id)
                ->where('perform_element_response.participant_instance_id', $this->participant_instance_id)
                ->order_by('created_at')
                ->order_by('id')
                ->get();

            $section_element_responses = [];
            foreach ($element_responses as $element_response) {
                $section_element_responses[] = new section_element_response(
                    $this->participant_instance,
                    section_element::load_by_entity($element_response->section_element),
                    $element_response,
                    new \core\orm\collection()
                );
            }

            return new collection($section_element_responses);
        } else {
            return $this->section_element_responses;
        }
    }

    /**
     * Update the response data held in memory on this instance.
     *
     * @param array $update_request_payload
     * @return $this
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function set_responses_data_from_request(array $update_request_payload): self {
        if ($this->get_availability_state() instanceof closed) {
            throw new moodle_exception('invalid_change_on_closed_participant_section', 'mod_perform');
        }

        foreach ($update_request_payload as $update) {
            $section_element_id = $update['section_element_id'];
            $updated_response_data = $update['response_data'];

            /** @var section_element_response $existing_element_response */
            $existing_element_response = $this->find_element_response($section_element_id);

            if ($existing_element_response === null) {
                throw new coding_exception(sprintf('Section element not found for id %d', $section_element_id));
            }

            if (!$existing_element_response->get_element()->get_is_respondable()) {
                throw new coding_exception(sprintf('Section element with id %d can not be responded to', $section_element_id));
            }

            $existing_element_response->set_response_data($updated_response_data);
        }

        return $this;
    }

    /**
     * Find an element response based on section element id.
     *
     * @param int $section_element_id
     * @return mixed|null
     */
    public function find_element_response(int $section_element_id): ?section_element_response {
        return $this->get_section_element_responses()->find(
            function (section_element_response $section_element_response) use ($section_element_id) {
                return (int) $section_element_response->section_element_id === $section_element_id;
            }
        );
    }

    /**
     * Try to complete the section (save all answers, and set the progress status to complete).
     * If any element validation rules do not pass no data is saved and the progress status is not updated.
     *
     * Validation errors are accessible on each element response.
     *
     * @return bool true if no validation rules failed and the section was completed, false if any validation rules failed.
     * @see get_section_element_responses
     * @see section_element_response::get_validation_errors
     * @see set_section_element_responses
     */
    public function complete(): bool {
        if (!$this->can_complete()) {
            throw new coding_exception('This participant section is not in a valid state to be completed');
        }

        // There was at least one validation problem, don't save anything.
        if ($this->has_response_validation_errors(false)) {
            return false;
        }

        // Validation has passed save all the responses, and ensure the progress status is set to complete.
        builder::get_db()->transaction(function () {
            foreach ($this->get_section_element_responses() as $element_response) {
                $element_response->save();
            }

            /** @var participant_section_progress $state */
            $state = $this->get_progress_state();
            $state->complete();
        });

        $this->refresh();

        return true;
    }

    /**
     * Indicates if it is valid for this participant section to be marked as complete
     *
     * @return bool
     */
    public function can_complete(): bool {
        return $this->get_progress_state() instanceof not_started ||
            $this->get_progress_state() instanceof in_progress ||
            $this->get_progress_state() instanceof complete;
    }

    /**
     * Save participant section responses as a draft which ignore all validation rules
     * manually triggered progress updated event
     *
     * @return bool
     */
    public function draft(): bool {
        if (!$this->can_save_draft()) {
            throw new coding_exception('This participant section is not in a valid state to be saved as draft');
        }

        // There was at least one draft validation problem, don't save anything.
        if ($this->has_response_validation_errors(true)) {
            return false;
        }

        builder::get_db()->transaction(function () {
            foreach ($this->get_section_element_responses() as $section_element_response) {
                $section_element_response->save();
            }

            /** @var participant_section_progress $state */
            $state = $this->get_progress_state();
            $state->on_participant_access();
        });

        $this->refresh();

        participant_section_saved_as_draft::create_from_participant_section($this)
            ->trigger();

        return true;
    }

    /**
     * @param bool $is_draft
     *
     * @return bool
     */
    private function has_response_validation_errors(bool $is_draft = false): bool {
        $has_response_validation_errors = false;
        foreach ($this->get_section_element_responses() as $element_response) {
            if (!$element_response->validate_response($is_draft)) {
                // We don't stop here because validate_response has side-effects that we want.
                $has_response_validation_errors  = true;
            }
        }
        return $has_response_validation_errors ;
    }

    /**
     * Indicates if it is valid for this participant section to be saved as draft
     *
     * @return bool
     */
    public function can_save_draft(): bool {
        return $this->get_progress_state() instanceof not_started ||
            $this->get_progress_state() instanceof in_progress;
    }

    /**
     * Called when a participant has accessed this section.
     */
    public function on_participant_access() {
        /** @var participant_section_progress $state */
        $state = $this->get_progress_state();
        $state->on_participant_access();
    }

    public function get_current_state_code(string $state_type): int {
        return $this->{$state_type};
    }

    protected function update_state_code(state $state): void {
        $this->entity->{$state::get_type()} = $state::get_code();
        $this->entity->update();
    }

    /**
     * Get internal name of current progress state.
     *
     * @return string
     */
    public function get_progress_status(): string {
        return $this->get_progress_state()->get_name();
    }

    /**
     * Get internal name of current availability state.
     *
     * @return string
     */
    public function get_availability_status(): string {
        return $this->get_availability_state()->get_name();
    }

    /**
     * Get progress state class.
     *
     * @return participant_section_progress
     */
    public function get_progress_state(): state {
        return $this->get_state(participant_section_progress::get_type());
    }

    /**
     * Get availability state class.
     *
     * @return participant_section_availability
     */
    public function get_availability_state(): state {
        return $this->get_state(participant_section_availability::get_type());
    }

    /**
     * Checks if overdue
     *
     * @return bool
     */
    public function get_is_overdue(): bool {
        return !$this->is_complete()
            && $this->participant_instance->is_overdue;
    }

    /**
     * Get url for a user to participate in this section
     *
     * @return moodle_url
     */
    public function get_participation_url(): moodle_url {
        if ($this->participant_instance->participant_source == participant_source::EXTERNAL) {
            return view_external_participant_activity::get_url([
                'participant_section_id' => $this->id,
                'token' => $this->entity->participant_instance->external_participant->token
            ]);
        } else {
            return view_user_activity::get_url(['participant_section_id' => $this->id]);
        }
    }

    /**
     * Checks if participant section is complete.
     * This means it is not in a "draft" state.
     *
     * @return bool
     */
    public function is_complete(): bool {
        return $this->get_progress_state() instanceof complete;
    }

    /**
     * Delete the participant section
     *
     */
    public function delete(): void {
        $this->entity->delete();
    }

    /**
     * Manually close the participant section
     *
     * The following changes are applied, in this order:
     * - Change availability to "Closed"
     * - If progress is "Not yet started" or "In progress" then set progress to "Not submitted"
     * - If progress is "Complete" then don't change progress
     */
    public function manually_close(): void {
        if (!$this->get_availability_state() instanceof open) {
            throw new coding_exception('This function can only be called if the participant section is open');
        }

        $this->get_availability_state()->close();
        // This will trigger an event which will end up calling $this->participant_instance->update_progress_status!
        $this->get_progress_state()->manually_complete();
    }

    /**
     * Manually open the participant section
     *
     * Related participant instance and subject instance may be affected by this action.
     *
     * The following changes are applied, in this order:
     * - Change availability to "Open"
     * - Recalculate progress, either "Not yet started" or "In progress"
     * - Change participant instance availability to "Open" and recalculate progress
     * - Change subject instance availability to "Open" and recalculate progress
     *
     * @param bool $open_parent
     */
    public function manually_open(bool $open_parent = true): void {
        $this->get_availability_state()->open();
        // This will trigger an event which will end up calling $this->participant_instance->update_progress_status!
        $this->get_progress_state()->manually_uncomplete();

        if ($open_parent) {
            $participant_instance = $this->participant_instance;
            if ($participant_instance->get_availability_state() instanceof participant_instance_closed) {
                $participant_instance->manually_open(true, false);
            }
        }
    }

    /**
     * Reload the data for this model
     *
     * @return $this
     */
    public function refresh(): self {
        $this->entity->refresh();

        return $this;
    }

}

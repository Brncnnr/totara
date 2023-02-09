<?php
/*
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
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\models\response;

use coding_exception;
use core\collection;
use core\entity\user;
use core\orm\entity\model;
use core\orm\query\builder;
use mod_perform\entity\activity\element_response;
use mod_perform\entity\activity\element_response as element_response_entity;
use mod_perform\entity\activity\section_element as section_element_entity;
use mod_perform\entity\activity\section_relationship;
use mod_perform\hook\post_element_response_submission;
use mod_perform\hook\element_response_visibility;
use mod_perform\models\activity\element;
use mod_perform\models\activity\helpers\displays_responses;
use mod_perform\models\activity\participant_instance;
use mod_perform\models\activity\participant_source;
use mod_perform\models\activity\respondable_element_plugin;
use mod_perform\models\activity\section_element;
use mod_perform\util;

/**
 * Represents the responses (or lack of) to an element from the
 * perspective of a participant.
 *
 * response_data holds the participants response,
 * other participants (or all participants in the case of a view-only
 * observer) are held in other_responder_groups.
 *
 * @property int $section_element_id Foreign key
 * @property int $participant_instance_id Foreign key
 * @property-read section_element $section_element The parent section element
 * @property-read participant_instance $participant_instance The participant instance submitted the response.
 * @property-read collection|element_validation_error[] $validation_errors A collection of element_validation_errors
 * @property-read string $response_data Processed response data
 * @property-read string $raw_response_data Raw JSON encoded response data
 * @property-read bool $can_respond Can the main participant respond to this element (it could be the case that only other relationships/groups can)
 * @property-read element $element The element this is a response to
 * @property-read collection|participant_instance[] $visible_to
 * @property-read collection|responder_group[] $other_responder_groups
 *                Other responses grouped by relationship types (Manager/Appraiser)
 * @property-read int $sort_order The order this element should appear in the section
 * @package mod_perform\models\activity
 */
class section_element_response extends model implements section_element_responses_interface {

    protected $entity_attribute_whitelist = [
        'participant_instance_id'
    ];

    protected $model_accessor_whitelist = [
        'id',
        'section_element_id',
        'section_element',
        'section_element_id',
        'can_respond',
        'response_data', // as a JSON encoded string
        'raw_response_data',
        'response_data_formatted_lines',
        'element',
        'validation_errors',
        'participant_instance',
        'other_responder_groups',
        'visible_to',
        'sort_order',
    ];
    /**
     * @var collection|responder_group[]
     */
    protected $other_responder_groups;

    /**
     * @var participant_instance
     */
    private $participant_instance;

    /**
     * @var element_response_entity
     */
    protected $entity;

    /**
     * @var section_element_entity
     */
    protected $section_element;

    /**
     * @var collection
     */
    protected $validation_errors;

    /**
     * @var bool
     */
    protected $can_respond = true;

    /**
     * @var string|null
     */
    private $computed_response_data;

    /**
     * @var string|null
     */
    private $computed_response_data_formatted_lines;

    /**
     * @inheritDoc
     */
    protected static function get_entity_class(): string {
        return element_response_entity::class;
    }

    /**
     * element_response constructor.
     *
     * @param participant_instance|element_response_entity $participant_instance Participant instance or element response entity
     * @param section_element $section_element
     * @param element_response_entity|null $element_response_entity
     * @param collection|responder_group[] $other_responder_groups
     * @param bool $can_respond Can the main participant respond to this element (it could be the case that only other relationships/groups can)
     * @throws coding_exception
     */
    public function __construct(
        participant_instance $participant_instance,
        section_element $section_element,
        ?element_response_entity $element_response_entity,
        collection $other_responder_groups,
        $can_respond = true
    ) {
        if ($element_response_entity === null) {
            $element_response_entity = new element_response_entity();
            $element_response_entity->participant_instance_id = $participant_instance->id;
            $element_response_entity->section_element_id = $section_element->id;
        } else {
            $this->verify_participant_instance($element_response_entity, $participant_instance);
            $this->verify_section_element($element_response_entity, $section_element);
        }

        $this->entity = $element_response_entity;
        $this->participant_instance = $participant_instance;
        $this->section_element = $section_element;
        $this->other_responder_groups = $other_responder_groups;
        $this->can_respond = $can_respond;
    }

    /**
     * Verifies element response is for the participant instance.
     *
     * @param $element_response_entity
     * @param $participant_instance
     *
     * @throws coding_exception
     */
    private function verify_participant_instance($element_response_entity, $participant_instance) {
        if ((int) $element_response_entity->participant_instance_id !== (int) $participant_instance->id) {
            throw new coding_exception(
                'participant_instance_id of the element response does not match the supplied participant instance'
            );
        }
    }

    /**
     * Verifies section_element is for element_response.
     *
     * @param $element_response_entity
     * @param $section_element_entity
     *
     * @throws coding_exception
     */
    private function verify_section_element($element_response_entity, $section_element_entity) {
        if ((int) $element_response_entity->section_element_id !== (int) $section_element_entity->id) {
            throw new coding_exception(
                'section_element_id of the element response does not match the supplied section element'
            );
        }
    }

    /**
     * Can the main participant respond to this element (it could be the case that only other relationships/groups can).
     *
     * @return bool
     */
    public function get_can_respond(): bool {
        return $this->can_respond;
    }

    /**
     * Get's the computed response data.
     * The raw response is processed by the element plugin.
     *
     * @return string|null
     */
    public function get_response_data(): ?string {
        $element_plugin = $this->section_element->element->element_plugin;

        if (!$element_plugin instanceof displays_responses) {
            return null;
        }

        if ($this->computed_response_data === null) {
            $this->computed_response_data = $element_plugin->build_response_data($this);
        }

        return $this->computed_response_data;
    }

    /**
     * Get's the raw response data from the entity.
     *
     * @return string|null
     */
    public function get_raw_response_data(): ?string {
        return $this->entity->response_data;
    }

    /**
     * Get the computed response data formatted ready for display broken into an array entry for each response.
     * For example for multi_choice_multi will have each selected checkbox value as an array entry.
     *
     * @return string[]
     */
    public function get_response_data_formatted_lines(): array {
        $element_plugin = $this->get_element()->get_element_plugin();

        if (!$element_plugin instanceof displays_responses) {
            return [];
        }

        if ($this->computed_response_data_formatted_lines === null) {
            $this->computed_response_data_formatted_lines = $element_plugin->build_response_data_formatted_lines($this);
        }

        return $element_plugin->format_response_lines(
            $this->computed_response_data_formatted_lines,
            $this->get_element()->data
        );
    }

    /**
     * @inheritDoc
     */
    public function get_section_element(): section_element {
        return $this->section_element;
    }

    /**
     * @inheritDoc
     */
    public function get_section_element_id(): int {
        return $this->section_element->id;
    }

    /**
     * @return collection|participant_instance[]
     */
    public function get_visible_to(): collection {
        // This is just stubbed for now.
        return new collection();
    }

    /**
     * Get the order this response should appear in the section.
     * @return int
     */
    public function get_sort_order(): int {
        return $this->section_element->sort_order;
    }

    /**
     * Get the main participant responding to this question.
     * @return participant_instance
     */
    public function get_participant_instance(): participant_instance {
        return $this->participant_instance;
    }

    /**
     * Set the raw JSON encoded response data.
     *
     * @param string|null $encoded_response_data
     * @return self
     */
    public function set_response_data(?string $encoded_response_data): self {
        $this->entity->response_data = $encoded_response_data;
        return $this;
    }

    /**
     * Set the response to be empty.
     *
     * @return self
     */
    public function set_empty_response(): self {
        $this->entity->response_data = null;
        return $this;
    }

    /**
     * Run the element plugin specific validation on the response data.
     *
     * This function has the side-effect of setting the validation_errors
     * property.
     *
     * @param bool $is_draft_validation
     * @return bool
     */
    public function validate_response($is_draft_validation = false): bool {
        $element_plugin = $this->get_element()->get_element_plugin();

        if (!$element_plugin instanceof respondable_element_plugin) {
            return true;
        }

        $this->validation_errors = $element_plugin->validate_response(
            $this->raw_response_data,
            $this->get_element(),
            $is_draft_validation
        );

        return $this->validation_errors->count() === 0;
    }

    /**
     * Get validation errors produced by calling validate_response.
     * @see validate_response
     * @return collection|element_validation_error[]
     */
    public function get_validation_errors(): collection {
        return $this->validation_errors ?? new collection();
    }

    /**
     * Save section element response.
     * @return $this
     */
    public function save(): self {
        return builder::get_db()->transaction(function () {
            // Need to save first in case the response ID is required.

            $this->entity->save();

            $hook = new post_element_response_submission(
                $this->id,
                $this->element,
                $this->participant_instance,
                $this->raw_response_data,
            );
            $hook->execute();
            $this->set_response_data($hook->get_response_data());
            $this->entity->save();
            $this->computed_response_data = null;
            $this->computed_response_data_formatted_lines = null;

            return $this;
        });
    }

    /**
     * Get the relationship
     * @return string
     */
    public function get_relationship_name(): string {
        return $this->participant_instance->core_relationship->get_name();
    }

    /**
     * @inheritDoc
     */
    public function get_element(): ?element {
        $element = null;
        if ($this->section_element->element) {
            $element = $this->section_element->element;
        }

        return $element;
    }

    /**
     * Get the other participants responses grouped by relationship types (Manager/Appraiser).
     * @return collection
     */
    public function get_other_responder_groups(): ?collection {
        return $this->other_responder_groups;
    }

    /**
     * Does this response exist yet?
     *
     * @return bool
     */
    public function exists(): bool {
        return $this->entity->exists();
    }

    /**
     * Is the specified user allowed to view this response?
     *
     * @param element_response_entity $response
     * @param int|null $viewing_user_id Defaults to the current user if a user isn't specified.
     * @return bool
     */
    public static function can_user_view_response(element_response_entity $response, int $viewing_user_id = null): bool {
        if (!$response->exists()) {
            throw new coding_exception('Can not call user_can_view_response() on a non-existent response.');
        }
        $viewing_user_id = $viewing_user_id ?? user::logged_in()->id;

        $participant_instance = participant_instance::load_by_entity($response->participant_instance);

        if ($participant_instance->is_for_user($viewing_user_id)) {
            return true;
        }

        $participating_in_same_subject_instance_and_can_view = section_relationship::repository()
            // Join on the response record
            ->join('perform_section_element', 'section_id', 'section_id')
            ->join('perform_element_response', 'perform_section_element.id', 'section_element_id')
            ->where('perform_element_response.id', $response->id)
            // Check that the user also has a participant instance in the same section
            ->join(['perform_participant_instance', 'subject_pi'], 'perform_element_response.participant_instance_id', 'id')
            ->join('perform_subject_instance', 'subject_pi.subject_instance_id', 'id')
            ->join(['perform_participant_instance', 'viewer_pi'], 'perform_subject_instance.id', 'subject_instance_id')
            ->where('viewer_pi.participant_source', participant_source::INTERNAL)
            ->where('viewer_pi.participant_id', $viewing_user_id)
            ->where_field('subject_pi.participant_id', '!=', 'viewer_pi.participant_id')
            ->where_field('core_relationship_id', 'viewer_pi.core_relationship_id')
            // Must have can view other's responses enabled for the section's relationship
            ->where('can_view', 1)
            ->exists();

        if ($participating_in_same_subject_instance_and_can_view) {
            return true;
        }

        if (util::can_report_on_user($participant_instance->subject_instance->subject_user_id, $viewing_user_id)) {
            return true;
        }

        $hook = element_response_visibility::for_viewing_user($response, $viewing_user_id);
        $hook->execute();
        return $hook->get_can_view();
    }

    /**
     * Is the specified participant instance allowed to view this response?
     *
     * @param element_response_entity $response
     * @param participant_instance $viewing_participant_instance
     * @return bool
     */
    public static function can_participant_view_response(
        element_response_entity $response,
        participant_instance $viewing_participant_instance
    ): bool {
        if (!$response->exists()) {
            throw new coding_exception('Can not call user_can_view_response() on a non-existent response.');
        }

        if ($viewing_participant_instance->participant_source == participant_source::INTERNAL) {
            // Can just rely on the above method.
            return static::can_user_view_response($response, $viewing_participant_instance->participant_id);
        }

        $response_participant_instance = $response->participant_instance;

        if ($viewing_participant_instance->participant_id == $response_participant_instance->participant_id &&
            $response_participant_instance->participant_source == participant_source::EXTERNAL) {
            // Is the same (external) participant user.
            return true;
        }

        $participating_in_same_subject_instance_and_can_view = section_relationship::repository()
            // Join on the response record
            ->join('perform_section_element', 'section_id', 'section_id')
            ->join('perform_element_response', 'perform_section_element.id', 'section_element_id')
            ->where('perform_element_response.id', $response->id)
            // Check that the user also has a participant instance in the same section
            ->join(['perform_participant_instance', 'subject_pi'], 'perform_element_response.participant_instance_id', 'id')
            ->join('perform_subject_instance', 'subject_pi.subject_instance_id', 'id')
            ->join(['perform_participant_instance', 'viewer_pi'], 'perform_subject_instance.id', 'subject_instance_id')
            ->where('viewer_pi.id', $viewing_participant_instance->id)
            ->where_field('core_relationship_id', 'viewer_pi.core_relationship_id')
            // Must have can view other's responses enabled for the section's relationship
            ->where('can_view', 1)
            ->exists();

        if ($participating_in_same_subject_instance_and_can_view) {
            return true;
        }

        $hook = element_response_visibility::for_viewing_participant($response, $viewing_participant_instance);
        $hook->execute();
        return $hook->get_can_view();
    }

}

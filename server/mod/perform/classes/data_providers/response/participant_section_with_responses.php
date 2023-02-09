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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\data_providers\response;

use core\collection;
use core\orm\entity\repository;
use mod_perform\entity\activity\element_response;
use mod_perform\entity\activity\element_response as element_response_entity;
use mod_perform\entity\activity\participant_instance;
use mod_perform\entity\activity\participant_instance as participant_instance_entity;
use mod_perform\entity\activity\participant_instance_repository;
use mod_perform\entity\activity\participant_section as participant_section_entity;
use mod_perform\entity\activity\participant_section_repository;
use mod_perform\models\activity\activity_setting;
use mod_perform\models\activity\derived_responses_element_plugin;
use mod_perform\models\activity\participant_instance as participant_instance_model;
use mod_perform\models\activity\section_element;
use mod_perform\models\activity\section_relationship;
use mod_perform\models\activity\settings\visibility_conditions\visibility_manager;
use mod_perform\models\activity\settings\visibility_conditions\visibility_option;
use mod_perform\models\response\participant_section;
use mod_perform\models\response\responder_group;
use mod_perform\models\response\section_element_response;
use mod_perform\state\participant_section\complete as participant_section_complete;
use totara_core\entity\relationship as relationship_entity;
use totara_core\relationship\relationship;

class participant_section_with_responses {

    /** @var participant_section|null */
    private $participant_section;

    /** @var collection|section_element_response[] */
    private $others_section_element_responses;

    /** @var bool */
    private $load_responses_for_submission = false;

    /**
     * @var collection|relationship[]
     */
    private $other_participant_core_relationships;

    /**
     * @var derived_responder_group $derived_responder_group_provider
     */
    private $derived_responder_group_provider;

    /**
     * responses_for_participant_section constructor.
     *
     * @param participant_section $participant_section
     */
    public function __construct(participant_section $participant_section) {
        $this->participant_section = $participant_section;
        $this->others_section_element_responses = new collection();
    }

    /**
     * Process only participant's response. Used for submitting responses.
     *
     * @return $this
     */
    public function process_for_response_submission(): self {
        $this->load_responses_for_submission = true;

        return $this;
    }

    /**
     * Builds the participant section and all child data into memory.
     *
     * @return participant_section
     */
    public function build(): participant_section {
        $this->other_participant_core_relationships = $this->get_other_participants_core_relationships();

        $action_data = $this->analyze_response_visibility_and_other_participants();

        // Build the the responses from other participants.
        // We do this before building the main participants ($participant_id) responses because
        // these will be injected as child data to the main section_element_response models.
        if ($action_data['include_responder_groups']) {
            $this->others_section_element_responses = $this->create_others_element_responses(
                $action_data['other_participant_instances'],
                $this->participant_section->section->section_elements,
                $action_data['existing_responses']
            );
        }

        // We get the main responses after the "others responses" because
        // the "others responses" are children of the main responses through
        // relationship groups.
        $main_section_element_responses = $this->create_section_element_responses(
            $this->participant_section->participant_instance,
            $this->participant_section->section->section_elements,
            $action_data['existing_responses'],
            $action_data['include_responder_groups']
        );

        // Finally add section_element_responses collection to participant_section.
        return $this->participant_section->set_section_element_responses($main_section_element_responses);
    }

    /**
     * Analyzes the response visibility and the other participants responses to include.
     *
     * @return array
     */
    private function analyze_response_visibility_and_other_participants(): array {
        // Always include the viewing participant, as thy might have derived answers.
        $participant_instance_ids = [$this->participant_section->participant_instance_id];

        $other_participant_instances = new collection();
        $process_other_responses = false;
        $participant_can_view = $this->participant_section_relationship_can_view();

        if (!$this->load_responses_for_submission && $participant_can_view) {
            $other_participant_instances = $this->fetch_other_participant_instances();
            $process_other_responses = $this->visibility_conditions_pass($other_participant_instances);
            if ($other_participant_instances->count() > 0) {
                array_push($participant_instance_ids, ...$other_participant_instances->pluck('id'));
            }
        }

        $existing_responses = $this->fetch_existing_responses(
            $participant_instance_ids,
            $this->participant_section->section->section_elements->pluck('id')
        );

        return [
            'existing_responses' => $existing_responses,
            'other_participant_instances' => $other_participant_instances->map_to(participant_instance_model::class),
            'participant_relationship_has_can_view_permissions' => $participant_can_view,
            'include_responder_groups' => $process_other_responses && $participant_can_view,
        ];
    }

    /**
     * Fetch the already entered responses for every question for all participants.
     *
     * @param int[] $participant_instance_ids
     * @param int[] $section_element_ids
     * @return collection
     */
    private function fetch_existing_responses(array $participant_instance_ids, array $section_element_ids): collection {
        return element_response_entity::repository()->find_for_participants_and_section_elements(
            $participant_instance_ids,
            $section_element_ids
        );
    }

    /**
     * Fetch participant instances related to this participant section that are not the main participant ($participant_id).
     *
     * @return collection|participant_instance_entity[]
     */
    private function fetch_other_participant_instances(): collection {
        $participant_instance = $this->participant_section->participant_instance;

        return participant_instance_entity::repository()
            ->as('pi')
            // Bulk fetch all required related entities.
            ->with('core_relationship.resolvers') // Required for grouping section_element_responses by relationship_name.
            ->with('participant_user') // Required for the eventual output of other responders section_element_response models.
            ->with('participant_sections')
            ->join([participant_section_entity::TABLE, 'ps'], 'id', 'participant_instance_id')
            ->when(true, function (repository $repository) {
                participant_instance_repository::add_user_not_hidden_filter($repository, 'pi');
            })
            ->where('subject_instance_id', $participant_instance->subject_instance_id)
            // Guard on the actual participant_instance_id (rather than participant_user_id)
            // because we need to handle the case where the same user is both a manager and appraiser to the subject.
            ->where('pi.id', '!=', $this->participant_section->participant_instance_id)
            ->where('ps.section_id', $this->participant_section->section_id)
            ->when(
                get_config(null, 'perform_hide_incomplete_responses_closed_instances'),
                function (repository $repository) use ($participant_instance) {
                    // Hide other user's sections with progress state 'not submitted'.
                    $repository->where_raw(participant_section_repository::get_hide_incomplete_sections_condition_sql(
                        $participant_instance->participant_id,
                        $participant_instance->participant_source
                    ));
                }
            )
            ->left_join([relationship_entity::TABLE, 'cr'], 'core_relationship_id', 'cr.id')
            ->order_by('cr.sort_order')
            ->order_by('id')
            ->get();
    }

    /**
     * @param participant_instance_model $target_participant_instance
     * @param collection $section_elements
     * @param collection $existing_responses
     * @param bool $include_other_responder_groups
     * @param bool $include_draft
     *
     * @return collection|section_element_response[]
     */
    private function create_section_element_responses(
        participant_instance_model $target_participant_instance,
        collection $section_elements,
        collection $existing_responses,
        bool $include_other_responder_groups = false,
        bool $include_draft = true
    ): collection {
        if ($this->load_responses_for_submission) {
            $section_elements = $section_elements->filter(function (section_element $section_element_entity) {
                // We are only interested in respondable elements
                return $section_element_entity->element->is_respondable;
            });
        }

        return $section_elements->map(
            function (section_element $section_element) use (
                $target_participant_instance,
                $existing_responses,
                $include_other_responder_groups,
                $include_draft
            ) {
                //get target participant section
                $target_participant_section = $target_participant_instance->get_participant_sections()->filter(
                    function (participant_section $participant_section) use ($section_element) {
                        return $participant_section->section_id == $section_element->section_id;
                    }
                )->first();

                $is_derived_response_element = $section_element->get_element()->get_element_plugin() instanceof derived_responses_element_plugin;

                // The element response model will accept missing entities
                // in the case where a question has not yet been answered.
                if ($include_draft
                    || $is_derived_response_element
                    || $target_participant_section->get_progress_state() instanceof participant_section_complete
                ) {
                    $element_response_entity = $this->find_existing_response_entity(
                        $existing_responses,
                        $section_element->id,
                        $target_participant_instance->id
                    );
                } else {
                    $element_response_entity = null;
                }

                $other_responder_groups = new collection();
                if ($include_other_responder_groups) {
                    // Note that derived response elements do not need to be directly respondable (is_respondable = true).
                    // For example aggregation is not "respondable".
                    if ($is_derived_response_element) {
                        $other_responder_groups = $this->get_derived_responder_group_provider()->build_for($section_element);
                    } else if ($section_element->element->is_respondable) {
                        $other_responder_groups = $this->create_other_responder_groups($section_element->id);
                    }
                }

                $can_respond = $section_element->element->is_respondable;
                if ($is_derived_response_element) {
                    $can_respond = $this->get_derived_responder_group_provider()->viewing_participant_has_source_relationship($section_element);
                }

                return new section_element_response(
                    $target_participant_instance,
                    $section_element,
                    $element_response_entity,
                    $other_responder_groups,
                    $can_respond
                );
            }
        );
    }

    /**
     * Find an element response based on it's candidate key (section_element_id and participant_instance_id).
     *
     * @param collection $existing_responses
     * @param int $section_element_id
     * @param int $participant_instance_id
     * @return element_response_entity|null
     */
    protected function find_existing_response_entity(
        collection $existing_responses,
        int $section_element_id,
        int $participant_instance_id
    ): ?element_response_entity {
        return $existing_responses->find(
            function (element_response_entity $existing_element_response) use ($section_element_id, $participant_instance_id) {
                return (int) $existing_element_response->section_element_id === $section_element_id &&
                    (int) $existing_element_response->participant_instance_id === $participant_instance_id;
            }
        );
    }

    /**
     * Create the responses by other participants (not $participant_id) for a particular section element.
     *
     * @param collection|participant_instance_model[] $other_participant_instances
     * @param collection $section_elements
     * @param collection $existing_responses
     * @return collection
     */
    protected function create_others_element_responses(
        collection $other_participant_instances,
        collection $section_elements,
        collection $existing_responses
    ): collection {
        if ($other_participant_instances->count() <= 0) {
            return new collection();
        }

        $others_responses = [];
        foreach ($other_participant_instances as $other_participant_instance) {
            $element_responses = $this->create_section_element_responses(
                $other_participant_instance,
                $section_elements,
                $existing_responses,
                false,
                false
            );

            array_push($others_responses, ...$element_responses);
        }

        return new collection($others_responses);
    }

    /**
     * Creates the other responder groups for a section element.
     * If a relationship is not resolved, i.e. a user does not have a manger,
     * a responder group for the type is still created but it will be empty.
     *
     * Note: this is not yet filtering based on "perform_section_relationship.can_answer".
     *
     * @param int $section_element_id
     * @return collection|responder_group[]
     */
    private function create_other_responder_groups(int $section_element_id): collection {
        // Always create a group for the other participants relationship types.
        $grouped_by_relationship = [];

        foreach ($this->other_participant_core_relationships as $core_relationship) {
            $grouped_by_relationship[$core_relationship->get_name()] = [];
        }

        /** @var collection|section_element_response[] $others_responses */
        $others_responses = $this->others_section_element_responses->filter(
            function (section_element_response $other_response) use ($section_element_id) {
                return (int) $other_response->section_element_id === $section_element_id;
            }
        );

        foreach ($others_responses as $other_response) {
            $relationship_name = $other_response->get_relationship_name();

            // There is an edge case where the subject has more than one job assignment
            // and hence more than one manager or appraiser.
            // If we are fetching for one of the many manager/appraisers their relationship type name
            // will not have a group, as the relationship type name of the $participant_id
            // is excluded from the $this->other_participant_relationship_type_names array.
            if (!array_key_exists($relationship_name, $grouped_by_relationship)) {
                $grouped_by_relationship[$relationship_name] = [];
            }

            $grouped_by_relationship[$relationship_name][] = $other_response;
        }

        if ($this->is_anonymous_responses()) {
            $anonymous_group = $this->build_anonymous_responder_group($grouped_by_relationship);
            return new collection([$anonymous_group]);
        }

        $other_responder_groups = new collection();
        foreach ($grouped_by_relationship as $relationship_name => $responses) {
            if (!$this->relationship_group_can_answer($relationship_name)) {
                continue;
            }

            $other_responder_groups->append(new responder_group($relationship_name, new collection($responses)));
        }

        return $other_responder_groups;
    }

    private function relationship_group_can_answer(string $relationship_name): bool {
        /** @var section_relationship $section_relationship */
        $section_relationship = $this->participant_section
            ->section
            ->section_relationships
            ->find(function (section_relationship $section_relationship) use ($relationship_name) {
                return $section_relationship->core_relationship->get_name() === $relationship_name;
            });

        if ($section_relationship === null) {
            throw new \coding_exception("${relationship_name} not found in section relationships");
        }

        return $section_relationship->can_answer;
    }

    /**
     * Lazily fetch all the relationship type names of the other participants.
     * Any double ups are removed.
     *
     * @return collection|relationship[]
     */
    private function get_other_participants_core_relationships(): collection {
        $section_relationships = $this->participant_section->section->section_relationships;

        $core_relationships = $section_relationships->map(
            function (section_relationship $section_relationship) {
                return $section_relationship->core_relationship;
            }
        );

        $main_participant_relationship = $this->fetch_main_participant_core_relationship();

        return $core_relationships->filter(
            function (relationship $core_relationship) use ($main_participant_relationship) {
                return $core_relationship->get_id() !== $main_participant_relationship->get_id();
            }
        );
    }

    /**
     * Fetch the relationship to the subject for the main participant ($participant_id).
     *
     * @return relationship
     */
    private function fetch_main_participant_core_relationship(): relationship {
        return $this->participant_section
            ->participant_instance
            ->core_relationship;
    }

    /**
     * Checks if section relationship for the participant has can_view permissions.
     *
     * @return bool
     */
    private function participant_section_relationship_can_view(): bool {
        return $this->participant_section->can_view_others_responses();
    }

    /**
     * Checks if section relationship for the participant has can_answer permissions.
     *
     * @return bool
     */
    private function participant_section_relationship_can_answer(): bool {
        return $this->participant_section->get_can_answer();
    }

    /**
     * Checks if visibility conditions on activity allow viewing other responses.
     *
     * @param collection|participant_instance[] $participant_instances
     * @return bool
     */
    private function visibility_conditions_pass(collection $participant_instances): bool {
        $visibility_option = $this->get_activity_visibility_option();

        return $visibility_option->show_responses($this->participant_section->participant_instance, $participant_instances);
    }

    /**
     * Get the activity visibility setting.
     *
     * @return visibility_option
     */
    private function get_activity_visibility_option(): visibility_option {
        $activity_settings = $this->participant_section->section->activity->get_settings();
        $visibility_value = $activity_settings->lookup(activity_setting::VISIBILITY_CONDITION) ?? 0;

        return (new visibility_manager())->get_option_with_value($visibility_value);
    }

    private function is_anonymous_responses(): bool {
        return $this->participant_section->section->activity->anonymous_responses;
    }

    /**
     * @param array $relationship_name_to_responses_map
     * @return responder_group
     */
    private function build_anonymous_responder_group(array $relationship_name_to_responses_map): responder_group {
        $anonymous_group = responder_group::create_anonymous_group();

        foreach ($relationship_name_to_responses_map as $relationship_name => $responses) {
            if (!$this->relationship_group_can_answer($relationship_name)) {
                continue;
            }

            $anonymous_group->append_responses($responses);
        }

        return $anonymous_group;
    }

    private function get_derived_responder_group_provider(): derived_responder_group {
        if ($this->derived_responder_group_provider === null) {
            $this->derived_responder_group_provider = derived_responder_group::for_participant_section(
                $this->participant_section,
                $this->is_anonymous_responses()
            );
        }

        return $this->derived_responder_group_provider;
    }

}

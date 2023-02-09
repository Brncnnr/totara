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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\data_providers\response;

use core\collection;
use core\orm\entity\repository;
use mod_perform\entity\activity\element_response as element_response_entity;
use mod_perform\entity\activity\participant_instance;
use mod_perform\entity\activity\participant_instance as participant_instance_entity;
use mod_perform\entity\activity\participant_instance_repository;
use mod_perform\entity\activity\participant_section as participant_section_entity;
use mod_perform\models\response\participant_section;
use mod_perform\entity\activity\section as section_entity;
use mod_perform\models\activity\section;
use mod_perform\entity\activity\section_element as section_element_entity;
use mod_perform\entity\activity\section_relationship;
use mod_perform\entity\activity\subject_instance as subject_instance_entity;
use mod_perform\models\activity\subject_instance;
use mod_perform\models\activity\derived_responses_element_plugin;
use mod_perform\models\activity\element;
use mod_perform\models\activity\participant_instance as participant_instance_model;
use mod_perform\models\activity\respondable_element_plugin;
use mod_perform\models\activity\section_element;
use mod_perform\models\response\responder_group;
use mod_perform\models\response\section_element_response;
use mod_perform\models\response\view_only_element_response;
use mod_perform\models\response\view_only_section;
use totara_core\relationship\relationship as core_relationship_model;

class view_only_section_with_responses {

    /**
     * @var section_entity
     */
    protected $section_entity;

    /**
     * @var subject_instance_entity
     */
    protected $subject_instance_entity;

    /**
     * @var view_only_section
     */
    protected $view_only_section;

    /**
     * @var collection|section_element_entity[]
     */
    protected $section_elements;

    /**
     * @var collection|participant_section_entity[]
     */
    protected $responding_participant_sections;

    /**
     * @var collection|section_relationship[]
     */
    protected $responding_section_relationships;

    /**
     * @var collection|element_response_entity[]
     */
    protected $existing_responses;

    /**
     * @var derived_responder_group
     */
    protected $derived_responder_group_provider;

    /**
     * @param section_entity $section_entity
     * @param subject_instance_entity $subject_instance_entity
     */
    public function __construct(section_entity $section_entity, subject_instance_entity $subject_instance_entity) {
        $this->section_entity = $section_entity;
        $this->subject_instance_entity = $subject_instance_entity;
    }

    /**
     * Load the participant section and all child data into memory.
     *
     * @return $this
     */
    public function fetch(): self {
        // Load all data required.
        $this->responding_participant_sections = $this->fetch_responding_participant_sections();
        $this->section_elements = $this->fetch_section_elements();

        $responding_participant_instances_ids = $this->responding_participant_sections->pluck('participant_instance_id');
        $section_element_ids = $this->section_elements->pluck('id');

        $this->existing_responses = $this->fetch_existing_responses(
            $responding_participant_instances_ids,
            $section_element_ids
        );

        $this->responding_section_relationships = $this->fetch_responding_relationships();

        // Build the child virtual models.
        $section_element_responses = $this->build_section_element_responses();
        $siblings = $this->fetch_sibling_sections();

        // Finally create the top level model and inject all the child models.
        $this->view_only_section = new view_only_section(
            $this->section_entity,
            $section_element_responses,
            $siblings
        );

        return $this;
    }

    /**
     * Get the fetched view_only_section.
     *
     * @return view_only_section|null
     */
    public function get(): ?view_only_section {
        return $this->view_only_section;
    }

    /**
     * Fetch the already entered responses for every question for both the main participant ($participant_id)
     * and other responder groups/participants.
     *
     * @param int[] $participant_instance_ids
     * @param int[] $section_element_ids
     * @return collection
     */
    protected function fetch_existing_responses(array $participant_instance_ids, array $section_element_ids): collection {
        return element_response_entity::repository()->find_for_participants_and_section_elements(
            $participant_instance_ids,
            $section_element_ids
        );
    }

    /**
     * Fetch responding participant sections related to this section.
     *
     * @return collection|participant_section_entity[]
     */
    protected function fetch_responding_participant_sections(): collection {
        return participant_section_entity::repository()
            ->as('ps')
            ->select_raw('ps.*')
            // Required for the eventual output of other responders section_element_response models.
            ->with('participant_instance.participant_user')
            ->join([section_relationship::TABLE, 'sr'], 'sr.section_id', 'ps.section_id')
            ->join([participant_instance_entity::TABLE, 'pi'], 'pi.id', 'ps.participant_instance_id')
            ->when(true, function (repository $repository) {
                participant_instance_repository::add_user_not_hidden_filter($repository, 'pi');
            })
            ->where('ps.section_id', $this->section_entity->id)
            ->where('pi.subject_instance_id', $this->subject_instance_entity->id)
            ->where_raw('pi.core_relationship_id = sr.core_relationship_id')
            ->where('sr.can_answer', true)
            ->get();
    }

    /**
     * Fetch all section elements.
     *
     * @return collection|section_element_entity[]
     */
    protected function fetch_section_elements(): collection {
        return section_element_entity::repository()
            ->as('se')
            ->where('se.section_id', $this->section_entity->id)
            ->order_by('se.sort_order')
            ->get();
    }

    /**
     * @return collection|section_relationship[]
     */
    protected function fetch_responding_relationships(): collection {
        return section_relationship::repository()
            ->as('sr')
            ->with('core_relationship.resolvers')
            ->where('section_id', $this->section_entity->id)
            ->where('can_answer', true)
            ->get();
    }

    /**
     * @return collection|section_entity[]
     */
    protected function fetch_sibling_sections(): collection {
        return section_entity::repository()
            ->as('s')
            ->where('activity_id', $this->section_entity->activity_id)
            ->order_by('sort_order')
            ->get();
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
            function (
                element_response_entity $existing_element_response
            ) use ($section_element_id, $participant_instance_id) {
                return (int) $existing_element_response->section_element_id === $section_element_id &&
                    (int) $existing_element_response->participant_instance_id === $participant_instance_id;
            }
        );
    }

    /**
     * @return collection|view_only_element_response[]
     */
    protected function build_section_element_responses(): collection {
        return $this->section_elements->map(function (section_element_entity $section_element) {
            $other_responder_groups = new collection();

            $element_plugin = element::load_by_entity($section_element->element)->get_element_plugin();

            // Don't return peoples "responses" for non-respondable elements.
            if ($element_plugin instanceof respondable_element_plugin) {
                $other_responder_groups = $this->build_responder_groups_for_section_element(
                    $section_element
                );
            }

            if ($element_plugin instanceof derived_responses_element_plugin) {
                $other_responder_groups = $this->get_derived_responder_group_provider()->build_for(
                    new section_element($section_element)
                );
            }

            return new view_only_element_response($section_element, $other_responder_groups);
        });
    }

    /**
     * @param section_element_entity $section_element
     * @return collection|responder_group[]
     */
    protected function build_responder_groups_for_section_element(
        section_element_entity $section_element
    ): collection {
        $responder_groups = $this->build_relationship_id_to_responder_group_map();
        $anonymous_group = responder_group::create_anonymous_group();

        foreach ($this->responding_participant_sections as $participant_section) {
            $core_relationship_id = $participant_section->participant_instance->core_relationship_id;
            $responder_group = $responder_groups[$core_relationship_id] ?? null;

            // Not a responding relationship, skip.
            if ($responder_group === null) {
                continue;
            }

            $response_entity = $this->find_non_draft_element_response($section_element, $participant_section);

            $section_element_response = new section_element_response(
                new participant_instance_model($participant_section->participant_instance),
                new section_element($section_element),
                $response_entity,
                new collection()
            );

            // If anonymous responses are enabled, we group them all together.
            if ($this->is_anonymous_responses()) {
                $anonymous_group->append_response($section_element_response);
            } else {
                $responder_group->append_response($section_element_response);
            }
        }

        if ($this->is_anonymous_responses()) {
            return collection::new([$anonymous_group]);
        }

        return collection::new(array_values($responder_groups));
    }

    /**
     * Builds a map of core relationship ids to an empty responder group with
     * only the name populated.
     *
     * @return responder_group[]
     */
    protected function build_relationship_id_to_responder_group_map(): array {
        return $this->responding_section_relationships->key_by('core_relationship_id')
            ->map(function (section_relationship $section_relationship) {
                $name = (new core_relationship_model($section_relationship->core_relationship))->get_name();

                return new responder_group($name, new collection());
            })->all(true);
    }

    protected function is_anonymous_responses(): bool {
        return $this->section_entity->activity->anonymous_responses;
    }

    /**
     * Find an element response entity for a particular section element and participant section.
     *
     * This will also consider the progress status of the participant section,
     * and return null for participant sections that have a progress status other than "COMPLETE".
     *
     * @param section_element_entity $section_element
     * @param participant_section_entity $participant_section_entity
     * @return mixed|null
     */
    protected function find_non_draft_element_response(
        section_element_entity $section_element,
        participant_section_entity $participant_section_entity
    ): ?element_response_entity {
        $participant_section = new participant_section($participant_section_entity);

        if (!$participant_section->is_complete()) {
            return null;
        }

        return $this->existing_responses->find(
            function (element_response_entity $response) use ($section_element, $participant_section_entity) {
                return (int) $response->section_element_id === (int) $section_element->id
                    && (int) $response->participant_instance_id === (int) $participant_section_entity->participant_instance_id;
            }
        );
    }

    private function get_derived_responder_group_provider(): derived_responder_group {
        if ($this->derived_responder_group_provider === null) {
            $this->derived_responder_group_provider = derived_responder_group::for_view_only_section(
                new section($this->section_entity),
                new subject_instance($this->subject_instance_entity),
                $this->is_anonymous_responses()
            );
        }

        return $this->derived_responder_group_provider;
    }

}

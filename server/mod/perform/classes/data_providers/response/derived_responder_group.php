<?php
/*
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
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\data_providers\response;

use coding_exception;
use core\collection;
use mod_perform\entity\activity\element_response as element_response_entity;
use mod_perform\entity\activity\section_element_reference;
use mod_perform\entity\activity\section_relationship;
use mod_perform\entity\activity\subject_instance as subject_instance_entity;
use mod_perform\models\activity\element_plugin;
use mod_perform\models\activity\participant_instance;
use mod_perform\entity\activity\participant_instance as participant_instance_entity;
use mod_perform\models\activity\section;
use mod_perform\models\activity\subject_instance;
use mod_perform\models\activity\section_element;
use mod_perform\entity\activity\section_element as section_element_entity;
use mod_perform\entity\activity\element as element_entity;
use mod_perform\models\response\participant_section;
use mod_perform\models\response\responder_group;
use mod_perform\models\response\section_element_response;
use totara_core\relationship\relationship;

/**
 * Class derived_responder_group
 *
 * Creates responder groups for derived response elements that use section element references (aggregation element).
 * This class is necessary because the groups may not line up with the groups in the section they will be displayed in.
 *
 * @package mod_perform\data_providers\response
 */
class derived_responder_group {

    /**
     * Special virtual relationship for the viewing participants own responses, i.e. "Your response".
     */
    private const VIEWING_PARTICIPANT_GROUP = - 1;

    /**
     * @var collection|participant_instance[]
     */
    protected $source_participant_instances;

    /**
     * @var section
     */
    protected $target_section;

    /**
     * @var int
     */
    protected $viewing_source_participant_instance;

    /**
     * @var collection|element_response_entity[]
     */
    private $existing_derived_responses;

    /**
     * @var collection|relationship[]
     */
    private $derived_source_relationships;

    /**
     * @var bool
     */
    private $is_anonymous_responses;

    /**
     * @var bool
     */
    private $is_prepared;

    /**
     * Factory method for when building responder groups from the perspective of a specific participant (participant section).
     * The viewing participant's responses will be filtered out, and the relationship group they belong to will be removed,
     * unless there are other source participants that have the same relationship
     * i.e. the viewing participant is a manager, and there is another manager.
     *
     * @param participant_section $display_participant_section
     * @param bool $is_anonymous_responses
     * @return static
     */
    public static function for_participant_section(
        participant_section $display_participant_section,
        bool $is_anonymous_responses
    ): self {
        $target_section = $display_participant_section->get_section();
        $source_participant_instances = participant_instance_entity::repository()
            ->as('pi')
            ->join([subject_instance_entity::TABLE, 'si'], 'si.id', 'pi.subject_instance_id')
            ->join([participant_instance_entity::TABLE, 'display_pi'], 'display_pi.subject_instance_id', 'si.id')
            ->where('display_pi.id', $display_participant_section->participant_instance_id)
            ->with('core_relationship')
            ->order_by('id')
            ->get()
            ->map_to(participant_instance::class);

        $viewing_participant_instance = $source_participant_instances->find(
            'id',
            $display_participant_section->participant_instance_id
        );

        return new static($target_section, $source_participant_instances, $viewing_participant_instance, $is_anonymous_responses);
    }

    /**
     * Factory method for when building responder groups from the perspective no participant.
     * There is no filtering of any source participants responses, and no removal of responder groups.
     *
     * @param section $section
     * @param subject_instance $subject_instance
     * @param bool $is_anonymous_responses
     * @return static
     */
    public static function for_view_only_section(
        section $section,
        subject_instance $subject_instance,
        bool $is_anonymous_responses
    ): self {
        $source_participant_instances = participant_instance_entity::repository()
            ->as('pi')
            ->where('pi.subject_instance_id', $subject_instance->get_id())
            ->with('core_relationship')
            ->order_by('id')
            ->get()
            ->map_to(participant_instance::class);

        return new static($section, $source_participant_instances, null, $is_anonymous_responses);
    }

    /**
     * derived_response_responder_groups constructor.
     *
     * @param section $target_section
     * @param collection|participant_instance[] $source_participant_instances
     * @param participant_instance|null $viewing_source_participant_instance The viewing participant
     * that is also a source section respondant.
     * @param bool $is_anonymous_responses
     */
    public function __construct(
        section $target_section,
        collection $source_participant_instances,
        ?participant_instance $viewing_source_participant_instance,
        bool $is_anonymous_responses
    ) {
        $this->source_participant_instances = $source_participant_instances
            ->sort(function (participant_instance $a, participant_instance $b) {
                $by_relationship = $a->get_core_relationship()->sort_order <=> $b->get_core_relationship()->sort_order;

                if ($by_relationship === 0) {
                    return $a->id <=> $b->id;
                }

                return $by_relationship;
            });

        $this->target_section = $target_section;
        $this->viewing_source_participant_instance = $viewing_source_participant_instance;
        $this->is_anonymous_responses = $is_anonymous_responses;
    }

    /**
     * Build the other responders group for a section element (the element displaying derived responses).
     *
     * @param section_element $target_section_element
     * @return collection|responder_group[]
     * @throws coding_exception
     */
    public function build_for(section_element $target_section_element): collection {
        if (!$this->is_prepared) {
            $this->prepare();
        }

        $responses_by_core_relationship = $this->group_responses_by_core_relationship($target_section_element);
        $source_relationships = $this->find_derived_source_relationships($target_section_element->element_id);
        $responder_groups = new collection();

        if ($this->is_anonymous_responses) {
            $anonymous_group = responder_group::create_anonymous_group();

            foreach ($responses_by_core_relationship as $core_relationship_id => $section_element_responses) {
                $anonymous_group->append_responses($section_element_responses);
            }

            $responder_groups->append($anonymous_group);

            return $responder_groups;
        }

        foreach ($responses_by_core_relationship as $core_relationship_id => $section_element_responses) {
            /** @var relationship $relationship */
            $relationship = collection::new($source_relationships)->find('id', $core_relationship_id);

            $responder_group = new responder_group(
                $relationship->get_name(),
                collection::new($section_element_responses),
                $relationship->sort_order
            );

            $responder_groups->append($responder_group);
        }

        return $responder_groups->sort(function (responder_group $a, responder_group $b) {
            return ($a->get_sort_order() <=> $b->get_sort_order());
        }, null, false);
    }

    /**
     * Checks if the viewing participant instance has a relationship with can_answer permissions in any of the source sections.
     *
     * @param section_element $target_section_element
     * @return bool
     */
    public function viewing_participant_has_source_relationship(section_element $target_section_element): bool {
        if ($this->viewing_source_participant_instance === null) {
            return false;
        }

        if (!$this->is_prepared) {
            $this->prepare();
        }

        $source_relationships = $this->find_derived_source_relationships($target_section_element->element_id);
        foreach ($source_relationships as $source_relationship) {
            if ($source_relationship->get_id() === (int) $this->viewing_source_participant_instance->core_relationship_id) {
                return true;
            }
        }

        return false;
    }

    private function prepare(): self {
        if (count($this->source_participant_instances) === 0) {
            $this->existing_derived_responses = [];
            $this->derived_source_relationships = [];
        } else {
            $this->existing_derived_responses = $this->fetch_existing_responses();
            $this->derived_source_relationships = $this->fetch_derived_source_can_answer_relationships();
        }

        $this->is_prepared = true;

        return $this;
    }

    /**
     * Fetch all core relationships for source sections.
     * These are only answering relationships and only sections with section_element_references are populated.
     * The result is keyed by referencing_element_id:
     * i.e. "100"
     *
     * @return relationship[][]
     */
    private function fetch_derived_source_can_answer_relationships(): array {
        $derived_responses_plugin_names = array_keys(element_plugin::get_derived_responses_plugins());
        $referencing_section_element_ids = $this->target_section->get_section_elements()->pluck('id');

        /** @var section_element_reference[] $references */
        $references = section_element_reference::repository()
            ->as('ser')
            ->join([element_entity::TABLE, 'referencing_element'], 'referencing_element.id', 'ser.referencing_element_id')
            ->join(
                [section_element_entity::TABLE, 'referencing_section_element'],
                'referencing_section_element.element_id',
                'ser.referencing_element_id'
            )
            ->where_in('referencing_element.plugin_name', $derived_responses_plugin_names)
            ->where_in('referencing_section_element.id', $referencing_section_element_ids)
            ->with('source_section_element.section.section_relationships.core_relationship.resolvers')
            ->order_by('id')
            ->get();

        $derived_source_relationships = [];
        foreach ($references as $reference) {
            $responding_source_relationships = $reference->source_section_element
                ->section
                ->section_relationships
                ->filter(function (section_relationship $section_relationship) {
                    return $section_relationship->can_answer;
                })
                ->map(function (section_relationship $section_relationship) {
                    return new relationship($section_relationship->core_relationship);
                })
                ->all();

            $key = $reference->referencing_element_id;

            if (!array_key_exists($key, $derived_source_relationships)) {
                $derived_source_relationships[$key] = [];
            }

            foreach ($responding_source_relationships as $source_relationship) {
                $already_in_group = collection::new($derived_source_relationships[$key])
                        ->has(function (relationship $relationship) use ($source_relationship) {
                            return $relationship->get_id() === $source_relationship->get_id();
                        });

                if (!$already_in_group) {
                    $derived_source_relationships[$key][] = $source_relationship;
                }
            }
        }

        foreach ($derived_source_relationships as &$derived_source_relationship) {
            usort($derived_source_relationship, function (relationship $a, relationship $b) {
                return $a->sort_order <=> $b->sort_order;
            });
        }

        return $derived_source_relationships;
    }

    /**
     * @param int $referencing_element_id
     * @return relationship[]
     */
    private function find_derived_source_relationships(int $referencing_element_id): array {
        $key = $referencing_element_id;

        return $this->derived_source_relationships[$key] ?? [];
    }

    private function derived_source_relationship_exists(int $referencing_element_id, int $core_relationship_id): bool {
        return collection::new($this->find_derived_source_relationships($referencing_element_id))->has('id', $core_relationship_id);
    }

    /**
     * Fetch the already entered responses for every question for all participants.
     * The result is keyed by participant_instance_id and section_element_id:
     * i.e. "100_202".
     *
     * @return element_response_entity[]
     */
    private function fetch_existing_responses(): array {
        $participant_instance_ids = $this->source_participant_instances->pluck('id');
        $section_element_ids = $this->target_section->get_section_elements()->pluck('id');

        /** @var element_response_entity[] $element_responses */
        $element_responses = element_response_entity::repository()->find_for_participants_and_section_elements(
            $participant_instance_ids,
            $section_element_ids
        );

        $keyed_element_responses = [];
        foreach ($element_responses as $element_response) {
            $key = $element_response->participant_instance_id . '_' . $element_response->section_element_id;
            $keyed_element_responses[$key] = $element_response;
        }

        return $keyed_element_responses;
    }

    private function find_element_response(int $participant_instance_id, int $section_element_id): ?element_response_entity {
        $key = $participant_instance_id . '_' . $section_element_id;

        return $this->existing_derived_responses[$key] ?? null;
    }

    /**
     * @param section_element $target_section_element
     * @return section_element_response[][]
     * @throws coding_exception
     */
    private function group_responses_by_core_relationship(section_element $target_section_element): array {
        $responses_by_core_relationship = [];

        // Create all the empty responder groups.
        $source_relationships = $this->find_derived_source_relationships($target_section_element->element_id);
        foreach ($source_relationships as $source_relationship) {
            $responses_by_core_relationship[$source_relationship->get_id()] = [];
        }

        // Iterate through the potential participants, and populate them if they are a responding participant in the source section.
        foreach ($this->source_participant_instances as $potential_participant_instance) {
            $potential_participant_relationship_id = $potential_participant_instance->core_relationship_id;

            // This participant instance is not a responding participant in the source section, continue.
            if (!$this->derived_source_relationship_exists(
                $target_section_element->element_id,
                $potential_participant_relationship_id
            )
            ) {
                continue;
            }

            $element_response_entity = $this->find_element_response(
                $potential_participant_instance->id,
                $target_section_element->get_id()
            );

            $section_element_response = new section_element_response(
                $potential_participant_instance,
                $target_section_element,
                $element_response_entity,
                new collection(),
            );

            if ($this->viewing_source_participant_instance !== null
                && $potential_participant_instance->get_id() === $this->viewing_source_participant_instance->get_id()) {
                $responses_by_core_relationship[self::VIEWING_PARTICIPANT_GROUP] = [$section_element_response];
            } else {
                $responses_by_core_relationship[$potential_participant_relationship_id][] = $section_element_response;
            }
        }

        if ($this->viewing_source_participant_instance !== null) {
            // If the viewing participant was the only one in their group delete their group we don't need it.
            // The viewing participant will live in their special "Your response" group.
            $core_relationship_id = $this->viewing_source_participant_instance->core_relationship_id;
            $viewing_participants_group = $responses_by_core_relationship[$core_relationship_id] ?? null;
            if ($viewing_participants_group !== null && count($viewing_participants_group) === 0) {
                unset($responses_by_core_relationship[$this->viewing_source_participant_instance->core_relationship_id]);
            }
        }

        // The viewing participant are attached the the parent section_element_response,
        // that these responder groups will be attached to. So now we have determined if
        // we need a responder group of the viewing participants relationship or not, we can remove their group.
        unset($responses_by_core_relationship[self::VIEWING_PARTICIPANT_GROUP]);

        return $responses_by_core_relationship;
    }

}
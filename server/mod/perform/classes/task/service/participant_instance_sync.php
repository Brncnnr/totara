<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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

namespace mod_perform\task\service;

use coding_exception;
use core\collection;
use mod_perform\entity\activity\participant_instance as participant_instance_entity;
use mod_perform\entity\activity\section_relationship;
use mod_perform\entity\activity\subject_instance;
use mod_perform\models\activity\activity_setting;
use mod_perform\models\activity\helpers\participation_sync_settings_helper;
use mod_perform\models\activity\participant_instance as participant_instance_model;
use mod_perform\models\activity\participant_source;
use mod_perform\state\participant_instance\closed as participant_instance_closed;
use mod_perform\state\participant_instance\complete as participant_instance_complete;
use mod_perform\state\participant_instance\not_started;
use mod_perform\state\participant_instance\open;
use mod_perform\state\subject_instance\complete;
use mod_perform\state\subject_instance\open as subject_instance_open;
use mod_perform\state\subject_instance\pending;
use mod_perform\task\service\data\subject_instance_activity_collection;
use totara_core\relationship\relationship_resolver;
use totara_core\relationship\relationship_resolver_dto;

class participant_instance_sync extends participant_instance_service {

    /** @var participation_sync_settings_helper */
    private $sync_settings_helper;

    public function sync_instances(): void {
        $this->activity_collection = new subject_instance_activity_collection();

        $subject_instances = subject_instance::repository()
            ->with('track')
            ->where('needs_sync', 1)
            ->where('availability', subject_instance_open::get_code())
            ->where('progress', '<>', complete::get_code())
            ->where('status', '<>', pending::get_code())
            ->get();

        if ($subject_instances->count() < 1) {
            return;
        }

        $this->sync_settings_helper = participation_sync_settings_helper::create_from_subject_instances($subject_instances);

        // Remove subject instances that are configured not to be synchronised from the collection. Also, unflag those.
        [
            $subject_instance_dtos_to_process,
            $subject_instance_ids_to_unflag
        ] = $subject_instances->reduce(
            function (array $tuple, subject_instance $subject_instance): array {
                [$subject_instance_dtos, $subject_instances_to_unflag] = $tuple;
                $activity_id = $subject_instance->track->activity->id;

                if (!$this->sync_settings_helper->should_instance_creation_be_synced($activity_id)
                    && !$this->sync_settings_helper->should_instance_closure_be_synced($activity_id)) {
                    $subject_instances_to_unflag[] = $subject_instance->id;
                } else {
                    $dto = subject_instance_dto::create_from_entity($subject_instance);
                    $subject_instance_dtos->append($dto);
                }

                return [$subject_instance_dtos, $subject_instances_to_unflag];
            },
            [collection::new([]), []]
        );

        $this->unflag_subject_instances($subject_instance_ids_to_unflag);
        $this->aggregate_participant_instances($subject_instance_dtos_to_process);
    }

    /**
     * @param array $subject_instance_ids
     * @return void
     */
    private function unflag_subject_instances(array $subject_instance_ids): void {
        subject_instance::repository()
            ->where_in('id', $subject_instance_ids)
            ->update(['needs_sync' => 0]);
    }

    /**
     * Synchronise participant instances for a list of relationships.
     * Creates, re-opens and closes participant instances according to the subject's current relationships.
     *
     * @param array $relationship_data Contains core_relationships, activity_id, subject instance and participant ids.
     * @return void
     */
    protected function create_participant_instances_for_relationships(array $relationship_data): void {
        $section_relationships_per_core_relationship = $relationship_data['section_relationships_per_core_relationship'];
        /** @var subject_instance_dto $subject_instance */
        $subject_instance = $relationship_data['subject_instance'];
        $participant_dtos = $relationship_data['participant_dtos'];

        $existing_participant_instances = $this->get_existing_participant_instances(
            $subject_instance->get_id(),
            array_keys($section_relationships_per_core_relationship)
        );

        /**
         * @var int $core_relationship_id
         * @var section_relationship[] $section_relationships
         */
        $to_be_added_participant_instance_data = [];
        $to_be_reopened_participant_instance_ids = [];
        $to_be_closed_participant_instance_ids = [];
        foreach ($section_relationships_per_core_relationship as $core_relationship_id => $section_relationships) {
            $relationship_participant_dtos = $participant_dtos[$core_relationship_id] ?? [];

            // Make sure we only have internal relationships.
            $relationship_participant_dtos = array_filter(
                $relationship_participant_dtos,
                static function (relationship_resolver_dto $relationship_participant) {
                    return $relationship_participant->get_source() === relationship_resolver::SOURCE;
                }
            );

            /*
             * Build an array of participant ids from the dtos. These are the "to be" participants that reflect
             * the actual current relationship to the subject.
             */
            $calculated_relationship_participant_ids = array_map(
                static function (relationship_resolver_dto $relationship_participant) {
                    return $relationship_participant->get_user_id();
                },
                $relationship_participant_dtos
            );

            // Only add/re-open participant instances if the setting requires it.
            if ($this->sync_settings_helper->should_instance_creation_be_synced($subject_instance->get_activity_id())) {
                $to_be_added_participant_instance_data[] = [
                    'participant_instance_data' =>
                        $this->build_participant_instance_data(
                            $core_relationship_id,
                            $subject_instance,
                            $section_relationships
                        ),
                    'participant_dtos' => $this->get_to_be_added_participant_instance_dtos(
                        $existing_participant_instances[$core_relationship_id],
                        $calculated_relationship_participant_ids,
                        $relationship_participant_dtos
                    )
                ];

                $to_be_reopened_participant_instance_ids[] = $this->get_to_be_reopened_participant_instance_ids(
                    $existing_participant_instances[$core_relationship_id],
                    $calculated_relationship_participant_ids
                );
            }

            // Only close participant instances if the setting requires it.
            if ($this->sync_settings_helper->should_instance_closure_be_synced($subject_instance->get_activity_id())) {
                $to_be_closed_participant_instance_ids[] = $this->get_to_be_closed_participant_instance_ids(
                    $existing_participant_instances[$core_relationship_id],
                    $calculated_relationship_participant_ids
                );
            }
        }

        /**
         * Process the changes for this subject instance in the correct order to make sure we end up with the expected
         * progress and availability states.
         * Adding and re-opening participant instances will not affect the current subject instance progress state
         * because we exclude subject instances with 'complete' progress or 'closed' availability. Closing can affect
         * the state (it could lead to completing/closing the whole subject instance), so it's done last.
         *
         *  1. Add new participant instances.
         *  2. Re-open closed participant instances.
         *  3. Close participant instances.
         */

        if (count($to_be_added_participant_instance_data) > 0) {
            foreach ($to_be_added_participant_instance_data as $to_be_added_data) {
                $this->create_participant_instances_for_user_list(
                    $to_be_added_data['participant_instance_data'],
                    $to_be_added_data['participant_dtos']
                );
            }
            // Flush the buffer now, so they are actually created before we close other participant instances for the
            // same subject instance.
            $this->save_data_internal();
        }

        $to_be_reopened_participant_instance_ids = array_merge([], ...$to_be_reopened_participant_instance_ids);
        foreach ($to_be_reopened_participant_instance_ids as $participant_instance_id) {
            $participant_instance = participant_instance_model::load_by_id($participant_instance_id);
            $participant_instance->manually_open(false, true);
        }

        $to_be_closed_participant_instance_ids = array_merge([], ...$to_be_closed_participant_instance_ids);
        foreach ($to_be_closed_participant_instance_ids as $participant_instance_id) {
            $participant_instance = participant_instance_model::load_by_id($participant_instance_id);
            $participant_instance->manually_close();
        }

        // Un-flag the subject instance
        $this->unflag_subject_instances([$subject_instance->get_id()]);
    }

    /**
     * Get participant instances currently existing in DB for the given subject instance and relationship ids.
     *
     * @param int $subject_instance_id
     * @param array $core_relationship_ids
     * @return collection[]  array of collections of participant instance entities, keyed by core relationship id
     */
    private function get_existing_participant_instances(int $subject_instance_id, array $core_relationship_ids): array {
        $existing_participant_instances = [];
        foreach ($core_relationship_ids as $core_relationship_id) {
            $existing_participant_instances[$core_relationship_id] = new collection();
        }
        $existing = participant_instance_entity::repository()
            ->where('subject_instance_id', $subject_instance_id)
            ->where_in('core_relationship_id', $core_relationship_ids)
            ->where('participant_source', participant_source::INTERNAL)
            ->get();
        /** @var participant_instance_entity $participant_instance */
        foreach ($existing as $participant_instance) {
            $existing_participant_instances[$participant_instance->core_relationship_id]->append($participant_instance);
        }
        return $existing_participant_instances;
    }

    /**
     * From the given relationship_participant_dtos, return only the ones that have to be added because they don't
     * exist in the DB yet.
     *
     * @param collection $existing_participant_instances
     * @param array $calculated_relationship_participant_ids
     * @param array $relationship_participant_dtos
     * @return array
     */
    private function get_to_be_added_participant_instance_dtos(
        collection $existing_participant_instances,
        array $calculated_relationship_participant_ids,
        array $relationship_participant_dtos
    ): array {
        // Get existing participant instances that match the calculated ones.
        $existing_matching_participant_ids = $existing_participant_instances
            ->filter(function (participant_instance_entity $participant_instance) use ($calculated_relationship_participant_ids) {
                return in_array($participant_instance->participant_id, $calculated_relationship_participant_ids);
            })
            ->pluck('participant_id');

        $to_be_added_participant_ids = array_diff($calculated_relationship_participant_ids, $existing_matching_participant_ids);

        return array_filter(
            $relationship_participant_dtos,
            static function (relationship_resolver_dto $relationship_participant) use ($to_be_added_participant_ids) {
                return in_array($relationship_participant->get_user_id(), $to_be_added_participant_ids);
            }
        );
    }

    /**
     * Find out what participant instances we have to re-open and return their ids.
     *
     * We have to re-open a participant instance if it's closed and the participant is in the relationship again,
     * unless it was completed before.
     *
     * @param collection $existing_participant_instances
     * @param array $calculated_relationship_participant_ids
     * @return array
     */
    private function get_to_be_reopened_participant_instance_ids(
        collection $existing_participant_instances,
        array $calculated_relationship_participant_ids
    ): array {
        return $existing_participant_instances
            ->filter(function (participant_instance_entity $participant_instance) use ($calculated_relationship_participant_ids) {
                return in_array($participant_instance->participant_id, $calculated_relationship_participant_ids)
                    && (int)$participant_instance->availability === participant_instance_closed::get_code()
                    && (int)$participant_instance->progress !== participant_instance_complete::get_code();
            })
            ->pluck('id');
    }

    /**
     * Get existing participant instances that don't match the calculated ones, are open and have not been started.
     * These must be closed.
     *
     * @param collection $existing_participant_instances
     * @param array $calculated_relationship_participant_ids
     * @return array
     */
    private function get_to_be_closed_participant_instance_ids(
        collection $existing_participant_instances,
        array $calculated_relationship_participant_ids
    ): array {
        return $existing_participant_instances
            ->filter(function (participant_instance_entity $participant_instance) use ($calculated_relationship_participant_ids) {
                return (
                    !in_array($participant_instance->participant_id, $calculated_relationship_participant_ids)
                    && (int)$participant_instance->availability === open::get_code()
                    && (int)$participant_instance->progress === not_started::get_code()
                );
            })
            ->pluck('id');
    }
}
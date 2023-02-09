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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\task\service;

use coding_exception;
use core\collection;
use core\entity\user;
use core\orm\lazy_collection;
use core\orm\query\builder;
use mod_perform\constants;
use mod_perform\entity\activity\participant_instance as participant_instance_entity;
use mod_perform\entity\activity\section;
use mod_perform\entity\activity\section_relationship;
use mod_perform\entity\activity\subject_instance;
use mod_perform\entity\activity\track;
use mod_perform\entity\activity\track_user_assignment;
use mod_perform\event\participant_instance_manually_added;
use mod_perform\hook\participant_instances_created;
use mod_perform\models\activity\external_participant;
use mod_perform\models\activity\participant_instance as participant_instance_model;
use mod_perform\models\activity\participant_source;
use mod_perform\models\activity\subject_instance as subject_instance_model;
use mod_perform\state\activity\active;
use mod_perform\state\participant_instance\availability_not_applicable;
use mod_perform\state\participant_instance\not_started;
use mod_perform\state\participant_instance\open;
use mod_perform\state\participant_instance\progress_not_applicable;
use mod_perform\state\subject_instance\closed;
use mod_perform\state\subject_instance\pending;
use mod_perform\task\service\data\subject_instance_activity_collection;
use stdClass;
use totara_core\relationship\helpers\relationship_collection_manager as core_relationship_collection_manager;
use totara_core\relationship\relationship as core_relationship;
use totara_core\relationship\relationship_resolver_dto;

/**
 * Class participant_instance_creation
 * Used to initially generate participant instances for a collection of new subject instances.
 * Also used to synchronise participant instances for existing open subject instances.
 *
 * @package mod_perform\task\service
 */
abstract class participant_instance_service {

    /**
     * List of participant instances to be created for bulk insert.
     *
     * @var collection|array[]
     */
    private $participation_creation_list = [];

    /**
     * @var array
     */
    private $external_participation_creation_list = [];

    /**
     * Maximum number of participants aggregated before bulk insert.
     *
     * @var int
     */
    private $buffer_count = BATCH_INSERT_MAX_ROW_COUNT;

    /**
     * Collection of activities for the subject instance dtos.
     *
     * @var subject_instance_activity_collection
     */
    protected $activity_collection;

    abstract protected function create_participant_instances_for_relationships(array $relationship_data): void;

    /**
     * Add a list of participants to all given subject instances.
     *
     * No permission checks here. Calling code must take care of that.
     * The subject instances must belong to the same activity, otherwise an exception is thrown.
     *
     * @param array $subject_instance_ids
     * @param array $participant_relationship_map array of arrays with participant_id/core_relationship_id tuples
     * @return collection|participant_instance_model[] returns added participant instances
     */
    public function add_instances(array $subject_instance_ids, array $participant_relationship_map): collection {
        $subject_instance_ids = array_map('intval', array_unique($subject_instance_ids));

        $activity_id = $this->validate_add_instances($subject_instance_ids, $participant_relationship_map);

        // Main transaction to add all the new participant instances.
        $added_instances_data = builder::get_db()->transaction(
            function () use ($activity_id, $subject_instance_ids, $participant_relationship_map) {
                $added_instance_groups = [];

                $subject_instance_ids_chunks = array_chunk($subject_instance_ids, builder::get_db()->get_max_in_params());
                foreach ($subject_instance_ids_chunks as $subject_instance_ids_chunk) {
                    $subject_instances = subject_instance::repository()
                        ->where('id', $subject_instance_ids_chunk)
                        ->get();

                    foreach ($subject_instances as $subject_instance) {
                        $added_instance_groups[] = $this->add_instances_for_subject_instance(
                            $activity_id,
                            $subject_instance,
                            $participant_relationship_map
                        );
                    }
                }
                return array_merge([], ...$added_instance_groups);
            }
        );

        $participant_instances = $this->build_models_from_participant_instance_data($added_instances_data);

        foreach ($participant_instances as $participant_instance) {
            participant_instance_manually_added::create_from_participant_instance($participant_instance)
                ->trigger();
        }

        return $participant_instances;
    }

    /**
     * Build a collection of participant instance models from given tuples of
     * [subject_instance_id, core_relationship_id, participant_id].
     *
     * @param array $participant_instances_data
     * @return collection|participant_instance_model[]
     */
    private function build_models_from_participant_instance_data(array $participant_instances_data): collection {
        if (count($participant_instances_data) === 0) {
            return new collection();
        }
        $repository = participant_instance_entity::repository();
        foreach ($participant_instances_data as $participant_instances_datum) {
            $repository->or_where(function (builder $builder) use ($participant_instances_datum) {
                $builder->where('subject_instance_id', $participant_instances_datum['subject_instance_id'])
                    ->where('core_relationship_id', $participant_instances_datum['core_relationship_id'])
                    ->where('participant_id', $participant_instances_datum['participant_id'])
                    ->where('participant_source', participant_source::INTERNAL);
            });
        }
        return $repository->get()->map_to(participant_instance_model::class);
    }

    /**
     * @param int $activity_id
     * @param subject_instance $subject_instance_entity
     * @param array $participant_relationship_map
     * @return array
     * @throws coding_exception
     */
    private function add_instances_for_subject_instance(
        int $activity_id,
        subject_instance $subject_instance_entity,
        array $participant_relationship_map
    ): array {
        $subject_instance = subject_instance_model::load_by_entity($subject_instance_entity);
        // Skip instances which are pending
        if ($subject_instance->is_pending()) {
            return [];
        }

        $added_instances_data = [];
        $subject_instance_has_new_participant_instance = false;
        foreach ($participant_relationship_map as $participant_relationship) {
            // Ignore if such a participant instance already exists.
            if ($this->participant_instance_exists(
                $subject_instance->id,
                $participant_relationship['core_relationship_id'],
                $participant_relationship['participant_id'],
                participant_source::INTERNAL
            )) {
                continue;
            }

            $subject_instance_has_new_participant_instance = true;
            $subject_instance_data = (object)[
                'id' => $subject_instance->id,
                'activity_id' => $activity_id,
            ];

            $section_relationships = section_relationship::repository()
                ->where('core_relationship_id', $participant_relationship['core_relationship_id'])
                ->join([section::TABLE, 'section'], 'section_id', 'id')
                ->where('section.activity_id', $activity_id)
                ->get();

            $this->create_participant_instances_for_user_list(
                $this->build_participant_instance_data(
                    $participant_relationship['core_relationship_id'],
                    $subject_instance_data,
                    $section_relationships
                ),
                [new relationship_resolver_dto($participant_relationship['participant_id'])]
            );
            $this->save_data();

            $added_instances_data[] = [
                'subject_instance_id' => $subject_instance->id,
                'core_relationship_id' => $participant_relationship['core_relationship_id'],
                'participant_id' => $participant_relationship['participant_id'],
            ];
        }
        if ($subject_instance_has_new_participant_instance) {
            if ($subject_instance->get_availability_state() instanceof closed) {
                // Re-open subject instance if it was closed. Also takes care of progress state.
                $subject_instance->manually_open(false);
            } else {
                // Otherwise just make sure progress state is correct.
                $subject_instance->update_progress_status();
            }
        }
        return $added_instances_data;
    }

    /**
     * Validate data for processing by add_instances().
     *
     * @param array $subject_instance_ids
     * @param array $participant_data
     * @return int the single activity_id that the subject_instances have in common
     */
    private function validate_add_instances(array $subject_instance_ids, array $participant_data): int {
        // First get the activity id from one of the subject instances.
        /** @var subject_instance $subject_instance */
        $subject_instance = subject_instance::repository()
            ->where('id', $subject_instance_ids)
            ->with('track.activity')
            ->order_by('id')
            ->first();
        if (!$subject_instance) {
            throw new coding_exception('Invalid subject_instance_ids detected');
        }
        $activity_id = $subject_instance->activity()->id;

        // Activity must be active.
        if ((int)$subject_instance->activity()->status !== active::get_code()) {
            throw new coding_exception('Cannot add participant instances for inactive activity.');
        }

        // Subject instances must be for the same activity.
        $this->validate_subject_instances_for_activity($subject_instance_ids, $activity_id);

        // Relationships must be involved in the activity and cannot be 'subject' relationship.
        $relationship_ids = array_unique(array_column($participant_data, 'core_relationship_id'));
        $this->validate_relationships_for_activity($relationship_ids, $activity_id);

        // Participant ids must be valid user ids.
        $user_ids = array_unique(array_column($participant_data, 'participant_id'));
        $this->validate_user_ids($user_ids);

        return $activity_id;
    }

    /**
     * Validate that the given subject_instance_ids belong to the given activity id.
     *
     * @param array $subject_instance_ids
     * @param int $activity_id
     */
    private function validate_subject_instances_for_activity(array $subject_instance_ids, int $activity_id) {
        $subject_instance_ids_filtered = subject_instance::repository()
            ->join([track_user_assignment::TABLE, 'tua'], 'track_user_assignment_id', 'id')
            ->join([track::TABLE, 'tr'], 'tua.track_id', 'id')
            ->where('tr.activity_id', $activity_id)
            ->where_in('id', $subject_instance_ids)
            ->get()
            ->pluck('id');
        $bad_subject_instance_ids = array_diff($subject_instance_ids, $subject_instance_ids_filtered);
        if (count($bad_subject_instance_ids) > 0) {
            $bad_subject_instance_ids = implode(',', $bad_subject_instance_ids);
            throw new coding_exception(
                "Subject instances with these ids do not belong to activity {$activity_id}: {$bad_subject_instance_ids}"
            );
        }
    }

    /**
     * Validate that the given relationships can be added to the given activity, meaning they must be involved in the
     * activity but cannot be 'subject' relationship.
     *
     * @param array $relationship_ids_to_be_added
     * @param int $activity_id
     */
    private function validate_relationships_for_activity(array $relationship_ids_to_be_added, int $activity_id) {

        $subject_relationship = core_relationship::load_by_idnumber(constants::RELATIONSHIP_SUBJECT);
        $involved_valid_relationship_ids = section_relationship::repository()
            ->join([section::TABLE, 'sctn'], 'section_id', 'id')
            ->where('sctn.activity_id', $activity_id)
            ->where('core_relationship_id', '!=', $subject_relationship->id)
            ->get(true)
            ->pluck('core_relationship_id');

        $bad_relationship_ids = array_diff($relationship_ids_to_be_added, array_unique($involved_valid_relationship_ids));

        if (count($bad_relationship_ids) > 0) {
            sort($bad_relationship_ids);
            $bad_relationship_ids = implode(',', $bad_relationship_ids);
            throw new coding_exception(
                "Relationships with these ids cannot be used in activity {$activity_id}: {$bad_relationship_ids}"
            );
        }
    }

    /**
     * Validate that the given user ids exist.
     *
     * @param array $user_ids
     */
    private function validate_user_ids(array $user_ids) {
        $user_ids_filtered_by_existing = user::repository()
            ->where_in('id', $user_ids)
            ->get()
            ->pluck('id');

        $bad_user_ids = array_diff($user_ids, $user_ids_filtered_by_existing);
        if (count($bad_user_ids) > 0) {
            $bad_user_ids = implode(',', $bad_user_ids);
            throw new coding_exception("Users with these ids do not exist: {$bad_user_ids}");
        }
    }

    /**
     * Find out whether a participant instance exists for the given data.
     *
     * @param int $subject_instance_id
     * @param int $core_relationship_id
     * @param int $participant_id
     * @param int $participant_source
     * @return bool
     */
    private function participant_instance_exists(
        int $subject_instance_id,
        int $core_relationship_id,
        int $participant_id,
        int $participant_source
    ): bool {
        return participant_instance_entity::repository()
            ->where('subject_instance_id', $subject_instance_id)
            ->where('core_relationship_id', $core_relationship_id)
            ->where('participant_id', $participant_id)
            ->where('participant_source', $participant_source)
            ->exists();
    }

    /**
     * Aggregates participant instances to use for bulk insert or synchronisation.
     *
     * @param collection|subject_instance_dto[] $subject_instance_dtos
     * @return void
     */
    protected function aggregate_participant_instances(collection $subject_instance_dtos): void {
        // Find all the activities that are related to the subject instances.
        $activity_ids = array_unique($subject_instance_dtos->pluck('activity_id'), SORT_NUMERIC);
        $section_relationships = $this->get_section_relationships($activity_ids);
        $core_relationship_ids = array_unique($section_relationships->pluck('core_relationship_id'));

        if (empty($core_relationship_ids)) {
            return;
        }

        // Initialise the core relationship manager with the core relationships that we will be using.
        $relationship_manager = new core_relationship_collection_manager($core_relationship_ids);
        $section_relationships_per_core_relationship_per_activity = $this->group_relationships_by_activity($section_relationships);

        // Process each subject instance, one at a time.
        foreach ($subject_instance_dtos as $subject_instance) {
            if ($subject_instance->status === pending::get_code()) {
                continue;
            }
            // If there are no relationships defined for the activity then there is nothing to do.
            $has_no_relationships_for_activity =
                !isset($section_relationships_per_core_relationship_per_activity[$subject_instance->activity_id]);
            if ($has_no_relationships_for_activity) {
                continue;
            }

            $relationship_arguments = $this->build_relationship_arguments($subject_instance);

            $participant_ids_for_relationships = $relationship_manager->get_users_for_relationships(
                $relationship_arguments,
                array_keys($section_relationships_per_core_relationship_per_activity[$subject_instance->activity_id])
            );
            $relationship_data = [
                'section_relationships_per_core_relationship' =>
                    $section_relationships_per_core_relationship_per_activity[$subject_instance->activity_id],
                'subject_instance' =>
                    $subject_instance,
                'participant_dtos' =>
                    $participant_ids_for_relationships,
            ];

            $this->create_participant_instances_for_relationships($relationship_data);
        }
    }

    /**
     * Get section_relationships for all activities.
     *
     * @param array $activity_ids
     * @return collection
     */
    private function get_section_relationships(array $activity_ids): collection {
        $section_relationships = new collection();
        $this->activity_collection->load_activity_configs_if_missing($activity_ids);

        foreach ($activity_ids as $key => $activity_id) {
            $activity = $this->activity_collection->get_activity_config($activity_id);

            foreach ($activity->get_section_relationships() as $section_relationship) {
                $section_relationships->append($section_relationship);
            }
        }

        return $section_relationships;
    }

    /**
     * Get the distinct list of core relationships used in the given sections, grouped by activity
     *
     * @param collection $section_relationships
     * @return array
     */
    private function group_relationships_by_activity(collection $section_relationships): array {
        $relationships_per_activity = [];

        /** @var section_relationship $section_relationship */
        foreach ($section_relationships as $section_relationship) {
            $activity_id = $section_relationship->section->activity_id;
            $core_relationship_id = $section_relationship->core_relationship_id;

            $relationships_per_activity[$activity_id][$core_relationship_id][] = $section_relationship;
        }

        return $relationships_per_activity;
    }

    /**
     * Builds relationship class arguments from subject instance id.
     *
     * @param subject_instance_dto $subject_instance
     * @return array
     */
    private function build_relationship_arguments(
        subject_instance_dto $subject_instance
    ): array {
        $args = [];
        $should_use_subject_user_id = empty($subject_instance->job_assignment_id) && !empty($subject_instance->subject_user_id);

        if ($should_use_subject_user_id) {
            $args['user_id'] = $subject_instance->subject_user_id;
        } else {
            $args['user_id'] = $subject_instance->subject_user_id; // Always required for subject resolver.
            $args['job_assignment_id'] = $subject_instance->job_assignment_id;
        }

        // Always include subject instance id in order to support manual relationships.
        $args['subject_instance_id'] = $subject_instance->id;

        return $args;
    }

    /**
     * Build participant instance data.
     *
     * @param int $core_relationship_id
     * @param subject_instance_dto|stdClass $subject_instance
     * @param section_relationship[]|collection $section_relationships
     * @return array
     */
    protected function build_participant_instance_data(
        int $core_relationship_id,
        $subject_instance,
        $section_relationships
    ): array {
        $can_view = false;
        $can_answer = false;

        // If any related section_relationship.can_answer then the participant instance should be can_answer.
        foreach ($section_relationships as $section_relationship) {
            $can_view |= (int)$section_relationship->can_view === 1;
            $can_answer |= (int)$section_relationship->can_answer === 1;
        }

        if ($can_answer) {
            $progress = not_started::get_code();
            $availability = open::get_code();
        } else if ($can_view) {
            $progress = progress_not_applicable::get_code();
            $availability = availability_not_applicable::get_code();
        } else {
            throw new coding_exception(
                'Tried to create participant instance related to no sections which can view or answer'
            );
        }

        return [
            'participant_data' => [
                'core_relationship_id' => $core_relationship_id,
                'subject_instance_id' => $subject_instance->id,
                'availability' => $availability,
                'progress' => $progress,
                'created_at' => time(),
            ],
            'activity_id' => $subject_instance->activity_id,
        ];
    }

    /**
     * Create participant instances for a list of user ids.
     *
     * @param array $data
     * @param array|relationship_resolver_dto[] $participant_dto_list
     *
     * @return void
     */
    protected function create_participant_instances_for_user_list(
        array $data,
        array $participant_dto_list
    ): void {
        foreach ($participant_dto_list as $participant_dto) {
            $source = participant_source::INTERNAL;
            $user_id = $participant_dto->get_user_id();

            if (!$user_id) {
                $source = participant_source::EXTERNAL;

                $metadata = $participant_dto->get_meta();

                $data['external']['name'] = $metadata['name'];
                $data['external']['email'] = $metadata['email'];
            }

            $data['participant_data']['participant_source'] = $source;
            $data['participant_data']['participant_id'] = $user_id;

            // We do separate the internal from the external participants
            // as they cannot use the same bulk insert method
            if ($source === participant_source::EXTERNAL) {
                $this->external_participation_creation_list[] = $data;
                if (count($this->external_participation_creation_list) === $this->buffer_count) {
                    $this->save_data_external();
                }
            } else {
                $unique_hash = $this->create_unique_hash($data['participant_data']);

                $this->participation_creation_list[$unique_hash] = $data;

                if (count($this->participation_creation_list) === $this->buffer_count) {
                    $this->save_data_internal();
                }
            }
        }
    }

    /**
     * Create a uniqe hash for the given participant instance data. For the given
     * combination of fields there should only be one record in the database
     *
     * @param array $participant_instance_data
     * @return string
     */
    private function create_unique_hash(array $participant_instance_data): string {
        return md5(
            sprintf(
                '%s%s%s%s',
                $participant_instance_data['core_relationship_id'],
                $participant_instance_data['subject_instance_id'],
                $participant_instance_data['participant_source'],
                $participant_instance_data['participant_id']
            )
        );
    }

    /**
     * Save participant instances (external and internal) in database.
     */
    protected function save_data(): void {
        $this->save_data_internal();
        $this->save_data_external();
    }

    /**
     * Persists internal participant instances in database.
     *
     * @return void
     */
    protected function save_data_internal(): void {
        $task_id = uniqid();

        if (count($this->participation_creation_list) === 0) {
            return;
        }

        builder::get_db()->transaction(function () use ($task_id) {
            $created_participants_dtos = new collection();

            // Attach the current task id to all the instances to make it possible
            // to identify just inserted rows by this run and use bulk inserts
            $participant_instance_to_create = array_column($this->participation_creation_list, 'participant_data');
            $participant_instance_to_create = array_map(
                function ($item) use ($task_id) {
                    $item['task_id'] = $task_id;
                    return (object) $item;
                },
                $participant_instance_to_create
            );

            if (!empty($participant_instance_to_create)) {
                builder::get_db()->insert_records(participant_instance_entity::TABLE, $participant_instance_to_create);

                /** @var participant_instance_entity[]|lazy_collection $participant_instances */
                $participant_instances = participant_instance_entity::repository()
                    ->where('task_id', $task_id)
                    ->get_lazy();

                foreach ($participant_instances as $participant_instance) {
                    $unique_hash = $this->create_unique_hash($participant_instance->to_array());
                    $instance_creation_data = $this->participation_creation_list[$unique_hash] ?? null;
                    if (!$instance_creation_data) {
                        throw new coding_exception('Data was not found in creation list for given participant instance');
                    }

                    $section_data = [];
                    $section_data['activity_id'] = $instance_creation_data['activity_id'];
                    $section_data['core_relationship_id'] = $instance_creation_data['participant_data']['core_relationship_id'];
                    $section_data['id'] = $participant_instance->id;
                    $section_data['subject_instance_id'] = $instance_creation_data['participant_data']['subject_instance_id'];

                    $created_participants_dtos->append(participant_instance_dto::create_from_data($section_data));
                }

                (new participant_instances_created($created_participants_dtos, $this->activity_collection))->execute();

                participant_instance_entity::repository()
                    ->where('task_id', $task_id)
                    ->update(['task_id' => null]);
            }

            $this->participation_creation_list = [];
        });
    }

    /**
     * Persists external participant instances in database.
     *
     * @return void
     */
    private function save_data_external(): void {
        if (count($this->external_participation_creation_list) === 0) {
            return;
        }

        builder::get_db()->transaction(function () {
            $created_participants_dtos = new collection();
            foreach ($this->external_participation_creation_list as $participant_instance) {
                $participant_instance_id = builder::table(participant_instance_entity::TABLE)
                    ->insert($participant_instance['participant_data']);

                $section_data = [];
                $section_data['activity_id'] = $participant_instance['activity_id'];
                $section_data['core_relationship_id'] = $participant_instance['participant_data']['core_relationship_id'];
                $section_data['id'] = $participant_instance_id;
                $section_data['subject_instance_id'] = $participant_instance['participant_data']['subject_instance_id'];

                // This creates the external participant and updates the user_id of the participant instance
                external_participant::create(
                    $participant_instance_id,
                    $participant_instance['external']['name'],
                    $participant_instance['external']['email']
                );

                $created_participants_dtos->append(participant_instance_dto::create_from_data($section_data));
            }
            (new participant_instances_created($created_participants_dtos, $this->activity_collection))->execute();
            $this->external_participation_creation_list = [];
        });
    }
}

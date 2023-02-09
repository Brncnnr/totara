<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency;

use core\orm\query\builder;
use stdClass;
use totara_competency\entity\achievement_via;
use totara_competency\entity\assignment;
use totara_competency\entity\competency_achievement;
use totara_competency\hook\competency_achievement_updated_bulk;
use totara_core\advanced_feature;
use totara_hierarchy\entity\competency;

/**
 * Class aggregator
 *
 * Aggregates the competency values for users based on the given achievement configuration.
 */
final class competency_achievement_aggregator {

    /** @var achievement_configuration */
    private $achievement_configuration;

    /** @var overall_aggregation */
    private $aggregation_instance;

    /** @var competency_aggregator_user_source $user_id_source */
    protected $user_id_source = null;


    /**
     * aggregator constructor.
     * @param achievement_configuration $achievement_configuration
     * @param competency_aggregator_user_source $user_id_source
     */
    public function __construct(
        achievement_configuration $achievement_configuration,
        competency_aggregator_user_source $user_id_source
    ) {
        $this->achievement_configuration = $achievement_configuration;
        $this->user_id_source = $user_id_source;
    }

    /**
     * @return achievement_configuration
     */
    public function get_achievement_configuration(): achievement_configuration {
        return $this->achievement_configuration;
    }

    private function get_aggregation_instance(): overall_aggregation {
        if (is_null($this->aggregation_instance)) {
            $type = $this->get_achievement_configuration()->get_aggregation_type();
            $this->aggregation_instance = overall_aggregation_factory::create($type);
            $this->aggregation_instance->set_pathways($this->get_achievement_configuration()->get_active_pathways());
        }

        return $this->aggregation_instance;
    }

    public function set_aggregation_instance(overall_aggregation $aggregation_instance): competency_achievement_aggregator {
        $this->aggregation_instance = $aggregation_instance;
        return $this;
    }

    /**
     * Aggregate the competency values for users marked as having changes
     * Results will be used to add or update the applicable records in totara_competency_achievement
     *
     * @param int|null $aggregation_time
     */
    public function aggregate(?int $aggregation_time = null) {
        /** @var competency $competency */
        $competency = $this->get_achievement_configuration()->get_competency();
        $scale_values = $competency->scale->values;

        if (is_null($aggregation_time)) {
            $aggregation_time = time();
        }

        // Setting the competency id on the source will set it on the table instance
        // which make sure the competency will be included in all queries for the queueing table
        $this->user_id_source->set_competency_id($competency->id);
        $this->user_id_source->archive_non_assigned_achievements($competency->id, $aggregation_time);
        $this->user_id_source->mark_newly_assigned_users($competency->id);
        $user_assignment_records = $this->user_id_source->get_users_to_reaggregate($competency->id);

        builder::get_db()->transaction(function () use ($competency, $aggregation_time, $user_assignment_records, $scale_values) {
            $hook = new competency_achievement_updated_bulk($competency);

            // A user can have multiple assignments to a competency. Some of these may have proficiency override settings resulting
            // in the user being considered proficient in one assignment but not all.
            // However, the competency_achievement_updated_bulk hook is NOT assignment aware. If a user is considered proficient
            // via ANY assignment, the hook will indicate that the user is marked as proficient
            $hook_data = [];

            foreach ($user_assignment_records as $user_assignment_record) {
                $user_id = $user_assignment_record->user_id;
                $user_achievement = $this->get_aggregation_instance()->aggregate_for_user($user_id);
                $previous_comp_achievement = $user_assignment_record->achievement;
                $this->ensure_legacy_assignment_exists($competency->id, $user_assignment_record);

                // We don't necessarily have a scale value in this case we store null
                $scale_value_data = [
                    'id' => null,
                    'is_proficient' => 0,
                    'name' => null
                ];
                if ($user_achievement['scale_value']) {
                    $scale_value_data['id'] = (int)$user_achievement['scale_value']->id;
                    $scale_value_data['name'] = $user_achievement['scale_value']->name;

                    // The aggregated scale value is the default value.
                    // Proficiency may differ per assignment
                    if ($user_assignment_record->assignment->minproficiencyid !== null) {
                        $min_proficient_scale_value = $scale_values->find('id', $user_assignment_record->assignment->minproficiencyid);
                        $scale_value_data['is_proficient'] = (int)($user_achievement['scale_value']->sortorder <= $min_proficient_scale_value->sortorder);
                    } else {
                        $scale_value_data['is_proficient'] = (int)($user_achievement['scale_value']->proficient);
                    }
                }

                // If the scale value changed or the proficiency value then we supersede the old record and create a new one
                if (is_null($previous_comp_achievement)
                    || (int)$previous_comp_achievement->scale_value_id !== $scale_value_data['id']
                    || (int)$previous_comp_achievement->proficient !== $scale_value_data['is_proficient']
                ) {
                    // New achieved value
                    if (!is_null($previous_comp_achievement)) {
                        $this->mark_achievement_as_superseded($previous_comp_achievement, $aggregation_time);
                    }

                    $new_achievement_data = [
                        'competency_id' => $competency->id,
                        'user_id' => $user_id,
                        'user_assignment_record' => $user_assignment_record,
                        'scale_value_id' => $scale_value_data['id'],
                        'is_proficient' => $scale_value_data['is_proficient'],
                        'aggregation_time' => $aggregation_time,
                    ];
                    $new_comp_achievement = $this->create_competency_achievement($new_achievement_data);

                    if (!empty($user_achievement['achieved_via'])) {
                        $this->create_achievements_via_records($user_achievement['achieved_via'], $new_comp_achievement);
                    }
                    $proficiency_changed = isset($previous_comp_achievement) && (int)$previous_comp_achievement->proficient !== $scale_value_data['is_proficient'];

                    if (!isset($hook_data[$user_id])) {
                        $hook_data[$user_id] = [
                            'is_proficient' => $scale_value_data['is_proficient'],
                            'new_scale_value' => [
                                'id' => $scale_value_data['id'],
                                'name' => $scale_value_data['name'],
                            ],
                            'proficiency_changed' => $proficiency_changed,
                        ];
                    } else {
                        $hook_data[$user_id]['is_proficient'] = (int)($hook_data[$user_id]['is_proficient'] || $scale_value_data['is_proficient']);
                        $hook_data[$user_id]['proficiency_changed'] = (int)($hook_data[$user_id]['proficiency_changed'] || $proficiency_changed);
                    }
                } else {
                    // No change.
                    $previous_comp_achievement->last_aggregated = $aggregation_time;
                    $previous_comp_achievement->save();
                }
            }

            if (!empty($hook_data)) {
                foreach($hook_data as $user_id => $hook_data) {
                    $hook->add_user_id($user_id, $hook_data);
                }
                $hook->execute();
            }
        });
    }

    /**
     * Marks a competency achievement as superseded when a new one is to be created.
     *
     * @param $previous_comp_achievement
     * @param int $aggregation_time
     * @return void
     */
    private function mark_achievement_as_superseded($previous_comp_achievement, int $aggregation_time): void {
        $previous_comp_achievement->status = competency_achievement::SUPERSEDED;
        $previous_comp_achievement->time_status = $aggregation_time;
        $previous_comp_achievement->save();
    }

    /**
     * Creates achievement_via_records for new competency achievement if there's a list available.
     *
     * @param array $pathways_achieved
     * @param competency_achievement $new_comp_achievement
     * @return void
     */
    private function create_achievements_via_records(array $pathways_achieved, competency_achievement $new_comp_achievement): void {
        foreach ($pathways_achieved as $pathway_achievement) {
            $via = new achievement_via();
            $via->comp_achievement_id = $new_comp_achievement->id;
            $via->pathway_achievement_id = $pathway_achievement->id;
            $via->save();
        }
    }

    /**
     * Creates competency achievement with data.
     *
     * @param array $data
     * @return competency_achievement
     */
    private function create_competency_achievement(array $data): competency_achievement {
        $new_comp_achievement = new competency_achievement();
        $new_comp_achievement->competency_id = $data['competency_id'];
        $new_comp_achievement->user_id = $data['user_id'];
        $new_comp_achievement->assignment_id = $data['user_assignment_record']->assignment->id;
        $new_comp_achievement->scale_value_id = $data['scale_value_id'];
        $new_comp_achievement->proficient = $data['is_proficient'];
        $new_comp_achievement->status = competency_achievement::ACTIVE_ASSIGNMENT;
        $new_comp_achievement->time_created = $data['aggregation_time'];
        $new_comp_achievement->time_status = $data['aggregation_time'];
        $new_comp_achievement->time_proficient = $data['aggregation_time'];
        $new_comp_achievement->time_scale_value = $data['aggregation_time'];
        $new_comp_achievement->last_aggregated = $data['aggregation_time'];
        $new_comp_achievement->save();

        return $new_comp_achievement;
    }

    /**
     * If it's a new achievement then we make sure there's an assignment for it.
     * This creates an archived legacy assignment
     *
     * @param int $competency_id
     * @param stdClass $user_assignment_record
     */
    private function ensure_legacy_assignment_exists(int $competency_id, stdClass $user_assignment_record) {
        if (advanced_feature::is_enabled('competency_assignment')) {
            return;
        }

        if ($user_assignment_record->assignment->id === null) {
            $assignment = new assignment();
            $assignment->type = assignment::TYPE_LEGACY;
            $assignment->user_group_type = user_groups::USER;
            $assignment->competency_id = $competency_id;
            $assignment->user_group_id = $user_assignment_record->user_id;
            $assignment->optional = 0;
            $assignment->status = assignment::STATUS_ARCHIVED;
            $assignment->created_by = 0;
            $assignment->archived_at = time();
            $assignment->save();

            $user_assignment_record->assignment = $assignment;
        }
    }

}

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
 * @package totara_competency
 */

namespace totara_competency;

use coding_exception;
use core\collection;
use core\orm\query\builder;
use totara_competency\entity\assignment;
use totara_competency\entity\configuration_change;
use totara_competency\event\assignment_min_proficiency_override_updated;
use totara_competency\models\assignment as assignment_model;
use totara_hierarchy\entity\competency;
use totara_hierarchy\entity\competency_framework;
use totara_hierarchy\entity\scale_value;

/**
 * A service class for bulk setting or unsetting the minimum required proficiency value override (minproficiencyid) of competency assignments.
 * All assignments must belong to the same frame work that the new scale value belongs to.
 * If unsetting the the override min scale value, frameworks are not checked.
 *
 * @package totara_competency\models
 */
class min_proficiency_override_for_assignments {

    public const MIX_OF_COMPETENCY_FRAMEWORKS_FOUND = 'All assignments must belong to the same framework, assignments from multiple frameworks were found';
    public const COMPETENCIES_DO_NOT_BELONG_TO_OVERRIDE_FRAMEWORK = 'Supplied assignments do not belong to the same framework as the new scale value';
    public const ASSIGNMENTS_WITH_IDS_DONT_EXIST = 'Assignments with ids (%s) do not exist';
    public const SCALE_VALUE_DOES_NOT_EXIST = 'The scale value does not exist';

    /**
     * @var int|null
     */
    protected $scale_value_id;

    /**
     * @var int[]
     */
    protected $assignment_ids;

    /***
     * @param int|null $scale_value_id The scale value id to set the override to, or null to unset.
     * @param int[] $assignment_ids The ids of all assignments to update the override for.
     */
    public function __construct(?int $scale_value_id, array $assignment_ids) {
        $this->scale_value_id = $scale_value_id;
        $this->assignment_ids = array_unique($assignment_ids);
    }

    /**
     * @return collection|assignment_model[] The updated competency assignments.
     */
    public function process(): collection {
        $this->ensure_all_assignments_exist();

        if ($this->scale_value_id !== null) {
            $this->ensure_all_assignments_belong_new_value_framework();
        }

        return $this->update_override_values();
    }

    /**
     * Resets all minimum proficiency overrides back to default where they have the given scale value ID.
     * Used when a scale value is deleted.
     *
     * @param int $scale_value_id
     */
    public static function reset_with_scale_value(int $scale_value_id): void {
        assignment::repository()
            ->where('minproficiencyid', $scale_value_id)
            ->update(['minproficiencyid' => null]);
    }

    private function ensure_all_assignments_exist(): void {
        $non_existent_assignment_ids = $this->get_non_existent_assignment_ids();

        if (count($non_existent_assignment_ids) > 0) {
            $ids_csv = implode(', ', $non_existent_assignment_ids);
            throw new coding_exception(sprintf(self::ASSIGNMENTS_WITH_IDS_DONT_EXIST, $ids_csv));
        }
    }

    private function ensure_all_assignments_belong_new_value_framework(): void {
        /** @var scale_value $new_scale_value */
        $new_scale_value = scale_value::repository()->find($this->scale_value_id);
        if ($new_scale_value === null) {
            throw new coding_exception(self::SCALE_VALUE_DOES_NOT_EXIST);
        }

        $framework_ids = assignment::repository()
            ->as('a')
            ->select_raw('distinct c.frameworkid')
            ->join([competency::TABLE, 'c'], 'c.id', 'a.competency_id')
            ->where_in('a.id', $this->assignment_ids)
            ->get()
            ->pluck('frameworkid');

        if (count($framework_ids) !== 1) {
            throw new coding_exception(self::MIX_OF_COMPETENCY_FRAMEWORKS_FOUND);
        }

        /** @var competency_framework $framework */
        $framework = competency_framework::repository()->find($framework_ids[0]);

        if ($new_scale_value->scale->id !== $framework->scale->id) {
            throw new coding_exception(self::COMPETENCIES_DO_NOT_BELONG_TO_OVERRIDE_FRAMEWORK);
        }
    }

    private function update_override_values(): collection {
        /** @var collection $updated */
        $updated = null;

        builder::get_db()->transaction(function () use(&$updated) {
            /** @var collection|assignment_model[] $updated */
            $updated = assignment::repository()->where_in('id', $this->assignment_ids)
                ->update(['minproficiencyid' => $this->scale_value_id])
                ->order_by('id')
                ->get()
                ->map_to([assignment_model::class, 'load_by_entity']);

            // Queue all assigned users for re-aggregation
            $queue = new aggregation_users_table();
            $competency_ids = array_unique($updated->pluck('competency_id'));
            foreach ($competency_ids as $competency_id) {
                $queue->queue_all_assigned_users_for_aggregation($competency_id, 1);
            }

            foreach ($updated as $assignment) {
                // Log the configuration change for this assignment and trigger the event for interested other components
                configuration_change::min_proficiency_override($assignment, $this->scale_value_id);
                assignment_min_proficiency_override_updated::create_from_assignment($assignment->get_entity())->trigger();
            }
        });

        return $updated;
    }

    /**
     * @return string[]
     */
    private function get_non_existent_assignment_ids(): array {
        $existing_ids =  builder::table(assignment::TABLE)
            ->select('id')
            ->where_in('id', $this->assignment_ids)
            ->get()
            ->pluck('id');

        return array_diff($this->assignment_ids, $existing_ids);
    }

}
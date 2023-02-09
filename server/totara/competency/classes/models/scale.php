<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com
 * @package totara_competency
 */

namespace totara_competency\models;

use core\orm\collection;
use core\orm\entity\model;
use core\orm\entity\repository;
use totara_competency\entity\assignment;
use totara_competency\entity\competency_achievement;
use totara_core\advanced_feature;
use totara_hierarchy\entity\competency;
use totara_hierarchy\entity\scale as scale_entity;
use totara_hierarchy\entity\scale_assignment;
use totara_hierarchy\entity\scale_value;

/**
 * Class scale
 * This is a model that represent a scale
 *
 * @property-read int $id
 * @property-read string $name
 * @property-read string $description
 * @property-read int $timemodified
 * @property-read int $usermodified
 * @property-read int $defaultid
 * @property-read int $minproficiencyid
 * @property-read collection|scale_value[] $values
 * @property scale_entity $entity
 *
 * @package totara_competency\models
 */
class scale extends model {

    /**
     * Scale constructor. It's here for the purpose of type-hint
     *
     * @param scale_entity $entity
     */
    public function __construct(scale_entity $entity) {
        parent::__construct($entity);
    }

    protected static function get_entity_class(): string {
        return scale_entity::class;
    }

    /**
     * Load scale by ID including values
     *
     * @param int $id Ids to load scales by
     * @return scale
     */
    public static function load_by_id_with_values(int $id): self {
        return static::load_by_ids([$id], true)->first();
    }

    /**
     * Load scales by IDs
     *
     * @param int[] $ids Ids to load scales by
     * @param bool $with_values A flag to load scale values
     * @return collection
     */
    public static function load_by_ids(array $ids, bool $with_values = true): collection {
        return scale_entity::repository()
            ->where('id', 'in', static::sanitize_ids($ids))
            ->when($with_values, function (repository $repository) {
                $repository->with('values');
            })
            ->get()
            ->transform_to(static::class);
    }

    /**
     * Load scales by competency ids
     *
     * @param int[] $ids Competency IDs
     * @param bool $with_values A flag to load scale values
     * @return collection|array|scale[]
     */
    public static function find_by_competency_ids(array $ids, bool $with_values = false): collection {
        $scales = competency::repository()
            ->where('id', 'in', static::sanitize_ids($ids))
            ->with('scale')
            ->get()
            ->pluck('scale');

        $scales = new collection($scales);
        return static::load_by_ids($scales->pluck('id'), $with_values);
    }

    /**
     * Load scales by competency id
     *
     * @param int $id Competency ID
     * @param bool $with_values A flag to load scale values
     * @return scale
     */
    public static function find_by_competency_id(int $id, bool $with_values = true): ?self {
        return static::find_by_competency_ids([$id], $with_values)->first();
    }

    /**
     * Load the scale a particular framework uses.
     *
     * Note, the underlying db structure allows one framework to many scales,
     * but the application only allows selecting one scale per framework.
     *
     * @param int $framework_id
     * @return static|null
     */
    public static function find_by_framework_id(int $framework_id): ?self {
        $scale_entity = scale_entity::repository()
            ->as('cs')
            ->select('cs.*')
            ->join([scale_assignment::TABLE, 'csa'], 'cs.id', 'csa.scaleid')
            ->where('csa.frameworkid', $framework_id)
            ->order_by('id')
            ->with('values')
            ->first();

        if ($scale_entity === null) {
            return null;
        }

         return static::load_by_entity($scale_entity);
    }

    /**
     *  Creates a virtual model that represent a scale for a specific competency assignment.
     *  The values proficiency flags are adjusted based on any assignment specific min proficient value override.
     *
     * @param assignment $assignment_entity
     * @return static
     */
    public static function create_for_assignment(assignment $assignment_entity): self {
        // Ensure we don't share underlying scale instances between different virtual scale models.
        // This would happen when we are dealing with a collection on assignments that share a competency and scale instance.
        $scale_entity_clone = clone $assignment_entity->competency->scale;

        $scale = new static($scale_entity_clone);

        $min_value_override = $assignment_entity->min_proficient_value_override;

        if ($min_value_override === null) {
            return $scale;
        }

        $assignment_specific_values = $scale->entity->values->map(function (scale_value $scale_value) use ($min_value_override) {
            $is_proficient = $scale_value->sortorder <= $min_value_override->sortorder;

            return new assignment_specific_scale_value($scale_value, $is_proficient);
        });

        $scale->entity->relate('values', $assignment_specific_values);

        return $scale;
    }

    /**
     * Checks if a scale is used in the system. A scale is used if:
     * - there are any achievement records
     * - it's been given a value in a learning plan
     * - there is an active assignment with a matching minimum proficiency override
     * - there is an active assignment and an achievement pathway using a criteria group
     *
     * @return bool
     */
    public function is_in_use(): bool {
        // There are achievement records.
        $has_achievement = competency_achievement::repository()
            ->where_not_null('scale_value_id')
            ->join(['comp', 'c'], 'competency_id', 'id')
            ->join(['comp_scale_assignments', 'sca'], 'c.frameworkid', 'sca.frameworkid')
            ->where('sca.scaleid', $this->id)
            ->exists();
        if ($has_achievement) {
            return true;
        }

        // The scale is used in a learning plan.
        if (advanced_feature::is_enabled('learningplans')) {
            global $CFG;
            require_once($CFG->dirroot.'/totara/plan/components/competency/competency.class.php');

            $used_in_lps = \dp_competency_component::is_competency_scale_used($this->id);

            if ($used_in_lps) {
                return true;
            }
        }

        // There is an active assignment with a matching minimum proficiency override.
        $has_active_proficiency_override = assignment::repository()
            ->where_in('status', [assignment::STATUS_ACTIVE, assignment::STATUS_ARCHIVED])
            ->join(['comp_scale_values', 'csv'], 'minproficiencyid', 'csv.id')
            ->where('csv.scaleid', $this->id)
            ->exists();
        if ($has_active_proficiency_override) {
            return true;
        }

        // There is an active assignment and an achievement pathway using a criteria group ("Criteria-based paths").
        $has_criteria_group = scale_value::repository()
            ->where('scaleid', $this->id)
            ->join(['pathway_criteria_group', 'pcg'], 'id', 'scale_value_id')
            ->join(['totara_competency_pathway', 'pathway'], 'pcg.id', 'pathway.path_instance_id')
            ->where('pathway.path_type', 'criteria_group')
            ->join([competency::TABLE, 'comp'], 'pathway.competency_id', 'comp.id')
            ->join([assignment::TABLE, 'assign'], 'comp.id', 'assign.competency_id')
            ->where_in('assign.status', [assignment::STATUS_ACTIVE, assignment::STATUS_ARCHIVED])
            ->exists();
        if ($has_criteria_group) {
            return true;
        }

        return false;
    }

    /**
     * Checks if a scale is assigned to any framework
     *
     * @return bool
     */
    public function is_assigned(): bool {
        return scale_assignment::repository()
            ->where('scaleid', $this->id)
            ->exists();
    }

    /**
     * Filter out bad ids if any
     *
     * @param array $ids
     * @return array
     */
    protected static function sanitize_ids(array $ids): array {
        return array_unique(array_filter(array_map('intval', $ids), function ($id) {
            return $id > 0;
        }));
    }

    /**
     * Get all entity properties
     *
     * @return array
     */
    public function to_array(): array {
        return $this->entity->to_array();
    }

}

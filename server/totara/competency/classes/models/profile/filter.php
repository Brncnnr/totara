<?php
/**
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

namespace totara_competency\models\profile;

use core\collection;
use totara_competency\data_providers\assignments;
use totara_competency\entity\assignment as assignment_entity;
use totara_competency\models\assignment as assignment_model;
use totara_competency\models\profile\traits\assignment_key;
use totara_competency\models\user_group_factory;

/**
 * Class filter
 *
 * This is a model representing a filter for assignment progress of the competency profile
 *
 * @property-read string $name Assignment progress name
 * @property-read string $status_name Human status name
 * @property-read int $status
 * @property-read string $user_group_type
 * @property-read int $user_group_id
 * @property-read string $type
 * @property-read string $key
 *
 * @package totara_competency\models
 */
class filter {

    use assignment_key;

    /**
     *  Filter properties
     *
     * @var array
     */
    protected $attributes;

    /**
     * Create filter from assignment and key
     *
     * @param assignment_model $assignment Assignment
     * @param string $key Key
     */
    public function __construct(assignment_model $assignment, string $key) {
        $this->attributes = [
            'name' => $assignment->get_progress_name(),
            'status_name' => $this->get_human_status($assignment),
            'status' => $this->get_status($assignment),
            'user_group_type' => $assignment->user_group_type,
            'user_group_id' => $assignment->user_group_id,
            'type' => $assignment->type,
            'key' => $key
        ];
    }

    public static function build_from_assignments_provider(assignments $provider) {
        return static::build_from_assignments($provider->get());
    }

    /**
     * Create an array of filters from a collection of assignments
     *
     * @param collection $assignments
     * @return array
     */
    public static function build_from_assignments(collection $assignments) {
        $filters = [];
        $user_group_entities = user_group_factory::load_user_groups($assignments);

        $assignments->map(function (assignment_entity $assignment) use (&$filters, $user_group_entities) {
            $assignment_model = assignment_model::load_by_entity($assignment);

            $user_group = $user_group_entities[$assignment->user_group_type][$assignment->user_group_id] ?? null;

            if ($user_group) {
                $assignment_model->set_user_group_entity($user_group);
            }

            $key = static::build_key($assignment, true);
            if (!isset($filters[$key])) {
                $filters[$key] = new static($assignment_model, $key);
            }
        });

        return $filters;
    }

    /**
     * Get attribute
     *
     * @param $name
     * @return mixed|null
     */
    public function __get($name) {
        return $this->attributes[$name] ?? null;
    }

    /**
     * Check whether an attribute is set
     *
     * @param $name
     * @return bool
     */
    public function __isset($name) {
        return array_key_exists($name, $this->attributes);
    }

    /**
     * Make sure we get the right status for this filter.
     * If an assignment is active but doesn't have user assignments treat it as archived.
     *
     * @param assignment_model $assignment
     * @return int
     */
    public function get_status(assignment_model $assignment): int {
        $status = $assignment->get_status();
        $user_assignment = $assignment->get_entity()->assignment_user;

        // If this assignment has no active user entries it has to be an archived one.
        if ($status == assignment_entity::STATUS_ACTIVE && !$user_assignment) {
            $status = assignment_entity::STATUS_ARCHIVED;
        }

        return $status;
    }

    /**
     * Get human readable name for the status filter
     *
     * @param assignment_model $assignment
     * @return string
     */
    protected function get_human_status(assignment_model $assignment) {
        $status = $this->get_status($assignment);

        switch ($status) {
            case assignment_entity::STATUS_ACTIVE:
                return get_string('status_active_alt', 'totara_competency');
            case assignment_entity::STATUS_ARCHIVED:
                return get_string('status_archived_alt', 'totara_competency');
            case assignment_entity::STATUS_DRAFT:
                return get_string('status_draft', 'totara_competency');
            default:
                debugging('Unknown assignment status: ' . $status, DEBUG_DEVELOPER);
                return 'Unknown';
        }
    }
}
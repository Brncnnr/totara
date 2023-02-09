<?php
/*
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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency\entity\filters;

use coding_exception;
use core\orm\query\builder;
use core\orm\entity\filter\filter;
use core\orm\query\field;
use totara_competency\entity\assignment;
use totara_competency\user_groups;
use totara_hierarchy\entity\competency;

/**
 * This filter filters by assignment type only considering assignments of given user
 *
 * @package totara_competency\entity\filters
 */
class competency_user_assignment_type extends filter {

    protected $assignment_types = [];
    protected $user_group_types = [];

    public function apply() {
        $user_id = $this->params[0] ?? null;
        if (!($user_id > 0)) {
            throw new coding_exception('Missing user id for user assignment type filter');
        }

        // For now this will only work for competencies
        if ($this->builder->get_table() != competency::TABLE) {
            throw new coding_exception('The user assignment type filter can only filter competencies');
        }

        // If any element in the array is null we ignore this filter
        if (!empty($this->value) && !in_array(null, $this->value, true)) {
            $this->init_types();

            $exist_builder = builder::table(assignment::TABLE)
                ->join(['totara_competency_assignment_users', 'ua'], 'id', 'assignment_id')
                ->where('ua.user_id', $user_id)
                ->where('status', assignment::STATUS_ACTIVE)
                ->where_field('competency_id', new field('id', $this->builder));

            $this->add_type_conditions($exist_builder);
            $this->builder->where_exists($exist_builder);
        }
    }

    private function init_types() {
        $available_user_groups = user_groups::get_available_types();
        $available_assignment_types = assignment::get_available_types();
        foreach ($this->value as $value) {
            if (in_array($value, $available_user_groups)) {
                $this->user_group_types[] = $value;
            } else if (in_array($value, $available_assignment_types)) {
                $this->assignment_types[] = $value;
            }
        }
    }

    /**
     * @param builder $builder
     * @return builder
     */
    private function add_type_conditions(builder $builder): builder {
        // Results in something like:
        // AND (user_group_type IN ('position', 'organisation')
        // OR type IN ('self', 'other') OR (user_group_type = 'user' AND type = 'admin'))
        $builder->where(function (builder $builder) {
            if (!empty($this->user_group_types)) {
                $builder->or_where('user_group_type', $this->user_group_types);
            }
            // Treat admin filter special as it actually means: all admin assignments for individual users
            $this->add_admin_type_condition($builder);
            if (!empty($this->assignment_types)) {
                // Treat the rest of the assignment types
                $builder->or_where('type', $this->assignment_types);
            }
        });

        return $builder;
    }

    /**
     * @param builder $builder
     */
    private function add_admin_type_condition(builder $builder) {
        // Treat admin filter special as it actually means: all admin assignments for individual users
        if (($key = array_search(assignment::TYPE_ADMIN, $this->assignment_types)) !== false) {
            $builder->or_where(function (builder $builder) {
                $builder->where('user_group_type', user_groups::USER)
                    ->where('type', assignment::TYPE_ADMIN);
            });
            // remove to not include it twice
            unset($this->assignment_types[$key]);
        }
    }

}
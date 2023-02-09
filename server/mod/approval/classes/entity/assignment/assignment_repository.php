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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\entity\assignment;

use core\orm\entity\repository;
use core\orm\query\builder;
use mod_approval\model\assignment\assignment_approver_type;
use mod_approval\model\assignment\assignment_type;


/**
 * Repository for assignment.
 */
class assignment_repository extends repository {

    /**
     * Adds contextid to select, and a 'ctx' join that can be used for discovery of or filtering by context.
     *
     * @return $this
     */
    public function with_context_fields(): self {
        $this->add_select_raw('ctx.id as contextid');
        $this->join(['course_modules', 'cm'], function (builder $builder) {
            $builder->where_field('course', '=', $this->get_alias() . '.course')
                ->where_field('instance', '=', $this->get_alias() . '.id');
        })
        ->join(['context', 'ctx'], function (builder $builder) {
            $builder->where_field('instanceid', '=', 'cm.id')
                ->where('contextlevel', '=', CONTEXT_MODULE);
        });
        return $this;
    }

    /**
     * Adds hierarchical path to select, a 'hier' join on appropriate hierarchy table, and sorts the results deepest first.
     *
     * @param int $type assignment_approver_type code
     * @return $this
     */
    public function with_hier_path(int $type): self {
        global $DB;
        $this->add_select_raw('hier.path');
        $this->join([assignment_type::entity_class($type)::TABLE, 'hier'], 'hier.id', '=', 'assignment_identifier');
        $this->where('assignment_type', '=', $type);
        if ($DB->get_dbfamily() === 'mssql') {
            $this->order_by_raw('LEN(hier.path) DESC, hier.path DESC');
        } else {
            $this->order_by_raw('LENGTH(hier.path) DESC, hier.path DESC');
        }
        return $this;
    }

    /**
     * Adds a fake hierarchical path to select.
     *
     * @return $this
     */
    public function with_cohort_path(): self {
        $this->add_select_raw("'/0' as path");
        $this->where('assignment_type', '=', assignment_type::COHORT);
        return $this;
    }
}
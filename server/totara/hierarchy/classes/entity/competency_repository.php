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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_competency
 */

namespace totara_hierarchy\entity;

use core\orm\query\builder;
use core\orm\query\field;
use core\orm\query\subquery;
use core\orm\query\table;
use totara_competency\entity\assignment;
use totara_competency\entity\competency_assignment_user;
use totara_competency\entity\filters\competency_assignment_status;
use totara_competency\entity\filters\competency_assignment_type;
use totara_competency\entity\pathway;

class competency_repository extends hierarchy_item_repository {

    /**
     * Define available default filters
     *
     * @return array
     */
    protected function get_default_filters(): array {
        return array_merge(
            parent::get_default_filters(),
            [
                'assignment_type' => new competency_assignment_type(),
                'assignment_status' => new competency_assignment_status(),
            ]
        );
    }

    /**
     * Filter by competencies which are only self assignable
     *
     * @param int $user_id
     * @return $this
     */
    public function filter_by_self_assignable(int $user_id) {
        if (!$this->has_join('comp_assign_availability', 'availabilityself')) {
            $this->join(['comp_assign_availability', 'availabilityself'], 'id', 'comp_id');
            $this->where('availabilityself.availability', competency::ASSIGNMENT_CREATE_SELF);
        }

        $exist_builder = builder::table(competency_assignment_user::TABLE)
            ->join([assignment::TABLE, 'ass'], 'assignment_id', 'id')
            ->where_field('competency_id', new field('id', $this->builder))
            ->where('user_id', $user_id)
            ->where('ass.type', assignment::TYPE_SELF);

        $this->where_not_exists($exist_builder);

        return $this;
    }

    /**
     * Filter by competencies which are only other assignable
     *
     * @param int $user_id
     * @return $this
     */
    public function filter_by_other_assignable(int $user_id) {
        if (!$this->has_join('comp_assign_availability', 'availabilityother')) {
            $this->join(['comp_assign_availability', 'availabilityother'], 'id', 'comp_id');
            $this->where('availabilityother.availability', competency::ASSIGNMENT_CREATE_OTHER);
        }

        $exist_builder = builder::table(competency_assignment_user::TABLE)
            ->join([assignment::TABLE, 'ass'], 'assignment_id', 'id')
            ->where_field('competency_id', new field('id', $this->builder))
            ->where('user_id', $user_id)
            ->where('ass.type', assignment::TYPE_OTHER);

        $this->where_not_exists($exist_builder);

        return $this;
    }

    /**
     * Set order by column and direction
     *
     * @param string $column
     * @param string $direction
     * @return $this
     *
     */
    public function order_by(string $column, string $direction = 'asc') {
        switch ($column) {
            case 'name':
                return $this->order_by('fullname')
                    ->order_by('id');
            case 'framework_hierarchy':
                if (!$this->has_join(competency_framework::TABLE)) {
                    $this->join([competency_framework::TABLE, 'framework'], 'frameworkid', 'id');
                }
                $framework_alias = $this->get_join(competency_framework::TABLE)->get_table()->get_alias();

                return $this
                    ->order_by($framework_alias . '.sortorder')
                    ->order_by('sortthread')
                    ->order_by('id');
            case 'pathway':
                $sub_query = builder::table(pathway::TABLE)
                    ->select_raw('DISTINCT competency_id')
                    ->where('status', \totara_competency\pathway::PATHWAY_STATUS_ACTIVE);
                return $this->left_join((new table($sub_query))->as(pathway::TABLE), 'id', '=', 'competency_id')
                    ->order_by_raw('case when "' . pathway::TABLE . '".competency_id is null then 1 else 0 end asc')
                    ->order_by(competency::TABLE . '.fullname', $direction);
            default:
                parent::order_by($column, $direction);
                break;
        }
        return $this;
    }

    /**
     * Include competency assignments as well
     *
     * @return $this
     */
    public function with_assignments_count(): competency_repository {
        $this->add_select((new subquery(function (builder $builder) {
            $builder->from('totara_competency_assignments')
                ->as('count_tac')
                ->select('count(id)')
                ->where_field('competency_id', new field('id', $this->builder));
        }))->as('assignments_count'));

        return $this;
    }

}

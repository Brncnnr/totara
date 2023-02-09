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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency;


use Closure;
use core\orm\query\builder;
use core\orm\query\raw_field;
use core\user_orm_helper;

class expanded_users {

    protected $user_ids = [];
    protected $pos_ids = [];
    protected $org_ids = [];
    protected $cohort_ids = [];

    protected $name_filter = '';

    protected $user_group_separator = '||-||||-|||-||||-||';

    protected $individual_string = '__|_!_individual_user_!_|__';

    public function set_user_ids($ids) {
        $this->user_ids = $ids;

        return $this;
    }

    public function set_position_ids($ids) {
        $this->pos_ids = $ids;

        return $this;
    }

    public function set_organisation_ids($ids) {
        $this->org_ids = $ids;

        return $this;
    }

    public function set_audience_ids($ids) {
        $this->cohort_ids = $ids;

        return $this;
    }

    public function filter_by_name(string $name) {
        $this->name_filter = $name;

        return $this;
    }

    /**
     * Fetch paginated list of users
     *
     * @param int $page
     * @return \core\orm\paginator
     */
    public function fetch_paginated(int $page) {
        $user_fields = totara_get_all_user_name_fields(true, 'users', null, null, true);
        $user_builder = builder::table($this->get_subquery());
        $user_builder->as('tbl')
            ->select_raw('user_id')
            ->add_select_raw($user_fields)
            ->add_select(new raw_field($user_builder->group_concat('tbl.user_group', $this->user_group_separator) . ' as user_group_name'))
            ->join(['user', 'users'], 'user_id', 'id')
            ->order_by_raw($user_fields)
            ->group_by_raw('tbl.user_id, '.$user_fields);

        // This is not very optimal, have a better idea?
        if (!empty($this->name_filter)) {
            user_orm_helper::filter_by_fullname($user_builder, $this->name_filter, 'users');
        }

        // Closure::fromCallable allows to keep map_users hidden within the class
        return $user_builder
            ->results_as_arrays()
            ->paginate($page)
            ->transform(Closure::fromCallable(([$this, 'map_users'])));
    }

    /**
     * Get query builder for selecting user ids with their position filtered by position id.
     *
     * @return builder
     */
    protected function position_filter(): builder {
        return builder::table('pos')
            ->as('pos')
            ->select(['ja.userid as user_id', 'pos.fullname as user_group'])
            ->join(['job_assignment', 'ja'], 'id', 'positionid')
            ->where('pos.id', $this->pos_ids);
    }

    /**
     * Get query builder for selecting user ids with their organisation filtered by organisation id.
     *
     * @return builder
     */
    protected function organisation_filter(): builder {
        return builder::table('org')
            ->select(['ja.userid as user_id', 'org.fullname as user_group'])
            ->join(['job_assignment', 'ja'], 'id', 'organisationid')
            ->where('org.id', $this->org_ids);
    }

    /**
     * Get query builder for selecting user ids with their audience filtered by cohort id.
     *
     * @return builder
     */
    protected function cohort_filter(): builder {
        return builder::table('cohort_members')
            ->select(['cohort_members.userid as user_id', 'cohort.name as user_group'])
            ->join('cohort', 'cohortid', 'id')
            ->where('cohort_members.cohortid', $this->cohort_ids);
    }

    /**
     * Get query builder for selecting user ids with their audience filtered by cohort id.
     *
     * @return builder
     */
    protected function user_filter(): builder {
        return builder::table('user')
            ->as('usr')
            // Not very optimal way of not injecting lang strings into the query
            ->select(['usr.id as user_id', new raw_field("'" . $this->individual_string . "' as user_group")])
            ->where('id', $this->user_ids);
    }

    /**
     * Return subquery for filtering users out
     *
     * @return builder
     */
    protected function get_subquery(): builder {
        $builder = $this->user_filter();
        if (!empty($this->cohort_ids)) {
            $builder->union($this->cohort_filter());
        }
        if (!empty($this->pos_ids)) {
            $builder = $builder->union($this->position_filter());
        }
        if (!empty($this->org_ids)) {
            $builder = $builder->union($this->organisation_filter());
        }
        return $builder;
    }

    protected function map_users($user) {
        $user['user_id'] = (int) $user['user_id'];

        $user_group_names = explode(
            $this->user_group_separator,
            $this->replace_individual_name_string($user['user_group_name'])
        );

        sort($user_group_names);

        $user['user_group_names'] = $user_group_names;
        unset($user['user_group_name']);

        return $user;
    }

    /**
     * @param $item
     * @return string
     */
    protected function replace_individual_name_string($item) {
        // No need to do this if there are no individuals
        if (empty($this->user_ids)) {
            return $item;
        }
        return str_replace($this->individual_string, get_string('individual', 'totara_competency'), $item);
    }

}
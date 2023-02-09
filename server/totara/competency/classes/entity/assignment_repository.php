<?php
/*
 * This file is part of Totara Perform
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
 * @package totara_competency
 */

namespace totara_competency\entity;


use core\orm\query\field;
use core\orm\query\raw_field;
use totara_competency\entity\filters\competency_assignment_type;
use core\orm\entity\filter\basket;
use totara_competency\user_groups;
use core\orm\query\builder;
use core\orm\entity\repository;
use core\orm\entity\filter\equal;
use core\orm\entity\filter\in;
use core\orm\entity\filter\like;

/**
 * Assignment entity repository
 *
 * @package totara_competency\entity
 */
class assignment_repository extends repository {

    /**
     * Define available default filters
     *
     * @return array
     */
    protected function get_default_filters(): array {
        global $DB;
        $user_name = $DB->sql_concat_join("' '", totara_get_all_user_name_fields_join('"user"', null, true));
        return [
            'assignment_type' => new competency_assignment_type(),
            'text' => new like([
                new raw_field($user_name),
                '"cohort".name',
                '"pos".fullname',
                '"org".fullname',
                '"comp".fullname'
            ]),
            'status' => new equal('status'),
            'basket' => new basket(),
            'framework' => new equal('"comp".frameworkid'),
            'ids' => new in('id')
        ];
    }

    /**
     * @param array $ids
     * @return $this
     */
    public function filter_by_ids(array $ids): assignment_repository {
        $this->where('id', $ids);

        return $this;
    }

    /**
     * Return only active assignments
     *
     * @return $this
     */
    public function filter_by_active(): self {
        $this->where('status', assignment::STATUS_ACTIVE);

        return $this;
    }

    /**
     * Return only assignments marked for expansion
     *
     * @return $this
     */
    public function filter_by_expand(): self {
        $this->where('expand', true);

        return $this;
    }

    /**
     * Return only draft assignments
     *
     * @return $this
     */
    public function filter_by_draft(): self {
        $this->where('status', assignment::STATUS_DRAFT);

        return $this;
    }

    /**
     * Return only non-draft assignments
     *
     * @return $this
     */
    public function filter_by_not_draft(): self {
        $this->where('status', '<>', assignment::STATUS_DRAFT);

        return $this;
    }

    /**
     * Return only draft assignments
     *
     * @return $this
     */
    public function filter_by_archived(): self {
        $this->where('status', assignment::STATUS_ARCHIVED);

        return $this;
    }

    /**
     * Filter assignment by user group type
     *
     * @param string $user_group
     * @return $this
     */
    public function filter_by_user_group_type(string $user_group) {
        if (!in_array($user_group, user_groups::get_available_types(), true)) {
            throw new \coding_exception('Invalid assignment type has been passed.');
        }

        return $this->where('user_group_type', $user_group);
    }

    /**
     * Filter by user group id(s)
     *
     * @param array|int $ids User group id(s)
     * @return $this
     */
    public function filter_by_user_group_ids($ids) {
        return $this->where('user_group_id', $ids);
    }

    /**
     * Add the names of the user groups (user, cohort, position, organisation) and competency to the result
     *
     * @return $this
     */
    public function with_names(): assignment_repository {
        return $this->with_user_group_name()
            ->with_competency_name();
    }

    /**
     * Add the names of the user groups (user, cohort, position, organisation) to the result
     *
     * @return $this
     */
    public function with_user_group_name(): assignment_repository {

        $this->builder->left_join(
            'user',
            function (builder $builder) {
                $builder->where(new field('user_group_type', $this->builder), user_groups::USER)
                    ->where_field('id', new field('user_group_id', $this->builder));
            }
        )->left_join(
            'cohort',
            function (builder $builder) {
                $builder->where(new field('user_group_type', $this->builder), user_groups::COHORT)
                    ->where_field('id', new field('user_group_id', $this->builder));
            }
        )->left_join(
            'pos',
            function (builder $builder) {
                $builder->where(new field('user_group_type', $this->builder), user_groups::POSITION)
                    ->where_field('id', new field('user_group_id', $this->builder));
            }
        )->left_join(
            'org',
            function (builder $builder) {
                $builder->where(new field('user_group_type', $this->builder), user_groups::ORGANISATION)
                    ->where_field('id', new field('user_group_id', $this->builder));
            }
        );

        // We need the full name to make ordering by user_group_name possible
        global $DB;
        $user_name = $DB->sql_concat_join("' '", totara_get_all_user_name_fields_join('"user"', null, true));


        // Having null for deleted doesn't return anything
        // if using an extra condition (AND !empty(user name)) will leave you with undefined or double
        // the number of conditions and it's already quite cumbersome, e.g:
        //   WHEN type = USER and !empty ug_name THEN $ug_name
        //   WHEN type = USER and empty ug_name THEN NULL
        //   ...
        // PS someone, find a better way of doing this.
        $this->builder
            // Later we want to run the name fields through the fullname function so we need all of them
            ->add_select_raw(totara_get_all_user_name_fields(true, '"user"', null, null, true))
            ->add_select_raw(
                "CASE
                    WHEN user_group_type = '".user_groups::USER."' THEN COALESCE({$user_name}, 'deleted')
                    WHEN user_group_type = '".user_groups::COHORT."' THEN COALESCE(\"cohort\".name, 'deleted')
                    WHEN user_group_type = '".user_groups::POSITION."' THEN COALESCE(\"pos\".fullname, 'deleted')
                    WHEN user_group_type = '".user_groups::ORGANISATION."' THEN COALESCE(\"org\".fullname, 'deleted')
                ELSE 'undefined' END as user_group_name")
            ->add_select_raw(
                "CASE
                    WHEN user_group_type = '".user_groups::USER."' THEN \"user\".idnumber
                    WHEN user_group_type = '".user_groups::COHORT."' THEN \"cohort\".idnumber
                    WHEN user_group_type = '".user_groups::POSITION."' THEN \"pos\".idnumber
                    WHEN user_group_type = '".user_groups::ORGANISATION."' THEN \"org\".idnumber
                ELSE '' END as idnumber");

        return $this;
    }

    /**
     * Add the name of the competency to the result
     *
     * @return $this
     */
    public function with_competency_name(): assignment_repository {

        if (!$this->builder->has_join('comp')) {
            $this->builder->join('comp', 'competency_id', 'id');
        }

        $this->add_select('comp.fullname as competency_name')
            ->add_select('comp.description as competency_description');

        return $this;
    }

}

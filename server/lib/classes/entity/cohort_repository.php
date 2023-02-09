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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @package core
 */

namespace core\entity;

use core\orm\entity\traits\has_visible_filter;
use core\orm\entity\filter\basket;
use core\orm\entity\filter\visible;
use core\orm\entity\repository;
use core\orm\query\field;
use core\orm\entity\filter\in;
use core\orm\entity\filter\like;
use core\orm\query\sql\query;

/**
 * @package core
 */
class cohort_repository extends repository {

    use has_visible_filter;

    public function get_default_filters(): array {
        return [
            'basket' => new basket(),
            'visible' => new visible(),
            'text' => new like([
                new field('name', $this->builder),
                new field('description', $this->builder),
                new field('idnumber', $this->builder),
            ]),
            'ids' => new in('id')
        ];
    }

    /**
     * Select only limited subset of columns intended to be used with picker dialogue
     *
     * @return $this
     */
    public function select_only_fields_for_picker() {
        $this->add_select([
            'id',
            'name',
            'description',
            'idnumber',
        ]);

        return $this;
    }

    /**
     * Get number of members in a cohort.
     *
     * @param int $cohort_id
     * @return int
     */
    public function get_members_count(int $cohort_id): int {
        return cohort_member::repository()->where('cohortid', $cohort_id)->count();
    }

    /**
     * Eager load the count of members to repository query.
     *
     * @return cohort_repository
     */
    public function preload_members_count(): cohort_repository {
        $cohort_alias = $this->get_alias();

        $member_count_builder = cohort_member::repository()->as('count_cm')
            ->select_raw("COUNT(count_cm.id)")
            ->where_raw("count_cm.cohortid = $cohort_alias.id")
            ->get_builder();
        [$sql, $params] = query::from_builder($member_count_builder)->build();
        $this->add_select_raw("($sql) as members_count", $params);

        return $this;
    }
}

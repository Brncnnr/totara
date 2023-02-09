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
 * @package totara_competency
 */

namespace totara_competency\entity;


use core\orm\entity\repository;
use core\orm\query\builder;

class competency_assignment_user_repository extends repository {

    /**
     * Remove orphan users from assignments expansion table
     */
    public static function remove_orphaned_records() {
        // Delete all orphaned records
        competency_assignment_user::repository()
            ->where_exists(function (builder $builder) {
                $builder->from(assignment::TABLE)
                    ->as('a')
                    ->select('a.id')
                    ->where('a.status', '<>', assignment::STATUS_ACTIVE)
                    ->where_field('a.id', '{' . competency_assignment_user::TABLE . '}.assignment_id');
            })
            ->delete();
    }

    /**
     * @param int $assignment_id
     * @return $this
     */
    public function filter_by_assignment_id(int $assignment_id): competency_assignment_user_repository {
        $this->where('assignment_id', $assignment_id);

        return $this;
    }

}

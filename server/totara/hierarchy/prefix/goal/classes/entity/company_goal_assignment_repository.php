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
 * @author Ning Zhou <ning.zhou@totaralearning.com>
 * @package hierarchy_goal
 */

namespace hierarchy_goal\entity;

use core\orm\entity\repository;

class company_goal_assignment_repository extends repository {

    /**
     * Set order by column and direction
     *
     * @param string $column
     * @param string $direction
     * @return $this
     */
    public function order_by(string $column, string $direction = 'asc') {
        switch ($column) {
            case 'targetdate':
                $default_date = 1;
                $company_goal_table = company_goal::TABLE;
                return $this->order_by_raw("COALESCE($company_goal_table.targetdate, $default_date) DESC, $company_goal_table.fullname $direction");
            default:
                parent::order_by($column, $direction);
                break;
        }
        return $this;
    }
}
<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package hierarchy_goal
 */

namespace hierarchy_goal\entity\filters;

use core\orm\entity\filter\filter;

use hierarchy_goal\entity\company_goal;

/**
 * Filters company goals by their full names.
 *
 * Note: this uses a join from <parent table> goalid column to the <goal> table
 * id column.
 */
class company_goal_assignment_fullname extends filter {
    /**
     * {@inheritdoc}
     */
    public function apply() {
        if (!$this->value) {
            return;
        }

        $table = [company_goal::TABLE, 'company_goal_table'];
        if (!$this->builder->get_join($table[0])) {
            $this->builder->join($table, 'goalid', 'id');
        }

        $this->builder->where($table[1] . '.fullname', 'ilike', $this->value);
    }
}

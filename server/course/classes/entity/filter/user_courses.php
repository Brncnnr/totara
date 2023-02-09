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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package core_course
 */

namespace core_course\entity\filter;

use core\orm\entity\filter\filter;

/**
 * Finds all courses where user is enrolled in.
 */
class user_courses extends filter {

    /**
     * @inheritDoc
     */
    public function apply() {
        $sql = enrol_get_all_users_courses_sql(
            $this->value,
            true
        );
        if (empty($sql)) {
            return;
        }

        [$select, $joins, $where, $order_by, $params] = $sql;
        [$visibility_sql, $visibility_params] = totara_visibility_where($this->value);

        $where .= " AND course.id = c.id";
        $this->builder->where_raw(" EXISTS (SELECT c.id FROM {course} c {$joins} {$where})", $params)
            ->where_raw($visibility_sql, $visibility_params);
    }


}

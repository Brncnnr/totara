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
use totara_competency\entity\assignment;
use core\orm\query\builder;
use core\orm\query\field;
use core\orm\entity\filter\filter;

class competency_user_assignment_status extends filter {

    public function apply() {
        $user_id = $this->params[0] ?? null;
        if (!($user_id > 0)) {
            throw new coding_exception('Missing user id for user assignment type filter');
        }

        $assigned = $this->value === 1;

        $exist_builder = builder::table(assignment::TABLE)
            ->join(['totara_competency_assignment_users', 'ua'], 'id', 'assignment_id')
            ->where('ua.user_id', $user_id)
            ->where('status', assignment::STATUS_ACTIVE)
            ->where_field('competency_id', new field('id', $this->builder));

        if ($assigned) {
            $this->builder->where_exists($exist_builder);
        } else {
            $this->builder->where_not_exists($exist_builder);
        }
    }
}
<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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

namespace totara_competency\models\profile\traits;

use totara_competency\entity\assignment;

trait assignment_key {

    /**
     * Build a key to group filters by
     *
     * @param assignment $assignment Assignment entity
     * @param bool $include_status A flag whether to add assignment status to the filter
     * @return string
     */
    protected static function build_key(assignment $assignment, bool $include_status = false) {
        $type = $assignment->type;

        // We are grouping individual admin and manager assignments together
        // in an artificial "direct assignments" group
        if ($type === assignment::TYPE_OTHER) {
            $type = assignment::TYPE_ADMIN;
        }

        $status = $assignment->status;

        // If this assignment has no active user entries it has to be an archived one.
        if ($assignment->status == assignment::STATUS_ACTIVE && !$assignment->assignment_user) {
            $status = assignment::STATUS_ARCHIVED;
        }

        $key = $include_status ? "{$status}/" : '';

        return "{$key}{$type}/{$assignment->user_group_type}/{$assignment->user_group_id}";
    }
}
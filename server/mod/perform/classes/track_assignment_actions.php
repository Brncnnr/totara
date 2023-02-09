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
 * @package mod_perform
 */

namespace mod_perform;

use mod_perform\entity\activity\track_assignment;

/**
 * This is mostly there for performing bulk actions on assignments
 */
class track_assignment_actions {

    /**
     * Mark all related assignments for expansion on the next expand run
     *
     * @param array $user_groupings array of [grouping]
     */
    public function mark_for_expansion(array $user_groupings) {
        foreach ($user_groupings as $grouping) {
            track_assignment::repository()
                ->where('user_group_type', $grouping->get_type())
                ->where('user_group_id', $grouping->get_id())
                ->where('expand', false)
                ->update(['expand' => 1]);
        }
    }

    /**
     * An inline constructor.
     *
     * @return $this
     */
    public static function create(): self {
        return new static();
    }

}
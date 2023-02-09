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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_criteria
 */

namespace totara_criteria\hook;

use totara_core\hook\base;

/**
 * Event to be triggered when a user's achievement of a specific criterion changed
 * The change can be either that the user now satisfies the criteria, or no longer satisfy the criteria
 */
class criteria_achievement_changed extends base {

    /** @var array */
    protected $user_criteria_ids;

    /**
     * @param array $user_criteria_ids List of affected criteria per affected user. Key: user_id, Value: array of criteria_ids
     */
    public function __construct(array $user_criteria_ids) {
        $this->user_criteria_ids = $user_criteria_ids;
    }

    /**
     * Return a list of criteria ids for all users whose achievements changed
     * @return array Key: user_id, Value: array of criteria_ids affected
     */
    public function get_user_criteria_ids(): array {
        return $this->user_criteria_ids;
    }
}

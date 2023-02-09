<?php
/**
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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_userstatus
 */

namespace mod_perform\controllers;

use mod_perform\models\activity\activity;
use moodle_exception;

/**
 * Use the methods in this trait if your controller requires an activity
 */
trait requires_activity {

    /**
     * @return int
     */
    protected function get_activity_id_param(): int {
        return $this->get_required_param('activity_id', PARAM_INT);
    }

    /**
     * Loads activity model from parameters
     * @return activity
     * @throws moodle_exception
     */
    protected function get_activity_from_param(): activity {
        try {
            return activity::load_by_id($this->get_activity_id_param());
        } catch (\Exception $exception) {
            throw new moodle_exception('invalid_activity', 'mod_perform');
        }
    }

}
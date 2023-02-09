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
 * @package totara_job
 */

namespace totara_job\testing;

/**
 * Jobs generator.
 *
 */
final class generator extends \core\testing\component_generator {

    /**
     * Creates a user and a job assignment with a manager for that user.
     *
     * @param array      $userdata
     * @param int        $managerid
     * @param array|null $options
     *
     * @return array[\stdClass, \totara_job\job_assignment] An array [the user, the user's job assignment]
     */
    public function create_user_and_job(array $userdata = [], int $managerid = null, array $options = null) {
        $user = $this->datagenerator->create_user($userdata, $options);

        // Assign manager for correct event messaging handler work.
        if ($managerid === null && isset($userdata['managerid'])) {
            $managerid = $userdata['managerid'];
        } else if ($managerid === null && !isset($userdata['managerid'])) {
            $admin = get_admin();
            $managerid = $admin->id;
        }

        $managerja = \totara_job\job_assignment::get_first($managerid, false);
        if (empty($managerja)) {
            $managerja = \totara_job\job_assignment::create_default($managerid);
        }

        $jobassignment = \totara_job\job_assignment::create_default($user->id, ['managerjaid' => $managerja->id]);

        return [$user, $jobassignment];
    }
}

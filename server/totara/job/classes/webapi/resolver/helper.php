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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package totara_job
 */

namespace totara_job\webapi\resolver;

use context_user;
use core\entity\user;
use core\exception\unresolved_record_reference;
use core\reference\user_record_reference;
use hierarchy_organisation\reference\hierarchy_organisation_record_reference;
use hierarchy_position\reference\hierarchy_position_record_reference;
use totara_core\advanced_feature;
use totara_job\reference\job_assignment_record_reference;

/**
 * Helper trait containing functions useful to the job resolvers
 */
trait helper {

    /**
     * Returns a user given its ID in the args array.
     * @param array $args
     * @param string $name
     * @param bool $defaulttocurrent
     * @return \stdClass
     */
    private static function get_user_from_args(array $args, string $name = 'userid', bool $defaulttocurrent = true) {
        global $DB, $USER;
        $userid = $args[$name] ?? null;
        if ($userid === null) {
            if ($defaulttocurrent) {
                return $USER;
            }
            throw new \moodle_exception('missingparam', '', '', $name, join(',', array_keys($args)));
        }
        if ($USER->id == $userid) {
            return $USER;
        }
        return $DB->get_record('user', ['id' => $userid, 'deleted' => 0], '*', MUST_EXIST);
    }

    /**
     * Returns an array with job assignment data extracted from the provided input data
     * @param \stdClass $user The user this job assignment belongs to
     * @param array $input Input data
     * @param string $exception_class The class of the exception that should be thrown
     * @return array
     */
    private static function get_job_assignment_params_from_input(
        \stdClass $user,
        array $input = [],
        string $exception_class = \moodle_exception::class
    ): array {
        global $CFG;
        $user_logged_in = user::logged_in();

        $fields_map = [
            'idnumber' => 'idnumber',
            'fullname' => 'fullname',
            'shortname' => 'shortname',
            'start_date' => 'startdate',
            'end_date' => 'enddate',
            'temp_manager_expiry_date' => 'tempmanagerexpirydate'
        ];
        $params = [];
        foreach ($fields_map as $key => $value) {
            if (array_key_exists($key, $input)) {
                $params[$value] = $input[$key];
            }
        }

        if (array_key_exists('position', $input)) {
            if (!empty($input['position'])) {
                if (advanced_feature::is_disabled('positions')) {
                    throw new $exception_class('Position feature is disabled.');
                }

                $position_reference = new hierarchy_position_record_reference();
                $position = $position_reference->get_record($input['position']);
                $params['positionid'] = $position->id;
            } else {
                $params['positionid'] = null;
            }
        }

        if (array_key_exists('organisation', $input)) {
            if (!empty($input['organisation'])) {
                if (advanced_feature::is_disabled('organisations')) {
                    throw new $exception_class('Organisations feature is disabled.');
                }

                $organisation_reference = new hierarchy_organisation_record_reference();
                $organisation = $organisation_reference->get_record($input['organisation']);
                $params['organisationid'] = $organisation->id;
            } else {
                $params['organisationid'] = null;
            }
        }

        // Start and end dates.
        if (isset($input['start_date']) && isset($input['end_date']) && $input['start_date'] > $input['end_date']) {
            throw new $exception_class('The start date can not be later than the end date.');
        }

        if (
            array_key_exists('manager', $input)
            || array_key_exists('temp_manager', $input)
            || array_key_exists('temp_manager_expiry_date', $input)
        ) {
            $delegatemanager = false;
            if (array_key_exists('temp_manager', $input) || array_key_exists('temp_manager_expiry_date', $input)) {
                if (empty($CFG->enabletempmanagers)) {
                    throw new $exception_class('Temporary managers config setting is disabled.');
                }
                if (has_capability('totara/core:delegateusersmanager', context_user::instance($user->id))) {
                    $delegatemanager = true;
                } else if (
                    $user_logged_in->id == $user->id
                    && has_capability('totara/core:delegateownmanager', context_user::instance($user->id))
                ) {
                    $delegatemanager = true;
                }

                if (!$delegatemanager) {
                    throw new $exception_class(
                        'You do not have permission to delegate a manager.'
                    );
                }
            }
        }

        if (array_key_exists('manager', $input)) {
            if (empty($input['manager'])) {
                $params['managerjaid'] = null;
            } else {
                $manager_reference = new job_assignment_record_reference('Manager');
                try {
                    $manager = $manager_reference->get_record($input['manager']);
                } catch (unresolved_record_reference $exc) {
                    if (isset($user_logged_in->tenantid)) {
                        // Make a second attempt for a possible tenant participant match for the user.
                        $manager_reference->set_allow_tenant_participants(true);
                        $manager = $manager_reference->is_a_tenant_participant($user_logged_in->tenantid)
                            ->get_record($input['manager']);
                    } else {
                        throw new unresolved_record_reference($exc->getMessage());
                    }
                }

                if ($user->id == $manager->userid) {
                    throw new $exception_class('The user cannot be assigned as their own manager.');
                }
                $params['managerjaid'] = $manager->id;
            }
        }

        if (array_key_exists('temp_manager', $input)) {
            if (empty($input['temp_manager'])) {
                $params['tempmanagerjaid'] = null;
                $params['tempmanagerexpirydate'] = $input['temp_manager_expiry_date'] = null;
            } else {
                if (!isset($input['temp_manager_expiry_date'])) {
                    throw new $exception_class('A temporary manager expiry date is required.');
                }

                $temp_manager_reference = new job_assignment_record_reference('Temporary manager');
                $temp_manager = $temp_manager_reference->get_record($input['temp_manager']);
                if ($user->id == $temp_manager->userid) {
                    throw new $exception_class(
                        'The user cannot be assigned as their own temporary manager.'
                    );
                }
                $params['tempmanagerjaid'] = $temp_manager->id;
            }
        }

        if (isset($input['temp_manager_expiry_date'])) {
            if ($input['temp_manager_expiry_date'] < time()) {
                throw new $exception_class('The temporary manager expiry date can not be in the past.');
            }
            $params['tempmanagerexpirydate'] = $input['temp_manager_expiry_date'];
        }

        if (array_key_exists('appraiser', $input)) {
            if (empty($input['appraiser'])) {
                $params['appraiserid'] = null;
            } else {
                $user_record_reference = new user_record_reference('Appraiser');
                $appraiser = $user_record_reference->not_a_guest()->get_record($input['appraiser']);
                if ($appraiser->id == $user->id) {
                    throw new $exception_class('The user can not be their own appraiser!');
                }
                $params['appraiserid'] = $appraiser->id;
            }
        }

        return $params;
    }
}
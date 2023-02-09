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

namespace totara_job\webapi\resolver\mutation;

use \core\webapi\execution_context;
use core\webapi\middleware\require_login;
use core\webapi\mutation_resolver;
use totara_core\advanced_feature;
use \totara_job\job_assignment;
use totara_job\webapi\resolver\helper;
use hierarchy_organisation\entity\organisation;
use hierarchy_position\entity\position;
use core\entity\user;

/**
 * Mutation to create a job assignment.
 */
class create_assignment extends mutation_resolver {

    use helper;

    /**
     * Creates an assignment and returns the new assignment id.
     *
     * @param array $args
     * @param execution_context $ec
     * @return int
     */
    public static function resolve(array $args, execution_context $ec) {
        global $CFG, $USER;

        require_once($CFG->dirroot . '/totara/job/lib.php');

        $user = self::get_user_from_args($args, 'userid', false);
        // They have to be able to view and edit the job assignments in order to create.
        if (!\totara_job_can_edit_job_assignments($user->id) || !\totara_job_can_view_job_assignments($user)) {
            throw new \coding_exception('No permission to create job assignments.');
        }

        $jobassignment = new \stdClass();
        $jobassignment->userid = $user->id;
        $jobassignment->idnumber = $args['idnumber'];
        $jobassignment->fullname = $args['fullname'] ?? null;
        $jobassignment->shortname = $args['shortname'] ?? null;
        $jobassignment->description = $args['description'] ?? null;

        // Position.
        if (isset($args['positionid']) && !advanced_feature::is_disabled('positions')) {
            if (!position::repository()->find($args['positionid'])) {
                throw new \coding_exception('The position does not exist.');
            }
            $jobassignment->positionid = $args['positionid'];
        }

        // Organisation.
        if (isset($args['organisationid']) && !advanced_feature::is_disabled('organisations')) {
            if (!organisation::repository()->find($args['organisationid'])) {
                throw new \coding_exception('The organisation does not exist.');
            }
            $jobassignment->organisationid = $args['organisationid'];
        }

        // Start and end dates.
        if (isset($args['startdate']) && isset($args['enddate']) && $args['startdate'] > $args['enddate']) {
            throw new \coding_exception('The start date can not be greater than the end date.');
        }
        $jobassignment->startdate = $args['startdate'] ?? null;
        $jobassignment->enddate = $args['enddate'] ?? null;

        $delegatemanager = false;
        if (!empty($CFG->enabletempmanagers)) {
            if (has_capability('totara/core:delegateusersmanager', \context_user::instance($user->id))) {
                $delegatemanager = true;
            } else if ($USER->id == $user->id && has_capability('totara/core:delegateownmanager', \context_user::instance($user->id))) {
                $delegatemanager = true;
            }
        }

        if (!$delegatemanager && (isset($args['managerjaid']) || isset($args['tempmanagerjaid']))) {
            throw new \coding_exception('You do not have permission to delegate a manager.');
        }

        // Manager.
        if (isset($args['managerjaid'])) {
            $job = job_assignment::get_with_id($args['managerjaid'], false);
            if (!$job) {
                throw new \coding_exception('The managers job assignment does not exists.');
            }
            if ($user->id == $job->userid) {
                throw new \coding_exception('The user cannot be assigned as their own manager.');
            }
            $jobassignment->managerjaid = $args['managerjaid'] ?? null;
        }

        // Temporary manager.
        if (isset($args['tempmanagerjaid'])) {
            if (!isset($args['tempmanagerexpirydate'])) {
                throw new \coding_exception('A temporary manager expiry date is required.');
            }

            $job = job_assignment::get_with_id($args['tempmanagerjaid'], false);
            if (!$job) {
                throw new \coding_exception('The temporary managers job assignment does not exists.');
            }
            if ($user->id == $job->userid) {
                throw new \coding_exception('The user cannot be assigned as their own temporary manager.');
            }
            $jobassignment->tempmanagerjaid = $args['tempmanagerjaid'] ?? null;

            if ($args['tempmanagerexpirydate'] < time()) {
                throw new \coding_exception('The temporary manager expiry date can not be in the past.');
            }
            $jobassignment->tempmanagerexpirydate = $args['tempmanagerexpirydate'] ?? null;
        }

        // Appraiser.
        if (isset($args['appraiserid'])) {
            if (!user::repository()->find($args['appraiserid'])) {
                throw new \coding_exception('The appraiser does not exist.');
            } else if ($args['appraiserid'] == $jobassignment->userid) {
                throw new \coding_exception('The user can not be their own appraiser!');
            } else if (isguestuser($args['appraiserid'])) {
                throw new \coding_exception('Guest user can not be an appraiser!');
            }
            $jobassignment->appraiserid = $args['appraiserid'];
        }

        $jobassignment->totarasync = $args['totarasync'] ?? null;

        $job = job_assignment::create($jobassignment);
        return $job->id;
    }

    public static function get_middleware(): array {
        return [
            require_login::class
        ];
    }

}
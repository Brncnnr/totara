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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_competency
 */

use core\event\cohort_deleted;
use core\event\cohort_member_added;
use core\event\cohort_member_removed;
use core\event\course_deleted;
use core\event\user_deleted;
use hierarchy_competency\event\competency_created;
use hierarchy_competency\event\competency_deleted;
use hierarchy_competency\event\competency_updated;
use hierarchy_competency\event\scale_min_proficient_value_updated;
use hierarchy_organisation\event\organisation_deleted;
use hierarchy_position\event\position_deleted;
use totara_cohort\event\members_updated;
use totara_competency\event\assignment_activated;
use totara_competency\event\assignment_archived;
use totara_competency\event\assignment_deleted;
use totara_competency\event\assignment_user_archived;
use totara_competency\event\assignment_user_assigned;
use totara_competency\event\assignment_user_assigned_bulk;
use totara_competency\event\assignment_user_unassigned;
use totara_competency\observers\assignment as assignment_observer;
use totara_competency\observers\assignment_aggregation;
use totara_competency\observers\assignment_user_groups;
use totara_competency\observers\audience_deleted as audience_deleted_observer;
use totara_competency\observers\competency as competency_observer;
use totara_competency\observers\competency_deleted as competency_deleted_observer;
use totara_competency\observers\course;
use totara_competency\observers\organisation_deleted as organisation_deleted_observer;
use totara_competency\observers\position_deleted as position_deleted_observer;
use totara_competency\observers\scale as scale_observer;
use totara_competency\observers\user_deleted as user_deleted_observer;
use totara_competency\observers\user_log as user_log_observer;
use totara_competency\observers\user_unassigned as user_unassigned_observer;
use totara_job\event\job_assignment_created;
use totara_job\event\job_assignment_deleted;
use totara_job\event\job_assignment_updated;

defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => competency_updated::class,
        'callback' => competency_observer::class.'::updated',
    ],
    [
        'eventname' => competency_created::class,
        'callback' => competency_observer::class.'::created',
    ],
    [
        'eventname' => competency_deleted::class,
        'callback' => competency_observer::class.'::deleted',
    ],
    [
        'eventname' => scale_min_proficient_value_updated::class,
        'callback' => scale_observer::class.'::min_proficient_value_updated',
    ],
    // Assignment events
    [
        'eventname' => assignment_activated::class,
        'callback'  => assignment_observer::class.'::activated'
    ],
    [
        'eventname' => assignment_archived::class,
        'callback'  => assignment_observer::class.'::archived'
    ],
    [
        'eventname' => assignment_deleted::class,
        'callback'  => assignment_observer::class.'::deleted'
    ],
    [
        'eventname' => assignment_user_assigned::class,
        'callback'  => user_log_observer::class.'::log'
    ],
    [
        'eventname' => assignment_user_assigned_bulk::class,
        'callback'  => user_log_observer::class.'::log'
    ],
    [
        'eventname' => assignment_user_archived::class,
        'callback'  => user_log_observer::class.'::log'
    ],
    [
        'eventname' => assignment_user_unassigned::class,
        'callback'  => user_unassigned_observer::class.'::observe'
    ],
    // Reacting to deleting competencies:
    [
        'eventname' => competency_deleted::class,
        'callback'  => competency_deleted_observer::class.'::observe'
    ],
    // Reacting to deleting user groups:
    [
        'eventname' => user_deleted::class,
        'callback'  => user_deleted_observer::class.'::observe'
    ],
    [
        'eventname' => cohort_deleted::class,
        'callback'  => audience_deleted_observer::class.'::observe'
    ],
    [
        'eventname' => position_deleted::class,
        'callback'  => position_deleted_observer::class.'::observe'
    ],
    [
        'eventname' => organisation_deleted::class,
        'callback'  => organisation_deleted_observer::class.'::observe'
    ],
    [
        'eventname' => assignment_user_assigned::class,
        'callback' => assignment_aggregation::class.'::user_assigned',
    ],
    [
        'eventname' => assignment_user_assigned_bulk::class,
        'callback' => assignment_aggregation::class.'::user_assigned_bulk',
    ],
    [
        'eventname' => assignment_user_unassigned::class,
        'callback' => assignment_aggregation::class.'::user_unassigned',
    ],
    [
        'eventname' => assignment_user_archived::class,
        'callback' => assignment_aggregation::class.'::user_archived',
    ],
    [
        'eventname' => course_deleted::class,
        'callback' => course::class.'::deleted',
    ],
    [
        'eventname' => members_updated::class,
        'callback' => assignment_user_groups::class.'::cohort_updated',
    ],
    [
        'eventname' => cohort_member_added::class,
        'callback' => assignment_user_groups::class.'::cohort_updated',
    ],
    [
        'eventname' => cohort_member_removed::class,
        'callback' => assignment_user_groups::class.'::cohort_updated',
    ],
    [
        'eventname' => job_assignment_created::class,
        'callback' => assignment_user_groups::class.'::job_assignment_updated',
    ],
    [
        'eventname' => job_assignment_updated::class,
        'callback' => assignment_user_groups::class.'::job_assignment_updated',
    ],
    [
        'eventname' => job_assignment_deleted::class,
        'callback' => assignment_user_groups::class.'::job_assignment_updated',
    ],
];

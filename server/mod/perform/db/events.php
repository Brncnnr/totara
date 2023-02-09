<?php
/**
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
 * @package mod_perform
 */

use core\event\cohort_deleted;
use core\event\cohort_member_added;
use core\event\cohort_member_removed;
use core\event\tenant_deleted;
use core\event\user_deleted;
use core\event\user_tenant_membership_changed;
use hierarchy_organisation\event\organisation_deleted;
use hierarchy_position\event\position_deleted;
use mod_perform\event\activity_created;
use mod_perform\event\participant_instance_progress_updated;
use mod_perform\event\participant_section_progress_updated;
use mod_perform\event\subject_instance_activated;
use mod_perform\event\subject_instance_progress_updated;
use mod_perform\observers\notification;
use mod_perform\observers\participant_instance_availability;
use mod_perform\observers\participant_instance_progress;
use mod_perform\observers\participant_instance_sync;
use mod_perform\observers\participant_section_availability;
use mod_perform\observers\participant_section_progress;
use mod_perform\observers\subject_instance_availability;
use mod_perform\observers\subject_instance_manual_status;
use mod_perform\observers\subject_static_instance_hierarchy;
use mod_perform\observers\tenant;
use mod_perform\observers\tenant_membership_changed;
use mod_perform\observers\totara_notification;
use mod_perform\observers\track_assignment_user_groups;
use mod_perform\observers\user;
use totara_cohort\event\members_updated;
use totara_core\event\user_suspended;
use totara_job\event\job_assignment_created;
use totara_job\event\job_assignment_deleted;
use totara_job\event\job_assignment_updated;

defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => user_deleted::class,
        'callback' => user::class.'::user_deleted',
    ],
    [
        'eventname' => user_suspended::class,
        'callback' => user::class.'::user_suspended',
    ],
    [
        'eventname' => members_updated::class,
        'callback' => track_assignment_user_groups::class.'::cohort_updated',
    ],
    [
        'eventname' => cohort_member_added::class,
        'callback' => track_assignment_user_groups::class.'::cohort_updated',
    ],
    [
        'eventname' => cohort_member_removed::class,
        'callback' => track_assignment_user_groups::class.'::cohort_updated',
    ],
    [
        'eventname' => cohort_deleted::class,
        'callback' => track_assignment_user_groups::class.'::cohort_deleted',
    ],
    [
        'eventname' => participant_section_progress_updated::class,
        'callback' => [participant_section_availability::class, 'close_completed_section_availability'],
    ],
    [
        'eventname' => participant_section_progress_updated::class,
        'callback' => participant_section_progress::class.'::progress_updated',
    ],
    [
        'eventname' => participant_instance_progress_updated::class,
        'callback' => participant_instance_progress::class.'::progress_updated',
    ],
    [
        'eventname' => participant_instance_progress_updated::class,
        'callback' => participant_instance_availability::class.'::close_completed_participant_instance',
    ],
    [
        'eventname' => subject_instance_progress_updated::class,
        'callback' => subject_instance_availability::class.'::close_completed_subject_instance',
    ],
    [
        'eventname' => participant_instance_progress_updated::class,
        'callback' => totara_notification::class.'::participant_section_submitted',
    ],
    [
        'eventname' => subject_instance_activated::class,
        'callback' => subject_instance_manual_status::class.'::subject_instance_activated',
    ],
    [
        'eventname' => organisation_deleted::class,
        'callback' => track_assignment_user_groups::class.'::organisation_deleted',
    ],
    [
        'eventname' => position_deleted::class,
        'callback' => track_assignment_user_groups::class.'::position_deleted',
    ],
    [
        'eventname' => job_assignment_created::class,
        'callback' => track_assignment_user_groups::class.'::job_assignment_updated',
    ],
    [
        'eventname' => job_assignment_created::class,
        'callback' => participant_instance_sync::class.'::job_assignment_created',
    ],
    [
        'eventname' => job_assignment_updated::class,
        'callback' => track_assignment_user_groups::class.'::job_assignment_updated',
    ],
    [
        'eventname' => job_assignment_updated::class,
        'callback' => participant_instance_sync::class.'::job_assignment_updated',
    ],
    [
        'eventname' => job_assignment_deleted::class,
        'callback' => track_assignment_user_groups::class.'::job_assignment_updated',
    ],
    [
        'eventname' => job_assignment_deleted::class,
        'callback' => participant_instance_sync::class.'::job_assignment_deleted',
    ],
    [
        'eventname' => activity_created::class,
        'callback' => notification::class.'::create_notifications',
    ],
    [
        'eventname' => subject_instance_progress_updated::class,
        'callback' => notification::class . '::send_completion_notification',
    ],
    [
        'eventname' => tenant_deleted::class,
        'callback' => tenant::class.'::deleted',
    ],
    [
        'eventname' => user_tenant_membership_changed::class,
        'callback' => tenant_membership_changed::class.'::updated',
    ],
    [
        'eventname' => position_deleted::class,
        'callback' => subject_static_instance_hierarchy::class.'::position_deleted',
    ],
    [
        'eventname' => job_assignment_deleted::class,
        'callback' => subject_static_instance_hierarchy::class.'::job_assignment_deleted',
    ],
    [
        'eventname' => organisation_deleted::class,
        'callback' => subject_static_instance_hierarchy::class.'::organisation_deleted',
    ],
];

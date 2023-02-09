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

namespace mod_perform\models\activity\helpers;

use cm_info;
use context_course;
use context_module;
use mod_perform\activity_access_denied_exception;
use mod_perform\models\activity\activity;
use stdClass;

/**
 * This helper contains some common access checks for an activity
 */
class access_checks {

    /**
     * @var stdClass
     */
    private $course;

    /**
     * @var cm_info
     */
    private $cm;

    /**
     * @param stdClass $course
     * @param cm_info $cm
     */
    protected function __construct(stdClass $course, cm_info $cm) {
        $this->course = $course;
        $this->cm = $cm;
    }

    /**
     * Returns a new instance of this helper given an activity model
     *
     * @param activity $activity
     * @return access_checks
     */
    public static function for_activity_model(activity $activity) {
        [$course, $cm] = get_course_and_cm_from_instance($activity->id, 'perform');

        return new self($course, $cm);
    }

    /**
     * Returns a new instance of this helper given a course module context
     *
     * @param context_module $module
     * @return static
     */
    public static function for_module_context(context_module $module): self {
        [$course, $cm] = get_course_and_cm_from_cmid($module->instanceid, 'perform');
        return new self($course, $cm);
    }

    /**
     * Process some core checks for activities, throws exceptions if it fails
     *
     * @throws activity_access_denied_exception
     */
    public function check(): void {
        // Deal with multi tenancy
        $course_context = context_course::instance($this->course->id, MUST_EXIST);
        if ($course_context->is_user_access_prevented()) {
            throw new activity_access_denied_exception('Cannot access activity');
        }

        // Deal with hidden activity
        if (!totara_course_is_viewable($this->course->id)) {
            throw new activity_access_denied_exception('Activity is hidden');
        }
    }

    /**
     * Returns the course instance
     *
     * @return stdClass|null
     */
    public function get_course(): ?stdClass {
        return $this->course;
    }

    /**
     * Returns the course module info instance
     *
     * @return cm_info|null
     */
    public function get_cm(): ?cm_info {
        return $this->cm;
    }

}
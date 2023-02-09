<?php
/**
 * This file is part of Totara Core
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package container_course
 */
namespace container_course;

use coding_exception;
use container_course\interactor\course_interactor;
use external_api;
use external_function_parameters;
use external_value;

global $CFG;
require_once($CFG->libdir . '/externallib.php');

/**
 * This is the external API for container_course.
 */
class external extends external_api {

    /**
     * Parameter definitions of search_catalog.
     *
     * @return external_function_parameters
     */
    public static function process_non_interactive_enrol_parameters(): external_function_parameters {
        return new external_function_parameters([
            'course_id' => new external_value(PARAM_INT, 'current course id', )
        ]);
    }

    /**
     * @param int $course_id
     * @return bool
     */
    public static function process_non_interactive_enrol(int $course_id): bool {
        $interactor = course_interactor::from_course_id($course_id);
        if ($interactor->is_enrolled()) {
            throw new coding_exception('You have already enrolled');
        }

        if ($interactor->is_site_guest()) {
            throw new coding_exception('Not support to site guest');
        }

        if (!$interactor->supports_non_interactive_enrol()) {
            throw new coding_exception('Not support non interactive enrol');
        }

        $non_interactive_enrolment = new non_interactive_enrolment($interactor);
        $non_interactive_enrolment->do_non_interactive_enrol(true);

        return true;
    }

    /**
     * @return null
     */
    public static function process_non_interactive_enrol_returns() {
        return null;
    }
}
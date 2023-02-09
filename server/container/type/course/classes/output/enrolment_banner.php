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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package container_course
 */
namespace container_course\output;

use coding_exception;
use container_course\course;
use container_course\interactor\course_interactor;
use container_course\non_interactive_enrolment;
use core\output\template;

class enrolment_banner extends template {
    /**
     * @param course   $course
     * @param int|null $user_id
     *
     * @return enrolment_banner
     */
    public static function create_from_course(course $course, ?int $user_id = null): enrolment_banner {
        $interactor = new course_interactor($course, $user_id);

        // Just a basic validator, which it should never happen.
        if ($interactor->is_enrolled()) {
            throw new coding_exception(
                "Cannot create an enrolment banner for the user who is already enrolled into the given course"
            );
        }

        $non_interactive_enrolment = new non_interactive_enrolment($interactor);

        return new static([
            "message" => $non_interactive_enrolment->get_message(),
            "enrol_button" => [
                "display" => $interactor->non_interactive_enrol_instance_enabled() && !$interactor->is_site_guest(),
                "url" => $non_interactive_enrolment->get_enrol_url()->out(),
                "course_id" => $interactor->get_course_id(),
            ]
        ]);
    }
}
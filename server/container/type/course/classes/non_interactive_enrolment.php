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
use moodle_url;

class non_interactive_enrolment {
    /**
     * @var int
     */
    protected $course_id;

    /**
     * @var int
     */
    protected $user_id;

    /**
     * @var course_interactor
     */
    protected $course_interactor;

    /**
     * non_interactive_enrolment constructor.
     * @param int $course_id
     * @param int $user_id
     */
    public function __construct(course_interactor $course_interactor) {
        $this->user_id = $course_interactor->get_actor_id();
        $this->course_id = $course_interactor->get_course_id();
        $this->course_interactor = $course_interactor;
    }

    /**
     * @param int $course_id
     * @param int $user_id
     * @return array
     */
    public static function get_non_interactive_enrols(int $course_id, int $user_id): array {
        $instances = enrol_get_instances($course_id, true);

        $array = [];
        foreach($instances as $instance) {
            if ($plugin = enrol_get_plugin($instance->enrol)) {
                $result = $plugin->supports_non_interactive_enrol($instance, $user_id);
                if (!is_null($result)) {
                    $array[] = $result;
                }
            }
        }

        return $array;
    }

    /**
     * @param int $course_id
     * @param int $user_id
     */
    public function do_non_interactive_enrol(bool $with_notificaton = false): void {
        $instances = enrol_get_instances($this->course_id, true);

        $result = false;
        foreach($instances as $instance) {
            if ($plugin = enrol_get_plugin($instance->enrol)) {
                $result = $plugin->do_non_interactive_enrol($instance, $this->user_id, $with_notificaton);
                if ($result) {
                    break;
                }
            }
        }

        // If no enrol plugin supports non interactive enrol, we throw exception.
        if (!$result) {
            throw new coding_exception('No enrol plugin supports non interactive enrol');
        }
    }

    /**
     * Computing enrol url.
     *
     * @return moodle_url
     */
    public function get_enrol_url(): moodle_url {
        if ($this->course_interactor->supports_non_interactive_enrol()) {
            return new moodle_url("/course/view.php", ["id" => $this->course_interactor->get_course_id()]);
        }

        // Default to enrol page.
        return new moodle_url("/enrol/index.php", ["id" => $this->course_interactor->get_course_id()]);
    }

    /**
     * Computing display message.
     *
     * @return string
     */
    public function get_message(): string {
        $component = "container_course";

        if ($this->course_interactor->has_view_capability()) {
            // For user, whoever has a capability moodle/course:view, then it can be defined as that user
            // is an admin user within the course context. The name isn't ideal, but the capability
            // is created to serve admin role.
            if ($this->course_interactor->non_interactive_enrol_instance_enabled()) {
                // Yes, there is a link
                return get_string(
                    "view_course_as_admin_with_enrol_options",
                    $component
                );
            }

            return get_string("view_course_as_admin", $component);
        }

        if ($this->course_interactor->is_guest()) {
            // Current user is a guess.
            if ($this->course_interactor->non_interactive_enrol_instance_enabled() &&
                !$this->course_interactor->is_site_guest()
            ) {
                return get_string(
                    "view_course_as_guest_with_enrol_options",
                    $component
                );
            }

            return get_string("view_course_as_guest", $component);
        }

        throw new coding_exception("Cannot compute the banner's message");
    }

}
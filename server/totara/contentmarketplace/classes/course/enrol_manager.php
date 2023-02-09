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
 * @package totara_contentmarketplace
 */
namespace totara_contentmarketplace\course;

use coding_exception;
use container_course\course;

class enrol_manager {
    /**
     * @var course
     */
    private $course;

    /**
     * enrol_manager constructor.
     * @param course $course
     */
    public function __construct(course $course) {
        global $CFG;
        require_once("{$CFG->dirroot}/lib/enrollib.php");

        $this->course = $course;
    }

    /**
     * @param int $user_id
     * @return void
     */
    public function enrol_course_creator(int $user_id): void {
        global $CFG;
        $context = $this->course->get_context();

        if (isguestuser($user_id)) {
            debugging(
                "Cannot enrol the guest user as the course creator to the course",
                DEBUG_DEVELOPER
            );

            return;
        }

        $is_viewing = is_viewing($context, $user_id, 'moodle/role:assign');
        $is_enrolled = is_enrolled($context, $user_id, 'moodle/role:assign');

        if (!empty($CFG->creatornewroleid) && !$is_viewing && !$is_enrolled) {
            enrol_try_internal_enrol(
                $this->course->get_id(),
                $user_id,
                $CFG->creatornewroleid
            );
        }
    }

    /**
     * @param int $user_id
     */
    public function do_non_interactive_enrol(int $user_id): void {
        $instances = enrol_get_instances($this->course->id, true);

        $result = false;
        foreach($instances as $instance) {
            if ($plugin = enrol_get_plugin($instance->enrol)) {
                $result = $plugin->do_non_interactive_enrol($instance, $user_id);
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
}
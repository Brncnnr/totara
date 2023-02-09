<?php
/*
 * This file is part of Totara Learn
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
 * @author David Curry <david.curry@totaralearning.com>
 * @package core
 */

namespace core\webapi\resolver\query;

use core\webapi\query_resolver;
use core\webapi\execution_context;
use core\webapi\middleware\require_login;


class enrol_course_info extends query_resolver {

    /**
     * Retrieve course enrolment information, user specific fields will be in the type resolver.
     */
    public static function resolve(array $args, execution_context $ec) {
        global $DB;

        if (empty($args['courseid'])) {
            throw new \coding_exception('Missing courseid argument for enrol_course_info query');
        } else {
            // Fetch the course to make sure it's not a container type.
            $course = $DB->get_record('course', ['id' => $args['courseid']]);

            if (empty($course) || $course->containertype !== 'container_course') {
                throw new \coding_exception('Invalid courseid argument for enrol_course_info query');
            }
        }

        $context = \context_course::instance($course->id, MUST_EXIST);
        $ec->set_relevant_context($context);

        // Set up some basic return data.
        $data = [
            'course' => $course,
            'instances' => [],
            'canenrol' => false,
            'guestaccess' => false,
        ];

        $supported = ['guest', 'self']; // Limit response to guest and self enrolments.
        $instances = enrol_get_instances($course->id, true); // Excludes disabled instances.
        foreach ($instances as $instance) {
            if (!in_array($instance->enrol, $supported)) {
                continue;
            } else if ($instance->enrol == 'guest' && empty($data['guestaccess'])) {
                $data['guestaccess'] = true;
            } else if ($instance->enrol == 'self' && empty($data['canenrol'])) {
                $data['canenrol'] = true;
            }

            $data['instances'][$instance->sortorder] = $instance;
        }

        return $data;
    }

    public static function get_middleware(): array {
        return [
            require_login::class
        ];
    }
}

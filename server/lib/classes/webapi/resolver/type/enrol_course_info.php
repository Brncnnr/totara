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

namespace core\webapi\resolver\type;

use core\webapi\execution_context;
use core\webapi\type_resolver;

class enrol_course_info extends type_resolver {

    public static function resolve(string $field, $data, array $args, execution_context $ec) {
        global $USER;

        if (!is_array($data) || empty($data['course'])) {
            throw new \coding_exception('Invalid data handed to enrol_course_info type resolver');
        }

        $course = $data['course'];
        $context = $ec->get_relevant_context();
        switch ($field) {
            case 'is_complete':
                // should be auto included completion/completion_completion.php
                $params = ['userid' => $USER->id, 'course' => $course->id];
                $ccomp = new \completion_completion($params);
                return $ccomp->is_complete();
            case 'is_enrolled':
                // should be auto included lib/enrollib.php
                return is_enrolled($context, $USER, '', true);
            case 'guest_access':
                return $data['guestaccess'] ?? false;
            case 'can_enrol':
                return $data['canenrol'] ?? false;
            case 'can_view':
                return has_capability('moodle/course:update', $context);
            case 'enrolment_options':
                return $data['instances'] ?? null;
            default:
                throw new \coding_exception('Unrecognised field requested for enrol_course_info type: ' . $field);
        }
    }
}

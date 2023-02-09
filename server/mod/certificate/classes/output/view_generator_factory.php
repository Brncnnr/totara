<?php
/**
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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package mod_certificate
 */

namespace mod_certificate\output;

use coding_exception;
use stdClass;

final class view_generator_factory {

    /**
     * Create a certificate generator instance for a specific certificate type.
     *
     * @param stdClass $certificate
     * @param stdClass $certificate_record
     * @param stdClass $course
     * @param stdClass $course_module
     *
     * @return view_generator
     */
    public static function create(
        stdClass $certificate,
        stdClass $certificate_record,
        stdClass $course,
        stdClass $course_module
    ): view_generator {
        if (!self::exists($certificate->certificatetype)) {
            throw new coding_exception('Invalid certificate type');
        }

        $class = "{$certificate->certificatetype}_view_generator";
        return new $class($certificate, $certificate_record, $course, $course_module);
    }

    /**
     * @param string $certificate_type
     *
     * @return bool
     */
    public static function exists(string $certificate_type): bool {
        global $CFG;

        // Check if file exists.
        $type = "{$CFG->dirroot}/mod/certificate/type/{$certificate_type}/view_generator.php";
        if (!file_exists($type)) {
            return false;
        }

        // Check if class exists.
        require_once($type);
        $class = "{$certificate_type}_view_generator";
        return class_exists($class);
    }

}
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
 * @author Brian Barnes <brian.barnes@totaralearning.com>
 * @package mod_certificate
 */

defined('MOODLE_INTERNAL') || die();

use mod_certificate\output\view_generator;

class letter_non_embedded_view_generator extends view_generator {

    /**
     * Defines the A4_non_embedded certificate
     *
     * @param stdClass $certificate The certificate module object
     * @param stdClass $certificate_record The individual certificate record for the user
     * @param stdClass $course The course that this certificate is being issued from
     * @param stdClass $course_module The course module object
     */
    public function __construct(
        stdClass $certificate,
        stdClass $certificate_record,
        stdClass $course,
        stdClass $course_module
    ) {
        parent::__construct($certificate, $certificate_record, $course, $course_module);

        if ($certificate->orientation == 'L') {
            $this->template_name = 'html_view_landscape';
        } else {
            $this->template_name = 'html_view_portrait';
        }

        $this->teacher_y_offset = 12;
    }

    /**
     * @inheritDoc
     */
    public function get_type(): string {
        return 'letter_non_embedded';
    }

}
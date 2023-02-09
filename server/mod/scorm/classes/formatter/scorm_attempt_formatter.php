<?php
/**
 *
 * This file is part of Totara LMS
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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_scorm
 */

namespace mod_scorm\formatter;

use core\webapi\formatter\formatter;
use core\webapi\formatter\field\date_field_formatter;

class scorm_attempt_formatter extends formatter {

    protected function get_map(): array {
        return [
            'attempt' => null,
            'timestarted' => date_field_formatter::class,
            'gradereported' => null,
            'defaults' => null,
            'objectives' => null,
            'interactions' => null,
        ];
    }
}

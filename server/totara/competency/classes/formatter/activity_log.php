<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency\formatter;

use core\webapi\formatter\field\date_field_formatter;
use core\webapi\formatter\field\string_field_formatter;
use core\webapi\formatter\formatter;

class activity_log extends formatter {
    protected function get_map(): array {
        return [
            'timestamp' => date_field_formatter::class,
            'description' => string_field_formatter::class,
            'comment' => string_field_formatter::class,
            'proficient_status' => null,
            'assignment' => null,
            'assignment_action' => null,
            'type' => null,
        ];
    }

    protected function get_field(string $field) {
        return $this->object->get_field($field);
    }

    protected function has_field(string $field): bool {
        return $this->object->has_field($field);
    }
}
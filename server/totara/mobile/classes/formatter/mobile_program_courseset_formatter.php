<?php
/**
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
 * @author David Curry <david.curry@totaralearning.com>
 * @package totara_mobile
 */

namespace totara_mobile\formatter;

use core\webapi\formatter\field\string_field_formatter;
use core\webapi\formatter\field\date_field_formatter;
use core\webapi\formatter\field\text_field_formatter;
use core\webapi\formatter\formatter;

class mobile_program_courseset_formatter extends formatter {

    protected function get_map(): array {
        return [
            'id' => null,
            'label' => string_field_formatter::class,
            'courses' => null, // Default - mobile\learning_item::class
            'criteria' => string_field_formatter::class,
            'nextsetoperator' => string_field_formatter::class,
            'statuskey' => string_field_formatter::class
        ];
    }
}

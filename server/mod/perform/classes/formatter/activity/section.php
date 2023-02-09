<?php
/*
 * This file is part of Totara Learn
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\formatter\activity;

use core\orm\formatter\entity_model_formatter;
use core\webapi\formatter\field\date_field_formatter;
use core\webapi\formatter\field\string_field_formatter;

/**
 * Class section
 *
 * @package mod_perform\formatter\activity
 */
class section extends entity_model_formatter {

    protected function get_map(): array {
        return [
            'id' => null,
            'title' => null,
            'display_title' => string_field_formatter::class,
            'activity' => null,
            'sort_order' => null,
            'section_elements' => null,
            'respondable_section_elements' => null,
            'aggregatable_section_elements' => null,
            'section_relationships' => null,
            'section_elements_summary' => null,
            'created_at' => date_field_formatter::class,
            'updated_at' => date_field_formatter::class,
        ];
    }
}
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
use core\webapi\formatter\field\string_field_formatter;

/**
 * Class element
 *
 * @package mod_perform\formatter\activity
 *
 * @property \mod_perform\models\activity\element $object
 */
class element extends entity_model_formatter {

    protected function get_map(): array {
        return [
            'id' => null,
            'element_plugin' => null,
            'title' => string_field_formatter::class,
            'identifier' => string_field_formatter::class,
            'data' => element_data_field_formatter::for_model($this->object),
            'is_required' => null,
            'is_respondable' => null,
            'displays_responses' => null,
            'children' => null,
            'parent_element' => null,
            'sort_order' => null,
        ];
    }
}

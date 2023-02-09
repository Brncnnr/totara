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
 * @author David Curry <david.curry@totaralearning.com>
 * @package mobile_currentlearning
 */

namespace mobile_currentlearning\formatter;

use totara_core\formatter\learning_item_formatter;
use core\webapi\formatter\field\date_field_formatter;
use core\webapi\formatter\field\string_field_formatter;

/**
 * Formatter for program content as learning items
 *
 * @property-read item|item_base $object
 */
class item_formatter extends learning_item_formatter {

    protected function get_map(): array {
        $map = [
            'id' => null,
            'itemtype' => null,
            'itemcomponent' => null,
            'fullname' => 'item_fullname_formatter',
            'shortname' => 'item_shortname_formatter',
            'progress' => null,
            'idnumber' => null,
            'duedate' => date_field_formatter::class,
            'duedate_state' => string_field_formatter::class,
            'description' => 'item_description_formatter',
            'description_format' => null,
            'url_view' => null,
            'image_src' => null,
            'mobile_coursecompat' => null,
            'mobile_image' => null,
        ];

        return $map;
    }
}

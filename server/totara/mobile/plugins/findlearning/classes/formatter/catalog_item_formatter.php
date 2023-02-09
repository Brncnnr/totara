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
 * @package mobile_findlearning
 */

namespace mobile_findlearning\formatter;

use core\webapi\formatter\formatter;
use core\webapi\formatter\field\string_field_formatter;
use core_course\formatter\course_formatter;

/**
 * Formatter for catalog items
 *
 * @property-read item|item_base $object
 */
class catalog_item_formatter extends formatter {

    protected function get_map(): array {
        $map = [
            'id' => null, // Core_id.
            'itemid' => null, // Core_id.
            'title' => string_field_formatter::class,
            'item_type' => string_field_formatter::class,
            'summary' => 'item_summary_formatter',
            'summary_format' => null, // Enum string.
            'image_enabled' => null, // Boolean.
            'image_alt' => string_field_formatter::class,
            'image_url' => null, // URL string.
            'view_url' => null, // URL string.
            'description_enabled' => null, // Boolean.
            'description' => string_field_formatter::class,
        ];

        return $map;
    }

    public function item_summary_formatter($value, $format) {
        return self::item_inherited_field_formatter('summary', $value, $format);
    }

    /**
     * Calls the formatter for the specific itemtype on the given field
     *
     * @param $field
     * @param $value
     * @param $format
     * @return
     */
    private function item_inherited_field_formatter($field, $value, $format) {
        $item = (object)[
            'id' => $this->object->objectid,
            $field => $value
        ];

        // For summary we need extra information
        if ($field == 'summary') {
            $item->summaryformat = $this->object->summaryformat ?? FORMAT_PLAIN;
        }

        switch ($this->object->objecttype) {
            case 'engage_article':
            case 'playlist':
                return null;
            case 'course':
                $itemformatter = new course_formatter($item, $this->context);
                break;
            default:
                throw new \coding_exception('Unrecognised catalog item type, please add to the catalog item formatter.');
        }

        return $itemformatter->format($field, $format);
    }

}

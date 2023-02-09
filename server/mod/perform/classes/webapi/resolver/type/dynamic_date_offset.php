<?php
/**
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

namespace mod_perform\webapi\resolver\type;

use core\format;
use core\webapi\execution_context;
use core\webapi\type_resolver;
use mod_perform\dates\date_offset;
use mod_perform\formatter\activity\dynamic_date_offset as dynamic_date_offset_formatter;

defined('MOODLE_INTERNAL') || die();

/**
 * Maps the date_offset class into the GraphQL mod_perform_dynamic_date_offset type.
 */
class dynamic_date_offset extends type_resolver {

    /**
     * {@inheritdoc}
     */
    public static function resolve(string $field, $option, array $args, execution_context $ec) {
        if (!$option instanceof date_offset) {
            throw new \coding_exception(__METHOD__ . ' requires a date_offset object');
        }

        $format = $args['format'] ?? format::FORMAT_PLAIN;
        $context = $ec->get_relevant_context();
        $formatter = new dynamic_date_offset_formatter($option, $context);

        return $formatter->format($field, $format);
    }
}
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\webapi\resolver\type;

use context_system;
use core\date_format;
use core\format;

use core\webapi\execution_context;
use core\webapi\type_resolver;

use mod_perform\models\due_date as due_date_model;
use mod_perform\formatter\due_date as due_date_formatter;

defined('MOODLE_INTERNAL') || die();

/**
 * Maps the due_date class into a GraphQL mod_perform_due_date type.
 */
class due_date extends type_resolver {
    /**
     * Default formats.
     */
    private const DEF_FORMATS = [
        'due_date' => date_format::FORMAT_DATETIME,
        'units_to_due_date_type' => format::FORMAT_PLAIN
    ];

    /**
     * {@inheritdoc}
     */
    public static function resolve(string $field, $source, array $args, execution_context $ec) {
        if (!$source instanceof due_date_model) {
            throw new \coding_exception(__METHOD__ . ' requires due_date class');
        }

        $format = $args['format'] ?? self::DEF_FORMATS[$field] ?? null;
        $context = context_system::instance();
        $formatter = new due_date_formatter($source, $context);

        return $formatter->format($field, $format);
    }
}
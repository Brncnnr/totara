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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package core_cohort
 */

namespace core\webapi\resolver\type;

use coding_exception;
use context;
use context_system;
use core\format;
use core\entity\cohort as cohort_entity;
use core\webapi\execution_context;
use core\webapi\type_resolver;
use core\formatter\cohort as cohort_formatter;

defined('MOODLE_INTERNAL') || die();

/**
 * Maps a cohort entity into the GraphQL core_cohort type.
 */
class cohort extends type_resolver {
    /**
     * @var array default formatting for core_cohort type string fields.
     */
    private const DEFAULT_FORMATS = [
        'description' => format::FORMAT_HTML,
        'idnumber' => format::FORMAT_PLAIN,
        'name' => format::FORMAT_PLAIN
    ];

    /**
     * {@inheritdoc}
     */
    public static function resolve(string $field, $source, array $args, execution_context $ec) {
        if (!$source instanceof cohort_entity) {
            throw new coding_exception(__METHOD__ . ' requires a cohort_entity object');
        }

        $format = $args['format'] ?? self::DEFAULT_FORMATS[$field] ?? null;
        $context = $ec->has_relevant_context()
            ? $ec->get_relevant_context()
            : context_system::instance();

        if (!self::authorize($field, $format, $context)) {
            return null;
        }

        $formatter = new cohort_formatter($source, $context);

        return $formatter->format($field, $format);
    }

    /**
     * Validates that the user is allowed to see fields in the specified format.
     *
     * @param string $field field that is being accessed.
     * @param string $format output format; one of the format::FORMAT_XYZ constants.
     * @param context $context working context.
     *
     * @return bool True if the user can see the formatted field.
     */
    private static function authorize(string $field, ?string $format, context $context): bool {
        $checked_string_fields = [
            'name',
            'description'
        ];

        if ($format === format::FORMAT_RAW
            && in_array($field, $checked_string_fields)) {
            return has_capability("moodle/cohort:view", $context);
        }

        return true;
    }
}
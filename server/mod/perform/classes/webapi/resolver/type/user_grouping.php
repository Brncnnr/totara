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
 * @package mod_perform
 */

namespace mod_perform\webapi\resolver\type;

use core\format;

use core\webapi\execution_context;
use core\webapi\type_resolver;

use mod_perform\user_groups\formatter\grouping as grouping_formatter;

use mod_perform\user_groups\grouping;


defined('MOODLE_INTERNAL') || die();

/**
 * Maps the grouping class into the GraphQL mod_perform_user_grouping_cohort type.
 */
class user_grouping extends type_resolver {
    /**
     * Default formats.
     */
    private const DEF_FORMATS = [
        'type' => format::FORMAT_PLAIN,
        'type_label' => format::FORMAT_PLAIN,
        'name' => format::FORMAT_PLAIN,
        'extra' => format::FORMAT_PLAIN
    ];

    /**
     * {@inheritdoc}
     */
    public static function resolve(string $field, $source, array $args, execution_context $ec) {
        if (!$source instanceof grouping) {
            throw new \coding_exception(__METHOD__ . ' requires a grouping object');
        }

        $format = $args['format'] ?? self::DEF_FORMATS[$field] ?? null;

        $context = $ec->has_relevant_context()
            ? $ec->get_relevant_context() : \context_system::instance();

        $formatter = new grouping_formatter($source, $context);

        return $formatter->format($field, $format);
    }
}
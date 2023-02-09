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
 * @package totara_hierarchy
 */

namespace totara_hierarchy\webapi\resolver\type;

use coding_exception;
use context_system;

use core\format;
use core\webapi\type_resolver;
use core\webapi\execution_context;

use hierarchy_goal\entity\scale_value as scale_value_entity;
use hierarchy_goal\formatter\scale_value as scale_value_formatter;

/**
 * Populates a GraphQL totara_hierarchy_goal_scale_value type.
 */
class goal_scale_value extends type_resolver {
    /**
     * Default formats.
     */
    private const DEFAULT_FORMATS = [
        'name' => format::FORMAT_PLAIN,
        'description' => format::FORMAT_PLAIN
    ];

    /**
     * {@inheritdoc}
     */
    public static function resolve(string $field, $source, array $args, execution_context $ec) {
        if (!$source instanceof scale_value_entity) {
            throw new coding_exception(__METHOD__ . ' requires a scale_value entity');
        }

        $format = $args['format'] ?? self::DEFAULT_FORMATS[$field] ?? null;
        $context = $ec->has_relevant_context()
            ? $ec->get_relevant_context()
            : context_system::instance();
        $formatter = new scale_value_formatter($source, $context);

        return $formatter->format($field, $format);
    }
}

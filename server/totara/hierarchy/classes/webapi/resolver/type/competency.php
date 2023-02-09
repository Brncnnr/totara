<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Ning Zhou <ning.zhou@totaralearning.com>
 * @package totara_hierarchy
 */

namespace totara_hierarchy\webapi\resolver\type;

use context_system;
use core\format;
use core\webapi\execution_context;
use core\webapi\type_resolver;
use hierarchy_competency\formatter\competency as competency_formatter;
use totara_hierarchy\entity\competency as competency_entity;

/**
 * Populates a GraphQL totara_hierarchy_competency type.
 */
class competency extends type_resolver {

    private const DEFAULT_FORMATS = [
        'id' => format::FORMAT_PLAIN,
        'name' => format::FORMAT_PLAIN,
    ];

    /**
     * {@inheritdoc}
     */
    public static function resolve(string $field, $source, array $args, execution_context $ec) {
        if (!$source instanceof competency_entity) {
            throw new \coding_exception(__METHOD__ . ' requires a competency entity');
        }

        $format = $args['format'] ?? self::DEFAULT_FORMATS[$field] ?? null;
        $context = $ec->has_relevant_context()
            ? $ec->get_relevant_context()
            : context_system::instance();
        $formatter = new competency_formatter($source, $context);

        return $formatter->format($field, $format);
    }
}
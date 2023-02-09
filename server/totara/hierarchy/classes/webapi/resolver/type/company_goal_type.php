<?php
/*
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package totara_hierarchy
 */

namespace totara_hierarchy\webapi\resolver\type;

use coding_exception;
use context_system;
use core\format;
use core\webapi\execution_context;
use core\webapi\type_resolver;
use hierarchy_goal\entity\company_goal_type as company_goal_type_entity;
use totara_hierarchy\formatter\company_goal_type as company_goal_type_formatter;
use stdClass;

/**
 * company_goal type type
 *
 * Note: It is the responsibility of the query to ensure the user is permitted to see an company_goal type
 */
class company_goal_type extends type_resolver {

    /**
     * Default formats.
     */
    private const DEFAULT_FORMATS = [
        'shortname' => format::FORMAT_PLAIN,
        'fullname' => format::FORMAT_PLAIN,
    ];

    /**
     * Resolves fields for a company_goal type
     *
     * @param string $field
     * @param stdClass $source
     * @param array $args
     * @param execution_context $ec
     * @return mixed
     * @throws coding_exception If the goal type is not a DB record, or if the requested field does not exist.
     */
    public static function resolve(string $field, $source, array $args, execution_context $ec) {

        if (!$source instanceof company_goal_type_entity) {
            throw new coding_exception(
                'Only company goal type entities are accepted '
                . gettype($source)
            );
        }

        // The description field is part of the GraphQL hierarchy type interface, but is not yet exposed
        // through any persisted queries, so we always return null. Implement when needed.
        if ($field === 'description') {
            return null;
        }

        $format = $args['format'] ?? self::DEFAULT_FORMATS[$field] ?? null;
        $context = $ec->has_relevant_context()
            ? $ec->get_relevant_context()
            : context_system::instance();
        $formatter = new company_goal_type_formatter($source, $context);

        return $formatter->format($field, $format);
    }
}

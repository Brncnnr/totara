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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package hierarchy_goal
 */

namespace hierarchy_goal\webapi\resolver\type;

use context_system;
use core\webapi\execution_context;
use core\webapi\type_resolver;
use hierarchy_goal\formatter\perform_status as perform_status_formatter;
use hierarchy_goal\models\perform_status as perform_status_model;

/**
 * Perform status type resolver
 */
class perform_status extends type_resolver {

    /**
     * Resolves fields for a perform status
     *
     * @param string $field
     * @param perform_status_model $value
     * @param array $args
     * @param execution_context $ec
     * @return mixed
     */
    public static function resolve(string $field, $value, array $args, execution_context $ec) {
        if (!$value instanceof perform_status_model) {
            throw new \coding_exception('Please pass a perform status model');
        }

        $format = $args['format'] ?? null;

        $formatter = new perform_status_formatter($value, context_system::instance());
        return $formatter->format($field, $format);
    }
}

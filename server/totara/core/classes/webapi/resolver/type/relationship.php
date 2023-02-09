<?php
/*
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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_core
 */

namespace totara_core\webapi\resolver\type;

use coding_exception;
use context_system;
use core\webapi\execution_context;
use core\webapi\type_resolver;
use totara_core\formatter\relationship_formatter;
use totara_core\relationship\relationship as relationship_model;

class relationship extends type_resolver {

    /**
     * Resolve relationship fields
     *
     * @param string $field
     * @param relationship_model $relationship
     * @param array $args
     * @param execution_context $ec
     * @return mixed
     */
    public static function resolve(string $field, $relationship, array $args, execution_context $ec) {
        if (!$relationship instanceof relationship_model) {
             throw new coding_exception('Only instances of ' . relationship_model::class . ' are accepted');
        }

        $formatter = new relationship_formatter($relationship, context_system::instance());
        return $formatter->format($field, $args['format'] ?? null);
    }

}

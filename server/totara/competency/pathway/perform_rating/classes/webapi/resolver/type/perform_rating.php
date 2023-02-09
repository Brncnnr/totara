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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com
 * @package pathway_perform_rating
 */

namespace pathway_perform_rating\webapi\resolver\type;

use context_system;
use core\webapi\execution_context;
use core\webapi\type_resolver;
use pathway_perform_rating\formatter\perform_rating as perform_rating_formatter;
use pathway_perform_rating\models\perform_rating as perform_rating_model;

/**
 * Perform rating type resolver
 *
 * Note: It is the responsibility of the query to ensure the user is permitted to see an organisation.
 */
class perform_rating extends type_resolver {

    /**
     * Resolves fields for a perform rating
     *
     * @param string $field
     * @param perform_rating_model $value
     * @param array $args
     * @param execution_context $ec
     * @return mixed
     */
    public static function resolve(string $field, $value, array $args, execution_context $ec) {
        if (!$value instanceof perform_rating_model) {
            throw new \coding_exception('Please pass a perform rating model');
        }

        $format = $args['format'] ?? null;

        $formatter = new perform_rating_formatter($value, context_system::instance());
        return $formatter->format($field, $format);
    }

}

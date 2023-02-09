<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency\webapi\resolver\type;

use core\webapi\execution_context;
use core\webapi\type_resolver;

/**
 * Overall aggregation type for aggregating achieved competency ratings
 *
 * Please be aware that it is the responsibility of the query to ensure that the user is allowed to
 * see this.
 */
class overall_aggregation extends type_resolver {

    /**
     * Resolves a overall achievement aggregation field.
     *
     * @param string $field
     * @param pathway $pathway
     * @param array $args
     * @param execution_context $ec
     * @return mixed
     */
    public static function resolve(string $field, $aggregation, array $args, execution_context $ec) {

        if (!$aggregation instanceof \totara_competency\overall_aggregation) {
            throw new \coding_exception('Only \totara_competency\overall_aggregation objects are accepted: ' . gettype($aggregation));
        }

        // Using string_field_formatter as these values are not retrieved from user input
        switch ($field) {
            case 'aggregation_type':
                return $aggregation->get_agg_type();
            case 'description':
                return $aggregation->get_description();
            case 'title':
                return $aggregation->get_title();
        }

        throw new \coding_exception('Unknown field', $field);
    }
}

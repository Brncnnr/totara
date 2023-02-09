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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package ml_recommender
 */
namespace ml_recommender\webapi\resolver\type;

use core\webapi\execution_context;
use core\webapi\type_resolver;
use core\pagination\base_cursor_paginator;

/**
 * Resolver for the recommender cursor
 *
 * @package ml_recommender\webapi\resolver\type
 */
class cursor extends type_resolver {
    /**
     * @param string $field
     * @param base_cursor_paginator $source
     * @param array $args
     * @param execution_context $ec
     * @return mixed
     */
    public static function resolve(string $field, $source, array $args, execution_context $ec) {
        if (!($source instanceof base_cursor_paginator)) {
            throw new \coding_exception("Invalid parameter of source");
        }

        switch ($field) {
            case 'next';
                $cursor = $source->get_next_cursor();
                if (null !== $cursor) {
                    return $cursor->encode();
                }

                return null;

            case 'total':
                return $source->get_total();

            default:
                debugging("The field '{$field}' is not supported", DEBUG_DEVELOPER);
                return null;
        }
    }
}
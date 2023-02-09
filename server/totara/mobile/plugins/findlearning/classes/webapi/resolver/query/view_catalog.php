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
 * @author David Curry <david.curry@totaralearning.com>
 * @package mobile_findlearning
 */

namespace mobile_findlearning\webapi\resolver\query;

use core\webapi\query_resolver;
use core\webapi\execution_context;
use core\webapi\middleware\require_login;
use mobile_findlearning\catalog as mobile_catalog;


/**
 * Class current_learning extends totara_core_my_current_learning query
 *
 * @package totara_mobile\webapi\resolver\query
 */
class view_catalog extends query_resolver {

    /**
     * Fetch the data required to resolve a catalog page.
     *
     * @param array $args
     * @param execution_context $ec
     * @return stdClass[]
     */
    public static function resolve(array $args, execution_context $ec) {
        $limit = $args['limit_from'] ?? 0;
        $catpage = mobile_catalog::load_catalog_page_objects($limit);

        return $catpage;
    }

    public static function get_middleware(): array {
        return [
            require_login::class
        ];
    }
}

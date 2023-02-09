<?php
/**
 * This file is part of Totara Talent Experience Platform
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
 * @author Cody Finegan <cody.finegan@totara.com>
 * @package totara_useraction
 */

namespace totara_useraction\webapi\resolver\query;

use core\webapi\execution_context;
use core\webapi\middleware\require_authenticated_user;
use core\webapi\query_resolver;
use totara_useraction\model\scheduled_rule as model;
use totara_useraction\webapi\middleware\require_capability;

/**
 * Fetch a single scheduled rule instance.
 */
class scheduled_rule extends query_resolver {

    /**
     * @return array
     */
    public static function get_middleware(): array {
        return [
            new require_authenticated_user(),
            require_capability::from_id('id'),
        ];
    }

    /**
     * @param array $args
     * @param execution_context $ec
     * @return model
     */
    public static function resolve(array $args, execution_context $ec): model {
        // Capability check & confirming the model exists is handled by the middleware.
        return model::load_by_id($args['id']);
    }
}

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
 * @package totara_engage
 */

namespace totara_engage\webapi\resolver\query;

use core\webapi\execution_context;
use core\webapi\query_resolver;
use totara_core\advanced_feature;

/**
 * Query for checking common engage features that the frontend may need to account for
 *
 * @package totara_engage\webapi\resolver\query
 */
class advanced_features extends query_resolver {
    /**
     * @param array $args
     * @param execution_context $ec
     * @return array
     */
    public static function resolve(array $args, execution_context $ec) {
        return [
            'library' => advanced_feature::is_enabled('engage_resources'),
            'workspaces' => advanced_feature::is_enabled('container_workspace'),
            'recommenders' => advanced_feature::is_enabled('ml_recommender'),
        ];
    }
}
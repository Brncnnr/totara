<?php
/**
 * This file is part of Totara Core
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
 * @author  Michael Ivanov <michael.ivanov@totaralearning.com>
 * @package totara_api
 */

namespace totara_api\watcher;

use totara_api\model\client_rate_limit as client_rate_limit_model;
use totara_api\model\global_rate_limit as global_rate_limit_model;
use totara_webapi\hook\handle_request_post_hook;

/**
 * A watcher to flush rate limiting values to the database
 */
class handle_request_post_watcher {

    /**
     * @param handle_request_post_hook $hook
     * @return void
     */
    public static function watch(handle_request_post_hook $hook): void {
        /** @var global_rate_limit_model $global_rate_limit_model */
        $global_rate_limit_model = $hook->execution_context->get_variable('global_rate_limit_model');
        $global_complexity_cost = $hook->execution_context->flush_volatile_global_query_complexity_cost();
        if (!empty($global_complexity_cost) && $global_rate_limit_model instanceof global_rate_limit_model) {
            $global_rate_limit_model->add_value($global_complexity_cost);
        }

        /** @var client_rate_limit_model $client_rate_limit_model */
        $client_rate_limit_model = $hook->execution_context->get_variable('client_rate_limit_model');
        $client_complexity_cost = $hook->execution_context->flush_volatile_client_query_complexity_cost();
        if (!empty($client_complexity_cost) && $client_rate_limit_model instanceof client_rate_limit_model) {
            $client_rate_limit_model->add_value($client_complexity_cost);
        }
    }
}
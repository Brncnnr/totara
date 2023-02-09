<?php
/**
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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package totara_oauth2
 */

namespace totara_oauth2\webapi\middleware;

namespace totara_oauth2\webapi\middleware;

use Closure;
use core\webapi\middleware;
use core\webapi\resolver\payload;
use core\webapi\resolver\result;
use totara_webapi\local\util;
use totara_api\model\client_rate_limit as model;
use totara_webapi\client_aware_exception;
use totara_webapi\query_complexity_exception;

class client_rate_limit implements middleware {

    public const COMPLEXITY_COST_FLUSH_TIME = 1;

    /**
     * @param payload $payload
     * @param Closure $next
     * @return result
     */
    public function handle(payload $payload, Closure $next): result {
        $flush_time = $payload->get_execution_context()->get_volatile_client_complexity_cost_last_flushed_time();
        $flush_needed = abs(time() - $flush_time) >= self::COMPLEXITY_COST_FLUSH_TIME;

        /** @var model $client_rate_limit_model */
        $client_rate_limit_model = $payload->get_execution_context()->get_variable('client_rate_limit_model');
        if (is_null($client_rate_limit_model)) {
            $client = $payload->get_execution_context()->get_variable('client');

            if (!isset($client)) {
                return $next($payload);
            }
            $client_rate_limit_model = $client->get_client_rate_limit();
            if (is_null($client_rate_limit_model)) {
                $client_rate_limit_model = model::instance(
                    $client->id,
                    0,
                    0,
                    time()
                );
            }
            $payload->get_execution_context()->set_variable('client_rate_limit_model', $client_rate_limit_model);
        } else if ($flush_needed) {
            $client_rate_limit_model->refresh();
        }

        // TODO default value will be replaced with estimation algorithm TL-34843 
        if (!$client_rate_limit_model->has_capacity(1)) {
            $this->flush_complexity_cost($payload, $client_rate_limit_model);
           util::send_error('Too many requests', 429, !(defined('PHPUNIT_TEST') && PHPUNIT_TEST));
        }

        try {
            $result = $next($payload);
        } catch (query_complexity_exception $exception) {
            $this->flush_complexity_cost($payload, $client_rate_limit_model);
            throw new client_aware_exception($exception, ['category' => 'query_complexity']);
        }
        if ($flush_needed) {
            $this->flush_complexity_cost($payload, $client_rate_limit_model);
        }

        return $result;
    }

    /**
     * Saves complexity cost to the DB
     * @param payload $payload
     * @param model $client_rate_limit
     * @return void
     */
    protected function flush_complexity_cost(payload $payload, model $client_rate_limit): void {
        $complexity_cost = $payload->get_execution_context()->flush_volatile_client_query_complexity_cost();
        if (!empty($complexity_cost)) {
            $client_rate_limit->add_value($complexity_cost);
        }
    }
}
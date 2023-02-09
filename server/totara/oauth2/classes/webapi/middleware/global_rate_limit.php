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
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 *
 * @package totara_oauth2
 */

namespace totara_oauth2\webapi\middleware;

use Closure;
use core\orm\query\builder;
use core\orm\query\order;
use core\webapi\middleware;
use core\webapi\resolver\payload;
use core\webapi\resolver\result;
use totara_api\entity\global_rate_limit as entity;
use totara_api\model\global_rate_limit as model;
use totara_webapi\client_aware_exception;
use totara_webapi\local\util;
use totara_webapi\query_complexity_exception;

class global_rate_limit implements middleware {

    public const COMPLEXITY_COST_FLUSH_TIME = 1;

    /**
     * @param payload $payload
     * @param Closure $next
     * @return result
     */
    public function handle(payload $payload, Closure $next): result {
        /** @var model $global_rate_limit_model */
        $global_rate_limit_model = $payload->get_execution_context()->get_variable('global_rate_limit_model');

        $flush_time = $payload->get_execution_context()->get_volatile_global_complexity_cost_last_flushed_time();
        $flush_needed = abs(time() - $flush_time) >= self::COMPLEXITY_COST_FLUSH_TIME;

        if (empty($global_rate_limit_model)) {
            $global_rate_limit = builder::table(entity::TABLE)
                ->order_by('id', order::DIRECTION_DESC)
                ->first();
            if (is_null($global_rate_limit)) {
                $global_rate_limit_model = model::instance(0, 0, time());
            } else {
                $global_rate_limit_model = model::load_by_id($global_rate_limit->id);
            }
            $payload->get_execution_context()->set_variable('global_rate_limit_model', $global_rate_limit_model);
        } else if ($flush_needed) {
            $global_rate_limit_model->refresh();
        }

        // TODO default value will be replaced with estimation algorithm TL-34843
        if (!$global_rate_limit_model->has_capacity(1)) {
            $this->flush_complexity_cost($payload, $global_rate_limit_model);
            // This is for PHPUnit testing only to avoid unit waring.
            if (defined('PHPUNIT_TEST') && PHPUNIT_TEST) {
                throw new \coding_exception('Too many requests');
            }
            util::send_error('Too many requests', 429);
        }

        try {
           $result = $next($payload);
        } catch (query_complexity_exception $exception) {
            $this->flush_complexity_cost($payload, $global_rate_limit_model);
            throw new client_aware_exception($exception, ['category' => 'query_complexity']);
        }
        if ($flush_needed) {
            $this->flush_complexity_cost($payload, $global_rate_limit_model);
        }

        return $result;
    }

    /**
     * Saves complexity cost to the DB
     * @param payload $payload
     * @param model $global_rate_limit
     * @return void
     */
    protected function flush_complexity_cost(payload $payload, model $global_rate_limit): void {
        $complexity_cost = $payload->get_execution_context()->flush_volatile_global_query_complexity_cost();
        if (!empty($complexity_cost)) {
            $global_rate_limit->add_value($complexity_cost);
        }
    }
}
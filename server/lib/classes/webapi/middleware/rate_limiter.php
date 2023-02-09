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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package core
 */

namespace core\webapi\middleware;

use Closure;
use core\webapi\middleware;
use core\webapi\resolver\payload;
use core\webapi\resolver\result;
use core\json_editor\helper\document_helper;

/**
 * Middle-ware for rate limiting a webapi query or mutation
 */
class rate_limiter implements middleware {

    /**
     * If this is true we will throw an exception, if this is false we will return an empty result
     *
     * @var bool
     */
    protected $fail_on_limit;

    /**
     * amount of requests allowed with the time defined in limit_time
     * defaults to 0, means unlimited
     *
     * @var int
     */
    private $rate_limit = 0;

    /**
     * The time the limit is applied to (in seconds), only x amount of request can be triggered
     * in the currect session within this time
     *
     * @var int
     */
    private $limit_time = 60;

    /**
     * @param int|null $rate_limit
     * @param int|null $limit_time
     * @param bool $fail_on_limit
     */
    public function __construct(int $rate_limit = null, int $limit_time = null, bool $fail_on_limit = true) {
        if ($rate_limit) {
            $this->rate_limit = $rate_limit;
        }
        if ($limit_time) {
            $this->limit_time = $limit_time;
        }
        $this->fail_on_limit = $fail_on_limit;
    }

    /**
     * @param payload $payload
     * @param Closure $next
     *
     * @return result
     */
    public function handle(payload $payload, Closure $next): result {
        global $SESSION;

        $key = $payload->get_execution_context()->get_resolve_info()->fieldName ?? null;
        // Without an name we better not apply any limit
        // because we cannot clearly identify where this belongs to
        if (empty($key)) {
            return $next($payload);
        }

        if (!isset($SESSION->webapi_rate_limits[$key])) {
            $this->reset_rate_limit($key);
        } else {
            $SESSION->webapi_rate_limits[$key]['request'] = $SESSION->webapi_rate_limits[$key]['request'] + 1;

            $timestamp = $SESSION->webapi_rate_limits[$key]['timestamp'];
            if (time() - $timestamp <= $this->limit_time) {
                if ($SESSION->webapi_rate_limits[$key]['request'] > $this->rate_limit) {
                    if ($this->fail_on_limit) {
                        throw new \coding_exception('failed due to rate limit exceeded');
                    } else {
                        return new result(null);
                    }
                }
            } else {
                $this->reset_rate_limit($key);
            }
        }

        return $next($payload);
    }

    /**
     * Reset the rate limit for the given key in the session
     *
     * @param string $key
     */
    private function reset_rate_limit(string $key) {
        global $SESSION;

        $SESSION->webapi_rate_limits[$key] = [
            'request' => 1,
            'timestamp' => time()
        ];
    }
}

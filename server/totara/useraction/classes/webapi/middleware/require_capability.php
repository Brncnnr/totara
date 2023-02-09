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
 * @package
 */

namespace totara_useraction\webapi\middleware;

use Closure;
use context_system;
use context_tenant;
use core\orm\query\exceptions\record_not_found_exception;
use core\webapi\middleware;
use core\webapi\resolver\payload;
use core\webapi\resolver\result;
use totara_useraction\model\scheduled_rule;

/**
 * Middleware to check if the user has the capability in the context we're dealing with.
 * It'll validate if the user is a tenant member (unlikely) and accessing a tenancy not their own.
 *
 * If an id is nor known it'll throw a generic access error rather than a missing record error.
 */
class require_capability implements middleware {

    /**
     * Function to lookup in the payload the correct tenant id
     *
     * @var Closure
     */
    private Closure $tenant_id_loader;

    /**
     * Create a new instance of this middleware. For flexibility, it is called with a closure, which
     * will be executed with the $payload parameter and expect to return the ID of the tenant involved
     * (or null if it is a system level scheduled rule).
     *
     * Cannot be invoked directly, must be created using one of the require_capability::from_* methods.
     *
     * @param Closure $tenant_id_loader
     */
    private function __construct(Closure $tenant_id_loader) {
        $this->tenant_id_loader = $tenant_id_loader;
    }

    /**
     * Validate the request using the specific scheduled rule ID.
     * This is for individual requests where the ID is known.
     *
     * @param string $input_key
     * @return static
     */
    public static function from_id(string $input_key): self {
        $callable = function (payload $payload) use ($input_key): ?int {
            $id = self::get_payload_value($input_key, $payload);
            if (empty($id)) {
                throw new \coding_exception('No ID was provided');
            }
            try {
                $model = scheduled_rule::load_by_id($id);
                $payload->set_variable('scheduled_rule_model', $model);
            } catch (record_not_found_exception $ex) {
                // Don't expose whether entities exist or not
                throw new \moodle_exception('notfound', 'totara_core');
            }
            return $model->tenant_id ?? null;
        };

        return new self($callable);
    }

    /**
     * Validate the request using a tenant id provided in the payload.
     * This is for bulk-request pages inside a specific context.
     *
     * @param string $input_key
     * @return static
     */
    public static function from_tenant_id(string $input_key): self {
        $callable = function (payload $payload) use ($input_key): ?int {
            $tenant_id = self::get_payload_value($input_key, $payload);
            return empty($tenant_id) ? null : $tenant_id;
        };

        return new self($callable);
    }

    /**
     * Common helper method to extract a single value from a posted payload.
     *
     * @param string $payload_keys
     * @param payload $payload
     * @return mixed|null
     */
    protected static function get_payload_value(string $payload_keys, payload $payload) {
        $keys = explode('.', $payload_keys);

        $initial = array_shift($keys);
        $result = $payload->get_variable($initial);

        if ($result) {
            foreach ($keys as $key) {
                $result = $result[$key] ?? null;
            }
        }

        return $result;
    }

    /**
     * Execute the middleware, and if the capability check fails throw an exception.
     *
     * @param payload $payload
     * @param Closure $next
     * @return result
     */
    public function handle(payload $payload, Closure $next): result {
        global $USER;

        $tenant_id = call_user_func($this->tenant_id_loader, $payload);
        if ($USER->tenantid && $tenant_id != $USER->tenantid) {
            throw new \moodle_exception('notfound', 'totara_core');
        }

        if ($tenant_id) {
            $context = context_tenant::instance($tenant_id);
            $payload->get_execution_context()->set_relevant_context($context);
        } else {
            $context = context_system::instance();
        }

        require_capability('totara/useraction:manage_actions', $context);

        return $next($payload);
    }
}
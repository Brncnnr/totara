<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTDvs
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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

namespace totara_api\webapi\middleware;

use Closure;
use context_system;
use core\entity\tenant;
use core\orm\query\exceptions\record_not_found_exception;
use core\webapi\middleware;
use core\webapi\resolver\payload;
use core\webapi\resolver\result;
use moodle_exception;
use totara_api\exception\require_manage_capability_exception;
use totara_api\model\client;
use totara_api\model\helpers\client_capability_helper;
use TypeError;

class require_manage_capability implements middleware {

    /** @var Closure */
    protected $retriever;

    /** @var bool */
    protected $set_relevant_context;

    /**
     * Default constructor.
     *
     * @param Closure $retriever method that retrieves a tenant id
     *        using data in the specified payload.
     * @param bool $set_relevant_context if true, sets the graphql execution context
     */
    public function __construct(Closure $retriever, bool $set_relevant_context = false) {
        $this->retriever = $retriever;
        $this->set_relevant_context = $set_relevant_context;
    }

    /**
     * @inheritDoc
     * @throws moodle_exception
     */
    public function handle(payload $payload, Closure $next): result {
        $retriever = $this->retriever;
        try {
            $tenant_id = $retriever($payload);
        } catch (record_not_found_exception | TypeError $exception) {
            throw new require_manage_capability_exception('Invalid client.');
        }

        $helper = client_capability_helper::for_tenant($tenant_id);
        $helper->can_manage(true);

        if ($this->set_relevant_context) {
            $context = $helper->get_execution_context();
            if (!$context instanceof context_system) {
                $payload->get_execution_context()->set_relevant_context($context);
            }
        }

        return $next($payload);
    }

    /**
     * @param string $payload_keys
     * @param bool $set_relevant_context
     * @return static
     */
    public static function by_client_id(string $payload_keys, bool $set_relevant_context = false): self {
        $retriever = function (payload $payload) use ($payload_keys) {
            $id = self::get_payload_value($payload_keys, $payload);
            $client = client::load_by_id($id);
            return $client->tenant_entity ?? null;
        };
        return new static($retriever, $set_relevant_context);
    }

    /**
     * @param string $payload_keys
     * @param bool $set_relevant_context
     * @return static
     */
    public static function by_tenant_id(string $payload_keys, bool $set_relevant_context = false): self {
        $retriever = function (payload $payload) use ($payload_keys) {
            $id = self::get_payload_value($payload_keys, $payload);
            return $id ? new tenant($id) : null;
        };
        return new static($retriever, $set_relevant_context);
    }

    /**
     * Returns a value extracted from the incoming payload.
     *
     * @param string $payload_keys the keys in the payload to use to extract the
     *        value from the payload. For example if the keys are "a.b.c", then the
     *        value is retrieved as $payload['a']['b']['c'].
     * @param payload $payload the incoming payload to parse.
     *
     * @return mixed the extracted value.
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
}
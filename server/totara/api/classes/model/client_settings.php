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
 * @author Scott Davies <scott.davies@totaralearning.com>
 * @package totara_api
 */

namespace totara_api\model;

use core\orm\entity\model;
use totara_api\entity\client_settings as entity;
use totara_api\global_api_config;
use totara_api\response_debug;

/**
 * @property-read int $id
 * @property-read int $default_token_expiry_time
 * @property-read int $client_rate_limit
 * @property-read int|null $response_debug
 * @property-read string $response_debug_string
 * @property-read int $client_id
 * @property-read int $time_created
 * @property-read client $client
 */
class client_settings extends model {
    /**
     * @var entity
     */
    protected $entity;

    /**
     * @var string[]
     */
    protected $entity_attribute_whitelist = [
        'id',
        'default_token_expiry_time',
        'client_rate_limit',
        'response_debug',
        'client_id',
        'time_created',
    ];

    /**
     * @var string[]
     */
    protected $model_accessor_whitelist = [
        'client',
        'response_debug_string',
    ];

    /**
     * @return string
     */
    protected static function get_entity_class(): string {
        return entity::class;
    }

    /**
     * Business logic means this is called automatically as well when an api_client model is created.
     *
     * @param int $client_id
     * @param int|null $default_token_expiry_time
     * @param int|null $client_rate_limit
     * @param int|null $response_debug
     * @return static
     */
    public static function create(
        int $client_id,
        int $default_token_expiry_time = null,
        int $client_rate_limit = null,
        int $response_debug = null
    ): self {
        $client_settings_entity = new entity();
        $client_settings_entity->client_id = $client_id;
        $client_settings_entity->time_created = time();

        if (isset($default_token_expiry_time)) {
            $client_settings_entity->default_token_expiry_time = $default_token_expiry_time;
        } else if (!is_null(global_api_config::get_default_token_expiration())){
            $client_settings_entity->default_token_expiry_time = global_api_config::get_default_token_expiration();
        }

        if (isset($client_rate_limit)) {
            $client_settings_entity->client_rate_limit = $client_rate_limit;
        } else if (!is_null(global_api_config::get_client_rate_limit())){
            $client_settings_entity->client_rate_limit = global_api_config::get_client_rate_limit();
        }

        $client_settings_entity->response_debug = $response_debug;

        $client_settings_entity = $client_settings_entity->save();

        return new static($client_settings_entity);
    }

    /**
     * @param array $args - Possible args are: 'client_id', 'client_rate_limit', 'default_token_expiry_time', 'response_debug'.
     * @return $this
     */
    public function update(array $args): self {
        global $DB;

        $transaction = $DB->start_delegated_transaction();

        if (isset($args['client_rate_limit'])) {
            $this->entity->client_rate_limit = $args['client_rate_limit'];
            $client_rate_limit_model = $this->client->client_rate_limit;
            if (!is_null($client_rate_limit_model)) {
                $client_rate_limit_model->update(
                    null,
                    null,
                    null,
                    $args['client_rate_limit']
                ) ;
            }
        }

        if (isset($args['default_token_expiry_time'])) {
            $this->entity->default_token_expiry_time = $args['default_token_expiry_time'];
        }

        if (array_key_exists('response_debug', $args)) {
            $this->entity->response_debug = $args['response_debug'];
        }

        $this->entity->save();
        $transaction->allow_commit();

        $this->entity->refresh();

        return $this;
    }

    /**
     * @param array $args - Possible args are: 'client_id', 'client_rate_limit', 'default_token_expiry_time', 'response_debug'.
     * @return static
     */
    public static function put(array $args): self {
        $entity = entity::repository()->find_by_client_id($args['client_id']);
        if (!is_null($entity)) {
            return self::load_by_entity($entity)->update($args);
        }

        // For creating a new client_settings, unpack $args and set values for the optional fields.
        $default_token_expiry_time = $args['default_token_expiry_time'] ?? null;
        $client_rate_limit = $args['client_rate_limit'] ?? null;
        $response_debug = $args['response_debug'] ?? null;
        return self::create($args['client_id'], $default_token_expiry_time, $client_rate_limit, $response_debug);
    }

    /**
     * @return client
     */
    public function get_client(): client {
        return client::load_by_entity($this->entity->client);
    }
    
    /**
     * @return string
     */
    public function get_response_debug_string(): ?string {
        return response_debug::get_string($this->response_debug);
    }
}
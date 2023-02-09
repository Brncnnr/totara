<?php
/**
 * This file is part of Totara Core
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
 * @author  Michael Ivanov <michael.ivanov@totaralearning.com>
 * @package totara_api
 */

namespace totara_api\testing;

use coding_exception;
use core\entity\tenant;
use core\testing\component_generator;
use totara_api\entity\client;
use totara_api\entity\client_oauth2_client_provider;
use totara_api\entity\client_settings as client_settings_entity;
use totara_api\model\client_settings as client_settings_model;
use totara_api\entity\client_rate_limit;
use totara_api\entity\global_rate_limit;
use totara_api\model\client as client_model;
use totara_oauth2\model\client_provider;

global $CFG;
require_once($CFG->dirroot . '/user/lib.php');

class generator extends component_generator {

    /**
     * The array $parameters should have these following attributes:
     * + name: string
     * + description: string|null
     * + tenant_id: int|null
     *
     * @param array $parameters
     *
     * @return client
     */
    public function create_client(array $parameters = []): client {
        $entity = new client();
        $entity->name = $parameters['name'] ?? 'client' . rand(0, 100);
        $entity->description = $parameters['description'] ?? null;
        $entity->tenant_id = $parameters['tenant_id'] ?? null;
        $entity->user_id = $parameters['user_id'] ?? null;

        if (!empty($parameters['time_created'])) {
            $entity->time_created = $parameters['time_created'];
        }

        $entity->save();
        $entity->refresh();

        return $entity;
    }

    /**
     * Callback from behat data generator.
     *
     * @param array $parameters
     * @return client
     */
    public function create_api_client_instance(array $parameters = []): client {
        if (!isset($parameters['name'])) {
            throw new coding_exception(
                "Cannot create client_provider from parameters that does not have the name itself"
            );
        }

        if (isset($parameters['username'])) {
           $user = \core_user::get_user_by_username($parameters['username']);
           if (!$user) {
               throw new coding_exception('User is not found');
           }
        }

        if (isset($parameters['tenant_id_number'])) {
            $tenant = tenant::repository()
                ->where('idnumber', $parameters['tenant_id_number'])
                ->one(true);
            if (!$tenant) {
                throw new coding_exception('Tenant is not found');
            }
        }
        if (isset($parameters['client_provider_id'])) {
            $client_provider = client_provider::load_by_id($parameters['client_provider_id']);
        } else {
            $client_provider = null;
        }
        $client = client_model::create(
            $parameters['name'],
                isset($user) ? $user->id : null,
                $parameters['description'] ?? null,
            isset($tenant) ? $tenant->id : null,
            true,
            ['create_client_provider' => !$client_provider]
        );
        if ($client_provider) {
            $api_client_to_oauth2_client_provider = new client_oauth2_client_provider();
            $api_client_to_oauth2_client_provider
                ->client_provider()
                ->associate($client_provider->get_entity_copy());
            $api_client_to_oauth2_client_provider->client()->associate($client->get_entity_copy());
            $api_client_to_oauth2_client_provider->save();
        }

        return $client->get_entity_copy();
    }

    /**
     * @param array $parameters
     * @return client_settings_entity
     */
    public function create_client_settings_entity(array $parameters = []) : client_settings_entity {
        $entity_client = $this->create_client();
        $client_settings = new client_settings_entity();
        $client_settings->client_id = $entity_client->id;

        if (isset($parameters['default_token_expiry_time'])) {
            $client_settings->default_token_expiry_time = $parameters['default_token_expiry_time'];
        }

        if (isset($parameters['client_rate_limit'])) {
            $client_settings->client_rate_limit = $parameters['client_rate_limit'];
        }

        // Add a 1-on-1 relationship from client to client_settings
        $entity_client->client_settings()->save($client_settings);

        return $client_settings;
    }

    /**
     * @param array $paramters
     * @return client_settings_model
     */
    public function create_client_settings_model(array $parameters = []): client_settings_model {
        $entity = $this->create_client($parameters);
        $client_settings = new \totara_api\entity\client_settings();

        if (isset($parameters['default_token_expiry_time'])) {
            $client_settings->default_token_expiry_time = $parameters['default_token_expiry_time'];
        }

        if (isset($parameters['client_rate_limit'])) {
            $client_settings->client_rate_limit = $parameters['client_rate_limit'];
        }

        $client_settings->client_id = $entity->id;
        $client_settings->time_created = time();

        $client_settings = $client_settings->save();
        return client_settings_model::load_by_entity($client_settings);
    }

    /**
     * Create a global rate limit record.
     *
     * @param array $parameters
     * @return global_rate_limit
     */
    public function create_global_rate_limit(array $parameters = []): global_rate_limit {
        $entity = new global_rate_limit();
        $entity->prev_window_value = $parameters['prev_window_value'] ?? 0;
        $entity->current_window_reset_time = $parameters['current_window_reset_time'] ?? null;
        $entity->current_window_value = $parameters['current_window_value'] ?? 0;
        $entity->current_limit = $parameters['current_limit'] ?? null;
        $entity->time_created = $parameters['time_created'] ?? time();

        $entity->save();
        $entity->refresh();

        return $entity;
    }

    /**
     * Create a client rate limit record.
     *
     * @param array $parameters
     *
     * @return client_rate_limit
     */
    public function create_client_rate_limit(array $parameters = []): client_rate_limit {
        $entity = new client_rate_limit();
        $entity->client_id = $parameters['client_id'] ?? $this->create_client()->id;
        $entity->prev_window_value = $parameters['prev_window_value'] ?? 0;
        $entity->current_window_reset_time = $parameters['current_window_reset_time'] ?? null;
        $entity->current_window_value = $parameters['current_window_value'] ?? 0;
        $entity->current_limit = $parameters['current_limit'] ?? null;
        $entity->time_created = $parameters['time_created'] ?? time();

        $entity->save();
        $entity->refresh();

        return $entity;
    }
}
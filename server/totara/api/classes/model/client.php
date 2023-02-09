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
 * @author Michael Ivanov <michael.ivanov@totaralearning.com>
 * @package totara_api
 */

namespace totara_api\model;

use context;
use context_coursecat;
use context_system;
use core\entity\tenant;
use core\entity\user;
use core\orm\collection;
use core\orm\entity\model;
use core\orm\query\builder;
use moodle_exception;
use totara_api\entity\client as entity;
use totara_api\entity\client_oauth2_client_provider;
use totara_oauth2\model\client_provider;
use totara_api\model\client_settings as client_settings_model;
use totara_api\exception\create_client_exception;
use totara_api\pdo\client_service_account;
use totara_api\exception\update_client_exception;

/**
 *
 * @property-read int $id
 * @property-read string $name
 * @property-read string|null $description
 * @property-read int|null $tenant_id
 * @property-read tenant|null $tenant_entity
 * @property-read int|null $user_id
 * @property-read int $time_created
 * @property-read bool $status
 * @property-read user|null $user
 * @property-read collection|client_provider[] $oauth2_client_providers
 * @property-read client_settings_model|null $client_settings
 * @property-read client_rate_limit|null $client_rate_limit
 * @property-read client_service_account|null $service_account
 */
class client extends model {
    /**
     * @var entity
     */
    protected $entity;

    /**
     * @var string[]
     */
    protected $entity_attribute_whitelist = [
        'id',
        'name',
        'description',
        'tenant_id',
        'user_id',
        'time_created',
        'status'
    ];

    protected $model_accessor_whitelist = [
        'tenant_entity',
        'user',
        'oauth2_client_providers',
        'client_settings',
        'client_rate_limit',
        'service_account'
    ];

    /**
     * @string
     */
    protected const COMPONENT = 'totara_api';

    /**
     * @return string
     */
    protected static function get_entity_class(): string {
        return entity::class;
    }

    /**
     * @param user|null $user
     * @param int|null $client_tenant_id
     * @return string User status enum (VALID, NOUSER, GUEST, ADMIN, DELETED, SUSPENDED, WRONGTENANT)
     */
    public static function validate_api_user(?user $user, int $client_tenant_id = null): string {
        global $CFG;
        if (empty($user) || !($user instanceof user)) {
            return client_service_account::NOUSER;
        }
        // Check if guest
        if ($CFG->siteguest == $user->id) {
            return client_service_account::GUEST;
        }
        // Check if admin
        $siteadmins = explode(',', $CFG->siteadmins);
        $knownresult = in_array($user->id, $siteadmins);

        if ($knownresult) {
            return client_service_account::ADMIN;
        }

        $user_repo = builder::table('user')
            ->as('u')
            ->where('u.id', $user->id)
            ->select(['u.id', 'u.suspended', 'u.deleted', 'u.tenantid'])
            ->one();

        if (is_null($user_repo)) {
            return client_service_account::NOUSER;
        } else if ($user_repo->suspended) {
            return client_service_account::SUSPENDED;
        } else if ($user_repo->deleted) {
            return client_service_account::DELETED;
        } else if ($user_repo->tenantid != $client_tenant_id) {
            // The following scenario will be found invalid: a system user assigned as service account in a tenant api_client.
            // Also a tenant user cannot be assigned as service account in a system api_client.
            return client_service_account::WRONGTENANT;
        }

        return client_service_account::VALID;
    }

    /**
     * @param string $name
     * @param user|int $user
     * @param string|null $description
     * @param int|tenant $tenant
     * @param bool $status
     * @param array $options
     * @return static|moodle_exception
     */
    public static function create(
        string $name,
        $user,
        string $description = null,
        $tenant = null,
        bool $status = true,
        array $options = []
    ): self {
        if (!empty($tenant) && !$tenant instanceof tenant) {
            $tenant = tenant::repository()->find_or_fail($tenant);

            // If status is true, but tenant is suspended
            if ($status && $tenant->suspended) {
                throw new create_client_exception(
                    'An enabled client can not be added to a suspended tenant.'
                );
            }
        }

        if (!$user instanceof user) { // It must be a user ID instead.
            $user = user::repository()->find($user);
        }
        $tenant_id = (empty($tenant) ? null : $tenant->id);
        $status_message = self::validate_api_user($user, $tenant_id);
        if ($status_message != client_service_account::VALID) {
            throw new create_client_exception('The user is not valid. The status is ' . $status_message);
        }

        $entity = new entity();
        $entity->name = $name;
        $entity->description = $description ?? '';
        $entity->status = $status;
        if (!empty($tenant)) {
            if ((int) $user->tenantid !== (int) $tenant->id) {
                throw new create_client_exception('The user does not belong to the tenant.');
            }
            $entity->tenant()->associate($tenant);
        }
        if (!empty($user)) {
            $entity->user()->associate($user);
        }

        builder::get_db()->transaction(function () use ($entity, $options) {
            $entity = $entity->save();

            // Create client settings.
            client_settings_model::create($entity->id);

            if (!empty($options['create_client_provider'])) {
                $client_provider = client_provider::create(
                    $entity->name,
                    '',
                    FORMAT_PLAIN,
                    $entity->description,
                    1,
                    $entity->status,
                    $entity->tenant_id,
                    self::COMPONENT
                );
                $api_client_to_oauth2_client_provider = new client_oauth2_client_provider();
                $api_client_to_oauth2_client_provider
                    ->client_provider()
                    ->associate($client_provider->get_entity_copy());
                $api_client_to_oauth2_client_provider->client()->associate($entity);
                $api_client_to_oauth2_client_provider->save();
            }
        });

        return new static($entity);
    }

    /**
     * @param string|null $name
     * @param user|int|null $user
     * @param string|null $description
     * @param bool|null $status
     * @return $this|moodle_exception
     */
    public function update(string $name = null, string $description = null, bool $status = null, $user = null): self {
        if (is_string($name)) {
            $this->entity->name = $name;
        }

        if (is_string($description)) {
            $this->entity->description = $description;
        }

        if (is_bool($status)) {
            $tenant = $this->get_tenant_entity();
            if (!empty($tenant) && $tenant->suspended && $status) {
                throw new update_client_exception('A client in a suspended tenant can not be enabled.');
            }

            $this->entity->status = $status;
        }

        // An API user for the API client is currently optional.
        if (!empty($user)) {
            if (!$user instanceof user) { // It must be a user ID instead.
                $user = user::repository()->find($user);
            }

            $status_message = self::validate_api_user($user, $this->entity->tenant_id);
            if ($status_message != client_service_account::VALID) {
                throw new create_client_exception('The user is not valid. The status is ' . $status_message);
            }

            $existing_user = $this->entity->user;
            if (is_null($existing_user) || $user->id !== $existing_user->id) {
                $this->entity->user()->associate($user);
            }
        }

        builder::get_db()->transaction(function () {
            $this->entity->save();
            $this->entity->oauth2_client_providers()->update([
                'name' => $this->entity->name,
                'description' => $this->entity->description,
                'status' => $this->entity->status
            ]);
        });
        $this->entity->refresh();

        return $this;
    }

    /**
     * @return void
     */
    public function delete(): void {
        builder::get_db()->transaction(function () {
            $this->entity->oauth2_client_providers()->delete();
            $this->entity->delete();
        });
    }

    /**
     * There is no model for tenant so return entity instead
     *
     * @return tenant|null
     */
    public function get_tenant_entity(): ?tenant {
        return $this->entity->tenant;
    }

    /**
     * @return collection|client_provider[]
     */
    public function get_oauth2_client_providers(): collection {
        return $this->entity->oauth2_client_providers->map_to(client_provider::class);
    }

    /**
     * @param bool $client_status
     * @return void
     */
    public function set_client_status(bool $status): void {
        $tenant = $this->get_tenant_entity();
        if (!empty($tenant) && $tenant->suspended && $status) {
            throw new update_client_exception('A client in a suspended tenant can not be enabled.');
        }

        $this->entity->status = $status;

        builder::get_db()->transaction(function () use ($status) {
            $this->entity->save();

            foreach ($this->entity->oauth2_client_providers->all() as $oauth2_client_provider) {
                $oauth2_client_provider->status = $status;
                $oauth2_client_provider->save();
            }
        });
    }

    /**
     * This method is needed by the ORM accessor.
     *
     * @return client_settings_model|null
     */
    public function get_client_settings(): ?client_settings_model {
        return $this->entity->client_settings
            ? client_settings_model::load_by_entity($this->entity->client_settings) : null;
    }

    /**
     * @return client_rate_limit|null
     */
    public function get_client_rate_limit(): ?client_rate_limit {
        return $this->entity->client_rate_limit ?
            client_rate_limit::load_by_entity($this->entity->client_rate_limit) : null;
    }

    /**
     * Return context for this client
     *
     * @return context
     */
    public function get_context(): context {
        if (isset($this->tenant_entity->categoryid)) {
            return context_coursecat::instance($this->tenant_entity->categoryid);
        }
        return context_system::instance();
    }

    /**
     * Return the API user for the API client.
     *
     * @return user
     */
    public function get_user(): user {
        return new user($this->entity->user_id);
    }

    /**
     * Returns the service account details for the API user.
     *
     * @return client_service_account
     */
    public function get_service_account(): client_service_account {
        return new client_service_account($this->get_user(), $this->tenant_id);
    }

}
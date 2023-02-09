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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package totara_core
 */

namespace totara_core\virtualmeeting;

use coding_exception;
use core\entity\user as user_entity;
use core\orm\entity\entity;
use core\orm\entity\model;
use core\plugininfo\virtualmeeting as virtualmeeting_plugininfo;
use Throwable;
use totara_core\entity\virtual_meeting_auth as virtual_meeting_auth_entity;
use totara_core\http\client;
use totara_core\http\clients\curl_client;
use totara_core\virtualmeeting\exception\unsupported_exception;
use totara_core\virtualmeeting\plugin\factory\auth_factory;
use totara_core\virtualmeeting\plugin\provider\auth_provider;

/**
 * Virtual meeting authentication/authorisation
 *
 * @property-read int $id ID
 * @property string $plugin
 * @property string $access_token
 * @property string $refresh_token
 * @property int $timeexpiry
 * @property int $userid
 * @property-read user_entity $user
 */
final class virtual_meeting_auth extends model {

    /** @var virtual_meeting_auth_entity */
    protected $entity;

    /** @var virtualmeeting_plugininfo|null */
    private $plugininfo = null;

    /** @var auth_provider|null */
    private $auth = null;

    /**
     * @return string
     * @codeCoverageIgnore
     */
    protected static function get_entity_class(): string {
        return virtual_meeting_auth_entity::class;
    }

    /**
     * Gets a model object based on the given entity.
     *
     * @param virtual_meeting_auth_entity $entity
     * @param client|null $client
     * @return self
     */
    public static function load_by_entity(entity $entity, client $client = null): self {
        /** @var self */
        $self = parent::load_by_entity($entity);
        // Load the auth_provider instance
        $self->create_auth_provider($client);
        return $self;
    }

    /**
     * Gets a model object based on the given id.
     *
     * @param integer $id
     * @param client|null $client
     * @return self
     */
    public static function load_by_id(int $id, client $client = null): self {
        /** @var self */
        $self = parent::load_by_id($id);
        // Load the auth_provider instance
        $self->create_auth_provider($client);
        return $self;
    }

    /**
     * Load a model object based on the given plugin and the user.
     *
     * @param string|virtualmeeting_plugininfo $plugin plugin name or instance
     * @param user_entity $user
     * @param boolean $strict blow up if something is wrong; setting to false is highly discouraged
     * @param client|null $client
     * @return self|null
     */
    public static function load_by_plugin_user($plugin, user_entity $user, bool $strict = true, client $client = null): ?self {
        if ($client === null) {
            $client = new curl_client();
        }
        if (!($plugin instanceof virtualmeeting_plugininfo)) {
            if (!$strict && !isset(virtualmeeting_plugininfo::get_available_plugins()[$plugin])) {
                return null;
            }
            $plugin = virtualmeeting_plugininfo::load($plugin);
        }
        $auth = self::create_auth_provider_of_plugin($plugin, $strict, $client);
        if ($auth === null) {
            return null;
        }
        $entity = virtual_meeting_auth_entity::repository()->find_by_plugin_and_user($plugin->name, $user->id, $strict);
        if ($entity === null) {
            return null;
        }
        $self = parent::load_by_entity($entity);
        $self->auth = $auth;
        $self->plugininfo = $plugin;
        return $self;
    }

    /**
     * Get the authentication endpoint of the given plugin.
     *
     * @param virtualmeeting_plugininfo|string $plugin
     * @param client|null $client
     * @return string
     */
    public static function get_authentication_endpoint($plugin, client $client = null): string {
        if ($client === null) {
            $client = new curl_client();
        }
        if (!($plugin instanceof virtualmeeting_plugininfo)) {
            $plugin = virtualmeeting_plugininfo::load_available($plugin);
        }
        $auth = self::create_auth_provider_of_plugin($plugin, true, $client);
        $url = $auth->get_authentication_endpoint();
        if (!preg_match('#^https?://#', $url)) {
            throw new coding_exception('invalid endpoint for plugin: '.$plugin->name);
        }
        return $url;
    }

    /**
     * Get the plugininfo instance.
     *
     * @return virtualmeeting_plugininfo
     * @codeCoverageIgnore
     */
    private function get_plugininfo(): virtualmeeting_plugininfo {
        if (!$this->plugininfo) {
            $this->plugininfo = virtualmeeting_plugininfo::load($this->entity->plugin);
        }
        return $this->plugininfo;
    }

    /**
     * Create an auth service provider instance.
     *
     * @param client|null $client
     * @return auth_provider
     */
    private function create_auth_provider(client $client = null): auth_provider {
        if (!$this->auth) {
            if ($client === null) {
                $client = new curl_client();
            }
            $this->auth = self::create_auth_provider_of_plugin($this->get_plugininfo(), true, $client);
        }
        return $this->auth;
    }

    /**
     * Create an auth service provider instance.
     *
     * @param virtualmeeting_plugininfo $plugin
     * @param boolean $strict blow up if auth service is not available
     * @param client $client
     * @return auth_provider|null
     */
    private static function create_auth_provider_of_plugin(virtualmeeting_plugininfo $plugin, bool $strict, client $client): ?auth_provider {
        $factory = $plugin->create_factory();
        if (!$factory->is_available()) {
            if ($strict) {
                throw new coding_exception('plugin not available: '.$plugin->name);
            } else {
                return null;
            }
        }
        if (!($factory instanceof auth_factory)) {
            if ($strict) {
                throw unsupported_exception::auth($plugin->name);
            } else {
                return null;
            }
        }
        return $factory->create_auth_service_provider($client);
    }

    /**
     * Return whether the token is expired or not.
     *
     * @param integer $time timestamp or 0 for the current time
     * @return boolean
     */
    public function is_expired(int $time = 0): bool {
        return $this->entity->is_expired($time);
    }

    /**
     * Get the user's profile.
     *
     * @param boolean $update tell plugin to update the user's token
     * @param boolean $strict blow up if something is wrong
     * @return array of the following keys
     * - name: account name etc.
     * - email: email address (optional)
     * - friendly_name: user's name (optional)
     */
    public function get_user_profile(bool $update = false, bool $strict = true): array {
        try {
            $auth = $this->create_auth_provider();
            $status = $auth->get_profile($this->user, $update);
            if (empty($status) || !isset($status['name'])) {
                throw new coding_exception('invalid response from plugin: '.$this->entity->plugin);
            }
            return $status;
        } catch (Throwable $ex) {
            if ($strict) {
                throw $ex;
            } else {
                return [];
            }
        }
    }

    /**
     * Delete the current auth token i.e. log out.
     */
    public function delete(): void {
        $this->entity->delete();
    }

    /**
     * Alias of the delete() method.
     */
    public function logout(): void {
        $this->delete();
    }
}

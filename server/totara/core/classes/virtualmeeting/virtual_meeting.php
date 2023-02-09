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
use core\orm\query\builder;
use core\orm\query\exceptions\record_not_found_exception;
use core\plugininfo\virtualmeeting as virtualmeeting_plugininfo;
use DateTime;
use stdClass;
use Throwable;
use totara_core\entity\virtual_meeting as virtual_meeting_entity;
use totara_core\http\client;
use totara_core\http\clients\curl_client;
use totara_core\virtualmeeting\dto\meeting_dto;
use totara_core\virtualmeeting\dto\meeting_edit_dto;
use totara_core\virtualmeeting\exception\auth_exception;
use totara_core\virtualmeeting\exception\base_exception;
use totara_core\virtualmeeting\exception\meeting_exception;
use totara_core\virtualmeeting\exception\not_implemented_exception;
use totara_core\virtualmeeting\exception\unsupported_exception;
use totara_core\virtualmeeting\plugin\factory\auth_factory;
use totara_core\virtualmeeting\plugin\factory\factory;
use totara_core\virtualmeeting\plugin\provider\provider;

/**
 * Virtual meeting.
 *
 * @property-read int $id
 * @property string $plugin plugin name
 * @property int $userid user id
 * @property-read int $timecreated
 * @property-read int $timemodified
 *
 * @property-read string $join_url meeting join url
 * @property-read string $host_url meeting host url
 * @property-read string $invitation meeting invitation html
 * @property-read string $preview meeting preview html
 */
final class virtual_meeting extends model {

    /** @var virtual_meeting_entity */
    protected $entity;

    /** @var virtualmeeting_plugininfo|null */
    private $plugininfo = null;

    /** @var client|null */
    private $client = null;

    /** @var string[] */
    protected $model_accessor_whitelist = [
        'name',
        'join_url',
        'host_url',
    ];

    /**
     * @return string
     * @codeCoverageIgnore
     */
    protected static function get_entity_class(): string {
        return virtual_meeting_entity::class;
    }

    /**
     * Load a model object based on the given entity.
     *
     * @param virtual_meeting_entity $entity
     * @param client|null $client
     * @return self
     */
    public static function load_by_entity(entity $entity, client $client = null): self {
        if ($client === null) {
            $client = new curl_client();
        }
        /** @var self */
        $self = parent::load_by_entity($entity);
        $self->client = $client;
        return $self;
    }

    /**
     * Load a model object based on the given id.
     *
     * @param integer $id
     * @param client|null $client
     * @return self
     */
    public static function load_by_id(int $id, client $client = null): self {
        if ($client === null) {
            $client = new curl_client();
        }
        /** @var self */
        $self = parent::load_by_id($id);
        $self->client = $client;
        return $self;
    }

    /**
     * Create a new virtual meeting instance.
     *
     * @param virtualmeeting_plugininfo|string $plugin
     * @param user_entity|stdClass|integer $user
     * @param string $name meeting name or summary
     * @param DateTime $timestart meeting start time
     * @param DateTime $timefinish meeting end time
     * @param client|null $client
     * @return self
     */
    public static function create($plugin, $user, string $name, DateTime $timestart, DateTime $timefinish, client $client = null): self {
        if ($client === null) {
            $client = new curl_client();
        }
        if ($user instanceof user_entity) {
            $userid = $user->id;
        } else if ($user instanceof stdClass) {
            $userid = $user->id;
        } else {
            $userid = (int)$user;
        }
        if (!($plugin instanceof virtualmeeting_plugininfo)) {
            $plugin = virtualmeeting_plugininfo::load_available($plugin);
        }
        $entity = builder::get_db()->transaction(
            function () use ($plugin, $userid, $name, $timestart, $timefinish, $client) {
                $entity = new virtual_meeting_entity();
                $entity->plugin = $plugin->name;
                $entity->userid = $userid;
                $entity->save();

                $factory = $plugin->create_factory();
                $provider = $factory->create_service_provider($client);
                $meeting = new meeting_edit_dto($entity, $name, $timestart, $timefinish);
                $provider->create_meeting($meeting);
                return $entity;
            }
        );
        $self = self::load_by_entity($entity, $client);
        $self->plugininfo = $plugin;
        return $self;
    }

    /**
     * Get the plugininfo instance.
     *
     * @return virtualmeeting_plugininfo
     * @codeCoverageIgnore
     */
    private function get_plugininfo(): virtualmeeting_plugininfo {
        if (!$this->plugininfo) {
            $this->plugininfo = virtualmeeting_plugininfo::load_available($this->entity->plugin);
        }
        return $this->plugininfo;
    }

    /**
     * Get the client instance.
     *
     * @return client
     * @codeCoverageIgnore
     */
    private function get_client(): client {
        if (!$this->client) {
            $this->client = new curl_client();
        }
        return $this->client;
    }

    /**
     * Create a factory instance.
     *
     * @return factory
     * @codeCoverageIgnore
     */
    private function create_factory(): factory {
        return $this->get_plugininfo()->create_factory();
    }

    /**
     * Create a service provider instance.
     *
     * @return provider
     * @codeCoverageIgnore
     */
    private function create_service_provider(): provider {
        return $this->create_factory()->create_service_provider($this->get_client());
    }

    /**
     * Get the name of the virtualmeeting plugin.
     *
     * @return string
     */
    public function get_plugin_name(): string {
        return $this->get_plugininfo()->get_name();
    }

    /**
     * Get the meeting join URL.
     *
     * @param boolean $strict blow up if a url not available
     * @return string
     */
    public function get_join_url(bool $strict = true): string {
        return $this->provider_getter_wrapper('url', $strict);
    }

    /**
     * Get the meeting host URL.
     *
     * @param boolean $strict blow up if a url not available
     * @return string
     */
    public function get_host_url(bool $strict = true): string {
        return $this->provider_getter_wrapper(provider::INFO_HOST_URL, $strict);
    }

    /**
     * Get the invitation in HTML.
     *
     * @param boolean $strict blow up if a text not available
     * @return string
     */
    public function get_invitation(bool $strict = true): string {
        return $this->provider_getter_wrapper(provider::INFO_INVITATION, $strict);
    }

    /**
     * Get the preview text in HTML.
     *
     * @param boolean $strict blow up if a text not available
     * @return string
     */
    public function get_preview(bool $strict = true): string {
        return $this->provider_getter_wrapper(provider::INFO_PREVIEW, $strict);
    }

    /**
     * Wrapper around provider::get_xxx() method.
     *
     * @param string $what 'url' or one of provider::INFO_xxx
     * @param boolean $strict
     * @return string
     */
    private function provider_getter_wrapper(string $what, bool $strict): string {
        if (!$this->entity->exists()) {
            if ($strict) {
                throw new coding_exception('record not found');
            } else {
                return '';
            }
        }
        try {
            if (!$this->is_plugin_available()) {
                throw new meeting_exception($this->entity->plugin.' plugin not available');
            }
            $provider = $this->create_service_provider();
            $meeting = new meeting_dto($this->entity);
            if ($what === 'url') {
                $result = $provider->get_join_url($meeting);
            } else {
                try {
                    $result = $provider->get_info($meeting, $what);
                } catch (not_implemented_exception $ex) {
                    throw new meeting_exception($what.' not available');
                } catch (unsupported_exception $ex) {
                    throw new meeting_exception($what.' not available');
                }
            }
            if ($result === '') {
                throw new coding_exception($what.' not available');
            }
            return $result;
        } catch (Throwable $ex) {
            if ($strict) {
                throw $ex;
            } else {
                if (!($ex instanceof meeting_exception) && !($ex instanceof auth_exception) && !($ex instanceof record_not_found_exception)) {
                    debugging('the plugin '.$this->entity->plugin.' has thrown an unacceptable exception: '.get_class($ex).': '.$ex->getMessage(), DEBUG_DEVELOPER);
                }
                return '';
            }
        }
    }

    /**
     * @return boolean
     * @internal
     */
    private function is_user_auth_required(): bool {
        // Currently, it just checks whether the factory class also implements auth_factory or not.
        // This is not contractual and might be altered in a subsequent release.
        $factory = $this->create_factory();
        return $factory instanceof auth_factory;
    }

    /**
     * @return boolean
     * @internal
     */
    private function is_plugin_available(): bool {
        if ($this->plugininfo) {
            return $this->plugininfo->is_available();
        }
        $plugins = virtualmeeting_plugininfo::get_all_plugins();
        if (!isset($plugins[$this->entity->plugin])) {
            return false;
        }
        return $plugins[$this->entity->plugin]->is_available();
    }

    /**
     * Determines whether the current user can manage the meeting instance.
     *
     * @param integer $userid
     * @return boolean
     */
    public function can_manage(int $userid): bool {
        // If the instance is gone, silently fails.
        if (!$this->entity->exists()) {
            return false;
        }
        // Check plugin availability.
        if (!$this->is_plugin_available()) {
            return false;
        }
        // If user ids are identical, the instance is manageable.
        if ($this->entity->userid == $userid) {
            return true;
        }
        // If user ids are different and the auth provider is available, the instance is not manageable.
        if ($this->is_user_auth_required()) {
            return false;
        }
        return true;
    }

    /**
     * Update the meeting instance.
     *
     * @param string $name meeting name or summary
     * @param DateTime $timestart meeting start time
     * @param DateTime $timefinish meeting end time
     */
    public function update(string $name, DateTime $timestart, DateTime $timefinish): void {
        // Check plugin availability.
        $this->get_plugininfo();
        builder::get_db()->transaction(
            function () use ($name, $timestart, $timefinish) {
                if (!$this->entity->exists()) {
                    throw new coding_exception('The meeting instance does not exist');
                }
                $provider = $this->create_service_provider();
                $meeting = new meeting_edit_dto($this->entity, $name, $timestart, $timefinish);
                $provider->update_meeting($meeting);
            }
        );
    }

    /**
     * Delete the meeting instance.
     */
    public function delete(): void {
        // Check plugin availability.
        $this->get_plugininfo();
        builder::get_db()->transaction(
            function () {
                if (!$this->entity->exists()) {
                    throw new coding_exception('The meeting instance does not exist');
                }
                $provider = $this->create_service_provider();
                $meeting = new meeting_dto($this->entity);
                $provider->delete_meeting($meeting);
                $this->entity->delete();
            }
        );
    }

    /**
     * Return true if at least one plugin is available.
     *
     * @return boolean
     */
    public static function is_any_plugin_available(): bool {
        return !empty(virtualmeeting_plugininfo::get_available_plugins());
    }

    /**
     * Get all the plugin information in a serialisable format.
     *
     * @param client|null $client set the client instance or null to use the default curl_client
     * @param boolean $sorted_by_displayname set true to sort by plugin's display name, otherwise sort by plugin name
     * @return array of {plugin_name: {name: 'display name', auth_endpoint: 'authentication URL'}, ...}
     */
    public static function get_availale_plugins_info(client $client = null, bool $sorted_by_displayname = false): array {
        if ($client === null) {
            $client = new curl_client();
        }
        $result = [];
        $plugins = virtualmeeting_plugininfo::get_available_plugins($sorted_by_displayname);
        foreach ($plugins as $plugin) {
            $data = [
                'name' => $plugin->get_name(),
            ];
            try {
                $data['auth_endpoint'] = virtual_meeting_auth::get_authentication_endpoint($plugin, $client);
            } catch (base_exception $ex) {
                // swallow exception
            }
            $result[$plugin->name] = $data;
        }
        return $result;
    }
}

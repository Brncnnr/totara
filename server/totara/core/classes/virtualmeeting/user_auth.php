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
 * @author  Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package totara_core
 */

namespace totara_core\virtualmeeting;

use coding_exception;
use core\entity\user;
use totara_core\entity\virtual_meeting_auth;
use totara_core\virtualmeeting\authoriser\authoriser;
use totara_core\virtualmeeting\exception\auth_exception;

/**
 * User authentication facility.
 */
final class user_auth {
    /** @var virtual_meeting_auth */
    private $entity;

    /**
     * Private constructor to enforce the factory pattern.
     *
     * @param virtual_meeting_auth $entity
     * @codeCoverageIgnore
     */
    private function __construct(virtual_meeting_auth $entity) {
        $this->entity = $entity;
    }

    /**
     * Load an associated token for the plugin and the user.
     *
     * @param string $plugin
     * @param user $user
     * @param boolean $strict blow up if a record not found
     * @return self|null
     */
    public static function load(string $plugin, user $user, bool $strict = false): ?self {
        $entity = virtual_meeting_auth::repository()->find_by_plugin_and_user($plugin, $user->id, $strict);
        if (!$entity) {
            return null;
        }
        return new self($entity);
    }

    /**
     * Insert a new token record.
     * Should *NOT* call this method directly in production.
     *
     * @param string $plugin plugin name
     * @param user|stdClass|integer $user
     * @param string $access_token
     * @param string $refresh_token
     * @param integer $expiry
     * @return self
     */
    public static function create(string $plugin, $user, string $access_token, string $refresh_token, int $expiry): self {
        if (is_object($user)) {
            $userid = $user->id;
        } else {
            $userid = (int)$user;
        }
        if (empty($userid)) {
            throw new coding_exception('invalid user');
        }
        $entity = new virtual_meeting_auth();
        $entity->plugin = $plugin;
        $entity->userid = $userid;
        $entity->access_token = $access_token;
        $entity->refresh_token = $refresh_token;
        $entity->timeexpiry = $expiry;
        $entity->save();
        return new self($entity);
    }

    /**
     * Load or create a token record.
     *
     * @param string $plugin
     * @param user $user
     * @param callable $callback a callback function that takes a virtual_meeting_auth entity object
     * @return self
     */
    public static function create_or_replace(string $plugin, user $user, callable $callback): self {
        $self = self::load($plugin, $user, false);
        if ($self === null) {
            $self = self::create($plugin, $user, '', '', 0);
        }
        $callback($self->entity);
        // Call entity->save() in case the callback forgets to call it.
        if ($self->entity->changed()) {
            $self->entity->save();
        }
        return $self;
    }

    /**
     * @return integer userid
     */
    public function get_userid(): int {
        return $this->entity->userid;
    }

    /**
     * Get a stored access token.
     * Note that the returned token might already be stale.
     *
     * @return string
     */
    public function get_token(): string {
        if (!$this->entity->exists()) {
            throw auth_exception::invalid_token();
        }
        if ($this->entity->is_expired()) {
            throw auth_exception::expired_token();
        }
        return $this->entity->access_token;
    }

    /**
     * Refresh and get a stored access token.
     *
     * @param authoriser $authoriser
     * @param boolean $always set true to always refresh token
     * @return string
     */
    public function get_fresh_token(authoriser $authoriser, bool $always = false): string {
        if (!$this->entity->exists()) {
            throw auth_exception::invalid_token();
        }
        if ($always || $this->entity->is_expired()) {
            $authoriser->refresh($this->entity);
            if ($this->entity->is_expired()) {
                // already expired???
                throw auth_exception::invalid_token();
            }
        }
        return $this->entity->access_token;
    }
}

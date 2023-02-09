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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package totara_msteams
 */

namespace totara_msteams\botfw\notification;

use core_user;
use dml_exception;
use stdClass;
use totara_msteams\botfw\entity\bot as bot_entity;
use totara_msteams\botfw\entity\subscription as subscription_entity;
use totara_msteams\botfw\entity\user;
use totara_msteams\botfw\exception\user_not_found_exception;

/**
 * A class that stores subscription information.
 */
class subscription {
    /** @var integer */
    private $id;

    /** @var string|null */
    private $conversation_id;

    /** @var integer */
    private $userid;

    /** @var string */
    private $lang;

    /** @var string */
    private $teams_id;

    /** @var string */
    private $channel_id;

    /** @var string */
    private $tenant_id;

    /** @var bot_entity */
    private $bot_entity;

    /**
     * Private constructor to enforce factory pattern.
     */
    private function __construct() {
        // Do nothing.
    }

    /**
     * @return integer subscription.id
     */
    public function get_id(): int {
        return $this->id;
    }

    /**
     * @return string|null MS Teams conversation id
     */
    public function get_conversation_id(): ?string {
        return $this->conversation_id;
    }

    /**
     * @return integer Totara user id
     */
    public function get_userid(): int {
        return $this->userid;
    }

    /**
     * @return string User's preferred language
     */
    public function get_lang(): string {
        return $this->lang;
    }

    /**
     * @return string MS Teams user id
     */
    public function get_teams_id(): string {
        return $this->teams_id;
    }

    /**
     * @return string MS Teams channel id
     */
    public function get_channel_id(): string {
        return $this->channel_id;
    }

    /**
     * @return string AAD tenant id
     */
    public function get_tenant_id(): string {
        return $this->tenant_id;
    }

    /**
     * @return string Bot app id
     */
    public function get_bot_id(): string {
        return $this->bot_entity->bot_id;
    }

    /**
     * @return string Bot handle name
     */
    public function get_bot_name(): string {
        return $this->bot_entity->bot_name;
    }

    /**
     * @return string Bot service URL
     */
    public function get_service_url(): string {
        return $this->bot_entity->service_url;
    }

    /**
     * Get the bot record.
     *
     * @return bot_entity
     */
    public function get_bot_record(): bot_entity {
        return clone $this->bot_entity;
    }

    /**
     * @return user
     * @throws user_not_found_exception
     */
    public function get_msuser(): user {
        $user = user::repository()->find($this->id);
        if (!$user) {
            throw new user_not_found_exception();
        }
        return $user;
    }

    /**
     * @param string $fields See core_user::get_user
     * @return stdClass user record
     * @throws user_not_found_exception
     */
    public function get_user(string $fields): stdClass {
        $user = core_user::get_user($this->userid, $fields, IGNORE_MISSING);
        if (!$user) {
            throw new user_not_found_exception();
        }
        return $user;
    }

    /**
     * @param string $conversation_id
     * @throws dml_exception
     */
    public function update_conversation_id(string $conversation_id): void {
        $entity = subscription_entity::repository()->find_or_fail($this->id);
        /** @var subscription_entity $entity */
        $entity->conversation_id = $conversation_id;
        $entity->save();
        $this->conversation_id = $conversation_id;
    }

    /**
     * Create an instance from a record.
     *
     * @param stdClass $data contains [id, conversation_id, userid, teams_id, channel_id, tenant_id, msbotid, bot_id, bot_name, service_url]
     * @return self
     */
    public static function from_record(stdClass $data): self {
        $self = new self();
        $self->id = $data->id;
        $self->conversation_id = $data->conversation_id;
        $self->userid = $data->userid;
        $self->lang = $data->lang;
        $self->teams_id = $data->teams_id;
        $self->channel_id = $data->channel_id;
        $self->tenant_id = $data->tenant_id;
        $self->bot_entity = new bot_entity();
        $self->bot_entity->bot_id = $data->bot_id;
        $self->bot_entity->bot_name = $data->bot_name;
        $self->bot_entity->service_url = $data->service_url;
        return $self;
    }
}

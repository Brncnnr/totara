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

namespace totara_msteams\botfw\storage;

use coding_exception;
use stdClass;
use totara_msteams\botfw\storage\traits\storage_bot;

/**
 * A volatile storage class.
 */
class memory_storage implements storage {
    use storage_bot;

    /**
     * The volatile storage for a bot.
     * @var array
     */
    private $bot_data = [];

    /**
     * The volatile storage for a user.
     * @var array
     */
    private $user_data = [];

    /**
     * Constructor.
     *
     * @param string $bot_app_id the bot app id
     * @param string $bot_app_secret the bot app secret
     */
    public function __construct(string $bot_app_id = '', string $bot_app_secret = '') {
        $this->initialise_bot($bot_app_id, $bot_app_secret);
    }

    /**
     * @inheritDoc
     */
    public function bot_load(string $key): ?stdClass {
        if (isset($this->bot_data[$key])) {
            return clone $this->bot_data[$key];
        }
        return null;
    }

    /**
     * @inheritDoc
     */
    public function bot_store(string $key, stdClass $data): void {
        $this->bot_data[$key] = clone $data;
    }

    /**
     * @inheritDoc
     */
    public function user_load(int $userid, string $key): ?stdClass {
        if (empty($userid)) {
            throw new coding_exception('userid cannot be zero');
        }
        if (isset($this->user_data[$userid][$key])) {
            return clone $this->user_data[$userid][$key];
        }
        return null;
    }

    /**
     * @inheritDoc
     */
    public function user_store(int $userid, string $key, stdClass $data): void {
        if (empty($userid)) {
            throw new coding_exception('userid cannot be zero');
        }
        if (!isset($this->user_data[$userid])) {
            $this->user_data[$userid] = [];
        }
        $this->user_data[$userid][$key] = clone $data;
    }
}

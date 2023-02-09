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

use stdClass;
use totara_msteams\botfw\context;

/**
 * An interface to load and store data associated to a bot.
 */
interface storage {
    /**
     * Initialiser. This function is called from a bot.
     *
     * @param context $context
     */
    public function initialise(context $context): void;

    /**
     * Return the application ID of the bot.
     *
     * @return string
     */
    public function get_app_id(): string;

    /**
     * Return the client secret of the bot.
     *
     * @return string
     */
    public function get_app_secret(): string;

    /**
     * Load bot data.
     *
     * @param string $key
     * @return stdClass|null
     */
    public function bot_load(string $key): ?stdClass;

    /**
     * Store bot data.
     *
     * @param string $key
     * @param stdClass $data
     */
    public function bot_store(string $key, stdClass $data): void;

    /**
     * Load per-user data.
     *
     * @param integer $userid Totara user id
     * @param string $key
     * @return stdClass|null
     */
    public function user_load(int $userid, string $key): ?stdClass;

    /**
     * Store per-user data.
     *
     * @param integer $userid Totara user id
     * @param string $key
     * @param stdClass $data
     */
    public function user_store(int $userid, string $key, stdClass $data): void;
}

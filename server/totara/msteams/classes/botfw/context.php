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

namespace totara_msteams\botfw;

use totara_core\http\client;
use totara_msteams\botfw\logger\logger;
use totara_msteams\botfw\storage\storage;

/**
 * The context of a bot.
 */
interface context {
    /**
     * Get the associated HTTP client interface.
     *
     * @return client
     */
    public function get_client(): client;

    /**
     * Get the associated storage interface.
     *
     * @return storage
     */
    public function get_storage(): storage;

    /**
     * Get the associated logger interface.
     *
     * @return logger
     */
    public function get_logger(): logger;

    /**
     * Get the MS Teams ID of the bot.
     * To get the app ID of the bot, use storage::get_app_id() instead.
     *
     * @return string
     * @throws bot_unavailable_exception
     */
    public function get_bot_id(): string;

    /**
     * Get the service URL of the bot.
     *
     * @return string
     */
    public function get_service_url(): string;
}

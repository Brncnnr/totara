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

namespace totara_msteams\botfw\storage\traits;

use totara_msteams\botfw\context;

/**
 * storage_bot trait
 */
trait storage_bot {
    /**
     * The bot app id.
     * @var string
     */
    private $bot_app_id;

    /**
     * The bot app secret.
     * @var string
     */
    private $bot_app_secret;

    /**
     * The bot context.
     * @var context|null
     */
    private $context = null;

    /**
     * Initialise
     *
     * @param string $bot_app_id
     * @param string $bot_app_secret
     */
    private function initialise_bot(string $bot_app_id, string $bot_app_secret): void {
        $this->bot_id = $bot_app_id ?: get_config('totara_msteams', 'bot_app_id') ?: '';
        $this->bot_secret = $bot_app_secret ?: get_config('totara_msteams', 'bot_app_secret') ?: '';
    }

    /**
     * @inheritDoc
     */
    public function initialise(context $context): void {
        $this->context = $context;
    }

    /**
     * @inheritDoc
     */
    public function get_app_id(): string {
        return $this->bot_id;
    }

    /**
     * @inheritDoc
     */
    public function get_app_secret(): string {
        return $this->bot_secret;
    }
}

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

namespace totara_msteams\botfw\repository;

use core\orm\entity\repository;
use totara_msteams\botfw\entity\bot;

/**
 * bot_repository class.
 */
class bot_repository extends repository {
    /**
     * Find a bot record by the MS Teams id of the bot.
     *
     * @param string $bot_id
     * @param boolean $strict
     * @return bot|null
     */
    public function find_by_id(string $bot_id, bool $strict = false): ?bot {
        return $this->builder->new()->where('bot_id', $bot_id)->map_to(bot::class)->one($strict);
    }
}

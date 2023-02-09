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
use core\orm\query\builder;
use totara_msteams\botfw\entity\bot_setting;

/**
 * bot_setting_repository class.
 */
class bot_setting_repository extends repository {
    /**
     * @param integer|string $botid
     * @param string $area
     * @param boolean $strict
     * @return user_setting|null
     */
    public function load($botid, string $area, bool $strict = true): ?bot_setting {
        $builder = builder::table($this->get_table())
            ->where('area', $area)
            ->map_to(bot_setting::class);

        if (is_int($botid)) {
            $builder->where('msbotid', $botid);
        } else {
            $builder->join('totara_msteams_bot', 'msbotid', 'id')
                ->where('totara_msteams_bot.bot_id', (string)$botid);
        }
        /** @var bot_setting|null $item */
        $item = $builder->one($strict);
        return $item;
    }
}

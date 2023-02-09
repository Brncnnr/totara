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
use totara_msteams\botfw\account\channel_account;
use totara_msteams\botfw\entity\channel;
use totara_msteams\botfw\entity\user;

/**
 * user_repository class.
 */
class user_repository extends repository {
    /**
     * @param channel_account|string $account channel_account record or MS Teams user ID
     * @param channel|string $channel channel instance or MS Teams channel ID
     * @param boolean $strict
     * @return user|null
     */
    public function load($account, $channel, bool $strict = true): ?user {
        if ($account instanceof channel_account) {
            $user_id = $account->id;
        } else {
            $user_id = (string)$account;
        }
        $builder = builder::table($this->get_table(), 'mu')
            ->join(['totara_msteams_channel', 'c'], 'mu.mschannelid', 'c.id')
            ->join(['user', 'u'], 'mu.userid', 'u.id')
            ->where('mu.teams_id', $user_id)
            ->select('mu.*')
            ->map_to(user::class);
        if ($channel instanceof channel) {
            $builder->where('c.id', $channel->id);
        } else {
            $builder->where('c.channel_id', (string)$channel);
        }
        /** @var user|null $item */
        $item = $builder->one($strict);
        return $item;
    }
}

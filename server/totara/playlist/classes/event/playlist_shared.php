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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package totara_playlist
 */
namespace totara_playlist\event;

use core\event\base;
use totara_engage\entity\share_recipient;
use totara_engage\share\share as share_model;

final class playlist_shared extends base {
    /**
     * @return void
     */
    protected function init(): void {
        $this->data['objecttable'] = share_recipient::TABLE;
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['crud'] = 'c';
    }

    /**
     * Create an event for a share recipient.
     *
     * @param share_model $share
     * @param \context $context
     * @param int|null $userid
     *
     * @return base_playlist
     */
    public static function from_share(share_model $share, $context, ?int $userid = null): playlist_shared {
        if (null == $userid) {
            $userid = $share->get_sharer_id();
        }

        $data = [
            'objectid' => $share->get_recipient_id(),
            'userid' => $userid,
            'context' => $context
        ];

        /** @var base_playlist $event */
        $event = static::create($data);
        return $event;
    }

    /**
     * @return string
     */
    public static function get_name(): string {
        return get_string('playlistshared', 'totara_playlist');
    }
}
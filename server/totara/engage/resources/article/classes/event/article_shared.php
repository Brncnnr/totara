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
 * @package engage_article
 */

namespace engage_article\event;

use core\event\base;
use totara_engage\entity\share_recipient;
use totara_engage\share\share as share_model;

final class article_shared extends base {
    /**
     * @return void
     */
    protected function init(): void {
        $this->data['objecttable'] = share_recipient::TABLE;
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
    }

    /**
     * Create an event for a share recipient.
     *
     * @param share_model $share
     * @param int|null $actorid
     * @return base_article_event
     */
    public static function from_share(share_model $share, int $actorid = null): article_shared {
        if (null == $actorid) {
            $actorid = $share->get_sharer_id();
        }

        $context = \context_user::instance($share->get_sharer_id());

        $data = [
            'objectid' => $share->get_recipient_id(),
            'context' => $context,
            'userid' => $actorid,
        ];

        /** @var base_article_event $event */
        $event = static::create($data);
        return $event;
    }

    /**
     * @return string
     */
    public static function get_name() {
        return get_string('articleshared', 'engage_article');
    }
}
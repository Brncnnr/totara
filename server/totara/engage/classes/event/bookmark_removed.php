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
 * @package totara_engage
 */

namespace totara_engage\event;

use core\event\base;
use totara_engage\bookmark\bookmark;
use totara_engage\entity\engage_bookmark;

class bookmark_removed extends base {

    /**
     * @inheritDoc
     */
    protected function init() {
        $this->data['objecttable'] = engage_bookmark::TABLE;
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['crud'] = 'c';
    }

    /**
     * @param bookmark $bookmark
     * @return bookmark_removed
     */
    public static function from_bookmark(bookmark $bookmark): bookmark_removed {
        $userid = $bookmark->get_userid();
        $context = \context_user::instance($userid);

        $data = [
            'objectid' => $bookmark->get_itemid(),
            'context' => $context,
            'userid' => $userid,
            'other' => [
                'component' => $bookmark->get_component(),
            ]
        ];

        /** @var bookmark_removed $event */
        $event = static::create($data);
        return $event;
    }
}
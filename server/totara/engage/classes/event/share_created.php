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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package totara_engage
 */
namespace totara_engage\event;

use core\event\base;
use totara_engage\entity\share as share_entity;
use totara_engage\share\share;

final class share_created extends base {
    /**
     * @inheritDoc
     */
    protected function init() {
        $this->data['objecttable'] = share_entity::TABLE;
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['crud'] = 'c';
    }

    public static function from_share(share $share): share_created {
        $user_id = $share->get_sharer_id();
        $user_context = \context_user::instance($user_id);

        $data = [
            'objectid' => $share->get_item_id(),
            'context' => $user_context,
            'userid' => $user_id,
            'other' => [
                'area' => $share->get_recipient_area(),
                'component' => $share->get_component(),
                'recipient_id' => $share->get_recipient_instanceid(),
                'recipient_component' => $share->get_recipient_component(),
            ]
        ];

        /** @var share_created $event */
        $event = static::create($data);
        return $event;
    }
}
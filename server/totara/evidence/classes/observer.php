<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_evidence
 */

namespace totara_evidence;

use core\event\user_deleted;
use core\orm\query\builder;
use totara_evidence\customfield_area\field_helper;
use totara_evidence\entity\evidence_item;

class observer {

    /**
     * Removes relevant evidence data pertaining to a user
     *
     * @param user_deleted $event
     */
    public static function user_deleted(user_deleted $event): void {
        $user_id = $event->objectid;
        builder::get_db()->transaction(static function () use ($user_id): void {
            $user_evidence_items = evidence_item::repository()->where('user_id', $user_id)->get_lazy();
            foreach ($user_evidence_items as $item) {
                /** @var evidence_item $item */
                foreach ($item->data as $data) {
                    field_helper::get_field_instance($data)->delete();
                }
                $item->delete();
            }
        });
    }

}

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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_engage
 */
namespace totara_engage\observer;

use totara_engage\event\clear_bookmark;
use totara_engage\entity\share;
use totara_engage\entity\engage_bookmark;
use totara_engage\resource\resource_item;
use totara_engage\share\provider;
use totara_engage\access\access_manager;

class clear_bookmark_observer {
    /**
     * @param clear_bookmark $event
     * @return void
     */
    public static function clear_bookmark(clear_bookmark $event): void {
        if (!$event->is_to_clear()) {
            return;
        }

        $user_ids = $event->get_target_user_ids();
        if (empty($user_ids)) {
            // No target users that we want to clear.
            return;
        }

        $share_repository = share::repository();
        $bookmark_repository = engage_bookmark::repository();

        $instance_identifier = $event->get_instance_identifier();
        $share_entities = $share_repository->get_shares_by_recipient(
            $instance_identifier->get_instance_id(),
            $instance_identifier->get_component(),
            $instance_identifier->get_area()
        );

        foreach ($share_entities as $share_entity) {
            foreach ($user_ids as $user_id) {
                if ($share_entity->ownerid == $user_id) {
                    continue;
                }

                if (!$bookmark_repository->is_bookmarked($user_id, $share_entity->itemid, $share_entity->component)) {
                    continue;
                }

                // Get accessible item to check user still can access the item or not.
                /** @var resource_item $resource */
                $resource = provider::create($share_entity->component)->get_item_instance($share_entity->itemid);
                if (access_manager::can_access($resource, $user_id)) {
                    continue;
                }

                $bookmark_repository->delete_bookmark($user_id, $share_entity->itemid, $share_entity->component);
            }
        }
    }
}
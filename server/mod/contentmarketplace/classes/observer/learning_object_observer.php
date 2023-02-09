<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @package mod_contentmarketplace
 */
namespace mod_contentmarketplace\observer;

use mod_contentmarketplace\entity\content_marketplace;
use totara_contentmarketplace\event\base_learning_object_updated;

/**
 * Class learning_object_observer
 */
final class learning_object_observer {
    /**
     * learning_object_observer constructor.
     */
    private function __construct() {
    }

    /**
     * @param base_learning_object_updated $event
     */
    public static function on_learning_object_updated(base_learning_object_updated $event): void {
        $learning_object_id = $event->objectid;
        $marketplace_component = $event->get_marketplace_component();

        $entities = content_marketplace::repository()->fetch_by_id_and_component(
            $learning_object_id,
            $marketplace_component
        );

        foreach ($entities as $entity) {
            if ($entity->name != $event->get_learning_object_name()) {
                $entity->name = $event->get_learning_object_name();
                $entity->update();
            }
        }
    }
}
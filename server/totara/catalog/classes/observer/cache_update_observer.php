<?php
/*
 * This file is part of Totara LMS
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
 * @author David Curry <david.curry@totaralearning.com>
 * @package totara_catalog
 */

namespace totara_catalog\observer;

use core\event\tenant_created;
use core\event\tenant_deleted;
use core\event\base as event_base;

/**
 * Observer to catch changes to catalog caches.
 */
class cache_update_observer {

    /**
     * Observer to update the container ids in course catalog caches.
     *
     * @param tenant_created|tenant_deleted $event
     * @return void
     */
    public static function update_catalog_container_cache(event_base $event) {
        $eventclass = get_class($event);

        // Just check it's one of the two expected classes.
        if ($eventclass == tenant_created::class || $eventclass == tenant_deleted::class) {
            $containers = \core_container\container_category_helper::get_container_category_ids();

            $catscache = \cache::make('core', 'coursecat');
            $catscache->set('containerids', $containers);
        }
    }
}

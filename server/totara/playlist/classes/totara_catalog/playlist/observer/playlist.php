<?php
/*
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author David Curry <david.curry@totaralearning.com>
 * @package totara_playlist
 * @category totara_catalog
 */

namespace totara_playlist\totara_catalog\playlist\observer;

defined('MOODLE_INTERNAL') || die();

use totara_catalog\observer\object_update_observer;
use core_user\totara_engage\share\recipient\user;

/**
 * update catalog data
 */
class playlist extends object_update_observer {

    public function get_observer_events(): array {
        return [
            '\totara_playlist\event\playlist_created',
            '\totara_playlist\event\playlist_updated',
        ];
    }

    /**
     * Adds or updates an items visibility cache
     */
    private function refresh_item_cache(): void {
        global $USER;
        $cache = \cache::make('totara_playlist', 'catalog_visibility');
        $cache->set($USER->id, null);
    }

    /**
     * init playlist update object for created or updated playlist
     */
    protected function init_change_objects(): void {
        $this->refresh_item_cache();

        $data = new \stdClass();
        $data->objectid = $this->event->objectid;
        $data->contextid = $this->event->contextid;
        $this->register_for_update($data);
    }
}

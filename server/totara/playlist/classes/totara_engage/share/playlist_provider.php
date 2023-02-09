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

namespace totara_playlist\totara_engage\share;

use totara_engage\share\shareable;
use totara_playlist\playlist;
use totara_engage\share\provider;

final class playlist_provider extends provider {

    /**
     * @inheritDoc
     */
    public function get_item_instance(int $id): shareable {
        return playlist::from_id($id, true);
    }

    /**
     * @param playlist|shareable    $instance
     * @param int                   $access
     * @param int                   $userid
     *
     * @return void
     */
    public function update_access(shareable $instance, int $access, int $userid): void {
        $instance->update(null, $access, null, null, $userid);
    }

    /**
     * @return string
     */
    public function get_provider_type(): string {
        return 'playlist';
    }
}
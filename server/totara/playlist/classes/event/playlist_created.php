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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_playlist
 */
namespace totara_playlist\event;

use totara_playlist\playlist;

final class playlist_created extends base_playlist {
    /**
     * @return void
     */
    protected function init(): void {
        parent::init();
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['crud'] = 'c';
    }

    /**
     * @param playlist $playlist
     * @param int|null $userid
     *
     * @return base_playlist
     */
    public static function from_playlist(playlist $playlist, ?int $userid = null): base_playlist {
        if (null == $userid) {
            // This is an event for just created playlist, therefore, the actor MUST
            // be the owner of the  playlist, if it is not set.
            $userid = $playlist->get_userid();
        }

        return parent::from_playlist($playlist, $userid);
    }

    /**
     * @return string
     */
    public static function get_name(): string {
        return get_string('playlistcreated', 'totara_playlist');
    }

    /**
     * @return string
     */
    public function get_interaction_type(): string {
        return 'create';
    }
}
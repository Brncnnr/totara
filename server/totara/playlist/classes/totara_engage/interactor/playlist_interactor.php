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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package totara_playlist
 */

namespace totara_playlist\totara_engage\interactor;

use totara_engage\access\access;
use totara_engage\access\accessible;
use totara_engage\interactor\interactor;
use totara_playlist\playlist;

final class playlist_interactor extends interactor {

    /**
     * @inheritDoc
     */
    public static function create_from_accessible(accessible $resource, ?int $actor_id = null): interactor {
        if (!($resource instanceof playlist)) {
            throw new \coding_exception('Invalid accessible resource for playlist interactor');
        }

        /** @var playlist $playlist */
        $playlist = $resource;

        return new self(
            [
                'access' => $playlist->get_access(),
                'userid' => $playlist->get_userid(),
                'id' => $playlist->get_id(),
                'resourcetype' => 'totara_playlist',
            ],
            $actor_id
        );
    }

    /**
     * Check if the logged in user can rate the playlist.
     *
     * @return bool
     */
    public function can_rate(): bool {
        // Do not allow guest users to rate the playlist.
        if (isguestuser($this->actor_id)) {
            return false;
        }

        // Private playlists cannot be rated.
        if (access::is_private($this->resource['access'])) {
            return false;
        }

        return true;
    }

    /**
     * Check if the logged in user can react on the playlist.
     *
     * @return bool
     */
    public function can_react(): bool {
        // Playlist does not allow reactions.
        return false;
    }

    /**
     * @return bool
     */
    public function can_add_resource(): bool {
        return has_any_capability(
            ['engage/article:share', 'engage/survey:share', 'totara/playlist:share'],
            playlist::from_id($this->resource['id'])->get_context(),
            $this->get_actor_id()
        );
    }

    /**
     * @inheritDoc
     */
    public function to_array(): array {
        $array = parent::to_array();
        $array['can_rate'] = $this->can_rate();
        $array['can_add_resource'] = $this->can_add_resource();
        return $array;
    }

}
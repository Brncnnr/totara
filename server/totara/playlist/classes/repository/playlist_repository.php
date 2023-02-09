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
namespace totara_playlist\repository;

use core\orm\entity\repository;
use core\orm\query\builder;
use totara_playlist\entity\playlist as playlist_entity;
use totara_playlist\entity\playlist_resource;
use totara_playlist\playlist;

/**
 * Playlist repository
 */
final class playlist_repository extends repository {
    /**
     * Find all playlists for a specific resource
     * @param int $resourceid
     * @return array
     */
    public function find_all_for_resource(int $resourceid): array {
        $builder = builder::table(static::get_table(), 'p')
            ->map_to(playlist_entity::class)
            ->join([playlist_resource::TABLE, 'pr'], 'p.id', 'pr.playlistid')
            ->where('pr.resourceid', $resourceid);

        return $builder->fetch();
    }

    /**
     * @param int $user_id
     * @return int
     */
    public function count_playlists_by_userid(int $user_id): int {
        return builder::table(static::get_table(), 'p')
            ->where('userid', $user_id)
            ->count();
    }

    /**
     * @param int $user_id
     * @return array|playlist_entity[]
     */
    public function get_playlists_by_userid(int $user_id): array {
        /** @var playlist_entity[] $playlists*/
        $playlists = builder::table(static::get_table(), 'p')
            ->map_to(playlist_entity::class)
            ->where('userid', $user_id)
            ->fetch();

        return $playlists;
    }

    /**
     * Get all playlist models.
     * @param int $user_id
     * @return array|playlist[]
     */
    public function load_models_by_userid(int $user_id): array {
        $builder = builder::table(static::get_table());
        $builder->results_as_arrays();
        $builder->map_to(
            function (array $record) {
                $playlist = new playlist_entity($record);

                return playlist::from_entity($playlist);
            }
        );
        $builder->where('userid', $user_id);

        /** @var playlist[] $playlists*/
        $playlists = $builder->fetch();
        return $playlists;
    }
}
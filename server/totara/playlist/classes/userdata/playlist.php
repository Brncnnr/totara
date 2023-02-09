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
 * @package totara_playlist
 */
namespace totara_playlist\userdata;

use totara_playlist\entity\playlist_resource;
use totara_playlist\local\helper;
use totara_playlist\repository\playlist_repository;
use totara_playlist\entity\playlist as playlist_entity;
use totara_playlist\repository\playlist_resource_repository;
use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

final class playlist extends item {
    /**
     * @param int $user_status
     * @return bool
     */
    public static function is_purgeable(int $user_status) {
        return true;
    }

    /**
     * @return bool
     */
    public static function is_exportable() {
        return true;
    }

    /**
     * @return bool
     */
    public static function is_countable() {
        return true;
    }

    /**
     * @param target_user $user
     * @param \context $context
     *
     * @return int
     */
    protected static function count(target_user $user, \context $context): int {
        /** @var playlist_repository $repo */
        $repo = playlist_entity::repository();
        return $repo->count_playlists_by_userid((int)$user->id);
    }

    /**
     * @param target_user $user
     * @param \context $context
     *
     * @return int
     */
    protected static function purge(target_user $user, \context $context): int {
        /** @var playlist_repository $repo */
        $repo = playlist_entity::repository();
        $playlists = $repo->load_models_by_userid((int)$user->id);

        foreach ($playlists as $playlist) {
            helper::purge_playlist($playlist, (int)$user->id);
        }

        return self::RESULT_STATUS_SUCCESS;
    }

    /**
     * @param target_user $user
     * @param \context $context
     *
     * @return export
     */
    protected static function export(target_user $user, \context $context): export {
        $repo = playlist_entity::repository();
        $playlists = $repo->get_playlists_by_userid((int)$user->id);

        $export = new export();
        $export->data = [];

        /** @var playlist_resource_repository $repo */
        $repo = playlist_resource::repository();
        foreach ($playlists as $playlist) {
            $export->data[] = [
                'name' => $playlist->name,
                'summary' => content_to_text($playlist->summary, $playlist->summaryformat),
                'time_created' => $playlist->timecreated,
                'Numberofresources' => $repo->get_total_of_resources($playlist->id)
            ];
        }

        return $export;
    }
}
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
 * @package totara_playlist
 */
namespace totara_playlist\webapi\resolver\query;

use core\webapi\execution_context;
use core\webapi\query_resolver;
use core\webapi\middleware\require_login;
use totara_engage\access\accessible;
use totara_playlist\playlist;
use totara_engage\access\access_manager;
use totara_engage\share\provider as share_provider;

class check_items_access extends query_resolver {
    /**
     * @param array $args
     * @param execution_context $ec
     *
     * @return array
     */
    public static function resolve(array $args, execution_context $ec): array {
        global $USER;

        $playlist_id = $args['playlist_id'];
        $playlist = playlist::from_id($playlist_id);

        if (!$playlist->is_available()) {
            throw new \coding_exception("The playlist is no longer available");
        } else if (!access_manager::can_access($playlist, $USER->id)) {
            throw new \coding_exception("The playlist is not accessible by the user");
        }

        $warning = false;
        $warning_message = "";

        $items = $args['items'];
        foreach ($items as $item) {
            $provider = share_provider::create($item['component']);
            $instance = $provider->get_item_instance($item['itemid']);

            if ($instance instanceof accessible) {
                if ($playlist->is_public() && !$instance->is_public()) {
                    $warning = true;
                    $warning_message = get_string('warning_change_to_public', 'totara_playlist');
                    break;
                } else if ($playlist->is_restricted() && !$instance->is_public()) {
                    // Playlist is restricted and one of the instance is not public then we can just give out warning.
                    $warning = true;
                    $warning_message = get_string('warning_change_to_restricted', 'totara_playlist');
                    break;
                }
            }

            // We skip for those that are not accessible for now.
        }

        return [
            'warning' => $warning,
            'message' => $warning_message
        ];
    }

    /**
     * @return array
     */
    public static function get_middleware(): array {
        return [
            new require_login()
        ];
    }
}
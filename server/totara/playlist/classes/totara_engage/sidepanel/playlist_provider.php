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
namespace totara_playlist\totara_engage\sidepanel;

use totara_engage\access\access_manager;
use totara_engage\sidepanel\provider;
use totara_playlist\playlist;

final class playlist_provider extends provider {

    /**
     * @inheritDoc
     */
    public static function provide_navigation_section(): bool {
        return true;
    }

    /**
     * @inheritDoc
     */
    function get_navigation_section(): array {
        return [
            'component' => 'PlaylistNavigation',
            'tuicomponent' => 'totara_playlist/components/sidepanel/PlaylistNavigation',
            'showcontribute' => $this->show_create_button(),
            'can_share' => $this->can_share()
        ];
    }

    /**
     * @return bool
     */
    function show_create_button(): bool {
        global $USER;

        return playlist::can_create($USER->id);
    }

    /**
     * @return bool
     */
    function can_share(): bool {
        global $USER;
        $context = \context_user::instance($USER->id);
        if (access_manager::can_manage_engage($context, $USER->id)) {
            return true;
        }

        return has_capability('totara/playlist:share', $context, $USER->id);
    }
}
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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package totara_playlist
 */

namespace totara_playlist\event;

use core_ml\event\interaction_event;

/**
 * An event for resharing the playlist (when a non-owner shares the playlist)
 */
final class playlist_reshared extends base_playlist implements interaction_event {
    public function get_interaction_type(): string {
        return 'reshare';
    }

    /**
     * @return void
     */
    protected function init(): void {
        parent::init();
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['crud'] = 'c';
    }
}
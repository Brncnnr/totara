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
namespace totara_playlist\totara_topic;

use totara_playlist\playlist;
use totara_topic\resolver\resolver;
use totara_topic\topic;

/**
 * Resolver for playlist's topic.
 */
final class topic_resolver extends resolver {
    /**
     * @param topic       $topic
     * @param int         $itemid
     * @param int         $actorid
     * @param string      $itemtype
     *
     * @return bool
     */
    public function can_add_usage(topic $topic, int $itemid, string $itemtype, int $actorid): bool {
        if ('playlist' != $itemtype) {
            debugging("Invalid itemtype '{$itemtype}'", DEBUG_DEVELOPER);
            return false;
        }

        $playlist = playlist::from_id($itemid);

        if (!$playlist->can_update($actorid)) {
            return false;
        }

        return true;
    }

    /**
     * @param topic  $topic
     * @param int    $instanceid
     * @param int    $actorid
     * @param string $itemtype
     *
     * @return bool
     */
    public function can_delete_usage(topic $topic, int $instanceid, string $itemtype, int $actorid): bool {
        if ('playlist' != $itemtype) {
            debugging("Invalid itemtype '{$itemtype}'", DEBUG_DEVELOPER);
            return false;
        }

        $playlist = playlist::from_id($instanceid);

        if (!$playlist->can_update($actorid)) {
            return false;
        }

        return true;
    }

    /**
     * @param int         $itemid
     * @param string|null $itemtype
     *
     * @return \context
     */
    public function get_context_of_item(int $itemid, ?string $itemtype = null): \context {
        $playlist = playlist::from_id($itemid);
        $userid = $playlist->get_userid();

        return \context_user::instance($userid);
    }
}
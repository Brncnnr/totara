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

namespace totara_playlist\observer;

use engage_article\event\article_deleted;
use engage_article\event\article_updated;
use totara_playlist\entity\playlist as entity;
use totara_playlist\local\image_processor;
use totara_playlist\playlist;

/**
 * Observe changes to articles & update the images on attached playlists
 *
 * @package totara_playlist\observer
 */
final class image_observer {
    /**
     * @param article_deleted $event
     */
    public static function article_deleted(article_deleted $event) {
        // Find the playlist this event applies to
        $resource_id = $event->other['resourceid'];
        self::update_playlist_image($resource_id);
    }

    /**
     * @param article_updated $event
     */
    public static function article_updated(article_updated $event) {
        // Find the playlist this event applies to
        $resource_id = $event->other['resourceid'];
        self::update_playlist_image($resource_id);
    }

    /**
     * Looks up the the playlist(s) attached to the article & triggers a image rewrite
     *
     * @param int $resource_id
     */
    private static function update_playlist_image(int $resource_id): void {
        $playlist_repo = entity::repository();

        /** @var entity[] $playlist_entities */
        $playlist_entities = $playlist_repo->find_all_for_resource($resource_id);
        $playlists = [];
        foreach ($playlist_entities as $playlist_entity) {
            $playlists[] = playlist::from_entity($playlist_entity, true);
        }

        $processor = image_processor::make();
        foreach ($playlists as $playlist) {
            $processor->update_playlist_images($playlist);
        }
    }
}
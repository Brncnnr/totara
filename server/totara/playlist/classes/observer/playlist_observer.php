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
namespace totara_playlist\observer;

use engage_article\event\article_deleted;
use engage_survey\event\survey_deleted;
use ml_recommender\local\seen_recommended_item;
use totara_playlist\entity\playlist_resource;
use totara_playlist\event\playlist_created;
use totara_playlist\event\playlist_updated;
use totara_playlist\playlist;
use totara_core\content\content_handler;
use totara_playlist\repository\playlist_resource_repository;
use totara_playlist\event\playlist_viewed;

final class playlist_observer {
    /**
     * @param article_deleted $event
     */
    public static function resource_article_deleted(article_deleted $event): void {
        /** @var playlist_resource_repository $repo */
        $repo = playlist_resource::repository();

        $others = $event->other;
        $repo->delete_resource_by_resourceid($others['resourceid']);
    }

    /**
     * @param survey_deleted $event
     */
    public static function resource_survey_deleted(survey_deleted $event): void {
        /** @var playlist_resource_repository $repo */
        $repo = playlist_resource::repository();

        $others = $event->other;
        $repo->delete_resource_by_resourceid($others['resourceid']);
    }

    /**
     * @param playlist_created $event
     * @return void
     */
    public static function on_created(playlist_created $event): void {
        $playlist = playlist::from_id($event->objectid);
        static::handle_playlist($playlist, $event->get_user_id());
    }

    /**
     * @param playlist_updated $event
     * @return void
     */
    public static function on_updated(playlist_updated $event): void {
        $playlist = playlist::from_id($event->objectid);
        static::handle_playlist($playlist, $event->userid);
    }

    /**
     * @param playlist_viewed $event
     * @return void
     */
    public static function on_viewed(playlist_viewed $event): void {
        // Flag playlist as seen if it is on users recommendations list.
        seen_recommended_item::seen_recommended_playlist($event);
    }

    /**
     * Pass content through content handlers.
     *
     * @param playlist $playlist
     * @param int|null $user_id
     *
     * @return void
     */
    private static function handle_playlist(playlist $playlist, ?int $user_id = null): void {
        $handler = content_handler::create();
        $handler->handle_with_params(
            $playlist->get_name(),
            $playlist->get_summary(),
            $playlist->get_summaryformat(),
            $playlist->get_id(),
            'totara_playlist',
            'playlist',
            $playlist->get_context()->id,
            $playlist->get_url(),
            $user_id
        );
    }
}
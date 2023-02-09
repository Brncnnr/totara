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
namespace totara_playlist\local;

use totara_comment\comment_helper;
use totara_engage\entity\rating;
use totara_engage\rating\rating_manager;
use totara_engage\resource\resource_factory;
use totara_engage\share\manager as share_manager;
use totara_playlist\entity\playlist_resource;
use totara_playlist\exception\playlist_exception;
use totara_playlist\playlist;
use totara_playlist\repository\playlist_resource_repository;

final class helper {
    /**
     * helper constructor.
     */
    private function __construct() {
        // Prevent the construction directly.
    }

    /**
     * @param int $playlist_id
     */
    public static function decrement_resource_usage_for_playlist(int $playlist_id): void {
        /** @var playlist_resource_repository $repo */
        $repo = playlist_resource::repository();
        $resources = $repo->get_all_for_playlist($playlist_id);

        if (empty($resources)) {
            return;
        }

        // Delete the resource usage.
        foreach ($resources as $resource) {
            $resource_item = resource_factory::create_instance_from_id($resource->resourceid);
            $resource_item->decrease_resource_usage();
        }
    }

    /**
     * It's for purging playlist and all related to playlist.
     *
     * @param playlist $playlist
     * @param int $user_id
     * @return void
     */
    public static function purge_playlist(playlist $playlist, int $user_id): void {
        global $DB;

        // Decrement usage.
        static::decrement_resource_usage_for_playlist($playlist->get_id());

        // Deleting comments.
        comment_helper::purge_area_comments(
            playlist::get_resource_type(),
            'comment',
            $playlist->get_id()
        );

        // Deleting ratings.
        rating_manager::instance(
            $playlist->get_id(),
            'totara_playlist',
            playlist::RATING_AREA
        )->delete();

        // Deleting shares.
        share_manager::delete($playlist->get_id(), playlist::get_resource_type());

        // Delete the banner image.
        $processor = image_processor::make();
        $images = [
            $processor->get_image_for_playlist($playlist),
            $processor->get_image_for_playlist($playlist, true)
        ];
        foreach ($images as $image) {
            if ($image) {
                $image->delete();
            }
        }

        // Delete resource links from the playlist.
        /** @var playlist_resource_repository $repo */
        $repo = playlist_resource::repository();
        $repo->delete_resources_by_playlistid($playlist->get_id());

        // Delete rating made by that user.
        $DB->delete_records(rating::TABLE, ['userid' => $user_id]);

        //Delete itself.
        $DB->delete_records('playlist', ['id' => $playlist->get_id()]);
    }

    /**
     * @param playlist $playlist
     * @param int $instanceid
     * @param int $order
     * @return void
     */
    public static function swap_card_sort_order(playlist $playlist, int $instanceid, int $order) {
        if ($order < 0 || $order > (count($playlist->get_resources()) - 1)) {
            throw playlist_exception::create("update_order");
        }

        /** @var playlist_resource_repository $repo */
        $repo = playlist_resource::repository();
        $target_resource = $repo->find_resource($instanceid, $playlist->get_id());

        if (empty($target_resource)) {
            throw new \coding_exception("Resource with {$instanceid} is not in the playlist");
        }

        $resources = array_values($playlist->get_resources());
        $source_resource = $resources[$order];
        $source_order = (int)$source_resource->sortorder;
        $target_order = (int)$target_resource->sortorder;

        // If the sort orders differ by one place, we can just swap them and save time.
        if (abs($source_order - $target_order) === 1) {
            $inner_order = $source_order;
            $source_resource->sortorder = $target_order;
            $target_resource->sortorder = $inner_order;

            $source_resource->save();
            $target_resource->save();

            return;
        }

        $sort_orders = [$target_order, $source_order];
        sort($sort_orders);

        $filtered_resources = array_filter($playlist->get_resources(), function ($resource) use ($sort_orders): bool {
            $sort_order = (int)$resource->sortorder;
            return $sort_order >= $sort_orders[0] && $sort_order <= $sort_orders[1];
        });

        foreach ($filtered_resources as $resource) {
            if ($resource->resourceid == $target_resource->resourceid) {
                $target_resource->sortorder = $source_order;
                $target_resource->save();
            } else {
                $resource->sortorder += ($target_order <=> $source_order);
                $resource->save();
            }
        }
    }
}
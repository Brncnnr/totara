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
 * @package ml_recommender
 */

namespace ml_recommender\loader\recommended_item;

use core\orm\pagination\offset_cursor_paginator;
use core\orm\query\builder;
use ml_recommender\entity\recommended_item;
use ml_recommender\entity\recommended_user_item;
use ml_recommender\query\recommended_item\item_query;
use ml_recommender\query\recommended_item\user_query;
use ml_recommender\recommendations;
use totara_engage\access\access;
use totara_engage\card\card_resolver;
use totara_playlist\entity\playlist as playlist_entity;
use totara_playlist\playlist;

/**
 * Loader class for a recommended item
 */
final class playlists_loader extends loader {
    /**
     * @var recommendations|null
     */
    protected static $recommendations_helper;

    /**
     * Select all related playlists based on the provided component id
     *
     * @param item_query $query
     * @param int $actor_id
     * @return offset_cursor_paginator
     */
    public static function get_recommended(item_query $query, int $actor_id = 0): offset_cursor_paginator {
        $builder = self::get_base_playlist_query(recommended_item::TABLE, $actor_id);

        // If the ml service is enabled, then we link to that first
        if (recommendations::is_ml_service_enabled()) {
            $helper = self::$recommendations_helper ?? recommendations::make($actor_id);
            $recommendations = $helper->get_similar_items($query->get_target_component(), $query->get_target_item_id());
            $builder->where_in('p.id', $recommendations ?? []);
            $helper->apply_sort_by_recommendations($builder, 'p.id', $recommendations);
        } else {
            $builder->where('r.target_item_id', $query->get_target_item_id());
            $builder->where('r.target_component', $query->get_target_component());
            $builder->where('r.target_area', $query->get_target_area());
        }

        $cursor = $query->get_cursor();
        return new offset_cursor_paginator($builder, $cursor);
    }

    /**
     * Select all recommended playlists for the user
     *
     * @param user_query $query
     * @param int $actor_id
     * @return offset_cursor_paginator
     */
    public static function get_recommended_for_user(user_query $query, int $actor_id = 0): offset_cursor_paginator {
        $builder = self::get_base_playlist_query(recommended_user_item::TABLE, $actor_id);

        // If the ml service is enabled, then we link to that first
        if (recommendations::is_ml_service_enabled()) {
            $helper = self::$recommendations_helper ?? recommendations::make($actor_id);
            $recommendations = $helper->get_user_recommendations($query->get_target_component());
            $builder->where_in('p.id', $recommendations ?? []);
            $helper->apply_sort_by_recommendations($builder, 'p.id', $recommendations);
        } else {
            $builder->where('r.user_id', $query->get_target_user_id());
            $builder->where('r.component', $query->get_target_component());
            $builder->where('r.area', $query->get_target_area());
        }

        $cursor = $query->get_cursor();
        return new offset_cursor_paginator($builder, $cursor);
    }

    /**
     * @param string $table
     * @param int $actor_id
     * @return builder
     */
    private static function get_base_playlist_query(string $table, int $actor_id = 0): builder {
        // If we're using the new service, then don't join on the recommendations table
        if (recommendations::is_ml_service_enabled()) {
            $builder = builder::table(playlist_entity::TABLE, 'p');
            $builder->select([
                'p.*',
                'p.id as instanceid',
            ]);
            $builder->add_select_raw("'totara_playlist' as component");
        } else {
            // Fall back to the legacy service instead
            $builder = builder::table($table, 'r');

            // Join against playlists
            $builder->join([playlist_entity::TABLE, 'p'], 'r.item_id', 'p.id');
            $builder->select([
                'p.*',
                'p.id as instanceid',
                'r.component as component'
            ]);
            $builder->where('r.component', 'totara_playlist');
            $builder->order_by_raw('r.score DESC');
        }

        $builder->where('p.access', access::PUBLIC);
        $builder->results_as_arrays();

        self::filter_multi_tenancy($builder, 'p.userid', $actor_id);

        $builder->map_to(
            function (array $record) {
                return card_resolver::create_card(playlist::get_resource_type(), $record);
            }
        );

        return $builder;
    }
}
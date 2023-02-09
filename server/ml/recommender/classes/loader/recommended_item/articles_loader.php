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
use engage_article\totara_engage\resource\article;
use ml_recommender\entity\recommended_item;
use ml_recommender\entity\recommended_user_item;
use ml_recommender\local\environment;
use ml_recommender\query\recommended_item\item_query;
use ml_recommender\query\recommended_item\user_query;
use ml_recommender\recommendations;
use totara_engage\access\access;
use totara_engage\card\card_resolver;
use totara_engage\entity\engage_resource;

/**
 * Loader class for a recommended item
 */
final class articles_loader extends loader {
    /**
     * @var recommendations|null
     */
    protected static $recommendations_helper;

    /**
     * Get the related articles for the provided component & id
     *
     * @param item_query $query
     * @param int $actor_id
     * @return offset_cursor_paginator
     */
    public static function get_recommended(item_query $query, int $actor_id = 0): offset_cursor_paginator {
        $builder = self::get_base_article_query(recommended_item::TABLE, $actor_id);

        // If the ml service is enabled, then we link to that first
        if (recommendations::is_ml_service_enabled()) {
            $helper = self::$recommendations_helper ?? recommendations::make($actor_id);
            $recommendations = $helper->get_similar_items($query->get_target_component(), $query->get_target_item_id());
            $builder->where_in('er.id', $recommendations ?? []);
            $helper->apply_sort_by_recommendations($builder, 'er.id', $recommendations);
        } else {
            $builder->where('r.target_item_id', $query->get_target_item_id());
            $builder->where('r.target_component', $query->get_target_component());
            $builder->where('r.target_area', $query->get_target_area());
        }

        $cursor = $query->get_cursor();
        $cursor->set_limit(environment::get_related_items_count());
        return new offset_cursor_paginator($builder, $cursor);
    }

    /**
     * Get the articles recommended for the provided user
     *
     * @param user_query $query
     * @param int $actor_id
     * @return offset_cursor_paginator
     */
    public static function get_recommended_for_user(user_query $query, int $actor_id = 0): offset_cursor_paginator {
        $builder = self::get_base_article_query(recommended_user_item::TABLE, $actor_id);
        if (recommendations::is_ml_service_enabled()) {
            $helper = self::$recommendations_helper ?? recommendations::make($query->get_target_user_id());
            $recommendations = $helper->get_user_recommendations($query->get_target_component());

            $builder->where_in('er.id', $recommendations ?? []);
            $helper->apply_sort_by_recommendations($builder, 'er.id', $recommendations);
        } else {
            $builder->where('r.user_id', $query->get_target_user_id());
            $builder->where('r.component', $query->get_target_component());
            $builder->where('r.area', $query->get_target_area());
        }

        $cursor = $query->get_cursor();
        return new offset_cursor_paginator($builder, $cursor);
    }

    /**
     * Build the base article fetch query
     *
     * @param string $table
     * @param int $actor_id
     * @return builder
     */
    private static function get_base_article_query(string $table, int $actor_id = 0): builder {
        // If we're using the new service, then don't join on the recommendations table
        if (recommendations::is_ml_service_enabled()) {
            $builder = builder::table(engage_resource::TABLE, 'er');
        } else {
            // Fall back to the legacy service instead
            $builder = builder::table($table, 'r');
            $builder->join([engage_resource::TABLE, 'er'], 'r.item_id', 'er.id');
            $builder->order_by_raw('r.score DESC');
        }

        $builder->results_as_arrays();

        // We only want to return public articles
        $builder->where('er.resourcetype', article::get_resource_type());
        $builder->where('er.access', access::PUBLIC);

        $builder->select(
            [
                'er.id as instanceid', // card doesn't want the article id, it want's the resource id
                'er.name',
                'er.resourcetype as component',
                'er.userid',
                'er.access',
                'er.timecreated',
                'er.timemodified',
                'er.extra',
            ]
        );

        self::filter_multi_tenancy($builder, 'er.userid', $actor_id);

        $builder->map_to(
            function (array $record) {
                return card_resolver::create_card(article::get_resource_type(), $record);
            }
        );

        return $builder;
    }
}
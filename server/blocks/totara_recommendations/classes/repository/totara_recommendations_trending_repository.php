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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Vernon Denny <vernon.denny@totaralearning.com>
 * @package block_totara_recommendations
 */

namespace block_totara_recommendations\repository;

use core\orm\entity\repository;
use block_totara_recommendations\entity\totara_recommendations_trending;
use core\orm\query\builder;
use core\orm\query\order;
use core\orm\collection as collection;
use totara_core\advanced_feature;

final class totara_recommendations_trending_repository extends repository {

    /**
     * Retrieve desired amount of records from trending cache table.
     *
     * @param int $max_count
     * @return array
     */
    public function get_cached_trending_content(int $max_count) {
        $builder = $this->get_base_cached_builder();

        // Only some components are shown
        $components = [
            'container_course',
            'totara_program',
        ];

        if (advanced_feature::is_enabled('engage_resources')) {
            $components[] = 'totara_playlist';
            $components[] = 'engage_article';
            $components[] = 'engage_survey';
        }
        if (advanced_feature::is_enabled('container_workspace')) {
            $components[] = 'container_workspace';
        }

        $builder->where_in('component', $components);
        $paginator = $builder->paginate(1, $max_count);
        return $paginator->get_items()->all(true);
    }

    /**
     * Base query for retrieving desired amount of records from trending cache table.
     *
     * @return builder
     */
    private function get_base_cached_builder(): builder {
        $builder = builder::table('ml_recommender_trending');
        $builder->select([
            'unique_id',
            'item_id',
            'component',
            'area',
            'counter'
            ])
            ->group_by([
                'counter',
                'component',
                'item_id',
                'unique_id',
                'area'
            ])
            ->order_by('counter', order::DIRECTION_DESC);

        return $builder;
    }

    /**
     * Retrieve trending content from Engage interactions table.
     *
     * Identifies and returns $limit number of records of trending content items for
     * components specified in $components array.
     *
     * @param array $components
     * @param int $days number of days worth of data to consider
     * @return array|null   of trending item records
     */
    public function get_trending_components(array $components, int $days): array {
        global $CFG, $DB;

        $params = [self::get_since_timestamp($days)];
        if (empty($components)) {
            $where = 'WHERE time_created > ?';
        } else {
            list($componentinorequal, $inparams) = $DB->get_in_or_equal($components);
            $params = array_merge($params, $inparams);
            $where = 'WHERE (
                time_created > ? AND
            component ' . $componentinorequal . ')';
        }

        $unique = builder::concat('component', 'item_id');

        $sql = '
            SELECT
                ' . $unique . ' as unique_id,
                item_id,
                component,
                COUNT(item_id) AS counter
            FROM {ml_recommender_interactions} mri
            INNER JOIN {ml_recommender_components} mrc ON (mrc.id = mri.component_id)';
        $sql .= ' ' . $where . ' ';
        $sql .= '
            GROUP BY
                component,
                item_id,
                ' . $unique . '
            ORDER BY
                counter DESC';

        $records = $DB->get_records_sql($sql, $params, 0, $CFG->block_totara_recommendations_overctr);

        return $records;
    }

    /**
     * Write collection to totara_trending table.
     *
     * @param collection $trending_items
     */
    public function store_trending_items(collection $trending_items) :void {
        /** @var totara_trending $totara_trending */
        foreach ($trending_items as $trending_item) {
            $trending_item->save();
        }
    }

    /**
     * Clear out old trending records.
     *
     */
    public function truncate_totara_recommendations_trending() :void {
        global $DB;
        $DB->delete_records('ml_recommender_trending');
    }

    /**
     * Delete any trending item records for the provided component
     *
     * @param string $component
     * @param int $item_id
     */
    public function delete_for_component(string $component, int $item_id): void {
        $builder = builder::table('ml_recommender_trending');

        $builder->where('component', $component);
        $builder->where('item_id', $item_id);

        $builder->delete();
    }

    /**
     * Compute a timestamp for $days ago from right now.
     *
     * Does not check for silly things (e.g. trying to go backward one million
     * days) because the LIMIT clause restricts the number of records being returned.
     *
     * @param int $days
     * @return float|int
     */
    private function get_since_timestamp(int $days) :int {
        // Seconds in a day: 60 * 60 * 24 = 86400;
        return time() - ($days * 86400);
    }
}

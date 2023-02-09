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
 * @package block_totara_recently_viewed
 */

namespace block_totara_recently_viewed\repository;

use core\orm\query\builder;
use core\orm\query\raw_field;
use totara_core\advanced_feature;

/**
 * Repo to provide interaction events
 *
 * @package block_totara_recently_viewed\repository
 */
final class interaction_repository {
    /**
     * Fetch the defined number of interactions
     *
     * @param int $max_count
     * @param int|null $user_id
     * @return array|\stdClass[]
     */
    public static function get_recently_viewed(int $max_count, int $user_id = null): array {
        global $USER;
        if (!$user_id) {
            $user_id = $USER->id;
        }

        // Only some components are shown
        $valid_components = [
            'container_course',
            'totara_program',
        ];

        if (advanced_feature::is_enabled('engage_resources')) {
            $valid_components[] = 'totara_playlist';
            $valid_components[] = 'engage_article';
            $valid_components[] = 'engage_survey';
        }
        if (advanced_feature::is_enabled('container_workspace')) {
            $valid_components[] = 'container_workspace';
        }

        // We need to select the first X, distinctively.
        // The ORM doesn't support distinct so we're instead grouping against the time created
        $builder = builder::table('ml_recommender_interactions', 'mri');
        $builder->join(['ml_recommender_components', 'mrc'], 'mrc.id', 'mri.component_id');
        $builder->join(['ml_recommender_interaction_types', 'mrit'], 'mrit.id', 'mri.interaction_type_id');
        $unique = builder::concat('mrc.component', 'item_id');
        $builder->select([
            new raw_field("{$unique} AS unique_id"),
            'mri.item_id',
            'mrc.component',
            'mrc.area',
            new raw_field('MAX(mri.time_created) AS max_time_created'),
        ]);

        $builder->where('mrit.interaction', 'view');
        $builder->where('mri.user_id', $user_id);
        $builder->where_in('mrc.component', $valid_components);

        $builder->group_by_raw("{$unique}, item_id, mrc.component, mrc.area");

        $builder->order_by_raw('max_time_created DESC');
        $builder->limit($max_count);

        return $builder->fetch();
    }
}
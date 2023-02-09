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
 * @package totara_reportedcontent
 */

namespace totara_reportedcontent\loader;

use core\orm\paginator;
use core\orm\query\builder;
use totara_reportedcontent\entity\review as review_entity;
use totara_reportedcontent\review;

/**
 * Communication layer between the application and the database.
 */
final class review_loader {
    /**
     * review_loader constructor.
     * Preventing this class from being constructed
     */
    private function __construct() {
    }

    /**
     * @param int $item_id
     * @param int $context_id
     * @param string $component
     * @param string $area
     * @param int $page
     * @return paginator
     */
    public static function get_paginator(int $item_id, int $context_id, string $component, string $area, int $page = 1): paginator {
        $per_page = 20;

        $builder = static::get_base_builder($item_id, $context_id, $component, $area);

        return $builder->paginate($page, $per_page);
    }

    /**
     * @param int $item_id
     * @param int $context_id
     * @param string $component
     * @param string $area
     * @return builder
     */
    private static function get_base_builder(int $item_id, int $context_id, string $component, string $area): builder {
        $builder = static::builder();
        $closure = \Closure::fromCallable([static::class, 'build_review']);

        $builder->map_to($closure);

        $builder->where('tr.item_id', $item_id);
        $builder->where('tr.context_id', $context_id);
        $builder->where('tr.component', $component);
        $builder->where('tr.area', $area);

        $builder->order_by('tr.id', 'asc');
        return $builder;
    }

    /**
     * @return builder
     */
    private static function builder(): builder {
        $builder = builder::table(review_entity::TABLE, 'tr');
        $builder->select(
            [
                "tr.id",
                "tr.content",
                "tr.url",
                "tr.time_content",
                "tr.status",
                "tr.time_created",
                "tr.time_reviewed",
                "tr.item_id",
                "tr.context_id",
                "tr.component",
                "tr.area",
                "tr.target_user_id",
                "tr.complainer_id",
                "tr.reviewer_id",
            ]
        );
        $builder->results_as_arrays();

        return $builder;
    }

    /**
     * @param array $record
     * @return review
     */
    private static function build_review(array $record): review {
        $entity = new review_entity();

        $map = [
            'id',
            'content',
            'url',
            'time_content',
            'status',
            'time_created',
            'time_reviewed',
            'item_id',
            'context_id',
            'component',
            'area',
            'target_user_id',
            'complainer_id',
            'reviewer_id',
        ];

        foreach ($map as $property) {
            if (!array_key_exists($property, $record)) {
                debugging("No property '{$property}' was found for the record", DEBUG_DEVELOPER);
                continue;
            }

            $entity->set_attribute($property, $record[$property]);
            unset($record[$property]);
        }

        return review::from_entity($entity);
    }
}
<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Ning Zhou <ning.zhou@totaralearning.com>
 * @package totara_hierarchy
 */

namespace totara_hierarchy\entity\filters;

use core\orm\entity\filter\do_nothing;
use core\orm\entity\filter\equal;
use core\orm\entity\filter\filter;
use core\orm\entity\filter\filter_factory;
use core\orm\entity\filter\in;
use core\orm\entity\filter\like;
use core\orm\entity\filter\not_in;

class competency_filters implements filter_factory {

    public const FILTER_IDS = 'ids';
    public const FILTER_EXCLUDED_IDS = 'excluded_ids';
    public const FILTER_NAME = 'name';
    public const FILTER_FRAMEWORK_ID = 'framework_id';
    public const FILTER_NO_PATH = 'no_path';
    public const FILTER_NO_HIERARCHY = 'no_hierarchy';
    public const FILTER_PARENT_ID = 'parent_id';

    public static $filter_processor = [
        self::FILTER_IDS => 'create_id_filter',
        self::FILTER_EXCLUDED_IDS => 'create_excluded_id_filter',
        self::FILTER_NAME => 'create_name_filter',
        self::FILTER_FRAMEWORK_ID => 'create_framework_filter',
        self::FILTER_NO_PATH => 'create_no_path_filter',
        self::FILTER_NO_HIERARCHY => 'create_no_hierarchy_filter',
        self::FILTER_PARENT_ID => 'create_parent_id_filter',
    ];

    /**
     * Returns the appropriate filter given the query key.
     *
     * @param string $key query key.
     * @param mixed $value search value(s).
     *
     * @return filter the filter if it was found or null if it wasn't.
     */
    public function create(string $key, $value, ?int $user_id = null): ?filter {
        if (!in_array($key, array_keys(self::$filter_processor))) {
            return (new do_nothing());
        }
        return $this->{self::$filter_processor[$key]}($value);
    }

    /**
     * Returns an instance of a competency id filter.
     *
     * @param int[] $value the matching values. Note this may be an empty array
     *        in which this filter will return nothing.
     *
     * @return filter the filter instance.
     */
    protected function create_id_filter(array $value): filter {
        return (new in('id'))
            ->set_value($value);
    }

    /**
     * @param array|string $value the matching value(s).
     * @return filter the filter instance.
     */
    protected function create_excluded_id_filter($value): filter {
        return (new not_in('id'))
            ->set_value($value);
    }

    /**
     * Returns an instance of a competency name filter.
     *
     * Note this does like '%name%" matches.
     *
     * @param string $value the matching value(s).
     *
     * @return filter the filter instance.
     */
    protected function create_name_filter($value): filter {
        return (new like('fullname'))
            ->set_value($value);
    }

    /**
     * @param string $value the matching value(s).
     * @return filter the filter instance.
     */
    protected function create_framework_filter($value): filter {
        return (new equal('frameworkid'))
            ->set_value($value);
    }

    /**
     * @param string $value the matching value(s).
     * @return filter the filter instance.
     */
    protected function create_no_path_filter($value): filter {
        return (new no_achievement_paths())
            ->set_value($value);
    }

    /**
     * This function is a filter handler of no hierarchy, it accepted boolean
     *
     * @param string $value
     * @return filter
     */
    protected function create_no_hierarchy_filter($value): filter {
        if ($value === 0) {
            // only return top level competencies
            return (new equal('parentid'))
                ->set_value(0);
        } else {
            return (new do_nothing());
        }
    }

    /**
     * This function is a filter handler of parent id filter.
     *
     * @param $value
     * @return filter
     */
    protected function create_parent_id_filter($value): filter {
        // only return direct children competencies for given parent id
        return (new equal('parentid'))
            ->set_value($value);
    }
}
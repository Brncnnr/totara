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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package hierarchy_position
 */
namespace hierarchy_position\entity;

defined('MOODLE_INTERNAL') || die();

use core\orm\entity\filter\equal;
use core\orm\entity\filter\filter;
use core\orm\entity\filter\in;
use core\orm\entity\filter\like;

/**
 * Convenience filters to use with the position entity.
 */
final class position_filters {

    /**
     * Returns the appropriate filter given the query key.
     *
     * @param string $key query key.
     * @param mixed $value search value(s).
     *
     * @return filter the filter if it was found or null if it wasn't.
     */
    public static function for_key(string $key, $value): ?filter {
        switch ($key) {
            case 'framework_id':
                return self::create_framework_filter($value);

            case 'parent_id':
                return self::create_parent_id_filter($value);

            case 'ids':
                $values = is_array($value) ? $value : [$value];
                return self::create_id_filter($values);

            case 'name':
                return self::create_name_filter($value);

            case 'type_id':
                return self::create_type_filter($value);
        }

        return null;
    }

    /**
     * Returns an instance of a position framework id filter.
     *
     * @param int $value the matching values.
     *
     * @return filter the filter instance.
     */
    public static function create_framework_filter(int $value): filter {
        return (new equal('frameworkid'))
            ->set_value($value)
            ->set_entity_class(position::class);
    }

    /**
     * Returns an instance of a position parent id filter.
     *
     * @param int $value
     *
     * @return filter
     */
    public static function create_parent_id_filter(int $value): filter {
        return (new equal('parentid'))
            ->set_value($value)
            ->set_entity_class(position::class);
    }

    /**
     * Returns an instance of a position id filter.
     *
     * @param int[] $values the matching values. Note this may be an empty array
     *        in which this filter will return nothing.
     *
     * @return filter the filter instance.
     */
    public static function create_id_filter(array $values): filter {
        return (new in('id'))
            ->set_value($values)
            ->set_entity_class(position::class);
    }

    /**
     * Returns an instance of a position name filter.
     *
     * Note this does like '%name%" matches.
     *
     * @param string $value the matching value(s).
     *
     * @return filter the filter instance.
     */
    public static function create_name_filter(string $value): filter {
        return (new like('fullname'))
            ->set_value($value)
            ->set_entity_class(position::class);
    }

    /**
     * Returns an instance of a position type filter.
     *
     * @param int $value id of the type.
     *
     * @return filter the filter instance.
     */
    public static function create_type_filter(int $value): filter {
        return (new equal('typeid'))
            ->set_value($value)
            ->set_entity_class(position::class);
    }

}

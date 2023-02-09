<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @package totara_evidence
 */

namespace totara_evidence\entity\filters;

use core\orm\entity\filter\equal;
use core\orm\entity\filter\filter;
use core\orm\entity\filter\in;
use core\orm\entity\filter\like;
use totara_evidence\entity\evidence_item;

/**
 * Convenience filters to use with the evidence_item entity.
 */
class evidence_item_filters {

    /**
     * Returns the appropriate filter given the query key.
     *
     * @param string $key query key.
     * @param mixed $value search value(s).
     *
     * @return filter the filter if it was found or null if it wasn't.
     */
    public static function for(string $key, $value): ?filter {
        switch ($key) {
            case 'ids':
                $values = is_array($value) ? $value : [$value];
                return self::create_id_filter($values);
            case 'name':
            case 'search':
                return self::create_name_filter($value);
            case 'user_id':
                return self::create_user_id_filter($value);
            case 'type_id':
                return self::create_type_id_filter($value);
        }
        return null;
    }

    /**
     * Returns an instance of a evidence item id filter.
     *
     * @param int[] $values the matching values. Note this may be an empty array
     *        in which this filter will return nothing.
     *
     * @return filter the filter instance.
     */
    public static function create_id_filter(array $values): filter {
        return (new in('id'))
            ->set_value($values)
            ->set_entity_class(evidence_item::class);
    }

    /**
     * Returns an instance of a evidence item name filter.
     *
     * Note this does like '%name%' matches.
     *
     * @param string $value the matching value(s).
     *
     * @return filter the filter instance.
     */
    public static function create_name_filter(string $value): filter {
        return (new like('name'))
            ->set_value($value)
            ->set_entity_class(evidence_item::class);
    }

    /**
     * Returns an instance of a evidence item user id filter.
     *
     * @param int $value the matching value.
     *
     * @return filter the filter instance.
     */
    public static function create_user_id_filter(int $value): filter {
        return (new equal('user_id'))
            ->set_value($value)
            ->set_entity_class(evidence_item::class);
    }

    /**
     * Returns an instance of a evidence item type id filter.
     *
     * @param int $value the matching value.
     *
     * @return filter the filter instance.
     */
    public static function create_type_id_filter(int $value): filter {
        return (new equal('typeid'))
            ->set_value($value)
            ->set_entity_class(evidence_item::class);
    }

}

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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package core_user
 */
namespace core\entity;

defined('MOODLE_INTERNAL') || die();

use user;
use core\orm\entity\filter\filter;
use core\orm\entity\filter\filter_factory;
use core\orm\entity\filter\equal;
use core\orm\entity\filter\in;
use core\orm\entity\filter\user_name;

/**
 * Convenience filters to use with the user entity.
 */
final class user_filters implements filter_factory {
    /**
     * @inheritDoc
     */
    public function create(string $key, $value, ?int $user_id = null): ?filter {
        switch ($key) {
            case 'ids':
                $values = is_array($value) ? $value : [$value];
                return self::create_id_filter($values);

            case 'name':
                return self::create_name_filter($value);
        }

        return null;
    }

    /**
     * Returns an instance of a user id filter.
     *
     * @param int[] $values the matching values. Note this may be an empty array
     *        in which this filter will return nothing.
     *
     * @return filter the filter instance.
     */
    public static function create_id_filter(array $values): filter {
        return (new in('id'))
            ->set_value($values)
            ->set_entity_class(user::class);
    }

    /**
     * Returns an instance of a user name filter.
     *
     * @param string $value the matching value(s).
     *
     * @return filter the filter instance.
     */
    public static function create_name_filter(string $value): filter {
        return (new user_name())
            ->set_value($value)
            ->set_entity_class(user::class);
    }
}

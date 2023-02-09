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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package totara_core
 */

namespace totara_core\user_learning;

use core\dml\pagination\offset_cursor_paginator;
use core\orm\pagination\cursor_paginator;
use core\orm\query\builder;
use core\orm\query\raw_field;
use core\pagination\cursor;
use core\pagination\offset_cursor;
use stdClass;
use totara_core\data_provider\provider;

abstract class learning_items_helper {

    /**
     * @param provider[] $data_providers
     * @param int $user_id
     * @param cursor|null $cursor
     *
     * @return array
     */
    public static function get_learning_items(array $data_providers, int $user_id, ?cursor $cursor = null): array {
        $sub_builder = self::combine_providers($data_providers);
        if (empty($sub_builder)) {
            return [
                'items' => [],
                'total' => 0,
                'next_cursor' => '',
            ];
        }

        $builder = builder::table($sub_builder, 'temp');
        $builder->order_by('id');

        // Get paginated result.
        $paginator = $cursor instanceof offset_cursor
            ? new offset_cursor_paginator($builder, $cursor)
            : new cursor_paginator($builder, $cursor, true);
        $paginator->transform(
            static function (stdClass $record) use ($user_id) {
                return item_helper::create_from_record($record->type, $user_id, $record);
            }
        );

        return $paginator->get();
    }

    /**
     * @param provider[] $providers
     * @return builder
     */
    private static function combine_providers(array $providers): ?builder {
        $builders = [];

        foreach ($providers as $provider) {
            $type = $provider->get_type();
            $unique = builder::concat('id', "'-'", "'{$type}'");

            $builder = $provider->get_builder();
            $builder->select([
                raw_field::raw("{$unique} AS unique_id"),
                'id',
                'fullname',
                'shortname',
                'summary',
                raw_field::raw("'{$type}' as type"),
                $provider->get_summary_format_select(),
            ]);
            $builder->reset_order_by();
            $builders[] = $builder;
        }

        return self::union_builders($builders);
    }

    /**
     * @param array $builders
     *
     * @return builder
     */
    private static function union_builders(array $builders): ?builder {
        $first_builder = null;

        /** @var builder $builder */
        foreach ($builders as $builder) {
            if (empty($first_builder)) {
                $first_builder = $builder;
                continue;
            }
            $first_builder->union_all($builder);
        }

        return $first_builder;
    }

}

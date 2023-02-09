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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_core
 */
namespace totara_core\hashtag;

use core\orm\query\builder;

final class hashtag_loader {
    /**
     * hashtag_loader constructor.
     */
    private function __construct() {
        // Preventing this class from being constructed.
    }

    /**
     * @param string $pattern
     * @return hashtag[]
     */
    public static function find_hashtags_by_pattern(string $pattern): array {
        global $CFG;

        if (!property_exists($CFG, 'hashtag_collection_id')) {
            debugging("No hashtag collection's id", DEBUG_DEVELOPER);
            return [];
        }

        $builder = builder::table('tag', 't');
        $builder->select('t.*');
        $builder->where('tagcollid', $CFG->hashtag_collection_id);
        $builder->where('rawname', 'ilike', $pattern);

        $builder->map_to(
            function (\stdClass $record): hashtag {
                $tag = \core_tag_tag::from_record($record);
                return new hashtag($tag);
            }
        );

        return $builder->fetch();
    }
}
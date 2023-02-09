<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @package totara_topic
 */
namespace totara_topic\provider;

use core\orm\query\builder;
use totara_topic\topic;

/**
 * A data loader class for topic.
 */
final class topic_provider {
    /**
     * topic_provider constructor.
     */
    private function __construct() {
        // Preventing this class from construction.
    }

    /**
     * @param string $name
     * @return topic|null
     */
    public static function find_by_name(string $name): ?topic {
        global $CFG;

        $tag = \core_tag_tag::get_by_name($CFG->topic_collection_id, $name);
        if (!$tag) {
            return null;
        }

        return topic::from_tag($tag);
    }

    /**
     * @param string    $name
     * @param int[]     $exclude_ids    The array of topics to be excluded from search.
     *
     * @return topic[]
     */
    public static function query_by_name(string $name, array $exclude_ids = []): array {
        global $CFG;

        $builder = builder::table('tag');
        $builder->where('tagcollid', $CFG->topic_collection_id);

        if (!empty(trim($name))) {
            $builder->where('name', 'ilike', $name);
        }

        if (!empty($exclude_ids)) {
            $builder->where_not_in('id', $exclude_ids);
        }

        $builder->map_to(
            function (\stdClass $record): topic {
                $tag = \core_tag_tag::from_record($record);
                return topic::from_tag($tag);
            }
        );

        return $builder->fetch();
    }

    /**
     * @param int $id
     * @return bool
     */
    public static function topic_exists(int $id): bool {
        $builder = builder::table('tag');
        $builder->where('id', $id);

        return $builder->exists();
    }

    /**
     * This will load with the limit of 150 topics.
     *
     * @param int $limit
     * @return topic[]
     */
    public static function get_all(int $limit = 150): array {
        global $CFG;
        $records = \core_tag_collection::get_tags($CFG->topic_collection_id, true, $limit);
        $topics = [];

        foreach ($records as $record) {
            $tag = \core_tag_tag::from_record($record);
            $topics[] = topic::from_tag($tag);
        }

        return $topics;
    }

    /**
     * @param int    $itemid
     * @param string $component
     * @param string $itemtype
     *
     * @return topic[]
     */
    public static function get_for_item(int $itemid, string $component, string $itemtype): array {
        $tags = \core_tag_tag::get_item_tags($component, $itemtype, $itemid);
        $topics = [];

        foreach ($tags as $tag) {
            $topics[] = topic::from_tag($tag);
        }

        return $topics;
    }
}
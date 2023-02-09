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
 * @package totara_topic
 */
namespace totara_topic;

use totara_topic\exception\topic_exception;
use totara_topic\resolver\resolver_factory;

/**
 * Use this class to remove/add topics to the instance.
 */
final class topic_helper {
    /**
     * topic_helper constructor.
     */
    private function __construct() {
        // Preventing this class from construction.
    }

    /**
     * Adding topic usage, which it will return a map's instance id.
     *
     * @param int       $topic_id
     * @param string    $component
     * @param string    $item_type
     * @param int       $item_id
     * @param int|null  $actor_id
     *
     * @return int
     */
    public static function add_topic_usage(int $topic_id, string $component, string $item_type,
                                     int $item_id, ?int $actor_id = null): int {
        global $USER;

        if (null === $actor_id || 0 === $actor_id) {
            $actor_id = $USER->id;
        }

        $topic = topic::from_id($topic_id);
        $resolver = resolver_factory::create_resolver($component);

        if (!$topic->can_be_added($component, $item_type) ||
            !$resolver->can_add_usage($topic, $item_id, $item_type, $actor_id)
        ) {
            $label = get_string('pluginname', $component);
            throw new topic_exception('unabletoaddusage', $label);
        }

        $context = $resolver->get_context_of_item($item_id, $item_type);
        return \core_tag_tag::add_item_tag(
            $component,
            $item_type,
            $item_id,
            $context,
            $topic->get_raw_name(),
            $actor_id
        );
    }

    /**
     * Removing the topic usage.
     *
     * @param int $topic_id
     * @param string $component
     * @param string $item_type
     * @param int $item_id
     * @param int|null $actor_id
     *
     * @return void
     */
    public static function delete_topic_usage_by_id(int $topic_id, string $component, string $item_type,
                                              int $item_id, ?int $actor_id = null): void {
        global $USER;

        if (null === $actor_id || 0 === $actor_id) {
            $actor_id = $USER->id;
        }

        $topic = topic::from_id($topic_id);
        static::delete_topic_usage($topic, $component, $item_type, $item_id, $actor_id);
    }

    /**
     * Removing the topic usage by the topic instance.
     *
     * @param topic $topic
     * @param string $component
     * @param string $item_type
     * @param int $item_id
     * @param int|null $actor_id
     *
     * @return void
     */
    public static function delete_topic_usage(topic $topic, string $component, string $item_type,
                                              int $item_id, ?int $actor_id = null): void {
        global $USER;

        if (null === $actor_id || 0 === $actor_id) {
            $actor_id = $USER->id;
        }

        $resolver = resolver_factory::create_resolver($component);

        if (!$resolver->can_delete_usage($topic, $item_id, $item_type, $actor_id)) {
            $label = get_string('pluginname', $component);
            throw new topic_exception('unabletodeleteusage', $label);
        }

        \core_tag_tag::remove_item_tag(
            $component,
            $item_type,
            $item_id,
            $topic->get_raw_name()
        );
    }

    /**
     * Verify whether topics catalog filter is enabled.
     *
     * @return bool
     */
    public static function topic_catalog_filter_enabled(): bool {
        global $CFG;

        // Leave if no topics collection exists.
        if (!isset($CFG->topic_collection_id)) {
            return false;
        }

        // Get catalog config (see totara_catalog\local\config::get()).
        $config_db = (array)get_config('totara_catalog');
        if (empty($config_db) || !is_array($config_db)) {
            return false;
        }

        // Check if the tag collection id is used in a currently active filter.
        $filters = $config_db['filters'] ?? null;
        if ($filters) {
            $filters = json_decode($filters, true);
            return !empty($filters['tag_panel_' . $CFG->topic_collection_id]);
        }

        return false;
    }
}
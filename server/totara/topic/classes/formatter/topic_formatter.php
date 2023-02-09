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
namespace totara_topic\formatter;

use core\webapi\formatter\field\string_field_formatter;
use core\webapi\formatter\formatter;
use totara_topic\topic;
use totara_topic\topic_helper;

/**
 * Formatter for the topic
 */
final class topic_formatter extends formatter {
    /**
     * topic_formatter constructor.
     * @param topic         $topic
     * @param \context|null $context
     */
    public function __construct(topic $topic, ?\context $context = null) {
        if (null === $context) {
            $context = \context_system::instance();
        }

        $record = new \stdClass();
        $record->id = $topic->get_id();
        $record->value = $topic->get_display_name();
        $record->catalog = $this->topic_catalog_filter($topic);

        parent::__construct($record, $context);
    }

    /**
     * @return array
     */
    protected function get_map(): array {
        return [
            'id' => null,
            'value' => string_field_formatter::class,
            'catalog' => null
        ];
    }

    /**
     *
     * @param topic $topic
     * @return string
     */
    protected function topic_catalog_filter(topic $topic): string {
        global $CFG;

        // Check if topic filtering is enabled.
        $enabled = topic_helper::topic_catalog_filter_enabled();

        // Build the catalog filter link suffix for each topic (FTS if tag filter not set).
        $filters = [];
        if ($enabled && $CFG->topic_collection_id) {
            // Catalog tag filter link.
            $filters['tag_panel_' . $CFG->topic_collection_id] = [$topic->get_id()];
        } else {
            // Catalog FTS filter link.
            $filters['catalog_fts'] = strtolower($topic->get_raw_name());
        }

        return http_build_query($filters);
    }
}
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
namespace totara_topic\hook;

use totara_core\hook\base;
use totara_topic\usage\item;

/**
 * A hook to gather the information about the usage of deleted topic(s).
 * Which the hook is triggered when totara_topic is trying to send a notification out to the
 * author/user who ever using the topic.
 */
final class get_deleted_topic_usages extends base {
    /**
     * @var item[]
     */
    private $items;

    /**
     * The affected component - or the component that is trying to use the topic.
     * @var string
     */
    private $component;

    /**
     * Specific area of within a component.
     * @var string
     */
    private $item_type;

    /**
     * An array of affected instance's id(s) identified by component and area.
     * @var int[]
     */
    private $instance_ids;

    /**
     * get_topic_usages constructor.
     * @param string $component
     * @param string $item_type
     * @param array $instance_ids
     */
    public function __construct(string $component, string $item_type, array $instance_ids) {
        $valid_instance_ids = array_filter(
            $instance_ids,
            function ($instance_id): bool {
                return is_numeric($instance_id);
            }
        );

        if (count($valid_instance_ids) !== count($instance_ids)) {
            throw new \coding_exception("Invalid array of instance id(s)");
        }

        $this->component = $component;
        $this->item_type = $item_type;
        $this->instance_ids = $instance_ids;

        $this->items = [];
    }

    /**
     * @return string
     */
    public function get_component(): string {
        return $this->component;
    }

    /**
     * @return string
     */
    public function get_item_type(): string {
        return $this->item_type;
    }

    /**
     * @return array|int[]
     */
    public function get_instance_ids(): array {
        return $this->instance_ids;
    }

    /**
     * Note that this function should not be responsible for formatting $title string.
     * It should be done before passing to this function.
     *
     * @param int $affected_user_id
     * @param string $title
     * @param \moodle_url $context_url
     *
     * @return void
     */
    public function add_item(int $affected_user_id, string $title,\moodle_url $context_url ): void {
        $item = new item(
            $affected_user_id,
            $title,
            $context_url
        );

        $item->set_component($this->component);
        $this->items[] = $item;
    }

    /**
     * @return item[]
     */
    public function get_items(): array {
        return $this->items;
    }
}
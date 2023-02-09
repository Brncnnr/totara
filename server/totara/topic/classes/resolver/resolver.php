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
namespace totara_topic\resolver;

use totara_topic\exception\topic_exception;
use totara_topic\topic;
use totara_topic\usage\item;

/**
 * To allow other component have the ability to add topic into the component instance. This class should be where the
 * child to update
 */
abstract class resolver {
    /**
     * @var string
     */
    protected $component;

    /**
     * Prevent any complicated functionality.
     * resolver constructor.
     */
    final public function __construct() {
        $cls = get_called_class();
        $parts = explode("\\", $cls);

        $component = reset($parts);
        $cleaned = clean_param($component, PARAM_COMPONENT);

        if ($cleaned !== $component) {
            throw new \coding_exception(
                "Unable to construct a resolver for component '{$component}' as it is an invalid component"
            );
        }

        $this->component = $component;
    }

    /**
     * We allow the children to check the ability of adding the usage of the topic of the instance.
     *
     * @param topic     $topic
     * @param int       $instanceid
     * @param int       $actorid
     * @param string    $itemtype
     *
     * @return bool
     */
    abstract public function can_add_usage(topic $topic, int $instanceid, string $itemtype, int $actorid): bool;

    /**
     * We allow the children to check the ability of deleting the usage of topic of the instance.
     *
     * @param topic     $topic
     * @param int       $instanceid
     * @param int       $actorid
     * @param string    $itemtype
     *
     * @return bool
     */
    abstract public function can_delete_usage(topic $topic, int $instanceid, string $itemtype, int $actorid): bool;

    /**
     * Returning the context for which the tag will be added into. With different itemtype, there can
     * have a different
     *
     * @param int       $itemid
     * @param string    $itemtype
     *
     * @return \context
     */
    abstract public function get_context_of_item(int $itemid, string $itemtype): \context;
}
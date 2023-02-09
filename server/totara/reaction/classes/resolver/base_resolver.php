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
 * @package totara_reaction
 */
namespace totara_reaction\resolver;

/**
 * Base class for resolving the reaction on anything in the system. This class will be extended by the component
 * that want to integrate with reaction component, to provided metadata such as capabilities and metadata to fetch
 * records.
 */
abstract class base_resolver {
    /**
     * @var string
     */
    protected $component;

    /**
     * Keeping the constructor simples
     * base_resolver constructor.
     */
    final public function __construct() {
        $cls = get_called_class();
        $parts = explode("\\", $cls);

        $this->component = reset($parts);
    }

    /**
     * @return string
     */
    final public function get_component(): string {
        return $this->component;
    }

    /**
     * @param int    $instanceid
     * @param string $area
     *
     * @return \context
     */
    public abstract function get_context(int $instanceid, string $area): \context;

    /**
     * Returning the number of record loaded per page. Overriding this function to change to the number that
     * the component want.
     * @return int
     */
    public function items_per_page(): int {
        return 20;
    }

    /**
     * Given the ability to check by the plugin that extend this resolver.
     *
     * @param int $instanceid
     * @param int $userid
     * @param string $area
     *
     * @return bool
     */
    public abstract function can_create_reaction(int $instanceid, int $userid, string $area): bool;

    /**
     * Checking if the user actor is able to view the record of who reacted to the instance.
     *
     * @param int       $instance_id
     * @param int       $user_id
     * @param string    $area
     *
     * @return bool
     */
    public function can_view_reactions(int $instance_id, int $user_id, string $area): bool {
        return true;
    }
}
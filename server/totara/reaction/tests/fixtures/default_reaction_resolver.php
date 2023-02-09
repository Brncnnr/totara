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

use totara_reaction\resolver\base_resolver;

/**
 * Class default_reaction_resolver
 */
final class default_reaction_resolver extends base_resolver {
    /**
     * @param string $component
     * @return void
     */
    public function set_component(string $component): void {
        $this->component = $component;
    }

    /**
     * @param int $instanceid
     * @param string $area
     *
     * @return context
     */
    public function get_context(int $instanceid, string $area): context {
        return context_system::instance();
    }

    /**
     * @param int $instanceid
     * @param int $userid
     * @param string $area
     *
     * @return bool
     */
    public function can_create_reaction(int $instanceid, int $userid, string $area): bool {
        return true;
    }
}
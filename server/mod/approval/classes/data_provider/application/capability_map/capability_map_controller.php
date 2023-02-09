<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\data_provider\application\capability_map;

use core_component;

/**
 * Capability map controller class for approval workflows
 *
 * Please note that this is an adviser and should not be used to replace access checks on individual
 * items that should be made when actually accessing those items.
 *
 */
final class capability_map_controller {

    /**
     * Returns the array of maps supported by this capability_map controller.
     *
     * @return string[]
     */
    public static function map_classes(): array {
        return core_component::get_namespace_classes(
            'data_provider\application\capability_map',
            capability_map_base::class,
            'mod_approval'
        );
    }

    /**
     * Returns an array of maps, one for each capability this capability controller supports.
     *
     * @param int $user_id The user to load the maps for.
     * @return capability_map_base[]
     */
    public static function get_all_maps(int $user_id): array {
        $maps = [];
        foreach (self::map_classes() as $class) {
            $maps[] = new $class($user_id);
        }
        return $maps;
    }

    /**
     * Gets a map that can be used to get assignments and applicants where the user has capability.
     *
     * @param string $capability The capability of the map you want.
     * @param int $user_id The user to load the map for.
     * @return capability_map_base
     */
    public static function get(string $capability, int $user_id): capability_map_base {
        // Accept full capability name, like mod/approval:view_in_dashboard_application_any
        if (strpos($capability, ':')) {
            list($component, $capability) = explode(':', $capability);
            if ($component != 'mod/approval') {
                throw new \coding_exception("Capability must belong to mod_approval");
            }
        }
        $class =  'mod_approval\data_provider\application\capability_map\\' . $capability;
        if (!in_array($class, self::map_classes())) {
            throw new \coding_exception('Tried to get unknown capability map', $capability);
        }
        return new $class($user_id);
    }

    /**
     * Regenerate all known capability maps for a user.
     *
     * @param int $user_id
     */
    public static function regenerate_all_maps(int $user_id): void {
        foreach (self::map_classes() as $class) {
            $class::generate_capability_map($user_id);
        }
    }
}
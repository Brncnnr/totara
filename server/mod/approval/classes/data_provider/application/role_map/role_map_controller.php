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

namespace mod_approval\data_provider\application\role_map;

use cache;
use core_component;

/**
 * Role map controller class for approval workflows
 *
 * This class provides an optimised way to get assignments or users where a given role has some capability
 *
 * Please note that this is an adviser and should not be used to replace access checks on individual
 * items that should be made when actually accessing those items.
 *
 */
final class role_map_controller {

    /**
     * Returns an array of the capabilities this role_map controller can advise on.
     *
     * @return string[]
     */
    public static function map_classes(): array {
        return core_component::get_namespace_classes(
            'data_provider\application\role_map',
            role_map_base::class,
            'mod_approval'
        );
    }

    /**
     * Returns the array of maps supported by this role_map controller.
     *
     * @return role_map_base[]
     */
    public static function get_all_maps(): array {
        $maps = [];
        foreach (self::map_classes() as $class) {
            $maps[] = new $class();
        }
        return $maps;
    }

    /**
     * Gets a role_map resolver that can be used to get visible items.
     *
     * @param string $capability The capability of the controller you want.
     * @return role_map_base
     */
    public static function get(string $capability): role_map_base {
        // Accept full capability name, like mod/approval:view_in_dashboard_application_any
        if (strpos($capability, ':')) {
            list($component, $capability) = explode(':', $capability);
            if ($component != 'mod/approval') {
                throw new \coding_exception("Capability must belong to mod_approval");
            }
        }
        $class =  'mod_approval\data_provider\application\role_map\\' . $capability;
        if (!in_array($class, self::map_classes())) {
            throw new \coding_exception('Unknown role map controller capability', $capability);
        }
        return new $class();
    }

    /**
     * Regenerate all known role capability maps.
     */
    public static function regenerate_all_maps(): void {
        $role_cache = cache::make('mod_approval', 'role_map');

        // Prevent multiple processes from recalculating at the same time.
        if ($role_cache->get('recalculating')) {
            return;
        }
        $role_cache->set('recalculating', 1);

        // Recalculate all maps.
        foreach (self::get_all_maps() as $map) {
            $map->recalculate_complete_map();
        }

        // Set the clean flag and clear the recalculating one.
        $role_cache->set_many(['maps_clean' => 1, 'recalculating' => 0]);
    }
}
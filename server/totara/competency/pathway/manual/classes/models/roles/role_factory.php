<?php
/*
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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package pathway_manual
 */

namespace pathway_manual\models\roles;

final class role_factory {

    /**
     * All available roles.
     *
     * We define them here instead of using core_component::get_namespace_classes() for performance reasons.
     *
     * @var role[]
     */
    protected const ROLES = [
        'appraiser' => appraiser::class,
        'manager' => manager::class,
        'self' => self_role::class,
    ];

    /**
     * Check if the specified role(s) exist.
     *
     * @param string|string[] $roles Single role name, or array of role names
     * @param bool $validate If true, throws exception if the role doesn't exist
     * @return bool
     */
    public static function roles_exist($roles, bool $validate = false): bool {
        if (!is_array($roles)) {
            $roles = [$roles];
        }

        foreach ($roles as $role) {
            if (!in_array($role, array_keys(self::ROLES)) && !in_array($role, array_values(self::ROLES))) {
                if ($validate) {
                    throw new \coding_exception('Invalid role specified: \'' . $role . '\'');
                } else {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Create role object from role name or class name.
     *
     * @param string $role
     * @return role
     */
    public static function create(string $role) {
        self::roles_exist($role, true);

        if (!is_a($role, role::class, true)) {
            $role = self::ROLES[$role];
        }

        return new $role();
    }

    /**
     * Create role objects from an array of role names.
     * The returned roles will be sorted in their defined sort order.
     *
     * @param string[] $roles
     * @return role[]
     */
    public static function create_multiple(array $roles): array {
        $roles = array_unique($roles);

        self::roles_exist($roles, true);

        $roles = array_map(function (string $role) {
            return self::create($role);
        }, $roles);

        return self::sort_roles($roles);
    }

    /**
     * Get all the different role options, sorted by display order.
     *
     * @return role[]
     */
    public static function create_all(): array {
        $roles = array_map(function (string $role) {
            return new $role();
        }, array_values(self::ROLES));

        return self::sort_roles($roles);
    }

    /**
     * Sort an array of roles by their display order.
     *
     * @param array $roles
     * @return array
     */
    private static function sort_roles(array $roles): array {
        usort($roles, function (role $a, role $b) {
            return $a::get_display_order() <=> $b::get_display_order();
        });
        return $roles;
    }

}

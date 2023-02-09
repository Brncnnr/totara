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

namespace pathway_manual\models;

use core\collection;
use core\orm\query\field;
use pathway_manual\entity\role as role_entity;
use pathway_manual\models\roles\role;
use pathway_manual\models\roles\role_factory;
use totara_competency\entity\pathway;
use totara_hierarchy\entity\competency;

class roles {

    /**
     * Get all unique roles that can add ratings for a competency.
     *
     * @param int $competency_id
     * @return role[]
     */
    public static function get_roles_for_competency(int $competency_id): array {
        $roles = role_entity::repository()
            ->select_raw('DISTINCT role')
            ->join([pathway::TABLE, 'pathway'], 'path_manual_id', 'pathway.path_instance_id')
            ->where('pathway.competency_id', $competency_id)
            ->get()
            ->pluck('role');

        return role_factory::create_multiple($roles);
    }

    /**
     * Get all the competencies that have the specified role available in the pathway configuration.
     *
     * @param role|string $role
     * @return competency[]|collection
     */
    public static function get_competencies_with_role($role): collection {
        $competency = competency::repository();

        $role = role_entity::repository()
            ->join([pathway::TABLE, 'pathway'], 'path_manual_id', 'path_instance_id')
            ->where('role', $role::get_name())
            ->where_field('pathway.competency_id', new field('id', $competency->get_builder()));

        return $competency
            ->where_exists($role->get_builder())
            ->order_by('id')
            ->get();
    }

    /**
     * Check if the specified competency has a pathway enabled with the specified role.
     *
     * @param int $competency_id
     * @param role|string $role
     * @return bool
     */
    public static function competency_has_role(int $competency_id, $role) {
        return static::role_in_array($role, static::get_roles_for_competency($competency_id));
    }

    /**
     * Get the roles that the current user has in relation to the subject user.
     *
     * @param int $subject_user
     * @return role[]
     */
    public static function get_current_user_roles(int $subject_user): array {
        return array_filter(role_factory::create_all(), function (role $role) use ($subject_user) {
            return $role::has_for_user($subject_user);
        });
    }

    /**
     * Get all the roles the current user has for any user across the system.
     *
     * @return role[]
     */
    public static function get_current_user_roles_for_any(): array {
        return array_filter(role_factory::create_all(), function (role $role) {
            return $role::has_for_any();
        });
    }

    /**
     * Check if the given role is in the list of roles specified.
     *
     * @param role|string $role
     * @param role[] $roles
     * @return bool
     */
    private static function role_in_array($role, array $roles) {
        foreach ($roles as $enabled_role) {
            if ($role::get_name() == $enabled_role::get_name()) {
                return true;
            }
        }
        return false;
    }

}

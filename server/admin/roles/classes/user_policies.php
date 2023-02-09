<?php
/*
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
 * @author  Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package core_role
 */

/**
 * Class core_role_user_policies
 * User policies roles assigned to the system or custom roles
 */
class core_role_user_policies {

    /**
     * All roles which assigned under User Policies by default.
     * @var string[]
     */
    private static $roles = [
        'notloggedinroleid',
        'guestroleid',
        'defaultuserroleid',
        'managerroleid',
        'learnerroleid',
        'assessorroleid',
        'performanceactivitycreatornewroleid',
        'creatornewroleid',
        'restorernewroleid',
    ];

    /**
     * Generate a list of roles assigned under User Policies.
     * @return array
     */
    public static function get_roles(): array {
        global $CFG;
        $policy_roles = [];
        foreach (self::$roles as $role) {
            $policy_roles[$CFG->{$role}] = [
                'config_name' => $role,
                'label' => get_string($role, 'admin'),
            ];
        }
        return $policy_roles;
    }
}
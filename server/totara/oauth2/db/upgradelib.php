<?php
/**
 * This file is part of Totara Core
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package totara_oauth2
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Assign manage client providers to the role if the role has 'moodle/site:config' capability.
 */
function totara_oauth2_assign_cap_user() {
    $context = context_system::instance();
    [$role_ids] = get_roles_with_cap_in_context($context, 'moodle/site:config');

    if (!empty($role_ids)) {
        update_capabilities('totara_oauth2');
        foreach ($role_ids as $role_id) {
            assign_capability('totara/oauth2:manageproviders', CAP_ALLOW, $role_id, $context);
        }
    }

}
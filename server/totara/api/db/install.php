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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package totara_api
 */
defined('MOODLE_INTERNAL') || die();

use core\orm\query\builder;

function xmldb_totara_api_install(): bool {
    $db = builder::get_db();

    $archetype = 'apiuser';
    // Check archetype.
    if ($db->record_exists('role', ['archetype' => $archetype])) {
        return true;
    }

    $apirole_id = create_role(
        get_string('apiuser'),
        $archetype,
        get_string('apiuserdescription'),
        $archetype
    );

    $role = $db->get_record('role', ['id' => $apirole_id], '*', MUST_EXIST);
    foreach (['assign', 'override', 'switch'] as $type) {
        $function = 'allow_' . $type;
        $allows = get_default_role_archetype_allows($type, $role->archetype);
        foreach ($allows as $allow_id) {
            $function($role->id, $allow_id);
        }

        set_role_contextlevels($role->id, get_default_contextlevels($role->archetype));
    }

    $default_caps = get_default_capabilities($role->archetype);
    foreach($default_caps as $cap => $permission) {
        assign_capability($cap, $permission, $role->id, (\context_system::instance())->id);
    }

    return true;
}
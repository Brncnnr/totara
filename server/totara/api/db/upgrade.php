<?php
/**
 * This file is part of Totara Core
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
 * @author  Michael Ivanov <michael.ivanov@totaralearning.com>
 * @package totara_api
 */

defined('MOODLE_INTERNAL') || die();

use core\orm\query\builder;

/**
 * @param int $old_version
 * @return bool
 */
function xmldb_totara_api_upgrade(int $old_version): bool {
    global $DB;
    $db_manager = $DB->get_manager();

    if ($old_version < 2022110802) {
        // Define table totara_api_client_settings
        $table = new xmldb_table('totara_api_client_settings');

        // Define fields
        $field = new xmldb_field('response_debug', XMLDB_TYPE_INTEGER, '1', null, null, null, null, 'default_token_expiry_time');

        if (!$db_manager->field_exists($table, $field)) {
            $db_manager->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2022110802, 'totara', 'api');
    }
    return true;
}
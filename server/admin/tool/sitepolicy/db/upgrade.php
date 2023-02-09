<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Courteney Brownie <courteney.brownie@totaralearning.com>
 * @package tool_sitepolicy
 */

/**
 * Upgrade script for tool_sitepolicy.
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade the plugin.
 *
 * @param int $oldversion
 * @return bool always true
 */
function xmldb_tool_sitepolicy_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // Totara 13.0 release line.

    if ($oldversion < 2022051900) {

        // Define field applies_to to be added to tool_sitepolicy_site_policy.
        $table = new xmldb_table('tool_sitepolicy_policy_version');
        $field = new xmldb_field('applies_to', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, 'all', 'timecreated');

        // Conditionally launch add field applies_to.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Sitepolicy savepoint reached.
        upgrade_plugin_savepoint(true, 2022051900, 'tool', 'sitepolicy');
    }

    return true;
}

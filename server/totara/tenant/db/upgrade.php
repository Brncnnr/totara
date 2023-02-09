<?php
/*
 * This file is part of Totara Learn
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_tenant
 */

/**
 * Database upgrade script
 *
 * @param   int $oldversion Current (pre-upgrade) local db version timestamp
 * @return  boolean always true
 */
function xmldb_totara_tenant_upgrade($oldversion) {
    global $DB;

    // Totara 13.0 release line.

    if ($oldversion < 2021021800) {

        $sql = 'SELECT t.*
                  FROM "ttr_tenant" t
                  JOIN "ttr_course_categories" cat ON cat.id = t.categoryid
                 WHERE t.suspended = cat.visible';
        $tenants = $DB->get_records_sql($sql);
        foreach ($tenants as $tenant) {
            $updatecategory = coursecat::get($tenant->categoryid, MUST_EXIST, true);
            $visible = ($tenant->suspended ? 0 : 1);
            $updatecategory->update(['visible' => $visible]);
        }

        upgrade_plugin_savepoint(true, 2021021800, 'totara', 'tenant');
    }

    return true;
}
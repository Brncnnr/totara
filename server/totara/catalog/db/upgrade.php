<?php
/*
 * This file is part of Totara LMS
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_catalog
 */

/**
 * Local database upgrade script
 *
 * @param   integer $oldversion Current (pre-upgrade) local db version timestamp
 * @return  boolean $result
 */
function xmldb_totara_catalog_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // Totara 13.0 release line.

    if ($oldversion < 2021031000) {
        global $DB;

        // Find and remove catalog previews for GIF images.
        $query = "
            SELECT DISTINCT preview.*
              FROM {files} AS preview
              JOIN {files} AS origin
                ON preview.filename = origin.contenthash
             WHERE preview.component = 'core'
               AND preview.filearea = 'preview'
               AND preview.filepath = '/totara_catalog_medium/ventura/'
               AND origin.id IS NOT NULL
               AND origin.component IN ('course', 'totara_program', 'engage_article')
               AND origin.filearea IN ('images', 'image')
               AND origin.mimetype = 'image/gif'
        ";

        $fstorage = get_file_storage();
        $records = $DB->get_records_sql($query);
        foreach ($records as $record) {
            $fstorage->get_file_instance($record)->delete();
        }

        upgrade_plugin_savepoint(true, 2021031000, 'totara', 'catalog');
    }

    return true;
}

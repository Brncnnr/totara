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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package performelement_linked_review
 */

/**
 * Database upgrade script
 *
 * @param integer $oldversion Current (pre-upgrade) local db version timestamp
 * @return bool
 *
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_performelement_linked_review_upgrade($oldversion) {
    global $DB, $CFG;

    $dbman = $DB->get_manager();

    if ($oldversion < 2021090700) {
        // Define field meta_data to be added to perform_element_linked_review_content.
        $table = new xmldb_table('perform_element_linked_review_content');
        $field = new xmldb_field('meta_data', XMLDB_TYPE_TEXT, null, null, null, null, null, 'content_type');

        // Conditionally launch add field meta_data.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Linked_review savepoint reached.
        upgrade_plugin_savepoint(true, 2021090700, 'performelement', 'linked_review');
    }

    return true;
}

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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_evidence
 */

/**
 * Database upgrade script
 *
 * @param  integer $oldversion Current (pre-upgrade) local db version timestamp
 * @return boolean
 */
function xmldb_totara_evidence_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2022042701) {
        global $CFG;
        require_once($CFG->dirroot . '/totara/evidence/db/upgradelib.php');

        // Add item_origin field to totara_evidence_item to distinguish between imported and manually added evidence.
        $table = new xmldb_table('totara_evidence_item');
        $field = new xmldb_field('imported', XMLDB_TYPE_INTEGER, '1', null, true, null, '0', 'modified_at');

        // Conditionally launch add field timeachieved.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        totara_evidence_restore_legacy_import_types();

        // Criteria savepoint reached.
        upgrade_plugin_savepoint(true, 2022042701, 'totara', 'evidence');
    }

    if ($oldversion < 2022042702) {
        global $CFG;
        require_once($CFG->dirroot . '/totara/reportbuilder/db/upgradelib.php');

        reportbuilder_rename_data('columns', 'evidence_item', 'base', 'location', 'type', 'location');

        totara_reportbuilder_inject_filter_into_report(
            'evidence_record_of_learning',
            'base',
            'source',
            '',
            ['operator' => 1, 'value' => 1]
        );

        totara_reportbuilder_inject_filter_into_report(
            'evidence_bank_self',
            'base',
            'source',
            '',
            ['operator' => 1, 'value' => 0]
        );

        totara_reportbuilder_inject_filter_into_report(
            'evidence_bank_other',
            'base',
            'source',
            '',
            ['operator' => 1, 'value' => 0]
        );

        // Criteria savepoint reached.
        upgrade_plugin_savepoint(true, 2022042702, 'totara', 'evidence');
    }

    if ($oldversion < 2022042703) {
        require_once($CFG->dirroot . '/totara/evidence/db/upgradelib.php');

        // Remove deleted users' evidence.
        totara_evidence_remove_deleted_user_evidence();

        // Criteria savepoint reached.
        upgrade_plugin_savepoint(true, 2022042703, 'totara', 'evidence');
    }

    return true;
}
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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package contentmarketplace_linkedin
 */

use contentmarketplace_linkedin\workflow\mod_contentmarketplace\create_marketplace_activity\linkedin;
use core\orm\query\builder;

defined('MOODLE_INTERNAL') || die();

function xmldb_contentmarketplace_linkedin_upgrade($oldversion): bool {
    global $DB;
    require_once(__DIR__ . '/upgradelib.php');
    $dbman = $DB->get_manager();

    if ($oldversion < 2021081900) {
        // Define table linkedin_user_completion to be created.
        $table = new xmldb_table('linkedin_user_completion');

        // Adding fields to table linkedin_user_completion.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('user_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('learning_object_urn', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('progress', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('completion', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null);
        $table->add_field('time_created', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table linkedin_user_completion.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('user_id_fk', XMLDB_KEY_FOREIGN, ['user_id'], 'user', ['id'], 'cascade');

        // Adding indexes to table linkedin_user_completion.
        $table->add_index('progress_idx', XMLDB_INDEX_NOTUNIQUE, ['progress']);
        $table->add_index('time_created_idx', XMLDB_INDEX_NOTUNIQUE, ['time_created']);

        // Conditionally launch create table for linkedin_user_completion.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Linkedin savepoint reached.
        upgrade_plugin_savepoint(true, 2021081900, 'contentmarketplace', 'linkedin');
    }

    if ($oldversion < 2021090200) {
        // Define table marketplace_linkedin_user_completion to be renamed to marketplace_linkedin_user_completion.
        $table = new xmldb_table('linkedin_user_completion');// Define key user_id_fk (foreign) to be dropped form marketplace_linkedin_user_completion.
        $key = new xmldb_key('user_id_fk', XMLDB_KEY_FOREIGN, ['user_id'], 'user', ['id'], 'cascade');

        // Launch drop key user_id_fk.
        if ($dbman->key_exists($table, $key)) {
            $dbman->drop_key($table, $key);
        }


        if ($dbman->table_exists("linkedin_user_completion")) {
            // Launch rename table for marketplace_linkedin_user_completion.
            $dbman->rename_table($table, 'marketplace_linkedin_user_completion');
            $new_table = new xmldb_table("marketplace_linkedin_user_completion");

            $dbman->add_key($new_table, $key);
        }

        // Linkedin savepoint reached.
        upgrade_plugin_savepoint(true, 2021090200, 'contentmarketplace', 'linkedin');
    }

    if ($oldversion < 2021090201) {
        // Define field availability to be added to marketplace_linkedin_learning_object.
        $table = new xmldb_table('marketplace_linkedin_learning_object');
        $field = new xmldb_field('availability', XMLDB_TYPE_CHAR, '10', null, null, null, null, 'asset_type');

        // Conditionally launch add field availability.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $db = builder::get_db();
        $records = $db->get_records('marketplace_linkedin_learning_object');

        foreach ($records as $record) {
            $record->availability = 'AVAILABLE';
            $db->update_record('marketplace_linkedin_learning_object', $record);
        }
        // Linkedin savepoint reached.
        upgrade_plugin_savepoint(true, 2021090201, 'contentmarketplace', 'linkedin');
    }

    if ($oldversion < 2021111800) {
        // Define key user_id_fk (foreign) to be dropped form marketplace_linkedin_user_completion.
        $table = new xmldb_table('marketplace_linkedin_user_completion');
        $key = new xmldb_key('user_id_fk', XMLDB_KEY_FOREIGN, array('user_id'), 'user', array('id'), 'cascade');

        // Launch drop key user_id_fk.
        if ($dbman->key_exists($table, $key)) {
            $dbman->drop_key($table, $key);
        }

        // Define index progress_idx (not unique) to be dropped form marketplace_linkedin_user_completion.
        $table = new xmldb_table('marketplace_linkedin_user_completion');
        $index = new xmldb_index('progress_idx', XMLDB_INDEX_NOTUNIQUE, array('progress'));

        // Conditionally launch drop index progress_idx.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Define index time_created_idx (not unique) to be dropped form marketplace_linkedin_user_completion.
        $table = new xmldb_table('marketplace_linkedin_user_completion');
        $index = new xmldb_index('time_created_idx', XMLDB_INDEX_NOTUNIQUE, array('time_created'));

        // Conditionally launch drop index time_created_idx.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Define field completed to be dropped from marketplace_linkedin_user_completion.
        $table = new xmldb_table('marketplace_linkedin_user_completion');
        $field = new xmldb_field('completion');

        // Conditionally launch drop field completed.
        if ($dbman->table_exists($table) && $dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define table marketplace_linkedin_user_completion to be renamed to marketplace_linkedin_user_progress.
        $table = new xmldb_table('marketplace_linkedin_user_completion');
        $new_table = new xmldb_table('marketplace_linkedin_user_progress');
        if (!$dbman->table_exists($new_table)) {
            // Launch rename table for marketplace_linkedin_user_completion.
            $dbman->rename_table($table, 'marketplace_linkedin_user_progress');
        }

        // Define key user_id_fk (foreign) to be added to marketplace_linkedin_user_progress.
        $table = new xmldb_table('marketplace_linkedin_user_progress');
        $key = new xmldb_key('user_id_fk', XMLDB_KEY_FOREIGN, array('user_id'), 'user', array('id'), 'cascade');

        // Launch add key user_id_fk.
        if (!$dbman->key_exists($table, $key)) {
            $dbman->add_key($table, $key);
        }

        // Changing precision of field progress on table marketplace_linkedin_user_progress to (3).
        $table = new xmldb_table('marketplace_linkedin_user_progress');
        $field = new xmldb_field('progress', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, null, 'learning_object_urn');

        // Launch change of precision for field progress.
        $dbman->change_field_precision($table, $field);

        // Define field time_updated to be added to marketplace_linkedin_user_progress.
        $table = new xmldb_table('marketplace_linkedin_user_progress');
        $field = new xmldb_field('time_updated', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'time_created');

        // Conditionally launch add field time_updated.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Set time_updated to be the same as time_created
        $DB->execute("UPDATE {marketplace_linkedin_user_progress} SET time_updated = time_created WHERE time_updated IS NULL");

        // Changing nullability of field time_updated on table marketplace_linkedin_user_progress to not null.
        $table = new xmldb_table('marketplace_linkedin_user_progress');
        $field = new xmldb_field('time_updated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'time_created');

        // Launch change of nullability for field time_updated.
        $dbman->change_field_notnull($table, $field);

        // Define field time_completed to be added to marketplace_linkedin_user_progress.
        $table = new xmldb_table('marketplace_linkedin_user_progress');
        $field = new xmldb_field('time_completed', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'time_updated');

        // Conditionally launch add field time_completed.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Set time_completed if progress is 100
        $DB->execute("UPDATE {marketplace_linkedin_user_progress} SET time_completed = time_updated WHERE progress >= 100 AND time_completed IS NULL");

        // Define index learning_object_urn_idx (not unique) to be added to marketplace_linkedin_user_progress.
        $table = new xmldb_table('marketplace_linkedin_user_progress');
        $index = new xmldb_index('learning_object_urn_idx', XMLDB_INDEX_NOTUNIQUE, array('learning_object_urn'));

        // Conditionally launch add index learning_object_urn_idx.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index user_id_urn_idx (unique) to be added to marketplace_linkedin_user_progress.
        $table = new xmldb_table('marketplace_linkedin_user_progress');
        $index = new xmldb_index('user_id_urn_idx', XMLDB_INDEX_UNIQUE, array('user_id', 'learning_object_urn'));

        // Conditionally launch add index user_id_urn_idx.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Linkedin savepoint reached.
        upgrade_plugin_savepoint(true, 2021111800, 'contentmarketplace', 'linkedin');
    }

    if ($oldversion < 2021111801) {
        contentmarketplace_linkedin_create_activity_progress_entries();

        // Linkedin savepoint reached.
        upgrade_plugin_savepoint(true, 2021111801, 'contentmarketplace', 'linkedin');
    }

    if ($oldversion < 2021111802) {
        $workflow = linkedin::instance();
        $workflow->enable();
        upgrade_plugin_savepoint(true, 2021111802, 'contentmarketplace', 'linkedin');
    }

    return true;
}
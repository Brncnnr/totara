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
 * @package totara_oauth2
 */
defined('MOODLE_INTERNAL') || die();

/**
 * @param int $old_version
 * @return bool
 */
function xmldb_totara_oauth2_upgrade(int $old_version): bool {
    global $DB;
    require_once(__DIR__ . '/upgradelib.php');
    $db_manager = $DB->get_manager();

    if ($old_version < 2021081800) {
        // Define table totara_oauth2_client_provider to be created.
        $table = new xmldb_table('totara_oauth2_client_provider');

        // Adding fields to table totara_oauth2_client_provider.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('client_id', XMLDB_TYPE_CHAR, '80', null, XMLDB_NOTNULL, null, null);
        $table->add_field('client_secret', XMLDB_TYPE_CHAR, '80', null, XMLDB_NOTNULL, null, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('description_format', XMLDB_TYPE_INTEGER, '1', null, null, null, null);
        $table->add_field('scope', XMLDB_TYPE_CHAR, '1333', null, null, null, null);
        $table->add_field('grant_types', XMLDB_TYPE_CHAR, '80', null, null, null, null);
        $table->add_field('time_created', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table totara_oauth2_client_provider.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Adding indexes to table totara_oauth2_client_provider.
        $table->add_index('name_idx', XMLDB_INDEX_NOTUNIQUE, ['name']);
        $table->add_index('client_id_idx', XMLDB_INDEX_UNIQUE, ['client_id']);
        $table->add_index('client_secret_idx', XMLDB_INDEX_UNIQUE, ['client_secret']);
        $table->add_index('time_created_idx', XMLDB_INDEX_NOTUNIQUE, ['time_created']);

        // Conditionally launch create table for totara_oauth2_client_provider.
        if (!$db_manager->table_exists($table)) {
            $db_manager->create_table($table);
        }

        // Oauth2 savepoint reached.
        upgrade_plugin_savepoint(true, 2021081800, 'totara', 'oauth2');
    }

    if ($old_version < 2021081801) {
        // Define table totara_oauth2_access_token to be created.
        $table = new xmldb_table('totara_oauth2_access_token');

        // Adding fields to table totara_oauth2_access_token.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('client_provider_id', XMLDB_TYPE_CHAR, '80', null, XMLDB_NOTNULL, null, null);
        $table->add_field('identifier', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('expires', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('scope', XMLDB_TYPE_CHAR, '1333', null, null, null, null);

        // Adding keys to table totara_oauth2_access_token.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('client_probider_id_fk', XMLDB_KEY_FOREIGN, ['client_provider_id'], 'totara_oauth2_client_provider', ['id']);

        // Adding indexes to table totara_oauth2_access_token.
        $table->add_index('identifier_idx', XMLDB_INDEX_UNIQUE, ['identifier']);

        // Conditionally launch create table for totara_oauth2_access_token.
        if (!$db_manager->table_exists($table)) {
            $db_manager->create_table($table);
        }

        // Oauth2 savepoint reached.
        upgrade_plugin_savepoint(true, 2021081801, 'totara', 'oauth2');
    }

    if ($old_version < 2021081802) {
        // Define field id_number to be added to totara_oauth2_client_provider.
        $table = new xmldb_table('totara_oauth2_client_provider');
        $field = new xmldb_field('id_number', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null, 'name');

        // Conditionally launch add field id_number.
        if (!$db_manager->field_exists($table, $field)) {
            $db_manager->add_field($table, $field);
        }

        // Oauth2 savepoint reached.
        upgrade_plugin_savepoint(true, 2021081802, 'totara', 'oauth2');
    }

    if ($old_version < 2021081803) {
        // Define index id_number_idx (unique) to be added to totara_oauth2_client_provider.
        $table = new xmldb_table('totara_oauth2_client_provider');
        $index = new xmldb_index('id_number_idx', XMLDB_INDEX_UNIQUE, ['id_number']);

        // Conditionally launch add index id_number_idx.
        if (!$db_manager->index_exists($table, $index)) {
            $db_manager->add_index($table, $index);
        }

        // Oauth2 savepoint reached.
        upgrade_plugin_savepoint(true, 2021081803, 'totara', 'oauth2');
    }

    if ($old_version < 2021090200) {
        // Define field component to be added to totara_oauth2_client_provider.
        $table = new xmldb_table('totara_oauth2_client_provider');
        $field = new xmldb_field('component', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'grant_types');

        // Conditionally launch add field component.
        if (!$db_manager->field_exists($table, $field)) {
            $db_manager->add_field($table, $field);
        }

        // Slightly a hack to update the current record from linkedin learning.
        if ($DB->record_exists("totara_oauth2_client_provider", ["id_number" => "linkedin_learning"])) {
            $record = $DB->get_record(
                "totara_oauth2_client_provider",
                ["id_number" => "linkedin_learning"],
                "*",
                MUST_EXIST
            );

            $record->component = "contentmarketplace_linkedin";
            $DB->update_record("totara_oauth2_client_provider", $record);
        }

        // Oauth2 savepoint reached.
        upgrade_plugin_savepoint(true, 2021090200, 'totara', 'oauth2');
    }

    if ($old_version < 2021110801) {

        // Define field id_number to be dropped from totara_oauth2_client_provider.
        $table = new xmldb_table('totara_oauth2_client_provider');

        $index = new xmldb_index('id_number_idx', XMLDB_INDEX_UNIQUE, array('id_number'));
        // Conditionally launch drop index id_number_idx.
        if ($db_manager->index_exists($table, $index)) {
            $db_manager->drop_index($table, $index);
        }

        $field = new xmldb_field('id_number');
        // Conditionally launch drop field id_number.
        if ($db_manager->field_exists($table, $field)) {
            $db_manager->drop_field($table, $field);
        }

        // Oauth2 savepoint reached.
        upgrade_plugin_savepoint(true, 2021110801, 'totara', 'oauth2');
    }

    if ($old_version < 2021110802) {

        $table = new xmldb_table('totara_oauth2_access_token');
        $key = new xmldb_key('client_provider_id_fk', XMLDB_KEY_FOREIGN, array('client_provider_id'), 'totara_oauth2_client_provider', ['id'], 'cascade');

        // If the right key is not there we will still have the wrong one which we need to fix
        if (!$db_manager->key_exists($table, $key)) {
            // Define key client_probider_id_fk (foreign) to be dropped form totara_oauth2_access_token.
            $key = new xmldb_key('client_probider_id_fk', XMLDB_KEY_FOREIGN, array('client_provider_id'), 'totara_oauth2_client_provider', ['id']);

            // Launch drop key client_probider_id_fk.
            if ($db_manager->key_exists($table, $key)) {
                $db_manager->drop_key($table, $key);
            }

            $field = new xmldb_field('client_provider_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'id');

            // Launch change of type for field client_provider_id.
            $db_manager->change_field_type($table, $field);

            $key = new xmldb_key('client_provider_id_fk', XMLDB_KEY_FOREIGN, array('client_provider_id'), 'totara_oauth2_client_provider', ['id'], 'cascade');

            // Launch add key client_probider_id_fk.
            if (!$db_manager->key_exists($table, $key)) {
                $db_manager->add_key($table, $key);
            }
        }

        // Oauth2 savepoint reached.
        upgrade_plugin_savepoint(true, 2021110802, 'totara', 'oauth2');
    }

    if ($old_version < 2021110803) {
        // Define field component to be removed from totara_oauth2_client_provider.
        $table = new xmldb_table('totara_oauth2_client_provider');
        $field = new xmldb_field('component', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'grant_types');

        // Conditionally launch drop field component.
        if ($db_manager->field_exists($table, $field)) {
            $db_manager->drop_field($table, $field);
        }

        // Oauth2 savepoint reached.
        upgrade_plugin_savepoint(true, 2021110803, 'totara', 'oauth2');
    }

    if ($old_version < 2021110804) {
        totara_oauth2_assign_cap_user();
        upgrade_plugin_savepoint(true, 2021110804, 'totara', 'oauth2');
    }

    if ($old_version < 2022042701) {
        $table = new xmldb_table('totara_oauth2_client_provider');
        $field = new xmldb_field('internal', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'grant_types');

        if (!$db_manager->field_exists($table, $field)) {
            $db_manager->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2022042701, 'totara', 'oauth2');
    }

    if ($old_version < 2022042702) {

        // Define field status to be added to totara_oauth2_client_provider.
        $table = new xmldb_table('totara_oauth2_client_provider');
        $field = new xmldb_field('status', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'time_created');

        // Conditionally launch add field status.
        if (!$db_manager->field_exists($table, $field)) {
            $db_manager->add_field($table, $field);
        }

        // Oauth2 savepoint reached.
        upgrade_plugin_savepoint(true, 2022042702, 'totara', 'oauth2');
    }

    if ($old_version < 2022042703) {
        $table = new xmldb_table('totara_oauth2_client_provider');
        $field = new xmldb_field('tenant_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        if (!$db_manager->field_exists($table, $field)) {
            $db_manager->add_field($table, $field);
        }

        $key = new xmldb_key(
            'totara_oauth2_client_provider_tenant_id_fk',
            XMLDB_KEY_FOREIGN,
            ['tenant_id'],
            'tenant',
            ['id'],
            'cascade',
            'cascade'
        );

        if (!$db_manager->key_exists($table, $key)) {
            $db_manager->add_key($table, $key);
        }

        // Oauth2 savepoint reached.
        upgrade_plugin_savepoint(true, 2022042703, 'totara', 'oauth2');
    }

    if ($old_version < 2022092801) {

        // Define field component to be added to totara_oauth2_client_provider.
        $table = new xmldb_table('totara_oauth2_client_provider');
        $field = new xmldb_field('component', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'tenant_id');

        // Conditionally launch add field component.
        if (!$db_manager->field_exists($table, $field)) {
            $db_manager->add_field($table, $field);
        }

        // Oauth2 savepoint reached.
        upgrade_plugin_savepoint(true, 2022092801, 'totara', 'oauth2');
    }

    return true;
}
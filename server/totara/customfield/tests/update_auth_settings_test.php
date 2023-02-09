<?php
/*
 * This file is part of Totara LMS
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
 * @author Maria Torres <maria.torres@totaralearning.com>
 * @package totara_customfield
 */

defined('MOODLE_INTERNAL') || die();

class totara_customfield_update_auth_settings_testcase extends advanced_testcase {

    public function test_profilefield_updated() {
        global $DB;

        $ldaplugin = 'auth_ldap';
        $casplugin = 'auth_cas';

        // Lets setup Auth config for a HireDate profile field.
        $first_auth_config_records = [
            ['plugin' => $ldaplugin, 'name' => 'field_map_profile_field_HireDate', 'value' => 'ldap_date'],
            ['plugin' => $ldaplugin, 'name' => 'field_updatelocal_profile_field_HireDate', 'value' => 'oncreate'],
            ['plugin' => $ldaplugin, 'name' => 'field_updateremote_profile_field_HireDate', 'value' => '1'],
            ['plugin' => $ldaplugin, 'name' => 'field_lock_profile_field_HireDate', 'value' => 'locked'],
            ['plugin' => $casplugin, 'name' => 'field_map_profile_field_HireDate', 'value' => 'cas_date'],
            ['plugin' => $casplugin, 'name' => 'field_updatelocal_profile_field_HireDate', 'value' => 'oncreate'],
            ['plugin' => $casplugin, 'name' => 'field_updateremote_profile_field_HireDate', 'value' => 0],
            ['plugin' => $casplugin, 'name' => 'field_lock_profile_field_HireDate', 'value' => 'unlocked'],
        ];

        // Set config for the Auth plugins.
        foreach($first_auth_config_records as $record) {
            set_config($record['name'], $record['value'], $record['plugin']);
        }

        $eventdata = new stdClass();
        $eventdata->objectid = 1;
        $eventdata->oldshortname = 'HireDate';
        $eventdata->shortname = 'Hiredate';

        $event = \totara_customfield\event\profilefield_updated::create_from_field($eventdata);

        totara_customfield_observer::update_auth_settings($event);

        // Check the fields were replaced correctly with the new custom field name and keep the values.
        $actual = get_config($ldaplugin, 'field_map_profile_field_Hiredate');
        $this->assertEquals('ldap_date', $actual);

        $actual = get_config($ldaplugin, 'field_updatelocal_profile_field_Hiredate');
        $this->assertEquals('oncreate', $actual);

        $actual = get_config($ldaplugin, 'field_updateremote_profile_field_Hiredate');
        $this->assertEquals('1', $actual);

        $actual = get_config($ldaplugin, 'field_lock_profile_field_Hiredate');
        $this->assertEquals('locked', $actual);

        // Check same thing for cas plugin.
        $actual = get_config($casplugin, 'field_map_profile_field_Hiredate');
        $this->assertEquals('cas_date', $actual);

        $actual = get_config($casplugin, 'field_updatelocal_profile_field_Hiredate');
        $this->assertEquals('oncreate', $actual);

        $actual = get_config($casplugin, 'field_updateremote_profile_field_Hiredate');
        $this->assertEquals(0, $actual);

        $actual = get_config($casplugin, 'field_lock_profile_field_Hiredate');
        $this->assertEquals('unlocked', $actual);

        // Check a copy of the deleted values was saved in the config_log table.
        foreach ($first_auth_config_records as $record) {
            $sql = "SELECT 1
                    FROM {config_log}
                    WHERE plugin =:plugin
                    AND name =:name
                    AND " . $DB->sql_compare_text('value') . " = " . $DB->sql_compare_text(':value');
            $conditions = ['plugin' => $record['plugin'], 'name' => $record['name'], 'value' => $record['value']];
            $this->assertTrue($DB->record_exists_sql($sql, $conditions));
        }
    }

    public function test_totara_customfield_upgrade_remove_auth_orphaned_settings() {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/totara/customfield/db/upgradelib.php');

        $ldaplugin = 'auth_ldap';
        $casplugin = 'auth_cas';

        // Create custom profile fields.
        /** @var \totara_core\testing\generator $totaragenerator */
        $totaragenerator = $this->getDataGenerator()->get_plugin_generator('totara_core');
        $totaragenerator->create_custom_profile_field(['datatype' => 'text', 'name' => 'HireDate', 'shortname' => 'HireDate']);

        // These are the records to create that are going to be orphaned as we don't have 'HireDate2' field.
        $orphaned_records = [
            ['plugin' => $ldaplugin, 'name' => 'field_map_profile_field_HireDate2', 'value' => 'ldap_date'],
            ['plugin' => $ldaplugin, 'name' => 'field_updatelocal_profile_field_HireDate2', 'value' => 'oncreate'],
            ['plugin' => $ldaplugin, 'name' => 'field_updateremote_profile_field_HireDate2', 'value' => '1'],
            ['plugin' => $ldaplugin, 'name' => 'field_lock_profile_field_HireDate2', 'value' => 'locked'],
            ['plugin' => $casplugin, 'name' => 'field_map_profile_field_HireDate2', 'value' => 'cas_date'],
            ['plugin' => $casplugin, 'name' => 'field_updatelocal_profile_field_HireDate2', 'value' => 'oncreate'],
            ['plugin' => $casplugin, 'name' => 'field_updateremote_profile_field_HireDate2', 'value' => 0],
            ['plugin' => $casplugin, 'name' => 'field_lock_profile_field_HireDate2', 'value' => 'unlocked'],
        ];

        // Set config for Auth LDAP.
        set_config('field_map_profile_field_HireDate', 'ldap_date', $ldaplugin);
        set_config('field_updatelocal_profile_field_HireDate', 'oncreate', $ldaplugin);
        set_config('field_updateremote_profile_field_HireDate', '1', $ldaplugin);
        set_config('field_lock_profile_field_HireDate', 'locked', $ldaplugin);

        // Set config for Auth Cas.
        set_config('field_map_profile_field_HireDate', 'cas_date', $casplugin);
        set_config('field_updatelocal_profile_field_HireDate', 'oncreate', $casplugin);
        set_config('field_updateremote_profile_field_HireDate', '0', $casplugin);
        set_config('field_lock_profile_field_HireDate', 'unlocked', $casplugin);

        // Set other settings that contain profile_field in its name.
        set_config('unrelated_mapping_profile_field_ldap', 'unrelated_ldap', $ldaplugin);
        set_config('unrelated_mapping_profile_field_cas', 'unrelated_cas', $casplugin);
        set_config('notmatch_field_map_profile_field_HireDate', 'notmatchingfield', $casplugin);

        // Set orphaned records.
        foreach ($orphaned_records as $record) {
            set_config($record['name'], $record['value'], $record['plugin']);
        }

        // Get number of settings for each plugin.
        $ldaplugin_config_count = count((array)get_config($ldaplugin));
        $caslugin_config_count = count((array)get_config($casplugin));

        // Call upgrade function to check it removes the correct fields.
        totara_customfield_upgrade_remove_auth_orphaned_settings();

        foreach ($orphaned_records as $record) {
            $this->assertFalse($DB->record_exists('config_plugins', array('plugin' => $record['plugin'], 'name' => $record['name'])));
        }

        // Check a copy of the deleted values was saved in the config_log table.
        foreach ($orphaned_records as $record) {
            $sql = "SELECT 1
                    FROM {config_log}
                    WHERE plugin =:plugin
                    AND name =:name
                    AND " . $DB->sql_compare_text('value') . " = " . $DB->sql_compare_text(':value');
            $conditions = ['plugin' => $record['plugin'], 'name' => $record['name'], 'value' => $record['value']];
            $this->assertTrue($DB->record_exists_sql($sql, $conditions));
        }

        // Check unrelated settings that contains the word 'profile_field' remains.
        $this->assertTrue(
            $DB->record_exists(
                'config_plugins',
                array('plugin' => $ldaplugin, 'name' => 'unrelated_mapping_profile_field_ldap')
            )
        );

        $this->assertTrue(
            $DB->record_exists(
                'config_plugins',
                array('plugin' => $casplugin, 'name' => 'unrelated_mapping_profile_field_cas')
            )
        );

        $this->assertTrue(
            $DB->record_exists(
                'config_plugins',
                array('plugin' => $casplugin, 'name' => 'notmatch_field_map_profile_field_HireDate')
            )
        );

        // Finally, count the settings and make sure we didn't remove more than expected.
        $this->assertEquals($ldaplugin_config_count - 4, count((array)get_config($ldaplugin)));
        $this->assertEquals($caslugin_config_count - 4, count((array)get_config($casplugin)));
    }
}
<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Jonathan Newman <jonathan.newman@catalyst.net.nz>
 * @author Ciaran Irvine <ciaran.irvine@totaralms.com>
 * @package totara
 * @subpackage totara_core
 */

/**
 * Upgrade script execute right after main lib/db/upgrade.php script
 * if version bump is detected in totara_core.
 *
 * NOTE: this file should not be used for core database changes any more.
 *
 * @param   integer $oldversion Current (pre-upgrade) local db version timestamp
 * @return  boolean $result
 */
function xmldb_totara_core_upgrade($oldversion) {
    global $CFG, $DB;
    require_once(__DIR__ . '/upgradelib.php');

    $dbman = $DB->get_manager();

    if ($oldversion < 2020100100) {
        // Somebody must have hacked upgrade checks, stop them here.
        throw new coding_exception('Upgrades are supported only from Totara 13.0 or later!');
    }

    // Totara 13.0 release line.

    if ($oldversion < 2021012000) {
        // NOTE: move this to the end of upgrade if new plugins to be removed
        //       are added to totara_core_upgrade_delete_removed_plugins()
        totara_core_upgrade_delete_removed_plugins();

        unset_config('allowobjectembed');
        unset_config('enabletrusttext');

        upgrade_plugin_savepoint(true, 2021012000, 'totara', 'core');
    }

    if ($oldversion < 2021020400) {
        // Check the default category, and fix it up if need be.
        $changed = totara_core_refresh_default_category();

        // Get the category at the top of the sortorder (ignoring unsorted)
        $topcats = $DB->get_records_select(
            'course_categories',
            'depth = 1 AND sortorder != 0',
            null,
            'sortorder, id',
            'issystem',
            0,
            1
        );
        $topcat = array_pop($topcats); // It's 1 record but in an array anyway.

        /**
         * We've added a new category and need to fix the ordering,
         * or we have a system category at the head of the sortorder.
         * Either way we need to resort the course categories.
         */
        if ($changed || !empty($topcat->issystem)) {
            totara_core_fix_course_sortorder(true); // Verbose.
        }

        // Main savepoint reached.
        upgrade_plugin_savepoint(true, 2021020400, 'totara', 'core');
    }

    if ($oldversion < 2021052501) {
        // Define field type to be added to oauth2_issuer.
        $table = new xmldb_table('oauth2_issuer');
        $field = new xmldb_field('type', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'requireconfirmation');

        // Conditionally launch add field type.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field show_default_branding to be added to oauth2_issuer.
        $table = new xmldb_table('oauth2_issuer');
        $field = new xmldb_field('show_default_branding', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'type', ['0', '1']);

        // Conditionally launch add field show_default_branding.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_oauth2_issuers_add_types();

        // Main savepoint reached.
        upgrade_plugin_savepoint(true, 2021052501, 'totara', 'core');
    }

    if ($oldversion < 2021061500) {
        // Reset default scheduled 'send_registration_data_task' task as the times have now been randomised.
        $taskname = '\totara_core\task\send_registration_data_task';
        $defaulttask = \core\task\manager::get_default_scheduled_task($taskname);

        $task = \core\task\manager::get_scheduled_task($taskname);
        $task->set_minute($defaulttask->get_minute());
        $task->set_hour($defaulttask->get_hour());
        $task->set_month($defaulttask->get_month());
        $task->set_day_of_week($defaulttask->get_day_of_week());
        $task->set_day($defaulttask->get_day());
        $task->set_disabled($defaulttask->get_disabled());
        $task->set_customised(false);

        \core\task\manager::configure_scheduled_task($task);

        // Main savepoint reached.
        upgrade_plugin_savepoint(true, 2021061500, 'totara', 'core');
    }

    if ($oldversion < 2021070700) {
        // Changing precision of field name on table course_categories to (1333).
        $table = new xmldb_table('course_categories');
        $field = new xmldb_field('name', XMLDB_TYPE_CHAR, '1333', null, XMLDB_NOTNULL, null, null, 'id');

        // Launch change of precision for field name.
        $dbman->change_field_precision($table, $field);

        // Main savepoint reached.
        upgrade_plugin_savepoint(true, 2021070700, 'totara', 'core');
    }

    if ($oldversion < 2021111101) {
        // Define field 'progress' to be added to 'course_modules_completion' table.
        $table = new xmldb_table('course_modules_completion');
        $field = new xmldb_field('progress', XMLDB_TYPE_INTEGER, 3, null, null, null, null, 'completionstate');

        // Conditionally launch add field type.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        // Main savepoint reached.
        upgrade_plugin_savepoint(true, 2021111101, 'totara', 'core');
    }

    if ($oldversion < 2022043001) {
        // Adding in the additional badgr domains (if they don't already exist)
        $badgr_regions = [
            'https://eu.badgr.com' => 'https://api.eu.badgr.io/v2',
            'https://ca.badgr.com' => 'https://api.ca.badgr.io/v2',
            'https://au.badgr.com' => 'https://api.au.badgr.io/v2',
        ];
        foreach ($badgr_regions as $web => $api) {
            $backpack = new \stdClass();
            $backpack->backpackapiurl = $api;
            $backpack->backpackweburl = $web;
            $backpack->apiversion = 2;
            $backpack->sortorder = 1;
            $backpack->password = '';

            if (!$DB->record_exists_select(
                'badge_external_backpack',
                'backpackapiurl = :api OR backpackweburl = :web',
                compact('api', 'web')
            )) {
                $DB->insert_record('badge_external_backpack', $backpack);
            }
        }

        // Main savepoint reached.
        upgrade_plugin_savepoint(true, 2022043001, 'totara', 'core');
    }

    if ($oldversion < 2022052801) {
        global $DB;

        // Add new role and set role context level.
        $role_names = ['approvalworkflowapprover', 'approvalworkflowmanager'];

        foreach ($role_names as $role_name) {
            if (!$DB->record_exists('role', ['shortname' => $role_name])) {
                create_role('', $role_name, '', $role_name);
            }

            $role = $DB->get_record('role', ['shortname' => $role_name], '*', MUST_EXIST);
            set_role_contextlevels($role->id, get_default_contextlevels($role->archetype));
        }

        // Main savepoint reached.
        upgrade_plugin_savepoint(true, 2022052801, 'totara', 'core');
    }

    if ($oldversion < 2022110803) {
        // Added a new capability in lib/db/access.php which won't be processed unless the main version file is bumped, which
        // we don't want to do.
        // However it is easy to trigger this ourselves in the same way that upgrade does.
        update_capabilities('moodle');

        upgrade_plugin_savepoint(true, 2022110803, 'totara', 'core');
    }

    return true;
}

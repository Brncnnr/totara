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
 * @package mod_facetoface
 */

// This file keeps track of upgrades to
// the facetoface module
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installtion to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the functions defined in lib/ddllib.php

/**
 * Local database upgrade script
 *
 * @param   int $oldversion Current (pre-upgrade) local db version timestamp
 * @return  boolean always true
 */
function xmldb_facetoface_upgrade($oldversion) {
    global $CFG, $DB;
    require_once(__DIR__ . '/upgradelib.php');

    $dbman = $DB->get_manager();

    // Totara 13.0 release line.

    if ($oldversion < 2020113000) {
        // Fixed the orphaned records with statuscode 50 as we deprecated "Approved" status.
        facetoface_upgradelib_approval_to_declined_status();

        // Facetoface savepoint reached.
        upgrade_mod_savepoint(true, 2020113000, 'facetoface');
    }

    // Virtual room updates.
    if ($oldversion < 2020122100) {
        // Create the room dates virtual meeting table to link room dates to virtual meetings.
        $table = new xmldb_table('facetoface_room_dates_virtualmeeting');
        // Fields.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('status', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('roomdateid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('virtualmeetingid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        // Keys.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('roomdatevm_date_fk', XMLDB_KEY_FOREIGN, array('roomdateid'), 'facetoface_room_dates', array('id'));
        $table->add_key('roomdatevm_meet_fk', XMLDB_KEY_FOREIGN, array('virtualmeetingid'), 'virtualmeeting', array('id'));

        // Add the meetingid field to room dates.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Create the room virtual meeting table, to link a room with a wirtual meeting plugin type.
        $table = new xmldb_table('facetoface_room_virtualmeeting');
        // Fields.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('status', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('roomid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('plugin', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
        $table->add_field('options', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        // Keys.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('roomvm_room_fk', XMLDB_KEY_FOREIGN, array('roomid'), 'facetoface_room', array('id'));
        $table->add_key('roomvm_user_fk', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));
        // Indexs.
        $table->add_index('roomvm_plugin', XMLDB_INDEX_NOTUNIQUE, array('plugin'));

        // Add the meetingid field to room dates.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Create the default notification template for virtualmeetingfailure.
        facetoface_upgradelib_add_new_template(
            'virtualmeetingfailure',
            get_string('setting:defaultvirtualmeetingfailuresubjectdefault', 'facetoface'),
            get_string('setting:defaultvirtualmeetingfailuremessagedefault', 'facetoface'),
            1 << 25 // MDL_F2F_CONDITION_VIRTUALMEETING_CREATION_FAILURE
        );

        // Facetoface savepoint reached.
        upgrade_mod_savepoint(true, 2020122100, 'facetoface');
    }

    if ($oldversion < 2021011800) {
        // Fixed the orphaned url records left after room changed from 'Internal' to 'MS teams'.
        facetoface_upgradelib_clear_room_url();

        // Facetoface savepoint reached.
        upgrade_mod_savepoint(true, 2021011800, 'facetoface');
    }

    if ($oldversion < 2021012300) {

        // ==== facetoface_room_dates_virtualmeeting ====
        //   1. Add field sessionsdateid
        //   2. Add foreign key sessionsdateid
        //   3. Add field roomid
        //   4. Add foreign key roomid
        //   5. Database migration roomdateid to sessionsdateid, roomid
        //   6. Drop foreign key virtualmeetingid
        //   7. Change nullable virtualmeetingid
        //  8. Restore foreign key virtualmeetingid
        //  9. Add unique index sessionsdateid, roomid
        //  10. Drop foreign key roomdateid
        //  11. Drop field roomdateid
        // ==== finalise ====
        //  12. Fix up the status field of existing virtual meetings.

        $table = new xmldb_table('facetoface_room_dates_virtualmeeting');
        // 1. Launch add field sessionsdateid.
        $field = new xmldb_field('sessionsdateid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'status');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        // 2. Launch add key roomdatevm_sess_fk.
        $key = new xmldb_key('roomdatevm_sess_fk', XMLDB_KEY_FOREIGN, array('sessionsdateid'), 'facetoface_sessions_dates', array('id'));
        if (!$dbman->key_exists($table, $key)) {
            $dbman->add_key($table, $key);
        }
        // 3. Launch add field roomid.
        $field = new xmldb_field('roomid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'sessionsdateid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        // 4. Launch add key roomdatevm_room_fk.
        $key = new xmldb_key('roomdatevm_room_fk', XMLDB_KEY_FOREIGN, array('roomid'), 'facetoface_room', array('id'));
        if (!$dbman->key_exists($table, $key)) {
            $dbman->add_key($table, $key);
        }

        // 5. Database migration from the previous versions.
        $records = $DB->get_records_sql(
            'SELECT frdvm.id, frd.roomid, frd.sessionsdateid
               FROM {facetoface_room_dates_virtualmeeting} frdvm
               JOIN {facetoface_room_dates} frd ON frdvm.roomdateid = frd.id'
        );
        foreach ($records as $record) {
            $DB->update_record('facetoface_room_dates_virtualmeeting', $record);
        }

        // 6. Launch drop key roomdatevm_meet_fk.
        $key = new xmldb_key('roomdatevm_meet_fk', XMLDB_KEY_FOREIGN, array('virtualmeetingid'), 'virtualmeeting', array('id'));
        if ($dbman->key_exists($table, $key)) {
            $dbman->drop_key($table, $key);
        }

        // 7. Launch change of nullability for field virtualmeetingid.
        $field = new xmldb_field('virtualmeetingid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'roomdateid');
        $dbman->change_field_notnull($table, $field);

        // 8. Launch add key roomdatevm_meet_fk.
        $key = new xmldb_key('roomdatevm_meet_fk', XMLDB_KEY_FOREIGN, array('virtualmeetingid'), 'virtualmeeting', array('id'));
        $dbman->add_key($table, $key);

        // 9. Launch add index roomdatevm_sessdatemeet_ix.
        $index = new xmldb_index('roomdatevm_sessmeet_ix', XMLDB_INDEX_UNIQUE, array('sessionsdateid', 'roomid'));
        $dbman->add_index($table, $index);

        // 10. Launch drop key roomdatevm_date_fk.
        $key = new xmldb_key('roomdatevm_date_fk', XMLDB_KEY_FOREIGN, array('roomdateid'), 'facetoface_room_dates', array('id'));
        if ($dbman->key_exists($table, $key)) {
            $dbman->drop_key($table, $key);
        }
        // 11. Launch drop field roomdateid.
        $field = new xmldb_field('roomdateid');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // 12. Fix up the status field of existing virtual meetings.
        facetoface_upgradelib_upgrade_existing_virtual_meetings();

        // Facetoface savepoint reached.
        upgrade_mod_savepoint(true, 2021012300, 'facetoface');
    }

    if ($oldversion < 2021021800) {
        // Change virtual meeting creating failure notification template.
        $oldplaceholder = '[session:room:link]';
        $newplaceholder = '[seminareventdetailslink]';

        // Load templates that match reference
        $templates = $DB->get_records('facetoface_notification_tpl', ['reference' => 'virtualmeetingfailure']);
        $matchingtemplates = [];

        // For each matching template, see if old placeholder is in use and replace it
        foreach ($templates as $template) {
            if (isset($template->body) && strpos($template->body, $oldplaceholder) !== false) {
                $matchingtemplates[$template->id] = ['old' => $template->body];
                $template->body = str_replace($oldplaceholder, $newplaceholder, $template->body);
                $matchingtemplates[$template->id]['new'] = $template->body;
                $DB->update_record('facetoface_notification_tpl', $template);
            }
        }

        // For each of the matching templates, sync up the body on linked activity notifications that haven't been changed
        foreach ($matchingtemplates as $id => $templatebody) {
            $notifications = $DB->get_records('facetoface_notification', ['templateid' => $id]);
            foreach ($notifications as $f2f_notification) {
                if (isset($f2f_notification->body) && $f2f_notification->body == $templatebody['old']) {
                    $f2f_notification->body = $templatebody['new'];
                    $DB->update_record('facetoface_notification', $f2f_notification);
                }
            }
        }

        // Facetoface savepoint reached.
        upgrade_mod_savepoint(true, 2021021800, 'facetoface');
    }

    /**
     * Replace 'sessiondate' with 'sessionstartdate' and 'datefinish' with 'sessionfinishdate' column values
     * for 'rb_source_facetofcae_sessions' and 'rb_source_facetoface_signin' seminar report sources to make consistency and
     * use it as a single column value for all seminar report sources
     */
    if ($oldversion < 2021101300) {

        facetoface_upgradelib_migrate_reoportbuilder_date_fields();
        // Facetoface savepoint reached.
        upgrade_mod_savepoint(true, 2021101300, 'facetoface');
    }

    /**
     * Remove deleted roles from facetoface_session_roles config
     */
    if ($oldversion < 2022012500) {
        $session_roles = get_config('', 'facetoface_session_roles');
        if (!empty($session_roles)) {
            list($sql, $params) = $DB->get_in_or_equal(explode(',', $session_roles));
            $roles = $DB->get_fieldset_select('role', 'id', "id {$sql}", $params);
            set_config('facetoface_session_roles', implode(',', $roles));
        }
        upgrade_mod_savepoint(true, 2022012500, 'facetoface');
    }

    if ($oldversion < 2022032500) {
        // Add the legacy_notifications column to {facetoface}
        $table = new xmldb_table('facetoface');
        $field = new xmldb_field('legacy_notifications', XMLDB_TYPE_INTEGER, '2', null, null, null, "0", 'completiondelay');

        // Conditionally launch add field and set the default to LEGACY for existing seminars
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);

            set_config('legacy_notifications', '1', 'facetoface');

            $sql = "UPDATE {facetoface} SET legacy_notifications = 1";
            $DB->execute($sql);
        }

        // Set the global setting
        set_config('facetoface_allow_legacy_notifications', '1');

        // Facetoface savepoint reached.
        upgrade_mod_savepoint(true, 2022032500, 'facetoface');
    }

    if ($oldversion < 2022032800) {
        require_once("{$CFG->dirroot}/totara/notification/db/upgradelib.php");

        totara_notification_sync_built_in_notification('mod_facetoface');

        // Facetoface savepoint reached.
        upgrade_mod_savepoint(true, 2022032800, 'facetoface');
    }

    return true;
}

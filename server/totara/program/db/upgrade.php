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
 * @author Ciaran Irvine <ciaran.irvine@totaralms.com>
 * @package totara
 * @subpackage program
 */

use totara_program\totara_notification\notification\assigned_for_managers;
use totara_program\totara_notification\notification\assigned_for_subject;
use totara_program\totara_notification\notification\completed_for_managers;
use totara_program\totara_notification\notification\completed_for_subject;
use totara_program\totara_notification\notification\course_set_completed_for_managers;
use totara_program\totara_notification\notification\course_set_completed_for_subject;
use totara_program\totara_notification\notification\new_exception_for_site_admin;
use totara_program\totara_notification\notification\unassigned_for_managers;
use totara_program\totara_notification\notification\unassigned_for_subject;
use totara_program\totara_notification\resolver\assigned;
use totara_program\totara_notification\resolver\completed;
use totara_program\totara_notification\resolver\course_set_completed;
use totara_program\totara_notification\resolver\course_set_due_date;
use totara_program\totara_notification\resolver\due_date;
use totara_program\totara_notification\resolver\new_exception;
use totara_program\totara_notification\resolver\unassigned;

/**
 * Local database upgrade script
 *
 * @param   integer $oldversion Current (pre-upgrade) local db version timestamp
 * @return  boolean $result
 */
function xmldb_totara_program_upgrade($oldversion) {
    global $CFG, $DB;
    require_once("{$CFG->dirroot}/totara/notification/db/upgradelib.php");
    require_once("{$CFG->dirroot}/totara/program/db/upgradelib.php");
    require_once("{$CFG->dirroot}/totara/program/program_messages.class.php");

    $dbman = $DB->get_manager();

    if ($oldversion < 2021041100) {
        totara_program_upgrade_migrate_messages(
            assigned::class,
            [MESSAGETYPE_ENROLMENT => false],
            true,
            'alert',
            'totara_message',
            [assigned_for_managers::class, assigned_for_subject::class]
        );

        totara_program_upgrade_migrate_messages(
            unassigned::class,
            [MESSAGETYPE_UNENROLMENT => false],
            true,
            'alert',
            'totara_message',
            [unassigned_for_managers::class, unassigned_for_subject::class]
        );

        totara_program_upgrade_migrate_messages(
            due_date::class,
            [MESSAGETYPE_PROGRAM_DUE => true, MESSAGETYPE_PROGRAM_OVERDUE => false],
            true,
            'alert',
            'totara_message',
            []
        );

        totara_program_upgrade_migrate_messages(
            completed::class,
            [MESSAGETYPE_PROGRAM_COMPLETED => false, MESSAGETYPE_LEARNER_FOLLOWUP => false],
            true,
            'alert',
            'totara_message',
            [completed_for_managers::class, completed_for_subject::class]
        );

        totara_program_upgrade_migrate_messages(
            course_set_due_date::class,
            [MESSAGETYPE_COURSESET_DUE => true, MESSAGETYPE_COURSESET_OVERDUE => false],
            true,
            'alert',
            'totara_message',
            []
        );

        totara_program_upgrade_migrate_messages(
            course_set_completed::class,
            [MESSAGETYPE_COURSESET_COMPLETED => false],
            true,
            'alert',
            'totara_message',
            [course_set_completed_for_managers::class, course_set_completed_for_subject::class]
        );

        totara_program_upgrade_migrate_messages(
            new_exception::class,
            [MESSAGETYPE_EXCEPTION_REPORT => false],
            true,
            'alert',
            'totara_message',
            [new_exception_for_site_admin::class]
        );

        // Savepoint reached.
        upgrade_plugin_savepoint(true, 2021041100, 'totara', 'program');
    }

    if ($oldversion < 2021091500) {
        $table = new xmldb_table('prog_assignment');

        // Define field completionoffsetamount to be added to prog_assignment.
        $field = new xmldb_field('completionoffsetamount', XMLDB_TYPE_INTEGER, '4', null, null, null, null, 'completiontime');

        // Conditionally launch add field completionoffsetamount.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field completionoffsetunit to be added to prog_assignment.
        $field = new xmldb_field('completionoffsetunit', XMLDB_TYPE_INTEGER, '1', null, null, null, null, 'completionoffsetamount');

        // Conditionally launch add field completionoffsetunit.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Changing nullability and the default of field completiontime on table prog_assignment.
        $field = new xmldb_field('completiontime', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'includechildren');

        // Launch change of nullability for field completiontime.
        $dbman->change_field_notnull($table, $field);

        // Launch change of default for field completiontime.
        $dbman->change_field_default($table, $field);

        // Migrate data to the new column.
        totara_program_upgrade_migrate_relative_dates_data();

        // Program savepoint reached.
        upgrade_plugin_savepoint(true, 2021091500, 'totara', 'program');
    }

    if ($oldversion < 2022070500) {
        // Update notifications to use FORMAT_JSON_EDITOR if weka editor is enabled.
        totara_program_upgrade_migrate_format_json([
            assigned::class,
            completed::class,
            course_set_completed::class,
            course_set_due_date::class,
            due_date::class,
            new_exception::class,
            unassigned::class,
        ]);

        // Program savepoint reached.
        upgrade_plugin_savepoint(true, 2022070500, 'totara', 'program');
    }

    if ($oldversion < 2022071700) {
        $old_class = 'totara_program\\totara_notification\\recipient\\';
        $new_class = 'totara_notification\\recipient\\';

        // Limit this to our notifs so we don't break any customisations.
        // Note: new_exception message doesn't use these recipients so no worries.
        $resolver_class = 'totara_program\\totara_notification\\resolver\\';
        $default_resolvers = [
            $resolver_class . 'assigned',
            $resolver_class . 'unassigned',
            $resolver_class . 'course_set_due_date',
            $resolver_class . 'due_date',
            $resolver_class . 'course_set_completed',
            $resolver_class . 'completed',
        ];
        list($resolver_insql, $resolver_inparams) = $DB->get_in_or_equal($default_resolvers, SQL_PARAMS_NAMED);

        $recipients = ['subject', 'manager'];
        $notification_pref_columns = $DB->get_columns('notification_preference');
        $update_recipients = isset($notification_pref_columns['recipients']);
        foreach ($recipients as $recipient) {
            $sql = "
                UPDATE {notification_preference}
                SET recipient = :new_recipient
                ";
            if ($update_recipients) {
                $sql .= ", recipients = :new_recipients ";
                $resolver_inparams['new_recipients'] = json_encode([$new_class . $recipient]);
            }
            $sql .= "
                WHERE recipient = :old_recipient
                AND resolver_class_name {$resolver_insql}
            ";

            $resolver_inparams['new_recipient'] = $new_class . $recipient;
            $resolver_inparams['old_recipient'] = $old_class . $recipient;

            $DB->execute($sql, $resolver_inparams);
        }

        // Program savepoint reached.
        upgrade_plugin_savepoint(true, 2022071700, 'totara', 'program');
    }

    return true;
}

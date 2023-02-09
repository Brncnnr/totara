<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @package mod_perform
 */

/**
 * Database upgrade script
 *
 * @param integer $oldversion Current (pre-upgrade) local db version timestamp
 * @return bool
 *
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_perform_upgrade($oldversion) {
    global $DB, $CFG;
    require_once(__DIR__ . '/upgradelib.php');

    $dbman = $DB->get_manager();

    // Totara 13.0 release line.

    if ($oldversion < 2020100800) {
        $sql = "UPDATE {report_builder}
                   SET source = 'perform_manage_participation_participant_instance'
                 WHERE source = 'participant_instance_manage_participation'";
        $DB->execute($sql);

        $sql = "UPDATE {report_builder}
                   SET source = 'perform_manage_participation_participant_section'
                 WHERE source = 'participant_section_manage_participation'";
        $DB->execute($sql);

        $sql = "UPDATE {report_builder}
                   SET source = 'perform_manage_participation_subject_instance'
                 WHERE source = 'subject_instance_manage_participation'";
        $DB->execute($sql);

        $sql = "UPDATE {report_builder}
                   SET source = 'perform_participation_participant_instance'
                 WHERE source = 'perform_participant_instance'";
        $DB->execute($sql);

        $sql = "UPDATE {report_builder}
                   SET source = 'perform_participation_participant_section'
                 WHERE source = 'perform_participant_section'";
        $DB->execute($sql);

        $sql = "UPDATE {report_builder}
                   SET source = 'perform_participation_subject_instance'
                 WHERE source = 'perform_subject_instance'";
        $DB->execute($sql);

        $sql = "UPDATE {report_builder}
                   SET source = 'perform_response_element'
                 WHERE source = 'element_performance_reporting'";
        $DB->execute($sql);

        $sql = "UPDATE {report_builder}
                   SET source = 'perform_response_subject_instance'
                 WHERE source = 'subject_instance_performance_reporting'";
        $DB->execute($sql);

        $sql = "UPDATE {report_builder}
                   SET source = 'perform_response_user'
                 WHERE source = 'user_performance_reporting'";
        $DB->execute($sql);

        // Perform savepoint reached.
        upgrade_mod_savepoint(true, 2020100800, 'perform');
    }

    if ($oldversion < 2020101201) {
        // Create records for existing activities that do not already have records for the following notifications:
        mod_perform_upgrade_create_missing_notification_records([
            'completion' => [],
            'due_date' => [],
            'due_date_reminder' => [86400], // Trigger: 1 day (in seconds)
            'instance_created' => [],
            'instance_created_reminder' => [86400], // Trigger: 1 day (in seconds)
            'overdue_reminder' => [86400], // Trigger: 1 day (in seconds)
            'participant_selection' => [],
            'reopened' => [],
        ]);

        // Perform savepoint reached.
        upgrade_mod_savepoint(true, 2020101201, 'perform');
    }

    if ($oldversion < 2020101202) {
        // Define field task_id to be added to perform_subject_instance.
        $table = new xmldb_table('perform_subject_instance');
        $field = new xmldb_field('task_id', XMLDB_TYPE_CHAR, '32', null, null, null, null, 'updated_at');

        // Conditionally launch add field task_id.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $index = new xmldb_index('task_id', XMLDB_INDEX_NOTUNIQUE, array('task_id'));

        // Conditionally launch add index task_id.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define field task_id to be added to perform_participant_instance.
        $table = new xmldb_table('perform_participant_instance');
        $field = new xmldb_field('task_id', XMLDB_TYPE_CHAR, '32', null, null, null, null, 'updated_at');

        // Conditionally launch add field task_id.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $index = new xmldb_index('task_id', XMLDB_INDEX_NOTUNIQUE, array('task_id'));

        // Conditionally launch add index task_id.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Perform savepoint reached.
        upgrade_mod_savepoint(true, 2020101202, 'perform');
    }

    if ($oldversion < 2020110900) {
        mod_perform_upgrade_unwrap_response_data();

        upgrade_mod_savepoint(true, 2020110900, 'perform');
    }

    if ($oldversion < 2020121500) {
        mod_perform_upgrade_long_text_responses_to_weka_format();

        upgrade_mod_savepoint(true, 2020121500, 'perform');
    }

    if ($oldversion < 2020122100) {
        $table = new xmldb_table('perform_element_response');
        $created_at_field = new xmldb_field('created_at', XMLDB_TYPE_INTEGER, '10', null, false, null, null, 'response_data');
        $updated_at_field = new xmldb_field('updated_at', XMLDB_TYPE_INTEGER, '10', null, false, null, null, 'created_at');

        // Conditionally launch add field created_at and update_d.
        if (!$dbman->field_exists($table, $created_at_field) && !$dbman->field_exists($table, $updated_at_field)) {
            $dbman->add_field($table, $created_at_field);
            $dbman->add_field($table, $updated_at_field);

            mod_perform_upgrade_element_responses_to_include_timestamps();

            $created_at_field = new xmldb_field('created_at', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'response_data');
            $dbman->change_field_notnull($table, $created_at_field);
        }

        upgrade_mod_savepoint(true, 2020122100, 'perform');
    }

    if ($oldversion < 2020122104) {
        // Define field parent to be added to perform_element.
        $table = new xmldb_table('perform_element');
        $field = new xmldb_field('parent', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'context_id');

        // Conditionally launch add field parent.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define key parent (foreign) to be added to perform_element.
        $key = new xmldb_key('parent', XMLDB_KEY_FOREIGN, array('parent'), 'perform_element', array('id'));

        // Launch add key parent.
        if (!$dbman->key_exists($table, $key)) {
            $dbman->add_key($table, $key);
        }

        // Define field sort_order to be added to perform_element.
        $field = new xmldb_field('sort_order', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'data');

        // Conditionally launch add field sort_order.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define index parent_sort_order (not unique) to be added to perform_element.
        $index = new xmldb_index('parent_sort_order', XMLDB_INDEX_NOTUNIQUE, array('parent', 'sort_order'));

        // Conditionally launch add index parent_sort_order.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        upgrade_mod_savepoint(true, 2020122104, 'perform');
    }

    if ($oldversion < 2021021800) {
        $table = new xmldb_table('perform_section_element_reference');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('source_section_element_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('referencing_element_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        // Do not add source_section_element_id as a foreign key, because mssql does not allow it
        $table->add_key('referencing_element_id', XMLDB_KEY_FOREIGN, ['referencing_element_id'], 'perform_element', ['id'], 'cascade');

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_mod_savepoint(true, 2021021800, 'perform');
    }

    if ($oldversion < 2021031500) {
        $table = new xmldb_table('perform_section_element_reference');
        $index = new xmldb_index('source_referencing_pair', XMLDB_INDEX_UNIQUE, ['source_section_element_id', 'referencing_element_id']);

        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        upgrade_mod_savepoint(true, 2021031500, 'perform');
    }

    if ($oldversion < 2021112500) {
        $DB->set_field('totara_core_relationship', 'sort_order', 9, ['idnumber' => 'perform_external']);

        upgrade_mod_savepoint(true, 2021112500, 'perform');
    }

    if ($oldversion < 2022030300) {
        // Define field needs_sync to be added to perform_subject_instance.
        $table = new xmldb_table('perform_subject_instance');
        $field = new xmldb_field('needs_sync', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'task_id');

        // Conditionally launch add field needs_sync.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Perform savepoint reached.
        upgrade_mod_savepoint(true, 2022030300, 'perform');
    }

    if ($oldversion < 2022030900) {
        $table = new xmldb_table('perform_subject_instance');
        $field = new xmldb_field('closed_at', XMLDB_TYPE_INTEGER, '10', null, false, null, null, 'completed_at');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
            mod_perform_upgrade_subject_instances_closed_at_times();
        }

        upgrade_mod_savepoint(true, 2022030900, 'perform');
    }

    if ($oldversion < 2022032200) {
        $table = new xmldb_table('perform_track');
        $field = new xmldb_field('repeating_trigger', XMLDB_TYPE_CHAR, '255', null, false, null, null, 'repeating_is_enabled');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
            mod_perform_upgrade_track_repeating_trigger();
        }

        upgrade_mod_savepoint(true, 2022032200, 'perform');
    }

    if ($oldversion < 2022033001) {
        /*
         * Adding the 'Direct report' relationship. This is also done in totara_job upgrade, but we have to make sure it's
         * done before updating the notification recipient records below (due to the sequence of plugin upgrades). There is
         * no harm in calling it twice.
         */
        require_once($CFG->dirroot . '/totara/core/db/upgradelib.php');
        totara_core_upgrade_create_relationship('totara_job\relationship\resolvers\direct_report', 'direct_report', 8);

        // We need to call this because a new relationship was introduced (Direct report).
        mod_perform_upgrade_create_missing_notification_recipient_records();

        upgrade_mod_savepoint(true, 2022033001, 'perform');
    }

    if ($oldversion < 2022080300) {
        require_once("{$CFG->dirroot}/totara/notification/db/upgradelib.php");

        totara_notification_sync_built_in_notification('mod_perform');

        upgrade_mod_savepoint(true, 2022080300, 'perform');
    }

    if ($oldversion < 2022091500) {
        // deprecating the old perform messages as they are being replaced by centralised notifications
        mod_perform_remove_legacy_message_providers();

        upgrade_mod_savepoint(true, 2022091500, 'perform');
    }

    if ($oldversion < 2022092100) {
        require_once("{$CFG->dirroot}/totara/notification/db/upgradelib.php");

        // Make all the upgrade notifs have the same time_created.
        $now = time();

        // Annoyingly since they inherit values from files, this is the only way to identify the enabled defaults.
        list($in_sql, $params) = $DB->get_in_or_equal(
            [
                'mod_perform\totara_notification\notification\participant_selection',
                'mod_perform\totara_notification\notification\participant_selection_for_subject',
                'mod_perform\totara_notification\notification\participant_instance_created',
                'mod_perform\totara_notification\notification\participant_instance_created_for_subject',
                'mod_perform\totara_notification\notification\participant_instance_created_for_external_respondent',
            ],
            SQL_PARAMS_NAMED
        );

        // Fetch the system context.
        $params['cid'] = \context_system::instance()->id;

        $sql = 'SELECT *
                  FROM {notification_preference}
                 WHERE context_id = :cid
                   AND notification_class_name ' . $in_sql;

        // Fetch the enabled default notifications.
        $defaults = $DB->get_records_sql($sql, $params);

        // Lets just fetch this once and hand it through.
        $relations = $DB->get_records('totara_core_relationship');

        $transaction = $DB->start_delegated_transaction();

        // Get all the active perform activities.
        $activities = $DB->get_records('perform');
        foreach ($activities as $activity) {
            // And move their notifications over to CN.
            mod_perform_migrate_notifications($activity, $relations, $defaults, $now);
        }

        $transaction->allow_commit();

        upgrade_mod_savepoint(true, 2022092100, 'perform');
    }

    return true;
}

<?php
/**
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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_approval
 */

/**
 * Local database upgrade script
 *
 * @param   integer $oldversion Current (pre-upgrade) local db version timestamp
 * @return  boolean $result
 */
function xmldb_approval_upgrade($oldversion) {
    global $CFG, $DB;
    require_once("{$CFG->dirroot}/totara/notification/db/upgradelib.php");
    require_once(__DIR__ . '/upgradelib.php');

    $dbman = $DB->get_manager();

    if ($oldversion < 2021051400) {
        // Define fields to be added to approval_application.
        $table = new xmldb_table('approval_application');
        $field_title = new xmldb_field('title', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'id');
        $field_id_number = new xmldb_field('id_number', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'title');

        // Conditionally launch add field title.
        if (!$dbman->field_exists($table, $field_title)) {
            $dbman->add_field($table, $field_title);
            $sql = 'SELECT a.id, t.name AS type_name
                      FROM {approval_application} a
                      JOIN {approval_workflow_version} v ON v.id = a.workflow_version_id
                      JOIN {approval_workflow} w ON w.id = v.workflow_id
                      JOIN {approval_workflow_type} t ON t.id = w.workflow_type_id';
            foreach ($DB->get_records_sql($sql) as $app) {
                $DB->set_field('approval_application', 'title', $app->type_name, ['id' => $app->id]);
            }
        }
        // Conditionally launch add field id_number.
        if (!$dbman->field_exists($table, $field_id_number)) {
            $dbman->add_field($table, $field_id_number);
            foreach ($DB->get_records('approval_application', null, 'id', 'id,title,created') as $entity) {
                // NOTE: this logic is not identical to the one for a new application
                $time = date('Ymdhis', $entity->created);
                $hash = md5($entity->id);
                $id_number = substr($entity->title, 0, 255 - strlen($time) - 4) . $time . strtoupper(substr($hash, 0, 4));
                $DB->set_field('approval_application', 'id_number', $id_number, ['id' => $entity->id]);
            }
        }

        // Approval savepoint reached.
        upgrade_mod_savepoint(true, 2021051400, 'approval');
    }

    if ($oldversion < 2021052400) {
        $table = new xmldb_table('approval_application_activity');

        // Define key workflow_stage_approval_level_fk (foreign) to be dropped form approval_application_activity.
        $key = new xmldb_key('workflow_stage_approval_level_fk', XMLDB_KEY_FOREIGN, array('workflow_stage_approval_level_id'), 'approval_workflow_stage_approval_level', array('id'), 'restrict');

        // Launch drop key workflow_stage_approval_level_fk.
        if ($dbman->key_exists($table, $key)) {
            $dbman->drop_key($table, $key);
        }

        // Changing nullability of field workflow_stage_approval_level_id on table approval_application_activity to null.
        $field = new xmldb_field('workflow_stage_approval_level_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'workflow_stage_id');

        // Launch change of nullability for field workflow_stage_approval_level_id.
        $dbman->change_field_notnull($table, $field);

        // Define key workflow_stage_approval_level_fk (foreign) to be added to approval_application_activity.
        $key = new xmldb_key('workflow_stage_approval_level_fk', XMLDB_KEY_FOREIGN, array('workflow_stage_approval_level_id'), 'approval_workflow_stage_approval_level', array('id'), 'restrict');

        // Launch add key workflow_stage_approval_level_fk.
        if (!$dbman->key_exists($table, $key)) {
            $dbman->add_key($table, $key);
        }

        // Approval savepoint reached.
        upgrade_mod_savepoint(true, 2021052400, 'approval');
    }

    if ($oldversion < 2021053100) {

        // Define field started to be dropped from approval_application.
        $table = new xmldb_table('approval_application');
        $field = new xmldb_field('started');

        // Conditionally launch drop field started.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Approval savepoint reached.
        upgrade_mod_savepoint(true, 2021053100, 'approval');
    }

    if ($oldversion < 2021053103) {

        $table = new xmldb_table('approval_workflow_type');
        // Define unique index to be added to workflow_type.
        $index = new xmldb_index('name_ix', XMLDB_INDEX_UNIQUE, ['name']);
        // Conditionally launch add index.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        $field = new xmldb_field('description', XMLDB_TYPE_TEXT);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Approval savepoint reached.
        upgrade_mod_savepoint(true, 2021053103, 'approval');
    }

    if ($oldversion < 2021053104) {
        // We're messing with activity type IDs, so flush the table.
        $DB->delete_records('approval_application_activity');

        // Define key user_fk (foreign) to be dropped form approval_application_activity.
        $table = new xmldb_table('approval_application_activity');
        $key = new xmldb_key('user_fk', XMLDB_KEY_FOREIGN, array('user_id'), 'user', array('id'), 'restrict');

        // Launch drop key user_fk.
        if ($dbman->key_exists($table, $key)) {
            $dbman->drop_key($table, $key);
        }

        // Changing nullability of field user_id on table approval_application_activity to null.
        $table = new xmldb_table('approval_application_activity');
        $field = new xmldb_field('user_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'workflow_stage_approval_level_id');

        // Launch change of nullability for field user_id.
        $dbman->change_field_notnull($table, $field);

        // Define key user_fk (foreign) to be added to approval_application_activity.
        $table = new xmldb_table('approval_application_activity');
        $key = new xmldb_key('user_fk', XMLDB_KEY_FOREIGN, array('user_id'), 'user', array('id'), 'restrict');

        // Launch add key user_fk.
        if (!$dbman->key_exists($table, $key)) {
            $dbman->add_key($table, $key);
        }

        // Approval savepoint reached.
        upgrade_mod_savepoint(true, 2021053104, 'approval');
    }

    if ($oldversion < 2021053106) {
        // Update application states -- 9 and 10 become 8, 11 becomes 9.
        $transaction = $DB->start_delegated_transaction();

        $sql = "UPDATE {approval_application}
                       SET state = :completed
                     WHERE state = :completed_approved OR state = :completed_rejected";
        $DB->execute($sql, ['completed' => 8, 'completed_approved' => 9, 'completed_rejected' => 10]);

        $sql = "UPDATE {approval_application}
                       SET state = :withdrawn
                     WHERE state = :old_withdrawn";
        $DB->execute($sql, ['withdrawn' => 9, 'old_withdrawn' => 11]);

        $transaction->allow_commit();

        // Approval savepoint reached.
        upgrade_mod_savepoint(true, 2021053106, 'approval');
    }

    if ($oldversion < 2021053107) {
        totara_notification_sync_built_in_notification('mod_approval');

        // Approval savepoint reached.
        upgrade_mod_savepoint(true, 2021053107, 'approval');
    }

    if ($oldversion < 2021053109) {
        // Update approval_approver to add ancestor_id field and new indexes.
        $table = new xmldb_table('approval_approver');
        $field = new xmldb_field('ancestor_id', XMLDB_TYPE_INTEGER, 10);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $index = new xmldb_index('ancestor_id_ix', XMLDB_INDEX_NOTUNIQUE, ['ancestor_id']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }
        $index = new xmldb_index(
            'unique_approver_ix',
            XMLDB_INDEX_UNIQUE,
            ['approval_id', 'workflow_stage_approval_level_id', 'type', 'identifier']
        );
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Add inherited approvers where necessary.
        mod_approval_upgrade_create_inherited_assignment_approvers();

        // Approval savepoint reached.
        upgrade_mod_savepoint(true, 2021053109, 'approval');
    }

    if ($oldversion < 2021053113) {
        // Define field owner_id to be added to approval_application.
        $table = new xmldb_table('approval_application');
        $field = new xmldb_field('owner_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'creator_id');

        // Conditionally launch add field owner_id.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Update owner of all existing records.
        $DB->execute("UPDATE {approval_application} SET owner_id = creator_id");

        // Changing nullability of field owner_id on table approval_application to not null.
        $field = new xmldb_field('owner_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'creator_id');

        // Launch change of nullability for field owner_id.
        $dbman->change_field_notnull($table, $field);

        // Define key owner_fk (foreign) to be added to approval_application.
        $key = new xmldb_key('owner_fk', XMLDB_KEY_FOREIGN, array('owner_id'), 'user', array('id'), 'restrict');

        // Launch add key owner_fk.
        if (!$dbman->key_exists($table, $key)) {
            $dbman->add_key($table, $key);
        }

        // Approval savepoint reached.
        upgrade_mod_savepoint(true, 2021053113, 'approval');
    }

    if ($oldversion < 2021053114) {
        // Define field submitter_id to be added to approval_application.
        $table = new xmldb_table('approval_application');
        $field = new xmldb_field('submitter_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'submitted');

        // Conditionally launch add field submitter_id.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define key submitter_fk (foreign) to be added to approval_application.
        $key = new xmldb_key('submitter_fk', XMLDB_KEY_FOREIGN, array('submitter_id'), 'user', array('id'), 'restrict');

        // Launch add key submitter_fk.
        if (!$dbman->key_exists($table, $key)) {
            $dbman->add_key($table, $key);
        }

        // Approval savepoint reached.
        upgrade_mod_savepoint(true, 2021053114, 'approval');
    }

    if ($oldversion < 2021053116) {
        $DB->execute("UPDATE {course_categories} SET name='mod-approval-workflow-category' WHERE issystem=1 AND name='Approval Workflows'");

        // Approval savepoint reached.
        upgrade_mod_savepoint(true, 2021053116, 'approval');
    }

    if ($oldversion < 2021053120) {
        // Make all id_number values unique before adding a unique index.
        mod_approval_upgrade_assign_unique_workflow_id_number();

        // Define index id_number_ix (unique) to be added to approval_workflow.
        $table = new xmldb_table('approval_workflow');
        $index = new xmldb_index('id_number_ix', XMLDB_INDEX_UNIQUE, array('id_number'));

        // Conditionally launch add index id_number_ix.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Approval savepoint reached.
        upgrade_mod_savepoint(true, 2021053120, 'approval');
    }

    if ($oldversion < 2021053122) {
        // Define field current_stage_condition to be added to approval_application.
        $table = new xmldb_table('approval_application');
        $field = new xmldb_field('current_stage_condition', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, null, 'current_stage_id');

        // Conditionally launch add field current_stage_condition.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Approval savepoint reached.
        upgrade_mod_savepoint(true, 2021053122, 'approval');
    }

    if ($oldversion < 2021053123) {
        // Define index state_ix (not unique) to be dropped form approval_application.
        $table = new xmldb_table('approval_application');
        $index = new xmldb_index('state_ix', XMLDB_INDEX_NOTUNIQUE, array('state'));

        // Conditionally launch drop index state_ix.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Define field state to be dropped from approval_application.
        $table = new xmldb_table('approval_application');
        $field = new xmldb_field('state');

        // Conditionally launch drop field state.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        /////////////////////////////////////////////////////////

        // Define index current_stage_ix (not unique) to be dropped form approval_application.
        $table = new xmldb_table('approval_application');
        $index = new xmldb_index('current_stage_ix', XMLDB_INDEX_NOTUNIQUE, array('current_stage_id'));

        // Conditionally launch drop index current_stage_ix.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Changing nullability of field current_stage_id on table approval_application to not null.
        $table = new xmldb_table('approval_application');
        $field = new xmldb_field(
            'current_stage_id',
            XMLDB_TYPE_INTEGER,
            '10',
            null,
            XMLDB_NOTNULL,
            null,
            null,
            'owner_id'
        );

        // Launch change of nullability for field current_stage_id.
        $dbman->change_field_notnull($table, $field);

        // Define index current_stage_ix (not unique) to be added to approval_application.
        $table = new xmldb_table('approval_application');
        $index = new xmldb_index('current_stage_ix', XMLDB_INDEX_NOTUNIQUE, array('current_stage_id'));

        // Conditionally launch add index current_stage_ix.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        /////////////////////////////////////////////////////////

        // Define index current_stage_condition_ix (not unique) to be added to approval_application.
        $table = new xmldb_table('approval_application');
        $index = new xmldb_index('current_stage_condition_ix', XMLDB_INDEX_NOTUNIQUE, array('current_stage_condition'));

        // Conditionally launch add index current_stage_condition_ix.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        /////////////////////////////////////////////////////////

        // Define index status_ix (not unique) to be dropped form approval_application_action.
        $table = new xmldb_table('approval_application_action');
        $index = new xmldb_index('status_ix', XMLDB_INDEX_NOTUNIQUE, array('status'));

        // Conditionally launch drop index status_ix.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Rename field status on table approval_application_action to code.
        $table = new xmldb_table('approval_application_action');
        $field = new xmldb_field(
            'status',
            XMLDB_TYPE_INTEGER,
            '2',
            null,
            XMLDB_NOTNULL,
            null,
            null,
            'workflow_stage_approval_level_id'
        );

        // Launch rename field status.
        $dbman->rename_field($table, $field, 'code');

        // Define index code_ix (not unique) to be added to approval_application_action.
        $table = new xmldb_table('approval_application_action');
        $index = new xmldb_index('code_ix', XMLDB_INDEX_NOTUNIQUE, array('code'));

        // Conditionally launch add index code_ix.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Approval savepoint reached.
        upgrade_mod_savepoint(true, 2021053123, 'approval');
    }

    if ($oldversion < 2021053124) {
        // Define field type to be added to approval_workflow_stage.
        $table = new xmldb_table('approval_workflow_stage');
        $field = new xmldb_field('type_code', XMLDB_TYPE_INTEGER, '10', null, true, null, 10, 'name');

        // Conditionally launch add field submitter_id.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Update field to not have default.
        $field->setDefault(null);

        $dbman->change_field_default($table, $field);


        // Approval savepoint reached.
        upgrade_mod_savepoint(true, 2021053124, 'approval');
    }

    if ($oldversion < 2021053125) {
        $table = new xmldb_table('approval_application_action');

        // Make approval level nullable
        $approval_level_id_field = new xmldb_field('workflow_stage_approval_level_id', XMLDB_TYPE_INTEGER, '10', null, false, null, null, 'user_id');

        // Remove workflow_stage_approval_level_fk key
        $approval_level_key = new xmldb_key(
            'workflow_stage_approval_level_fk',
            XMLDB_KEY_FOREIGN,
            array('workflow_stage_approval_level_id'),
            'approval_workflow_stage_approval_level',
            'id',
            'restrict'
        );
        $dbman->drop_key($table, $approval_level_key);
        $dbman->change_field_notnull($table, $approval_level_id_field);

        // Add stage_id
        $stage_id_field = new xmldb_field('workflow_stage_id', XMLDB_TYPE_INTEGER, '10', null, false, null, null, 'user_id');

        if (!$dbman->field_exists($table, $stage_id_field)) {
            $dbman->add_field($table, $stage_id_field);
        }

        // Set workflow stage for existing actions.
        $DB->execute("UPDATE {approval_application_action} aa SET workflow_stage_id=(select workflow_stage_id from {approval_workflow_stage_approval_level} where id = aa.workflow_stage_approval_level_id)");

        // Update field to NotNull.
        $stage_id_field->setNotNull(true);
        $dbman->change_field_notnull($table, $stage_id_field);

        // Add workflow_stage_fk key
        $workflow_stage_key = new xmldb_key(
            'workflow_stage_fk',
            XMLDB_KEY_FOREIGN,
            array('workflow_stage_id'),
            'approval_workflow_stage',
            array('id'),
            'restrict'
        );
        if (!$dbman->key_exists($table, $workflow_stage_key)) {
            $dbman->add_key($table, $workflow_stage_key);
        }

        // Approval savepoint reached.
        upgrade_mod_savepoint(true, 2021053125, 'approval');
    }

    if ($oldversion < 2021053126) {
        // Switch from using approval_application_view_any, approval_application_view_pending, and approval_application_view_user tables.

        // Define table approval_dashboard_application_any to be created.
        $table = new xmldb_table('approval_dashboard_application_any');

        // Adding fields to table approval_dashboard_application_any.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('approval_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('user_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table approval_dashboard_application_any.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('approval_fk', XMLDB_KEY_FOREIGN, array('approval_id'), 'approval', array('id'), 'cascade');
        $table->add_key('user_fk', XMLDB_KEY_FOREIGN, array('user_id'), 'user', array('id'), 'cascade');

        // Adding indexes to table approval_dashboard_application_any.
        $table->add_index('approval_user_ix', XMLDB_INDEX_NOTUNIQUE, array('approval_id', 'user_id'));

        // Conditionally launch create table for approval_dashboard_application_any.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table approval_dashboard_draft_application_any to be created.
        $table = new xmldb_table('approval_dashboard_draft_application_any');

        // Adding fields to table approval_dashboard_draft_application_any.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('approval_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('user_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table approval_dashboard_draft_application_any.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('approval_fk', XMLDB_KEY_FOREIGN, array('approval_id'), 'approval', array('id'), 'cascade');
        $table->add_key('user_fk', XMLDB_KEY_FOREIGN, array('user_id'), 'user', array('id'), 'cascade');

        // Adding indexes to table approval_dashboard_draft_application_any.
        $table->add_index('approval_user_ix', XMLDB_INDEX_NOTUNIQUE, array('approval_id', 'user_id'));

        // Conditionally launch create table for approval_dashboard_draft_application_any.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table approval_dashboard_pending_application_any to be created.
        $table = new xmldb_table('approval_dashboard_pending_application_any');

        // Adding fields to table approval_dashboard_pending_application_any.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('approval_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('workflow_stage_approval_level_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('user_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table approval_dashboard_pending_application_any.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('approval_fk', XMLDB_KEY_FOREIGN, array('approval_id'), 'approval', array('id'), 'cascade');
        $table->add_key('workflow_stage_approval_level_fk', XMLDB_KEY_FOREIGN, array('workflow_stage_approval_level_id'), 'approval_workflow_stage_approval_level', array('id'), 'cascade');
        $table->add_key('user_fk', XMLDB_KEY_FOREIGN, array('user_id'), 'user', array('id'), 'cascade');

        // Adding indexes to table approval_dashboard_pending_application_any.
        $table->add_index('approval_approval_level_user_ix', XMLDB_INDEX_NOTUNIQUE, array('approval_id', 'workflow_stage_approval_level_id', 'user_id'));

        // Conditionally launch create table for approval_dashboard_pending_application_any.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table approval_dashboard_application_user to be created.
        $table = new xmldb_table('approval_dashboard_application_user');

        // Adding fields to table approval_dashboard_application_user.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('applicant_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('user_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table approval_dashboard_application_user.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('applicant_fk', XMLDB_KEY_FOREIGN, array('applicant_id'), 'user', array('id'), 'cascade');
        $table->add_key('user_fk', XMLDB_KEY_FOREIGN, array('user_id'), 'user', array('id'), 'cascade');

        // Adding indexes to table approval_dashboard_application_user.
        $table->add_index('applicant_user_ix', XMLDB_INDEX_NOTUNIQUE, array('applicant_id', 'user_id'));

        // Conditionally launch create table for approval_dashboard_application_user.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table approval_dashboard_draft_application_user to be created.
        $table = new xmldb_table('approval_dashboard_draft_application_user');

        // Adding fields to table approval_dashboard_draft_application_user.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('applicant_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('user_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table approval_dashboard_draft_application_user.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('applicant_fk', XMLDB_KEY_FOREIGN, array('applicant_id'), 'user', array('id'), 'cascade');
        $table->add_key('user_fk', XMLDB_KEY_FOREIGN, array('user_id'), 'user', array('id'), 'cascade');

        // Adding indexes to table approval_dashboard_draft_application_user.
        $table->add_index('applicant_user_ix', XMLDB_INDEX_NOTUNIQUE, array('applicant_id', 'user_id'));

        // Conditionally launch create table for approval_dashboard_draft_application_user.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table approval_dashboard_pending_application_user to be created.
        $table = new xmldb_table('approval_dashboard_pending_application_user');

        // Adding fields to table approval_dashboard_pending_application_user.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('applicant_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('workflow_stage_approval_level_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('user_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table approval_dashboard_pending_application_user.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('applicant_fk', XMLDB_KEY_FOREIGN, array('applicant_id'), 'user', array('id'), 'cascade');
        $table->add_key('workflow_stage_approval_level_fk', XMLDB_KEY_FOREIGN, array('workflow_stage_approval_level_id'), 'approval_workflow_stage_approval_level', array('id'), 'cascade');
        $table->add_key('user_fk', XMLDB_KEY_FOREIGN, array('user_id'), 'user', array('id'), 'cascade');

        // Adding indexes to table approval_dashboard_pending_application_user.
        $table->add_index('applicant_approval_level_user_ix', XMLDB_INDEX_NOTUNIQUE, array('applicant_id', 'workflow_stage_approval_level_id', 'user_id'));

        // Conditionally launch create table for approval_dashboard_pending_application_user.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Conditionally delete the old capability map tables.
        $table = new xmldb_table('approval_application_view_any');
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }
        $table = new xmldb_table('approval_application_view_pending');
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }
        $table = new xmldb_table('approval_application_view_user');
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // Approval savepoint reached.
        upgrade_mod_savepoint(true, 2021053126, 'approval');
    }

    if ($oldversion < 2021053127) {
        // Define table approval_dashboard_application_applicant to be created.
        $table = new xmldb_table('approval_dashboard_application_applicant');

        // Adding fields to table approval_dashboard_application_applicant.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('approval_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('user_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table approval_dashboard_application_applicant.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('approval_fk', XMLDB_KEY_FOREIGN, array('approval_id'), 'approval', array('id'), 'cascade');
        $table->add_key('user_fk', XMLDB_KEY_FOREIGN, array('user_id'), 'user', array('id'), 'cascade');

        // Adding indexes to table approval_dashboard_application_applicant.
        $table->add_index('approval_user_ix', XMLDB_INDEX_NOTUNIQUE, array('approval_id', 'user_id'));

        // Conditionally launch create table for approval_dashboard_application_applicant.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table approval_dashboard_draft_application_applicant to be created.
        $table = new xmldb_table('approval_dashboard_draft_application_applicant');

        // Adding fields to table approval_dashboard_draft_application_applicant.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('approval_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('user_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table approval_dashboard_draft_application_applicant.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('approval_fk', XMLDB_KEY_FOREIGN, array('approval_id'), 'approval', array('id'), 'cascade');
        $table->add_key('user_fk', XMLDB_KEY_FOREIGN, array('user_id'), 'user', array('id'), 'cascade');

        // Adding indexes to table approval_dashboard_draft_application_applicant.
        $table->add_index('approval_user_ix', XMLDB_INDEX_NOTUNIQUE, array('approval_id', 'user_id'));

        // Conditionally launch create table for approval_dashboard_draft_application_applicant.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Approval savepoint reached.
        upgrade_mod_savepoint(true, 2021053127, 'approval');
    }

    if ($oldversion < 2021053128) {
        // Define index respondent_ix (not unique) to be dropped form approval_workflow_stage_formview.
        $table = new xmldb_table('approval_workflow_stage_formview');
        $index = new xmldb_index('respondent_ix', XMLDB_INDEX_NOTUNIQUE, array('respondent'));

        // Conditionally launch drop index respondent_ix.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Define field respondent to be dropped from approval_workflow_stage_formview.
        $table = new xmldb_table('approval_workflow_stage_formview');
        $field = new xmldb_field('respondent');

        // Conditionally launch drop field respondent.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field sortorder to be dropped from approval_workflow_stage_formview.
        $table = new xmldb_table('approval_workflow_stage_formview');
        $field = new xmldb_field('sortorder');

        // Conditionally launch drop field sortorder.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Approval savepoint reached.
        upgrade_mod_savepoint(true, 2021053128, 'approval');
    }

    if ($oldversion < 2021053129) {
        // Delete old transition table
        $table = new xmldb_table('approval_workflow_stage_transition');
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // Define table approval_workflow_stage_interaction to be created.
        $table = new xmldb_table('approval_workflow_stage_interaction');

        // Adding fields to table approval_workflow_stage_interaction.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('workflow_stage_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('action_code', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, null);
        $table->add_field('created', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('updated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table approval_workflow_stage_interaction.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('workflow_stage_fk', XMLDB_KEY_FOREIGN, array('workflow_stage_id'), 'approval_workflow_stage', array('id'), 'restrict');

        // Adding indexes to table approval_workflow_stage_interaction.
        $table->add_index('action_code_ix', XMLDB_INDEX_NOTUNIQUE, array('action_code'));

        // Conditionally launch create table for approval_workflow_stage_interaction.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table approval_workflow_stage_interaction_transition to be created.
        $table = new xmldb_table('approval_workflow_stage_interaction_transition');

        // Adding fields to table approval_workflow_stage_interaction_transition.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('workflow_stage_interaction_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('condition_key', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('condition_data', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('transition', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('transition_data', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('priority', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('created', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('updated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table approval_workflow_stage_interaction_transition.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('workflow_stage_interaction_fk', XMLDB_KEY_FOREIGN, array('workflow_stage_interaction_id'), 'approval_workflow_stage_interaction', array('id'), 'cascade');

        // Adding indexes to table approval_workflow_stage_interaction_transition.
        $table->add_index('condition_key_ix', XMLDB_INDEX_NOTUNIQUE, array('condition_key'));
        $table->add_index('transition_ix', XMLDB_INDEX_NOTUNIQUE, array('transition'));

        // Conditionally launch create table for approval_workflow_stage_interaction_transition.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table approval_workflow_stage_interaction_action to be created.
        $table = new xmldb_table('approval_workflow_stage_interaction_action');

        // Adding fields to table approval_workflow_stage_interaction_action.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('workflow_stage_interaction_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('condition_key', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('condition_data', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('effect', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('effect_data', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('created', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('updated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table approval_workflow_stage_interaction_action.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('workflow_stage_interaction_fk', XMLDB_KEY_FOREIGN, array('workflow_stage_interaction_id'), 'approval_workflow_stage_interaction', array('id'), 'cascade');

        // Adding indexes to table approval_workflow_stage_interaction_action.
        $table->add_index('condition_key_ix', XMLDB_INDEX_NOTUNIQUE, array('condition_key'));
        $table->add_index('effect_ix', XMLDB_INDEX_NOTUNIQUE, array('effect'));

        // Conditionally launch create table for approval_workflow_stage_interaction_action.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Approval savepoint reached.
        upgrade_mod_savepoint(true, 2021053129, 'approval');
    }

    if ($oldversion < 2021053131) {
        $table = new xmldb_table('approval_workflow_stage_formview');
        $restrict_key = new xmldb_key('workflow_stage_fk', XMLDB_KEY_FOREIGN, array('workflow_stage_id'), 'approval_workflow_stage', array('id'), 'restrict');

        if ($dbman->key_exists($table, $restrict_key)) {
            $dbman->drop_key($table, $restrict_key);
            $cascade_key = new xmldb_key('workflow_stage_fk', XMLDB_KEY_FOREIGN, array('workflow_stage_id'), 'approval_workflow_stage', array('id'), 'cascade');
            $dbman->add_key($table, $cascade_key);
        }

        // Approval savepoint reached.
        upgrade_mod_savepoint(true, 2021053131, 'approval');
    }

    if ($oldversion < 2021053132) {
        // Assign capabilities to approvalworkflowapprover & approvalworkflowmanager roles
        mod_approval_assign_new_roles_capabilities();

        // Unassign teacher role and assign approvalworkflowapprover role to approver users.
        mod_approval_transfer_approver_role_from_teacher_to_approvalworkflowapprover();

        // Approval savepoint reached.
        upgrade_mod_savepoint(true, 2021053132, 'approval');
    }

    if ($oldversion < 2021053133) {
        // Define table approval_role_capability_map to be created.
        $table = new xmldb_table('approval_role_capability_map');

        // Adding fields to table approval_role_capability_map.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('instanceid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('contextlevel', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('roleid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('capabilityid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table approval_role_capability_map.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('role', XMLDB_KEY_FOREIGN, array('roleid'), 'role', array('id'));
        $table->add_key('capability', XMLDB_KEY_FOREIGN, array('capabilityid'), 'capabilities', array('id'));

        // Adding indexes to table approval_role_capability_map.
        $table->add_index('context', XMLDB_INDEX_NOTUNIQUE, array('instanceid', 'contextlevel'));
        $table->add_index('instance-level-roleid-capabilityid', XMLDB_INDEX_UNIQUE, array('instanceid', 'contextlevel', 'roleid', 'capabilityid'));

        // Conditionally launch create table for approval_role_capability_map.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Approval savepoint reached.
        upgrade_mod_savepoint(true, 2021053133, 'approval');
    }

    if ($oldversion < 2021053134) {
        // Define field started to be dropped from approval_workflow_stage_interaction_transition.
        $table = new xmldb_table('approval_workflow_stage_interaction_transition');
        $field = new xmldb_field('transition_data');

        // Conditionally launch drop field started.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Approval savepoint reached.
        upgrade_mod_savepoint(true, 2021053134, 'approval');
    }

    if ($oldversion < 2021053135) {

        $table = new xmldb_table('approval_application');

        // Define index current_stage_condition_ix (not unique) to be dropped form approval_application.
        $index = new xmldb_index('current_stage_condition_ix', XMLDB_INDEX_NOTUNIQUE, array('current_stage_condition'));

        // Conditionally launch drop index current_stage_condition_ix.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Define field is_draft to be added to approval_application.
        $field = new xmldb_field('is_draft', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'current_stage_id');

        // Conditionally launch add field is_draft.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Set is_draft to false (default is true) for all non-draft applications.
        $DB->execute("UPDATE {approval_application} SET is_draft = 0 WHERE current_stage_condition <> 20");

        // Define field current_stage_condition to be dropped from approval_application.
        $field = new xmldb_field('current_stage_condition');

        // Conditionally launch drop field current_stage_condition.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define index is_draft_ix (not unique) to be added to approval_application.
        $table = new xmldb_table('approval_application');
        $index = new xmldb_index('is_draft_ix', XMLDB_INDEX_NOTUNIQUE, array('is_draft'));

        // Conditionally launch add index is_draft_ix.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Approval savepoint reached.
        upgrade_mod_savepoint(true, 2021053135, 'approval');
    }

    return true;
}

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

use core\orm\query\builder;
use mod_approval\entity\assignment\assignment_approver as assignment_approver_entity;
use mod_approval\model\assignment\assignment;
use mod_approval\model\assignment\assignment_approver;

function mod_approval_upgrade_create_inherited_assignment_approvers(): void {
    $local_approvers = assignment_approver_entity::repository()
        ->where_null('ancestor_id')
        ->where('active', '=', true)
        ->order_by('id')
        ->get();
    $transaction = builder::get_db()->start_delegated_transaction();
    foreach ($local_approvers as $approver_entity) {
        $approver = assignment_approver::load_by_entity($approver_entity);
        $approver->create_descendants($transaction);
    }
    $transaction->allow_commit();
}

function mod_approval_upgrade_assign_unique_workflow_id_number(): void {
    global $DB;
    $records = $DB->get_records_sql('SELECT id,id_number FROM {approval_workflow} ORDER BY id DESC');
    foreach ($records as $id => $record) {
        $index = 0;
        $id_number = $record->id_number;
        while ($DB->record_exists_select('approval_workflow', 'id != :id AND id_number = :idnum', ['id' => $id, 'idnum' => $id_number])) {
            $id_number = $record->id_number . (++$index);
        }
        if ($id_number != $record->id_number) {
            $DB->set_fields('approval_workflow', ['id_number' => $id_number, 'updated' => time()], ['id' => $id]);
        }
    }
}

function mod_approval_assign_new_roles_capabilities() {
    global $DB;
    $roles = ['approvalworkflowapprover', 'approvalworkflowmanager'];
    $system_context_id = context_system::instance()->id;

    foreach ($roles as $role_name) {
        $role = $DB->get_record('role', ['shortname' => $role_name], '*', MUST_EXIST);

        foreach (array('assign', 'override', 'switch') as $type) {
            $function = 'allow_' . $type;
            $allows = get_default_role_archetype_allows($type, $role->archetype);
            foreach ($allows as $allowid) {
                $function($role->id, $allowid);
            }
            set_role_contextlevels($role->id, get_default_contextlevels($role->archetype));
        }
        $default_capabilities = get_default_capabilities($role->archetype);
        foreach ($default_capabilities as $capability => $permission) {
            assign_capability($capability, $permission, $role->id, $system_context_id);
        }

        // Add allow_* defaults related to the new role.
        foreach ($DB->get_records('role') as $existing_role) {
            // Same role
            if ($existing_role->id == $role->id) {
                continue;
            }

            foreach (array('assign', 'override', 'switch') as $type) {
                $function = 'allow_'.$type;
                $allows = get_default_role_archetype_allows($type, $existing_role->archetype);
                foreach ($allows as $allowid) {
                    if ($allowid == $role->id) {
                        $function($role->id, $allowid);
                    }
                }
            }
        }
    }
}

function mod_approval_transfer_approver_role_from_teacher_to_approvalworkflowapprover(): void {
    builder::get_db()->transaction(function() {
        $approvers = builder::get_db()->get_recordset_sql(
            "SELECT aa.identifier as user_id, a.id as assignment_id 
                FROM {approval_approver} aa 
                INNER JOIN {approval} a ON aa.approval_id = a.id 
                WHERE aa.type = :user_type AND aa.active = :active",
            [
                'user_type' => 2,
                'active' => 1,
            ]
        );

        $assignment_contexts = [];

        foreach ($approvers as $approver) {
            $assignment_id = $approver->assignment_id;
            $user_id = $approver->user_id;

            // Group user_ids under the assignment context.
            if (!isset($assignment_contexts[$assignment_id])) {
                $assignment = assignment::load_by_id($assignment_id);
                $assignment_contexts[$assignment_id] = [
                    'context_id' => $assignment->contextid,
                    'user_ids' => []
                ];
            }
            $assignment_contexts[$assignment_id]['user_ids'][] = $user_id;
        }

        // Bulk assign approvalworkflowapprover roles and bulk un-assign teacher roles for the assignments.
        $roles = builder::table('role')
            ->where_in('shortname', ['teacher', 'approvalworkflowapprover'])
            ->select(['id', 'shortname'])
            ->get()
            ->key_by('shortname')
            ->all(true);

        foreach ($assignment_contexts as $assignment_id => $assignment_context) {
            role_assign_bulk(
                $roles['approvalworkflowapprover']->id,
                $assignment_context['user_ids'],
                $assignment_context['context_id'],
                'mod_approval',
                $assignment_id
            );

            $parameters = [
                'roleid' => $roles['teacher']->id,
                'userids' => $assignment_context['user_ids'],
                'contextid' => $assignment_context['context_id'],
                'component' => 'mod_approval',
            ];
            role_unassign_all_bulk($parameters);
        }
    });
}

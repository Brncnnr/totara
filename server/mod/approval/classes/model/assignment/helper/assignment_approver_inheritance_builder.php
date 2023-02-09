<?php
/**
 * This file is part of Totara Learn
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\model\assignment\helper;

use core\orm\query\builder;
use mod_approval\entity\workflow\workflow_stage_approval_level as approval_level_entity;
use mod_approval\entity\assignment\assignment_approver as assignment_approver_entity;
use mod_approval\model\assignment\assignment;
use mod_approval\model\assignment\assignment_approval_level;
use mod_approval\model\assignment\assignment_approver;
use mod_approval\model\assignment\assignment_type\cohort;
use mod_approval\model\assignment\assignment_type\organisation;
use mod_approval\model\assignment\assignment_type\position;
use mod_approval\model\assignment\assignment_type\provider;
use mod_approval\model\console_trait;
use mod_approval\model\status;
use mod_approval\model\workflow\workflow_stage_approval_level;
use mod_approval\model\workflow\workflow_version;

/**
 * Helper class for checking and rebuilding inherited assignment approver overrides.
 */
class assignment_approver_inheritance_builder {

    use console_trait;

    /**
     * Rebuilds all or part of the assignment approver override tree, from scratch.
     *
     * If default assignment is provided, entire tree will be rebuilt (not just assignments which inherit from default).
     * Likewise if a non-default hierarchical assignment is provided, all potential descendents will be rebuilt, not just
     * assignments which inherit from it.
     *
     * The tree is rebuilt from the bottom up so that direct approvers are in place and correct before inherited approvers
     * are added.
     *
     * This actual rebuilding is done by assignment_approver::deactivate() and assignment_approver::activate().
     *
     * @param assignment $assignment
     * @param workflow_version $workflow_version
     */
    public function rebuild_tree_for_assignment(assignment $assignment, workflow_version $workflow_version, bool $debug = false): void {
        global $DB;
        $workflow = $workflow_version->workflow;

        // Check out our approvers.
        $direct_approvers = $assignment->get_approvers();

        // Find each approval_level in each stage of the workflow's active version
        $approval_levels = approval_level_entity::repository()
            ->join([\mod_approval\entity\workflow\workflow_stage::TABLE, 'stage'], 'workflow_stage_id', '=', 'id')
            ->where('stage.workflow_version_id', '=', $workflow_version->id)
            ->where('stage.active', '=', 1)
            ->where('active', '=', 1)
            ->get()
            ->map_to(workflow_stage_approval_level::class);
        $this->log("There are {$direct_approvers->count()} direct approvers, spread over {$approval_levels->count()} approval levels in this assignment.");

        // Find and store affected assignments.
        $affected_assignments = [];
        $entity = $assignment->get_assigned_to();

        // The following queries need database-specific order by clauses.
        if ($DB->get_dbfamily() === 'mssql') {
            $order_by = 'LEN(hier.path) DESC, hier.path DESC';
        } else {
            $order_by = 'LENGTH(hier.path) DESC, hier.path DESC';
        }

        // If assignment is default, load all assignments. But do it in a way that allows them to be sorted by hierarchical
        //  depth, deepest first.
        if ($assignment->is_default) {
            if (empty($entity->path)) {
                $entity_path = '';
            } else {
                $entity_path = $entity->path;
            }
            // Give cohort assignments a fake path that puts them at a level just below the default assignment.
            $cohort_assignments = builder::table('approval')
                ->select_raw("id, '" . $entity_path . "/0' as path")
                ->where('approval.assignment_type', '=', cohort::get_code())
                ->where('approval.status', '=', status::ACTIVE)
                ->where('approval.course', '=', $workflow->course_id)
                ->where('approval.is_default', '=', 0)
                ->get();

            // Note that these are not filtered by path in any way, we are loading all organisation assignments.
            $org_assignments = builder::table('approval')
                ->select_raw('approval.id, hier.path')
                ->join([organisation::get_table(), 'hier'], 'hier.id', '=', 'approval.assignment_identifier')
                ->where('approval.assignment_type', '=', organisation::get_code())
                ->where('approval.status', '=', status::ACTIVE)
                ->where('approval.course', '=', $workflow->course_id)
                ->where('approval.is_default', '=', 0)
                ->order_by_raw($order_by)
                ->get();

            // Note that these are not filtered by path in any way, we are loading all position assignments.
            $pos_assignments = builder::table('approval')
                ->select_raw('approval.id, hier.path')
                ->join([position::get_table(), 'hier'], 'hier.id', '=', 'approval.assignment_identifier')
                ->where('approval.assignment_type', '=', position::get_code())
                ->where('approval.status', '=', status::ACTIVE)
                ->where('approval.course', '=', $workflow->course_id)
                ->where('approval.is_default', '=', 0)
                ->order_by_raw($order_by)
                ->get();

            $affected_assignments = array_merge($org_assignments->to_array(), $pos_assignments->to_array(), $cohort_assignments->to_array());
            $this->log("Default assignment rebuild, there are ".count($affected_assignments)." affected assignments");
        } else if ($assignment->assignment_type != cohort::get_code()) {
            // Load all the assignments of the same type below this one in the hierarchy.
            $hier_assignments = builder::table('approval')
                ->select_raw('approval.id, hier.path')
                ->join([provider::get_by_code($assignment->assignment_type)::get_table(), 'hier'], 'hier.id', '=', 'approval.assignment_identifier')
                ->where('approval.assignment_type', '=', $assignment->assignment_type)
                ->where('approval.status', '=', status::ACTIVE)
                ->where('approval.course', '=', $workflow->course_id)
                ->where('hier.path', 'like', $assignment->assigned_to->path .'/%')
                ->order_by_raw($order_by)
                ->get();
            $affected_assignments = $hier_assignments->to_array();
            $this->log("Hierarchical assignment rebuild, there are ".count($affected_assignments)." child assignments to be rebuilt");
        }

        // Also include this assignment at the end of the list of assignments to be rebuilt.
        $record = builder::table('approval')
            ->where('id', '=', $assignment->id)
            ->where('approval.status', '=', status::ACTIVE)
            ->where('approval.course', '=', $workflow->course_id)
            ->one(true);
        $affected_assignments[] = (array)$record;

        // For each approval level, for each affected assignment, rebuild approvers.
        $level_count = 0;
        $approver_count = 0;
        if (self::$logging_enabled || self::$cli_mode) {
            $read_offset = $DB->perf_get_reads();
            $write_offset = $DB->perf_get_writes();
        }
        foreach ($approval_levels as $approval_level) {
            $this->log("----------------");
            $this->log("Now working on level: {$approval_level->name}");
            $this->log("----------------");
            foreach ($affected_assignments as $record) {
                $working_assignment = assignment::load_by_id($record['id']);
                $assignment_approval_level = new assignment_approval_level($working_assignment, $approval_level);
                $assignment_approval_level->set_activemode(true);

                // Deactivate any inherited approvers here.
                $transaction = builder::get_db()->start_delegated_transaction();
                $this->log("Attempting to deactivate any inherited approvers here.");
                if ($debug) {
                    assignment_approval_level::$logging_enabled = true;
                    $assignment_approval_level->deactivate_inherited_approvers($transaction);
                    assignment_approval_level::$logging_enabled = false;
                    $this->log(assignment_approval_level::get_console());
                } else {
                    $assignment_approval_level->deactivate_inherited_approvers($transaction);
                }
                $transaction->allow_commit();

                // If there are direct approvers here, remove (deactivate) an indirect approvers at this level.
                $level_direct_approvers = $assignment_approval_level->get_approvers();
                if (count($level_direct_approvers)) {
                    $level_indirect_approvers = assignment_approver_entity::repository()
                        ->where('approval_id', $working_assignment->id)
                        ->where('workflow_stage_approval_level_id', $approval_level->id)
                        ->where_not_null('ancestor_id')
                        ->where('active', '=', true)
                        ->get()
                        ->map_to(assignment_approver::class)
                        ->all();
                    if (count($level_indirect_approvers)) {
                        $this->log("{$working_assignment->name} has " . count($level_indirect_approvers) . " inherited approvers that need to be deactivated");
                        foreach ($level_indirect_approvers as $approver) {
                            $approver->deactivate();
                        }
                    }
                    $this->log("{$working_assignment->name} has " . count($level_direct_approvers) . " direct approvers");
                    $local_approver_count = 0;
                    foreach ($level_direct_approvers as $approver) {
                        $this->log("- attempting to create descendants of {$approver->get_name()}...");
                        $approver->activate();
                        $approver_count++;
                        $local_approver_count++;
                    }
                    $this->log("- done with {$local_approver_count} approvers on this level");
                } else {
                    $this->log("{$working_assignment->name} has no direct approvers at this level.");
                    if ($working_assignment->id == $assignment->id) {
                        // This is the top of the inheritance tree, should there be any inherited approvers here?
                        $ancestor_assignment_approval_level = $assignment_approval_level->get_inherited_from_assignment_approval_level();
                        if ($ancestor_assignment_approval_level) {
                            $inherited_approvers = $ancestor_assignment_approval_level->get_approvers();
                            $this->log("Creating " . count($inherited_approvers) . " inherited approvers from " . $ancestor_assignment_approval_level->assignment->name);
                            foreach ($inherited_approvers as $inherited_approver) {
                                assignment_approver::create(
                                    $working_assignment,
                                    $approval_level,
                                    $inherited_approver->type,
                                    $inherited_approver->identifier,
                                    $inherited_approver->id
                                );
                            }
                        }
                    }
                }
            }
            $level_count++;
        }

        $this->log("Done rebuilding inherited approvers.");
        $this->log("Created descendant approvers for {$approver_count} approvers over {$level_count} levels");
        if (self::$logging_enabled || self::$cli_mode) {
            $read_count = $DB->perf_get_reads() - $read_offset;
            $write_count = $DB->perf_get_reads() - $write_offset;
            $this->log("Database reads: {$read_count} / writes: {$write_count}");
        }
    }
}
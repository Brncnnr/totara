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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\backup;

defined('MOODLE_INTERNAL') || die();

use core\orm\query\order;
use core\orm\query\builder;
use mod_approval\model\status;
use mod_approval\model\workflow\stage_feature\approval_levels;
use mod_approval\model\workflow\stage_feature\formviews;
use mod_approval\model\workflow\stage_type\form_submission;
use restore_path_element;
use container_approval\approval as approval_container;
use mod_approval\entity\assignment\assignment as assignment_entity;
use mod_approval\entity\workflow\workflow as workflow_entity;
use mod_approval\entity\workflow\workflow_version as workflow_version_entity;
use mod_approval\model\assignment\assignment;
use mod_approval\model\workflow\workflow;
use mod_approval\model\workflow\workflow_stage;
use mod_approval\model\workflow\workflow_version;
use mod_approval\model\workflow\workflow_stage_formview;
use mod_approval\model\workflow\workflow_stage_approval_level;

global $CFG;
require_once($CFG->dirroot . '/backup/moodle2/restore_stepslib.php');

class restore_activity_structure_step extends \restore_activity_structure_step {

    protected function define_structure() {
        $paths = [];

        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element(
            'workflow',
            '/activity/workflow'
        );

        $paths[] = new restore_path_element(
            'workflow_version',
            '/activity/workflow/workflow_versions/workflow_version'
        );

        $paths[] = new restore_path_element(
            'workflow_stage',
            '/activity/workflow/workflow_versions/workflow_version/workflow_stages/workflow_stage'
        );

        $paths[] = new restore_path_element(
            'workflow_stage_formview',
            '/activity/workflow/workflow_versions/workflow_version/workflow_stages/workflow_stage/workflow_stage_formviews/workflow_stage_formview'
        );

        $paths[] = new restore_path_element(
            'workflow_stage_approval_level',
            '/activity/workflow/workflow_versions/workflow_version/workflow_stages/workflow_stage/workflow_stage_approval_levels/workflow_stage_approval_level'
        );

        $paths[] = new restore_path_element(
            'assignment',
            '/activity/workflow/assignment'
        );

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_assignment($data) {
        $this->log('process_assignment', \backup::LOG_DEBUG);
        $data = (object) $data;

        $assignment = assignment::create(
            $this->get_courseid(),
            $data->assignment_type,
            $data->assignment_identifier,
            $data->is_default
        );
        $assignment->activate();
        $this->apply_activity_instance($assignment->id);
    }

    protected function process_workflow($data) {
        $this->log('process_workflow', \backup::LOG_DEBUG);

        $data = (object) $data;
        $entity = new workflow_entity();
        $entity->workflow_type_id = $data->workflow_type_id;
        $entity->name = $data->name;
        $entity->description = $data->description ?? '';
        $entity->id_number = uniqid('workflow');
        $entity->form_id = $data->form_id;
        $entity->template_id = null;
        $entity->course_id = $this->get_courseid();
        $entity->active = true;
        $entity->to_be_deleted = false;
        $entity->save();

        $workflow_new = workflow::load_by_entity($entity);
        $this->set_mapping('workflow', $data->id, $workflow_new->id);
    }

    protected function process_workflow_version($data) {
        $this->log('process_workflow_version', \backup::LOG_DEBUG);

        $data = (object) $data;
        $workflow_id = $this->get_new_parentid('workflow');
        $entity = new workflow_version_entity();
        $entity->workflow_id = $workflow_id;
        $entity->form_version_id = $data->form_version_id;
        $entity->status = status::DRAFT;
        $entity->save();

        $workflow_version_new = workflow_version::load_by_entity($entity);
        $this->set_mapping('workflow_version', $data->id, $workflow_version_new->id);
    }

    protected function process_workflow_stage($data) {
        $this->log('process_workflow_stage', \backup::LOG_DEBUG);

        $data = (object) $data;
        $workflow_version_id = $this->get_new_parentid('workflow_version');
        $workflow_version = workflow_version::load_by_id($workflow_version_id);
        $workflow_stage = workflow_stage::create(
            $workflow_version,
            $data->name,
            form_submission::get_enum()
        );

        $new_id = $workflow_stage->id;
        $this->set_mapping('workflow_stage', $data->id, $new_id);
    }

    protected function process_workflow_stage_formview($data) {
        $this->log('process_workflow_stage_formview', \backup::LOG_DEBUG);
        $data = (object) $data;

        $workflow_stage_id = $this->get_new_parentid('workflow_stage');
        $workflow_stage = workflow_stage::load_by_id($workflow_stage_id);

        // todo: TL-33025 Could this be put in mod/approval/classes/backup/backup_activity_structure_step.php:145 instead?
        if ($workflow_stage->feature_manager->has(formviews::get_enum())) {
            $workflow_stage_formview = workflow_stage_formview::create(
                $workflow_stage,
                $data->field_key,
                $data->required,
                $data->disabled,
                $data->default_value
            );
        }
    }

    protected function process_workflow_stage_approval_level($data) {
        $this->log('process_workflow_stage_approval_level', \backup::LOG_DEBUG);
        $data = (object) $data;

        $workflow_stage_id = $this->get_new_parentid('workflow_stage');
        $workflow_stage = workflow_stage::load_by_id($workflow_stage_id);

        // todo: TL-33025 Could this be put in mod/approval/classes/backup/backup_activity_structure_step.php:145 instead?
        if ($workflow_stage->feature_manager->has(approval_levels::get_enum())) {
            $workflow_stage->add_approval_level($data->name);
        }
    }

    /**
     * Hook to fix course_sections and course_modules extra record after restore.
     */
    protected function after_restore() {
        // The current module must exist.
        $pluginmanager = \core_plugin_manager::instance();
        $plugininfo = $pluginmanager->get_plugin_info('mod_approval');
        // Check that the approval module is installed.
        if ($plugininfo && $plugininfo->is_installed_and_upgraded()) {
            $assignments = builder::table(assignment_entity::TABLE, 'assignment')
                ->select(['assignment.*'])
                ->where('course', $this->get_courseid())
                ->get();
            $cms = builder::table('course_modules')
                ->select(['*'])
                ->where('course', $this->get_courseid())
                ->get();
            if ($assignments->count() !== $cms->count()) {
                $assignment_ids = $assignments->pluck('id');
                $cm_instances = $cms->pluck('instance');
                $invalid_instance = array_diff_key($cm_instances, $assignment_ids);
                if ($invalid_instance != false) {
                    $course_module = builder::table('course_modules')
                        ->select(['*'])
                        ->where('instance', current($invalid_instance))
                        ->order_by('id', order::DIRECTION_ASC)
                        ->limit(1)
                        ->get()
                        ->first();
                    $container = approval_container::from_id($this->get_courseid());
                    $module = $container->get_module($course_module->id);
                    $module->delete();
                }
            }
        }
    }
}
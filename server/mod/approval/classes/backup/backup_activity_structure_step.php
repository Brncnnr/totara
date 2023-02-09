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

use backup;
use backup_nested_element;

global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
require_once($CFG->dirroot . '/backup/moodle2/backup_stepslib.php');

class backup_activity_structure_step extends \backup_activity_structure_step {

    protected function define_structure() {

        // To know if we are including userinfo
        $userinfo = $this->get_setting_value('userinfo');

        $workflow = new backup_nested_element(
            'workflow',
            ['id'],
            [
                'course_id',
                'workflow_type_id',
                'name',
                'description',
                'id_number',
                'form_id',
                'template_id',
                'active',
                'created',
                'updated',
                'to_be_deleted'
            ]
        );

        $assignment = new backup_nested_element(
            'assignment',
            ['id'],
            [
                'course',
                'name',
                'id_number',
                'is_default',
                'assignment_type',
                'assignment_identifier',
                'status',
                'created',
                'updated',
                'to_be_deleted'
            ]
        );

        $workflow_versions = new backup_nested_element('workflow_versions');
        $workflow_version = new backup_nested_element(
            'workflow_version',
            ['id'],
            [
                'workflow_id',
                'form_version_id',
                'status',
                'created',
                'updated',
            ]
        );

        $workflow_stages = new backup_nested_element('workflow_stages');
        $workflow_stage = new backup_nested_element(
            'workflow_stage',
            ['id'],
            [
                'workflow_version_id',
                'name',
                'sortorder',
                'active',
                'created',
                'updated',
            ]
        );

        $workflow_stage_formviews = new backup_nested_element('workflow_stage_formviews');
        $workflow_stage_formview = new backup_nested_element(
            'workflow_stage_formview',
            ['id'],
            [
                'field_key',
                'workflow_stage_id',
                'required',
                'disabled',
                'default_value',
                'active',
                'created',
                'updated',
            ]
        );

        $workflow_stage_approval_levels = new backup_nested_element('workflow_stage_approval_levels');
        $workflow_stage_approval_level = new backup_nested_element(
            'workflow_stage_approval_level',
            ['id'],
            [
                'workflow_stage_id',
                'name',
                'sortorder',
                'active',
                'created',
                'updated',
            ]
        );

        $workflow->add_child($workflow_versions);
        $workflow->add_child($assignment);

        $workflow_versions->add_child($workflow_version);
        $workflow_version->add_child($workflow_stages);

        $workflow_stages->add_child($workflow_stage);
        $workflow_stage->add_child($workflow_stage_formviews);
        $workflow_stage_formviews->add_child($workflow_stage_formview);

        $workflow_stage->add_child($workflow_stage_approval_levels);
        $workflow_stage_approval_levels->add_child($workflow_stage_approval_level);

        $assignment->set_source_table('approval', ['course' => backup::VAR_COURSEID]);

        $workflow->set_source_table('approval_workflow', ['course_id' => backup::VAR_COURSEID]);
        $workflow_version->set_source_table('approval_workflow_version', ['workflow_id' => backup::VAR_PARENTID]);

        $workflow_stage->set_source_table('approval_workflow_stage', ['workflow_version_id' => backup::VAR_PARENTID]);
        $workflow_stage_formview->set_source_table('approval_workflow_stage_formview', ['workflow_stage_id' => backup::VAR_PARENTID]);
        $workflow_stage_approval_level->set_source_table('approval_workflow_stage_approval_level', ['workflow_stage_id' => backup::VAR_PARENTID]);

        $workflow->annotate_ids('approval_workflow_type', 'workflow_type_id');
        $workflow->annotate_ids('approval_form', 'form_id');
        $workflow_version->annotate_ids('approval_workflow', 'workflow_id');
        $workflow_version->annotate_ids('approval_form_version', 'form_version_id');
        $workflow_stage->annotate_ids('approval_workflow_version', 'workflow_version_id');
        $workflow_stage_formview->annotate_ids('approval_workflow_stage', 'workflow_stage_id');
        $workflow_stage_approval_level->annotate_ids('approval_workflow_stage', 'workflow_stage_id');

        if ($userinfo) {
            // TODO: What?
        }

        return $this->prepare_activity_structure($workflow);
    }
}

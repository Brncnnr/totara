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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 */

namespace mod_approval\event;

use core\event\base;
use mod_approval\controllers\workflow\edit;
use mod_approval\entity\workflow\workflow_stage_approval_level as workflow_stage_approval_level_entity;
use mod_approval\model\assignment\assignment;
use mod_approval\model\workflow\workflow_stage_approval_level;
use moodle_url;

/**
 * Event triggered when the approval level approvers for an assignment is changed.
 */
class workflow_stage_assignment_approvers_for_level_changed extends base {

    /**
     * @inheritDoc
     */
    protected function init(): void {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = workflow_stage_approval_level_entity::TABLE;
    }

    /**
     * Alias method to trigger event.
     *
     * @param assignment $assignment
     * @param workflow_stage_approval_level $approval_level
     * @return void
     */
    public static function execute(assignment $assignment, workflow_stage_approval_level $approval_level): void {
        self::create([
            'objectid' => $approval_level->id,
            'courseid' => $assignment->workflow->course_id,
            'context' => $assignment->workflow->container->get_context(),
            'other' => [
                'assignment_id' => $assignment->id,
                'workflow_id' => $assignment->workflow->id,
                'stage_id' => $approval_level->workflow_stage_id,
            ]
        ])->trigger();
    }

    /**
     * @inheritDoc
     */
    public static function get_name(): string {
        return get_string('event_workflow_stage_assignment_approvers_for_level_changed', 'mod_approval');
    }

    /**
     * @inheritDoc
     */
    public function get_url(): moodle_url {
        return new moodle_url(edit::get_url([
            'workflow_id' => $this->other['workflow_id'],
            'sub_section' => 'approvals',
            'stage_id' => $this->other['stage_id'],
        ]));
    }

    /**
     * @inheritDoc
     */
    public function get_description(): string {
        return get_string(
            'event_workflow_stage_assignment_approvers_for_level_changed_description',
            'mod_approval',
            [
                'userid' => $this->userid,
                'approval_level_id' => $this->objectid,
                'assignment_id' => $this->other['assignment_id'],
            ]
        );
    }
}
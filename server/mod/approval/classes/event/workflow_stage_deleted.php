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
use mod_approval\controllers\workflow\dashboard;
use mod_approval\controllers\workflow\edit;
use mod_approval\entity\workflow\workflow;
use mod_approval\entity\workflow\workflow_stage as workflow_stage_entity;
use mod_approval\model\workflow\workflow as workflow_model;
use mod_approval\model\workflow\workflow_stage;
use moodle_url;

/**
 * Event triggered when a workflow stage is deleted.
 */
class workflow_stage_deleted extends base {

    /**
     * @inheritDoc
     */
    protected function init(): void {
        $this->data['crud'] = 'd';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = workflow_stage_entity::TABLE;
    }

    /**
     * Alias method to trigger the event.
     *
     * @param workflow_stage $workflow_stage
     * @return void
     */
    public static function execute(workflow_stage $workflow_stage): void {
        self::create([
            'objectid' => $workflow_stage->id,
            'courseid' => $workflow_stage->workflow_version->workflow->course_id,
            'context' => $workflow_stage->workflow_version->workflow->container->get_context(),
            'other' => [
                'workflow_id' => $workflow_stage->workflow_version->workflow_id,
                'workflow_version_id' => $workflow_stage->workflow_version_id,
            ]
        ])->trigger();
    }

    /**
     * @inheritDoc
     */
    public static function get_name(): string {
        return get_string('event_workflow_stage_deleted', 'mod_approval');
    }

    /**
     * @inheritDoc
     */
    public function get_url(): moodle_url {
        return new moodle_url(edit::get_url([
            'workflow_id' => $this->other['workflow_id']
        ]));
    }

    /**
     * @inheritDoc
     */
    public function get_description(): string {
        return get_string(
            'event_workflow_stage_deleted_description',
            'mod_approval',
            [
                'userid' => $this->userid,
                'stage_id' => $this->objectid,
                'workflow_version_id' => $this->other['workflow_version_id'],
            ]
        );
    }
}
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
use mod_approval\entity\workflow\workflow;
use mod_approval\model\workflow\workflow as workflow_model;
use moodle_url;

/**
 * Event triggered when a workflow is created.
 */
class workflow_created extends base {

    /**
     * @inheritDoc
     */
    protected function init(): void {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = workflow::TABLE;
    }

    /**
     * Alias method to trigger the event.
     *
     * @param workflow_model $workflow
     * @return void
     */
    public static function execute(workflow_model $workflow): void {
        self::create([
            'objectid' => $workflow->id,
            'courseid' => $workflow->course_id,
            'context' => $workflow->container->get_context(),
        ])->trigger();
    }

    /**
     * @inheritDoc
     */
    public static function get_name(): string {
        return get_string('event_workflow_created', 'mod_approval');
    }

    /**
     * @inheritDoc
     */
    public function get_url(): moodle_url {
        return new moodle_url(edit::get_url(['workflow_id' => $this->objectid]));
    }

    /**
     * @inheritDoc
     */
    public function get_description(): string {
        return get_string(
            'event_workflow_created_description',
            'mod_approval',
            [
                'userid' => $this->userid,
                'workflow_id' => $this->objectid,
            ]
        );
    }
}
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

namespace mod_approval\event;

defined('MOODLE_INTERNAL') || die();

use core\event\base;
use mod_approval\entity\application\application as application_entity;
use mod_approval\model\application\application;

abstract class application_event_base extends base {
    /**
     * Initialise required event data properties.
     */
    protected function init() {
        $this->data['crud'] = static::get_crud();
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = application_entity::TABLE;
    }

    /**
     * Returns the value for event->crud.
     *
     * Override in event class if other than 'u' for update.
     *
     * @return string
     */
    protected static function get_crud(): string {
        return 'u';
    }

    /**
     * Create event by application.
     *
     * @param application $application
     * @param ?int $user_id ID of the user who triggered the event, or null for system trigger (e.g. cron task)
     * @return static
     */
    final public static function create_from_application(application $application, ?int $user_id = null): self {
        $data = [
            'objectid' => $application->get_id(),
            'userid' => $user_id,
            'relateduserid' => $application->user_id,
            'other' => [
                'workflow_type_name' => $application->workflow_type->name,
                'workflow_stage_id' => '',
                'workflow_stage_name' => '',
                'approval_level_id' => '',
                'approval_level_name' => '',
            ],
            'context' => $application->get_context(),
        ];
        if ($application->current_stage) {
            $data['other']['workflow_stage_id'] = $application->current_stage->id;
            $data['other']['workflow_stage_name'] = $application->current_stage->name;
        }
        if ($application->current_approval_level) {
            $data['other']['approval_level_id'] = $application->current_approval_level->id;
            $data['other']['approval_level_name'] = $application->current_approval_level->name;
        }
        /** @var self $event */
        $event = self::create($data);

        // Save a snapshot of the application record in case state is updated by observers.
        $application_entity = new application_entity($application->id);
        $record = (object) $application_entity->to_array();
        $event->add_record_snapshot('approval_application', $record);

        return $event;
    }
}
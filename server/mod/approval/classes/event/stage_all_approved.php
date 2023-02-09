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

use totara_notification\event\notifiable_event;

/**
 * Event stage_all_approved is triggered when all approval levels are complete for a stage.
 *
 * @package mod_approval\event
 */
class stage_all_approved extends application_event_base implements notifiable_event {

    /**
     * @inheritDoc
     */
    public static function get_name() {
        return get_string('event_stage_all_approved', 'mod_approval');
    }

    /**
     * @inheritDoc
     */
    public function get_description() {
        return "All {$this->other['workflow_stage_name']} approvals have been granted on the {$this->other['workflow_type_name']} application with id '{$this->objectid}'.";
    }

    /**
     * @inheritDoc
     */
    public function get_notification_event_data(): array {
        return [
            'application_id' => $this->get_data()['objectid'],
            'workflow_stage_id' => $this->other['workflow_stage_id'],
            'time_all_approved' => $this->get_data()['timecreated'],
        ];
    }
}

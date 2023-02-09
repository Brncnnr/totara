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
 * Event application_rejected_at_level_n is triggered when a rejection happens at any level, and exposes the level.
 *
 * @package mod_approval\event
 */
class level_rejected extends application_event_base implements notifiable_event {

    /**
     * @inheritDoc
     */
    public static function get_name() {
        return get_string('event_level_rejected', 'mod_approval');
    }

    /**
     * @inheritDoc
     */
    public function get_description() {
        return "The user with id '$this->userid' has rejected the {$this->other['workflow_type_name']} application with id '{$this->objectid}' at level {$this->other['approval_level_name']}.";
    }

    /**
     * @inheritDoc
     */
    public function get_notification_event_data(): array {
        return [
            'application_id' => $this->get_data()['objectid'],
            'workflow_stage_id' => $this->other['workflow_stage_id'],
            'approval_level_id' => $this->other['approval_level_id'],
            'time_rejected' => $this->get_data()['timecreated'],
        ];
    }
}

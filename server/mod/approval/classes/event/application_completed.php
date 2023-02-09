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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\event;

use totara_notification\event\notifiable_event;

/**
 * Event application_completed is triggered whenever an application has been completed.
 *
 * @package mod_approval\event
 */
class application_completed extends application_event_base implements notifiable_event {

    /**
     * @inheritDoc
     */
    public static function get_name(): string {
        return get_string('event_application_completed', 'mod_approval');
    }

    /**
     * @inheritDoc
     */
    public function get_description(): string {
        return "The {$this->other['workflow_type_name']} application with id '{$this->objectid}' has been completed";
    }

    /**
     * @inheritDoc
     */
    public function get_notification_event_data(): array {
        return [
            'application_id' => $this->get_data()['objectid'],
            'time_completed' => $this->get_data()['time_completed'],
        ];
    }
}

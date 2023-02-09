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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_notification
 */

use totara_core\extended_context;
use totara_notification\event\notifiable_event;

/**
 * The resolver of this event is {@see totara_notification_mock_scheduled_event_with_on_event_resolver}
 */
class totara_notification_mock_scheduled_event_with_on_event implements notifiable_event {
    /**
     * @var extended_context
     */
    private $extended_context;

    /**
     * @var array
     */
    private $event_data;

    /**
     * totara_notification_mock_scheduled_event_with_on_event constructor.
     * @param extended_context $extended_context
     * @param array            $event_data
     */
    public function __construct(extended_context $extended_context, array $event_data) {
        $this->extended_context = $extended_context;
        $this->event_data = $event_data;
    }

    /**
     * @return array
     */
    public function get_notification_event_data(): array {
        return $this->event_data;
    }
}
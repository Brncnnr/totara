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
namespace totara_notification\event;

use coding_exception;
use totara_notification\model\notification_preference;

/**
 * Event for create a custom notification preference.
 *
 * @method static create_custom_notification_preference_event create(array $event_data)
 */
class create_custom_notification_preference_event extends base_notification_preference_event {
    /**
     * @param notification_preference $preference
     * @param int                     $user_id
     * @return create_custom_notification_preference_event
     */
    public static function from_preference(
        notification_preference $preference,
        int $user_id
    ): create_custom_notification_preference_event {
        if ($preference->is_an_overridden_record()) {
            throw new coding_exception('Cannot create an event for notification preference that is not a custom');
        }

        $extended_context = $preference->get_extended_context();
        $context = $extended_context->get_context();

        $course_id = null;
        $course_context = $context->get_course_context(false);

        if ($course_context) {
            $course_id = $course_context->instanceid;
        }

        return static::create([
            'contextid' => $context->id,
            'courseid' => $course_id,
            'objectid' => $preference->get_id(),
            'userid' => $user_id,
            'other' => [
                'context_name' => $context->get_context_name(),
                'extended_context' => [
                    'component' => $extended_context->get_component(),
                    'area' => $extended_context->get_area(),
                    'item_id' => $extended_context->get_item_id(),
                ],
            ],
        ]);
    }

    /**
     * @return void
     */
    protected function validate_data(): void {
        parent::validate_data();
        if (!array_key_exists('context_name', $this->other)) {
            throw new coding_exception("The event data does not have property 'context_name'");
        }
    }

    /**
     * Returns non-localised description of what happened from the event.
     *
     * @return string
     */
    public function get_description(): string {
        $context_name = $this->other['context_name'];
        return "The user with id {$this->userid} had created a new custom notification at context {$context_name}";
    }

    /**
     * @return string
     */
    public static function get_name(): string {
        return get_string('event_create_custom_notification_preference', 'totara_notification');
    }
}
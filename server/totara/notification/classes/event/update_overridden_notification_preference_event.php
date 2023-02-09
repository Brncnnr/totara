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
 * An event to trigger when user update an overridden record of notification preference at lower context.
 *
 * @method static update_overridden_notification_preference_event create(array $event_data)
 */
class update_overridden_notification_preference_event extends base_notification_preference_event {
    /**
     * @param notification_preference $preference
     * @param int                     $user_id
     * @param array                   $overridden_fields
     *
     * @return update_overridden_notification_preference_event
     */
    public static function from_preference(
        notification_preference $preference,
        int $user_id,
        array $overridden_fields = []
    ): update_overridden_notification_preference_event {
        if (!$preference->is_an_overridden_record()) {
            // If the notification preference does not have parent and a custom notification, then this event
            // will become invalid to the preference. We are treating the built in notification at context system
            // as overridding.
            throw new coding_exception(
                'Cannot trigger an event update overridden notification preference for a custom preference'
            );
        }

        $extended_context = $preference->get_extended_context();
        $context = $extended_context->get_context();

        $course_id = null;
        $course_context = $context->get_course_context(false);

        if (false !== $course_context) {
            $course_id = $course_context->instanceid;
        }

        return static::create([
            'contextid' => $context->id,
            'courseid' => $course_id,
            'objectid' => $preference->get_id(),
            'userid' => $user_id,
            'other' => [
                'preference_title' => $preference->get_title(),
                'overridden_fields' => $overridden_fields,
                'context_name' => $context->get_context_name(),
                'extended_context' => [
                    'component' => $extended_context->get_component(),
                    'area' => $extended_context->get_area(),
                    'item_id' => $extended_context->get_item_id()
                ]
            ]
        ]);
    }

    /**
     * @return void
     */
    protected function validate_data(): void {
        parent::validate_data();
        $required_attributes = [
            'overridden_fields',
            'preference_title',
            'context_name'
        ];

        foreach ($required_attributes as $required_attribute) {
            if (!array_key_exists($required_attribute, $this->other)) {
                throw new coding_exception(
                    "The event data does not have attribute '{$required_attribute}'"
                );
            }
        }

        if (!is_array($this->other['overridden_fields'])) {
            throw new coding_exception("The value of attribute 'overridden_fields' is not an array");
        }
    }

    /**
     * @return string
     */
    public static function get_name(): string {
        return get_string('event_update_overridden_notification_preference', 'totara_notification');
    }
}
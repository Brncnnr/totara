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
 * Event to trigger when user admin create an overridden record for a notification
 * preference at lower context.
 *
 * @method static create_override_notification_preference_event create(array $event_data)
 */
class create_override_notification_preference_event extends base_notification_preference_event {
    /**
     * @param notification_preference $preference
     * @param int                     $user_id
     * @param string[]                $overridden_fields
     *
     * @return create_override_notification_preference_event
     */
    public static function from_preference(
        notification_preference $preference,
        int $user_id,
        array $overridden_fields = []
    ): create_override_notification_preference_event {
        if (!$preference->is_an_overridden_record()) {
            // If the notification preference does not have parent and a custom notification, then this event
            // will become invalid to the preference. We are treating the built in notification at context system
            // as overridding.
            throw new coding_exception('Cannot create an event for overridding a notification preference from a custom preference');
        }

        $extended_context = $preference->get_extended_context();
        $context = $extended_context->get_context();

        $course_id = null;
        $context_course = $context->get_course_context(false);

        if ($context_course) {
            $course_id = $context_course->instanceid;
        }

        return static::create([
            'contextid' => $context->id,
            'courseid' => $course_id,
            'userid' => $user_id,
            'objectid' => $preference->get_id(),
            'other' => [
                'context_name' => $context->get_context_name(),
                'preference_title' => $preference->get_title(),
                'extended_context' => [
                    'component' => $extended_context->get_component(),
                    'item_id' => $extended_context->get_item_id(),
                    'area' => $extended_context->get_area()
                ],
                'overridden_fields' => $overridden_fields
            ],
        ]);
    }

    /**
     * @return string
     */
    public static function get_name(): string {
        return get_string('event_create_override_notification_preference', 'totara_notification');
    }

    /**
     * @return void
     */
    protected function validate_data(): void {
        parent::validate_data();
        $required_attributes = [
            'overridden_fields',
            'context_name',
            'preference_title',
        ];

        foreach ($required_attributes as $required_attribute) {
            if (!array_key_exists($required_attribute, $this->other)) {
                throw new coding_exception(
                    "The event data does not have attribute '{$required_attribute}'"
                );
            }
        }

        if (!is_array($this->other['overridden_fields'])) {
            throw new coding_exception("Attribute 'overridden_fields' is not an array");
        }
    }

    /**
     * Returns the non-localised description of what happened from the event
     * @return string
     */
    public function get_description(): string {
        $overridden_fields = $this->other['overridden_fields'];
        $context_name = $this->other['context_name'];

        if (empty($overridden_fields)) {
            return "User with id {$this->userid} had created a new empty overridden " .
                "record of notification preference at context {$context_name}";
        }

        $fields_string = implode(', ', $overridden_fields);
        $title = $this->other['preference_title'];

        return "User with id {$this->userid} had overridden the following field(s) [{$fields_string}] " .
            "for notification {$title} at context {$context_name}";
    }
}
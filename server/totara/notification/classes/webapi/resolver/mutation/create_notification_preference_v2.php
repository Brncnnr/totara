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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author  Ben Fesili <ben.fesili@totaralearning.com>
 * @package totara_notification
 */
namespace totara_notification\webapi\resolver\mutation;

use coding_exception;
use context;
use context_system;
use core\webapi\execution_context;
use core\webapi\middleware\clean_content_format;
use core\webapi\middleware\clean_editor_content;
use core\webapi\middleware\require_login;
use core\webapi\mutation_resolver;
use totara_core\extended_context;
use totara_notification\builder\notification_preference_builder;
use totara_notification\entity\notification_preference as entity;
use totara_notification\event\create_custom_notification_preference_event;
use totara_notification\event\create_override_notification_preference_event;
use totara_notification\exception\notification_exception;
use totara_notification\interactor\notification_preference_interactor;
use totara_notification\local\helper;
use totara_notification\local\schedule_helper;
use totara_notification\model\notification_preference;
use totara_notification\webapi\middleware\validate_delivery_channel_components;
use totara_notification\webapi\middleware\validate_resolver_class_name;
use totara_notification\webapi\middleware\notification_input_extractor;

class create_notification_preference_v2 extends mutation_resolver {
    /**
     * @param array             $args
     * @param execution_context $ec
     * @return notification_preference
     */
    public static function resolve(array $args, execution_context $ec): notification_preference {
        global $DB, $USER;

        // Default to context system if none is provided.
        $context_id = $args['extended_context']['context_id'] ?? context_system::instance()->id;
        $resolver_class_name = $args['resolver_class_name'];

        // is valid context id
        if (!context::instance_by_id($context_id, IGNORE_MISSING)) {
            throw new coding_exception(
                "Invalid context"
            );
        }

        $extended_context = extended_context::make_with_id(
            $context_id,
            $args['extended_context']['component'] ?? extended_context::NATURAL_CONTEXT_COMPONENT,
            $args['extended_context']['area'] ?? extended_context::NATURAL_CONTEXT_AREA,
            $args['extended_context']['item_id'] ?? extended_context::NATURAL_CONTEXT_ITEM_ID
        );

        // Check the resolver is supported in the given context.
        $supported = call_user_func_array([$resolver_class_name, 'supports_context'], [$extended_context]);
        if (!$supported) {
            throw new coding_exception(
                "Cannot create a notification preference in this context"
            );
        }

        $interactor = new notification_preference_interactor($extended_context, $USER->id);
        if (!$interactor->can_manage_notification_preferences_of_resolver($resolver_class_name)) {
            throw notification_exception::on_manage();
        }

        $context = $extended_context->get_context();
        if (CONTEXT_SYSTEM != $context->contextlevel && !$ec->has_relevant_context()) {
            $ec->set_relevant_context($context);
        }

        $builder = new notification_preference_builder(
            $resolver_class_name,
            $extended_context
        );

        $title = $args['title'] ?? null;

        $overridding = isset($args['ancestor_id']);
        $overridding_fields = [];

        if ($overridding) {
            if ($extended_context->is_natural_context() && CONTEXT_SYSTEM == $context->contextlevel) {
                // Note that this part is also done in the builder as well.
                throw new coding_exception(
                    "Cannot create a notification at context system with the ancestor's id set"
                );
            }

            // Fetch the notification name if it is a built in notification.
            $notification_class_name = $DB->get_field(
                entity::TABLE,
                'notification_class_name',
                ['id' => $args['ancestor_id']],
                MUST_EXIST
            );

            // Found the notification's name. Check that if we have a built in record at this very context or
            // not, and also for the specific event name.
            if (null !== $notification_class_name) {
                if (!empty($title)) {
                    // We do not allow any sort of overridden title for the built in notification.
                    // It should come from the built in notification class.
                    throw new coding_exception(
                        "Cannot overridden the title of any built in notification"
                    );
                }
            }

            // We are checking if the overriding had already been existing in the system or not.
            // that it may exist in this context already.
            $custom_existing = $DB->record_exists(
                entity::TABLE,
                [
                    'ancestor_id' => $args['ancestor_id'],
                    'context_id' => $extended_context->get_context_id(),
                    'component' => $extended_context->get_component(),
                    'area' => $extended_context->get_area(),
                    'item_id' => $extended_context->get_item_id(),
                ]
            );

            if ($custom_existing) {
                throw new coding_exception(
                    "Notification override already exists in the given context"
                );
            }

            $builder->set_ancestor_id($args['ancestor_id']);
            $builder->set_notification_class_name($notification_class_name);

            // Populate the list of overridding fields for logging into the event.
            $override_able_fields = ['body', 'body_format', 'subject', 'subject_format'];
            foreach ($override_able_fields as $field) {
                if (isset($args[$field])) {
                    $overridding_fields[] = $field;
                }
            }
        }

        // Note: builder is able to validate the input data depending on the cases:
        //       either create new custom notification preference or overridden record.
        $builder->set_title($args['title'] ?? null);
        $builder->set_additional_criteria($args['additional_criteria'] ?? null);
        $builder->set_body($args['body'] ?? null);
        $builder->set_body_format($args['body_format'] ?? null);
        $builder->set_subject($args['subject'] ?? null);
        $builder->set_subject_format($args['subject_format'] ?? null);
        $builder->set_enabled($args['enabled'] ?? null);

        // Schedule works in a pair, but writes to a single value.
        $schedule_type = $args['schedule_type'] ?? null;
        $schedule_offset = $args['schedule_offset'] ?? null;
        $raw_schedule_offset = null;

        if (null !== $schedule_type && null !== $schedule_offset) {
            $raw_schedule_offset = schedule_helper::convert_schedule_offset_for_storage(
                $schedule_type,
                $schedule_offset
            );
        } else if ($schedule_type === null ^ $schedule_offset === null) {
            throw new coding_exception("schedule_type and schedule_offset are mutually inclusive");
        }

        $builder->set_schedule_offset($raw_schedule_offset);
        if ($overridding && null !== $raw_schedule_offset) {
            $overridding_fields[] = 'schedule_offset';
        }

        if (isset($args['recipient']) && !isset($args['recipients'])) {
            debugging('Do not use create_notification_preference_v2::recipient any more, use create_notification_preference_v2::recipients instead', DEBUG_DEVELOPER);
            if (!helper::is_valid_recipient_class($args['recipient'])) {
                throw new coding_exception("{$args['recipient']} is not a predefined recipient class");
            }
            // setup for the following logic statement to process
            $args['recipients'] = [$args['recipient']];
        }

        if (isset($args['recipients'])) {
            if (empty($args['recipients']) && is_array($args['recipients'])) {
                throw new coding_exception("recipients cannot be empty");
            }
            $recipients = $args['recipients'];
            foreach ($recipients as $recipient) {
                if (!helper::is_valid_recipient_class($recipient)) {
                    throw new coding_exception("{$recipient} is not a predefined recipient class");
                }
            }
            $builder->set_recipients($recipients);
            if ($overridding && !empty($args['recipients'])) {
                $overridding_fields[] = 'recipients';
            }
        }

        $delivery_channels = $args['forced_delivery_channels'] ?? null;
        if ($delivery_channels === null && !$overridding) {
            $delivery_channels = [];
        }
        $builder->set_forced_delivery_channels($delivery_channels);
        if ($overridding && !empty($args['forced_delivery_channels'])) {
            $overridding_fields[] = 'forced_delivery_channels';
        }

        $preference = $builder->save();

        // Triggers event, however depends on the properties of notification preference, we are going to trigger
        // the different events. As the event is what we are logging the activity.
        if (!$preference->is_an_overridden_record()) {
            // We are creating a new custom notification preference within context.
            $event = create_custom_notification_preference_event::from_preference($preference, $USER->id);
        } else {
            // Otherwise we are overriding the notification preference at a specific context.
            $event = create_override_notification_preference_event::from_preference(
                $preference,
                $USER->id,
                $overridding_fields
            );
        }

        $event->trigger();
        return $preference;
    }

    /**
     * @return array
     */
    public static function get_middleware(): array {
        return [
            new require_login(),
            new notification_input_extractor(),
            new clean_content_format('body_format', null, clean_content_format::SYSTEM_DEFAULT_FORMATS, false),
            new clean_content_format('subject_format', null, clean_content_format::SYSTEM_DEFAULT_FORMATS, false),
            new clean_editor_content('body', 'body_format', false),
            new clean_editor_content('subject', 'subject_format', false),
            new validate_resolver_class_name('resolver_class_name', true),
            new validate_delivery_channel_components('forced_delivery_channels', false)
        ];
    }
}
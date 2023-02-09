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
namespace totara_notification\webapi\resolver\mutation;

use coding_exception;
use core\webapi\execution_context;
use core\webapi\middleware\clean_content_format;
use core\webapi\middleware\clean_editor_content;
use core\webapi\middleware\require_login;
use core\webapi\mutation_resolver;
use totara_notification\builder\notification_preference_builder;
use totara_notification\event\update_custom_notification_preference_event;
use totara_notification\event\update_overridden_notification_preference_event;
use totara_notification\exception\notification_exception;
use totara_notification\interactor\notification_preference_interactor;
use totara_notification\local\helper;
use totara_notification\local\schedule_helper;
use totara_notification\model\notification_preference;
use totara_notification\webapi\middleware\validate_delivery_channel_components;

/**
 * @deprecated Since Totara 17.0
 */
class update_notification_preference extends mutation_resolver {
    /**
     * @param array             $args
     * @param execution_context $ec
     * @return notification_preference
     */
    public static function resolve(array $args, execution_context $ec): notification_preference {
        global $USER;

        debugging('Do not use update_notification_preference::resolve any more, use update_notification_preference_v2::resolve instead', DEBUG_DEVELOPER);

        if (empty($args['id'])) {
            throw new coding_exception(get_string('error_preference_id_missing', 'totara_notification'));
        }

        $preference_id = $args['id'];
        $notification_preference = notification_preference::from_id($preference_id);
        $extended_context = $notification_preference->get_extended_context();
        $interactor = new notification_preference_interactor($extended_context, $USER->id);

        $resolver_class_name = $notification_preference->get_resolver_class_name();
        if (!$interactor->can_manage_notification_preferences_of_resolver($resolver_class_name)) {
            throw notification_exception::on_manage();
        }

        if (CONTEXT_SYSTEM != $extended_context->get_context_level() && !$ec->has_relevant_context()) {
            $ec->set_relevant_context($extended_context->get_context());
        }

        $builder = notification_preference_builder::from_exist_model($notification_preference);
        $is_overridding = $notification_preference->is_an_overridden_record();

        // Records what fields had been overridding by the user actor in event log.
        $overridding_fields = [];

        if (array_key_exists('body', $args)) {
            // Treating empty string as null, so that our builder can reset the
            // value of $body.
            $body = ('' === $args['body']) ? null : $args['body'];
            $builder->set_body($body);
            if ($is_overridding && !empty($body)) {
                $overridding_fields[] = 'body';
            }
        }

        if (array_key_exists('body_format', $args)) {
            $builder->set_body_format($args['body_format']);
            if ($is_overridding) {
                $overridding_fields[] = 'body_format';
            }
        }

        if (array_key_exists('subject', $args)) {
            // Treating empty string as null, so that our builder can
            // reset the value of $subject.
            $subject = ('' === $args['subject']) ? null : $args['subject'];
            $builder->set_subject($subject);

            if ($is_overridding && !empty($subject)) {
                $overridding_fields[] = 'subject';
            }
        }

        if (array_key_exists('subject_format', $args)) {
            $builder->set_subject_format($args['subject_format']);
            if ($is_overridding) {
                $overridding_fields[] = 'subject_format';
            }
        }

        if (array_key_exists('title', $args)) {
            // Business logics check:
            // + If the notification preference is an overridden then it title should not be updated.
            // + If the notification preference is a custom one and does not override any other preference
            //   then the title should not be reset to null.
            // + If the notification preference is the built in at top level then the title should not be updated.
            if ($notification_preference->has_parent() || !$notification_preference->is_custom_notification()) {
                throw new coding_exception("The title of overridden notification preference cannot be updated");
            }

            // Treating empty string as null.
            $title = ('' === $args['title']) ? null : $args['title'];
            if (null === $title) {
                // At this point, we would know that the notification preference does not have any parent.
                // And also it is a custom notification. Hence we do not allow the title to be reset to null.
                throw new coding_exception(
                    "Cannot reset the title of notification of custom notification that does not have parent"
                );
            }

            $builder->set_title($title);
        }

        if (array_key_exists('additional_criteria', $args)) {
            // Treating empty string as null, so that our builder can reset the
            // value of $additional_criteria.
            $additional_criteria = in_array($args['additional_criteria'], ['', 'null']) ? null : $args['additional_criteria'];
            $builder->set_additional_criteria($additional_criteria);
            if ($is_overridding && !empty($additional_criteria)) {
                $overridding_fields[] = 'additional_criteria';
            }
        }

        if (array_key_exists('schedule_type', $args) && array_key_exists('schedule_offset', $args)) {
            // We must translate the value based on the provided schedule type
            $offset = null;
            if (null !== $args['schedule_type']) {
                $offset = schedule_helper::convert_schedule_offset_for_storage(
                    $args['schedule_type'],
                    $args['schedule_offset']
                );
            }
            $builder->set_schedule_offset($offset);

            if ($is_overridding && !empty($offset)) {
                $overridding_fields[] = 'schedule_offset';
            }
        }

        if (array_key_exists('recipient', $args)) {
            $recipient = ('' === $args['recipient']) ? null : $args['recipient'];

            if (!is_null($recipient) && !helper::is_valid_recipient_class($recipient)) {
                throw new coding_exception("{$recipient} is not a predefined recipient class");
            }
            if ($is_overridding && !empty($recipient)) {
                $overridding_fields[] = 'recipient';
            }
            $args['recipients'] = $recipient === null ? null : [$recipient];
        }

        if (array_key_exists('recipients', $args)) {
            $recipients = ('' === $args['recipients']) ? null : $args['recipients'];
            foreach ($recipients as $recipient) {
                if (!helper::is_valid_recipient_class($recipient)) {
                    throw new coding_exception("{$recipient} is not a predefined recipient class");
                }
            }
            $recipients = $args['recipients'];

            $builder->set_recipients($recipients);
            if ($is_overridding && !empty($args['recipients'])) {
                $overridding_fields[] = 'recipients';
            }
        }

        if (array_key_exists('forced_delivery_channels', $args)) {
            $delivery_channels = $args['forced_delivery_channels'];
            // only notification preferences with an ancestor or has notification_class_name set can have null channels
            if ($delivery_channels === null && !$is_overridding) {
                throw new coding_exception('forced_delivery_channels cannot be null if no ancestor exists');
            }
            $builder->set_forced_delivery_channels($delivery_channels);
            if ($is_overridding && !empty($delivery_channels)) {
                $overridding_fields[] = 'forced_delivery_channels';
            }
        }

        if (array_key_exists('enabled', $args)) {
            $builder->set_enabled($args['enabled']);
            if ($is_overridding && null !== $args['enabled']) {
                $overridding_fields[] = 'enabled';
            }
        }

        $notification_preference = $builder->save();

        if ($is_overridding) {
            $event = update_overridden_notification_preference_event::from_preference(
                $notification_preference,
                $USER->id,
                $overridding_fields
            );
        } else {
            $event = update_custom_notification_preference_event::from_preference(
                $notification_preference,
                $USER->id
            );
        }

        $event->trigger();
        return $notification_preference;
    }

    /**
     * @return array
     */
    public static function get_middleware(): array {
        debugging('Do not use update_notification_preference::get_middleware any more, use update_notification_preference_v2::get_middleware instead', DEBUG_DEVELOPER);
        return [
            new require_login(),
            new clean_content_format('body_format'),
            new clean_content_format('subject_format'),
            new clean_editor_content('body', 'body_format', false),
            new clean_editor_content('subject', 'subject_format', false),
            new validate_delivery_channel_components('forced_delivery_channels', false),
        ];
    }
}
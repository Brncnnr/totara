<?php

/**
 * This file is part of Totara Perform
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Gihan Hewaralalage <gihan.hewaralalage@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\totara_notification\resolver;

use container_course\module\course_module;
use core_user\totara_notification\placeholder\user as user_placeholder;
use core_user\totara_notification\placeholder\users as users_placeholder;
use lang_string;
use mod_perform\entity\activity\participant_instance;
use mod_perform\models\activity\activity;
use mod_perform\totara_notification\placeholder\participant_instance as participant_placeholder;
use mod_perform\totara_notification\placeholder\perform_activity as perform_activity_placeholder;
use mod_perform\totara_notification\placeholder\subject_instance as subject_placeholder;
use mod_perform\totara_notification\recipient\appraiser;
use mod_perform\totara_notification\recipient\direct_report;
use mod_perform\totara_notification\recipient\manager;
use mod_perform\totara_notification\recipient\managers_manager;
use mod_perform\totara_notification\recipient\perform_mentor;
use mod_perform\totara_notification\recipient\perform_peer;
use mod_perform\totara_notification\recipient\perform_reviewer;
use mod_perform\totara_notification\recipient\subject;
use totara_core\extended_context;
use totara_core\relationship\relationship as core_relationship;
use totara_job\job_assignment;
use totara_notification\placeholder\placeholder_option;
use totara_notification\resolver\abstraction\additional_criteria_resolver;
use totara_notification\resolver\abstraction\permission_resolver;
use totara_notification\resolver\notifiable_event_resolver;
use totara_notification\schedule\schedule_on_event;

class participant_instance_completion_resolver extends notifiable_event_resolver implements permission_resolver, additional_criteria_resolver {

    public static function get_notification_title(): string {
        return get_string('notification_participant_instance_completion_title', 'mod_perform');
    }

    public static function get_notification_available_recipients(): array {
        return [
            appraiser::class,
            direct_report::class,
            manager::class,
            managers_manager::class,
            perform_mentor::class,
            perform_peer::class,
            perform_reviewer::class,
            subject::class,
        ];
    }

    public static function get_notification_default_delivery_channels(): array {
        return ['email', 'popup'];
    }

    public static function get_notification_available_placeholder_options(): array {
        return [
            placeholder_option::create(
                'recipient',
                user_placeholder::class,
                new lang_string('placeholder_group_recipient', 'totara_notification'),
                function (array $event_data, int $target_user_id): user_placeholder {
                    return user_placeholder::from_id($target_user_id);
                }
            ),
            placeholder_option::create(
                'subject_user',
                user_placeholder::class,
                new lang_string('placeholder_group_subject', 'totara_notification'),
                function (array $event_data): user_placeholder {
                    return user_placeholder::from_id($event_data['subject_user_id']);
                }
            ),
            placeholder_option::create(
                'participant_user',
                user_placeholder::class,
                new lang_string('notification_placeholder_group_participant_user', 'mod_perform'),
                function (array $event_data): user_placeholder {
                    return user_placeholder::from_id($event_data['participant_id']);
                }
            ),
            placeholder_option::create(
                'managers',
                users_placeholder::class,
                new lang_string('placeholder_group_manager', 'totara_notification'),
                function (array $event_data): users_placeholder {
                    return users_placeholder::from_ids(job_assignment::get_all_manager_userids($event_data['subject_user_id']));
                }
            ),
            placeholder_option::create(
                'participant_instance',
                participant_placeholder::class,
                new lang_string('notification_placeholder_group_participant_instance', 'mod_perform'),
                function (array $event_data): participant_placeholder {
                    return participant_placeholder::from_id($event_data['participant_instance_id']);
                }
            ),
            placeholder_option::create(
                'subject_instance',
                subject_placeholder::class,
                new lang_string('notification_placeholder_group_subject_instance', 'mod_perform'),
                function (array $event_data, int $target_user_id): subject_placeholder {
                    $subject_placeholder = subject_placeholder::from_id($event_data['subject_instance_id']);
                    $subject_placeholder->set_recipient_id($target_user_id);
                    return subject_placeholder::from_id($event_data['subject_instance_id']);
                }
            ),
            placeholder_option::create(
                'perform_activity',
                perform_activity_placeholder::class,
                new lang_string('notification_placeholder_group_perform_activity', 'mod_perform'),
                function (array $event_data): perform_activity_placeholder {
                    return perform_activity_placeholder::from_id($event_data['activity_id']);
                }
            ),
        ];
    }

    public static function supports_context(extended_context $extended_context): bool {
        $context = $extended_context->get_context();

        if ($extended_context->is_natural_context()) {
            if ($context->contextlevel == CONTEXT_SYSTEM || $context->contextlevel == CONTEXT_COURSECAT) {
                return true;
            }
            if ($context->contextlevel == CONTEXT_MODULE) {
                $cm = course_module::from_id($context->instanceid);
                return $cm->get_modulename() === 'perform';
            }
        }

        return $context->contextlevel === CONTEXT_MODULE
            && $extended_context->get_area() === 'activity'
            && $extended_context->get_component() === 'mod_perform';
    }

    public static function can_user_manage_notification_preferences(extended_context $context, int $user_id): bool {
        $natural_context = $context->get_context();
        return has_capability('mod/perform:manage_activity', $natural_context, $user_id);
    }

    public static function uses_on_event_queue(): bool {
        return true;
    }

    public static function get_notification_available_schedules(): array {
        return [
            schedule_on_event::class,
        ];
    }

    public static function get_plugin_name(): string {
        return get_string('pluginname', 'mod_perform');
    }

    public function get_extended_context(): extended_context {
        $activity_id = $this->event_data['activity_id'];
        $activity = activity::load_by_id($activity_id);
        return extended_context::make_with_context(
            $activity->get_context(),
            'mod_perform',
            'activity',
            $this->event_data['activity_id']
        );
    }

    public function get_subject(): int {
        return $this->get_event_data()['subject_user_id'];
    }

    /**
     * Define the additional vue component necessary for the extra settings.
     */
    public static function get_additional_criteria_component(): string {
        return 'mod_perform/components/notification/SubmittedInstanceBy';
    }

    /**
     * Verify the returned data is a valid participant roles.
     */
    public static function is_valid_additional_criteria(array $additional_criteria, extended_context $extended_context): bool {
        if (!isset($additional_criteria['submitted_by']) || !is_array($additional_criteria['submitted_by'])) {
            return false;
        }

        // Define expected participant roles.
        $expected = [
            "subject",
            "manager",
            "managers_manager",
            "appraiser",
            "perform_peer",
            "perform_mentor",
            "perform_reviewer",
            "direct_report",
            "perform_external"
        ];

        foreach ($additional_criteria['submitted_by'] as $recipient) {
            if (!in_array($recipient, $expected)) {
                // We've returned something outside expected participant roles.
                return false;
            }
        }

        return true;
    }

    /**
     * @param array|null $additional_criteria
     * @param array $event_data
     * @return bool
     */
    public static function meets_additional_criteria(?array $additional_criteria, array $event_data): bool {
        if (!isset($additional_criteria['submitted_by']) || !is_array($additional_criteria['submitted_by'])) {
            return false;
        }

        $allowed_notification_role_ids = [];

        foreach ($additional_criteria['submitted_by'] as $recipient) {
            $allowed_notification_role_ids[] = core_relationship::load_by_idnumber($recipient)->id;
        }

        return  participant_instance::repository()
            ->where('id', $event_data['participant_instance_id'])
            ->where_in('core_relationship_id', $allowed_notification_role_ids)
            ->exists();
    }
}
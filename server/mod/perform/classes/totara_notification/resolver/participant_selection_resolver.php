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
 * @author Ben Fesili <ben.fesili@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\totara_notification\resolver;

use container_course\module\course_module;
use core\orm\query\builder;
use core_user\totara_notification\placeholder\user as user_placeholder;
use core_user\totara_notification\placeholder\users as users_placeholder;
use lang_string;
use mod_perform\models\activity\activity;
use mod_perform\totara_notification\placeholder\perform_activity as perform_activity_placeholder;
use mod_perform\totara_notification\placeholder\subject_instance as subject_placeholder;
use mod_perform\totara_notification\recipient\participant_selector_appraiser;
use mod_perform\totara_notification\recipient\participant_selector_direct_report;
use mod_perform\totara_notification\recipient\participant_selector_manager;
use mod_perform\totara_notification\recipient\participant_selector_managers_manager;
use mod_perform\totara_notification\recipient\participant_selector_subject;
use moodle_recordset;
use totara_core\extended_context;
use totara_job\job_assignment;
use totara_notification\placeholder\placeholder_option;
use totara_notification\resolver\abstraction\permission_resolver;
use totara_notification\resolver\abstraction\scheduled_event_resolver;
use totara_notification\resolver\notifiable_event_resolver;
use totara_notification\schedule\schedule_after_event;
use totara_notification\schedule\schedule_on_event;

class participant_selection_resolver extends notifiable_event_resolver implements permission_resolver, scheduled_event_resolver {

    public static function get_notification_title(): string {
        return get_string('notification_resolver_participant_selection_title', 'mod_perform');
    }

    public static function get_notification_available_recipients(): array {
        return [
            participant_selector_subject::class,
            participant_selector_manager::class,
            participant_selector_managers_manager::class,
            participant_selector_appraiser::class,
            participant_selector_direct_report::class,
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
                'managers',
                users_placeholder::class,
                new lang_string('placeholder_group_manager', 'totara_notification'),
                function (array $event_data): users_placeholder {
                    return users_placeholder::from_ids(job_assignment::get_all_manager_userids($event_data['subject_user_id']));
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
        return false;
    }

    public static function get_scheduled_events(int $min_time, int $max_time): moodle_recordset {
        // Get the data required from participant and subject instance.
        $builder = builder::table('perform_subject_instance')->as('subject_instance');
        $builder->join(['perform_track_user_assignment', 'track_user'], 'subject_instance.track_user_assignment_id', 'track_user.id');
        $builder->join(['perform_track', 'track'], 'track_user.track_id', 'track.id');
        $builder->select([
            'subject_instance.id as subject_instance_id',
            'subject_instance.subject_user_id as subject_user_id',
            'track.activity_id as activity_id',
            'subject_instance.created_at as created_at',
        ]);

        $builder->where('subject_instance.created_at', '>=', $min_time);
        $builder->where('subject_instance.created_at', '<', $max_time);
        $builder->group_by(['subject_instance.id', 'subject_instance.subject_user_id', 'track.activity_id', 'subject_instance.created_at']);

        return $builder->get_lazy();
    }

    public static function get_notification_available_schedules(): array {
        return [
            schedule_on_event::class,
            schedule_after_event::class,
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

    public function get_fixed_event_time(): int {
        return $this->event_data['created_at'];
    }

    public function get_subject(): int {
        return $this->get_event_data()['subject_user_id'];
    }
}
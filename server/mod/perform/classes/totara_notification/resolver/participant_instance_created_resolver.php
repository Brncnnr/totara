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
 * @author David Curry <david.curry@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\totara_notification\resolver;

use coding_exception;
use container_course\module\course_module;
use core\orm\query\builder;
use core_user\totara_notification\placeholder\user as user_placeholder;
use core_user\totara_notification\placeholder\users as users_placeholder;
use lang_string;
use mod_perform\entity\activity\participant_instance;
use mod_perform\models\activity\activity;
use mod_perform\models\activity\participant_source;
use mod_perform\state\participant_instance\in_progress;
use mod_perform\state\participant_instance\not_started;
use mod_perform\totara_notification\placeholder\external_participant;
use mod_perform\totara_notification\placeholder\participant_instance as participant_placeholder;
use mod_perform\totara_notification\placeholder\subject_instance as subject_placeholder;
use moodle_exception;
use moodle_recordset;
use mod_perform\totara_notification\recipient\participant;
use mod_perform\totara_notification\placeholder\perform_activity as perform_activity_placeholder;
use totara_notification\resolver\abstraction\additional_criteria_resolver;
use totara_notification\resolver\abstraction\permission_resolver;
use totara_core\extended_context;
use totara_core\relationship\relationship as core_relationship;
use totara_job\job_assignment;
use totara_notification\placeholder\placeholder_option;
use totara_notification\resolver\abstraction\audit_resolver;
use totara_notification\resolver\abstraction\scheduled_event_resolver;
use totara_notification\resolver\notifiable_event_resolver;
use totara_notification\schedule\schedule_after_event;
use totara_notification\schedule\schedule_on_event;

class participant_instance_created_resolver extends notifiable_event_resolver implements scheduled_event_resolver, permission_resolver, audit_resolver, additional_criteria_resolver {

    /**
     * Returns the title for this notifiable event, which should be used
     * within the tree table of available notifiable events.
     *
     * @return string
     * @throws coding_exception
     */
    public static function get_notification_title(): string {
        return get_string('notification_resolver_participant_instance_created_title', 'mod_perform');
    }

    /**
     * @param extended_context $context
     * @param int $user_id
     * @return bool
     * @throws coding_exception
     */
    public static function can_user_manage_notification_preferences(extended_context $context, int $user_id): bool {
        $natural_context = $context->get_context();
        return has_capability('mod/perform:manage_activity', $natural_context, $user_id);
    }

    /**
     * @inheritDoc
     */
    public static function can_user_audit_notifications(extended_context $context, int $user_id): bool {
        $natural_context = $context->get_context();
        return has_capability('mod/perform:audit_notifications', $natural_context, $user_id);
    }

    /**
     * This is to check whether the resolver is processed through event queue or not and also it could be override if
     * dev want to skip queueing up.
     *
     * @return bool
     */
    public static function uses_on_event_queue(): bool {
        return true;
    }

    /**
     * @return string[]
     */
    public static function get_notification_available_schedules(): array {
        return [
            schedule_on_event::class,
            schedule_after_event::class,
        ];
    }

    /**
     * @param int $min_time
     * @param int $max_time
     * @return moodle_recordset
     * @throws coding_exception
     */
    public static function get_scheduled_events(int $min_time, int $max_time): moodle_recordset {

        // Get the data required from participant and subject instance.
        $builder = builder::table('perform_participant_instance')->as('instance');
        $builder->join(['perform_subject_instance', 'subject'], 'instance.subject_instance_id', 'subject.id');
        $builder->join(['perform_track_user_assignment', 'track_user'], 'subject.track_user_assignment_id', 'track_user.id');
        $builder->join(['perform_track', 'track'], 'track_user.track_id', 'track.id');
        $builder->select([
            'subject.id as subject_instance_id',
            'subject.subject_user_id as subject_user_id',
            'track.activity_id as activity_id',
            'instance.created_at as created_at',
            'instance.id as participant_instance_id',
            'instance.participant_id as participant_id',
            'instance.participant_source as participant_source'
        ]);

        $builder->where('instance.created_at', '>=', $min_time);
        $builder->where('instance.created_at', '<', $max_time);
        $builder->where_in('instance.progress', [not_started::get_code(), in_progress::get_code()]);
        $builder->group_by([
            'subject.id',
            'subject.subject_user_id',
            'track.activity_id',
            'instance.created_at',
            'instance.id',
            'instance.participant_id',
            'instance.participant_source'
        ]);

        return $builder->get_lazy();
    }

    /**
     * @inheritDoc
     */
    public function get_fixed_event_time(): int {
        return $this->event_data['created_at'];
    }

    /**
     * Returns an array of available recipients (metadata) for this event.
     *
     * @return array
     */
    public static function get_notification_available_recipients(): array {
        return [
            participant::class
        ];
    }

    /**
     * Returns the default delivery channels that defined for the event by developers.
     * However, note that admin can override this default delivery channels.
     *
     * If nothing/a specific channel is not listed here, it will fallback to the built in default.
     * To disable it, specify the actual default here.
     *
     * @return array
     */
    public static function get_notification_default_delivery_channels(): array {
        return ['email', 'popup'];
    }

    /**
     * Returns the extended context of where this event occurred. Note that this should almost certainly be
     * either the same as the natural context (but wrapped in the extended context container class) or an
     * extended context where the natural context is the immediate parent.
     *
     * @return extended_context
     * @throws moodle_exception
     */
    public function get_extended_context(): extended_context {
        $activity = activity::load_by_id($this->event_data['activity_id']);
        return extended_context::make_with_context(
            $activity->get_context(),
            'mod_perform',
            'activity',
            $this->event_data['activity_id']
        );
    }

    /**
     * Indicates whether the resolver supports the given context.
     * By default, resolvers support the system context.
     * Override this function to support other contexts.
     *
     * @param extended_context $extended_context
     * @return bool
     */
    public static function supports_context(extended_context $extended_context): bool {
        $context = $extended_context->get_context();

        if ($extended_context->is_natural_context()) {
            // [CONTEXT_COURSECAT, CONTEXT_COURSE])) {
            if ($context->contextlevel == CONTEXT_SYSTEM || $context->contextlevel == CONTEXT_TENANT) {
                return true;
            }
            if ($context->contextlevel == CONTEXT_MODULE) {
                $cm = course_module::from_id($context->instanceid);
                return $cm->get_modulename() == 'perform';
            }
        }

        return false;
    }

    /**
     * @inheritDocs
     * @throws coding_exception
     */
    public static function get_plugin_name(): ?string {
        return get_string('pluginname', 'mod_perform');
    }

    /**
     * Returns an array of available recipients (metadata) for this event.
     *
     * @return array
     * @throws coding_exception
     */
    public static function get_notification_available_placeholder_options(): array {
        return [
            placeholder_option::create(
                'recipient',
                user_placeholder::class,
                new lang_string('placeholder_group_recipient', 'totara_notification'),
                function (array $event_data, int $target_user_id): user_placeholder {
                    if ($event_data['participant_source'] == participant_source::EXTERNAL) {
                        return new user_placeholder(null);
                    }
                    return user_placeholder::from_id($target_user_id);
                }
            ),
            placeholder_option::create(
                'external_recipient',
                external_participant::class,
                new lang_string('notification_placeholder_group_external_participant', 'mod_perform'),
                function (array $event_data, int $target_user_id): external_participant {
                    if ($event_data['participant_source'] == participant_source::EXTERNAL) {
                        return external_participant::from_id($event_data['participant_id']);
                    }
                    return new external_participant(null);
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
                'managers',
                users_placeholder::class,
                new lang_string('placeholder_group_manager', 'totara_notification'),
                function (array $event_data): users_placeholder {
                    return users_placeholder::from_ids(job_assignment::get_all_manager_userids($event_data['subject_user_id']));
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
                'participant_instance',
                participant_placeholder::class,
                new lang_string('notification_placeholder_group_participant_instance', 'mod_perform'),
                function (array $event_data): participant_placeholder {
                    $participant_placeholder = participant_placeholder::from_id($event_data['participant_instance_id']);
                    $participant_placeholder->set_recipient_id($event_data['participant_id']);
                    return $participant_placeholder;
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

    /**
     * @inheritDoc
     */
    public function get_notification_log_display_string_key_and_params(): array {
        // The resolver title is translated at view time
        $params = ['resolver_title' => ''];

        $user = user_placeholder::from_id($this->get_event_data()['subject_user_id']);
        $params['user'] = $user->do_get('full_name');

        $activity = perform_activity_placeholder::from_id($this->get_event_data()['activity_id']);
        $params['activity'] = $activity->do_get('name');

        return [
            'key' => 'notification_resolver_participant_instance_created_audit',
            'component' => 'mod_perform',
            'params' => $params,
        ];
    }

    public function get_subject(): int {
        return $this->get_event_data()['subject_user_id'];
    }

    /**
     * Define the additional vue componenent necessary for the extra settings.
     */
    public static function get_additional_criteria_component(): string {
        return 'mod_perform/components/notification/RecipientRole';
    }

    /**
     * Verify the returned data is a valid participant roles.
     */
    public static function is_valid_additional_criteria(array $additional_criteria, extended_context $extended_context): bool {
        if (!isset($additional_criteria['recipients']) || !is_array($additional_criteria['recipients'])) {
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

        foreach ($additional_criteria['recipients'] as $recipient) {
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
        if (!isset($additional_criteria['recipients']) || !is_array($additional_criteria['recipients'])) {
            return false;
        }

        $allowed_notification_role_ids = [];

        foreach ($additional_criteria['recipients'] as $recipient) {
            $allowed_notification_role_ids[] = core_relationship::load_by_idnumber($recipient)->id;
        }

        return  participant_instance::repository()
            ->where('id', $event_data['participant_instance_id'])
            ->where_in('core_relationship_id', $allowed_notification_role_ids)
            ->exists();
    }
}

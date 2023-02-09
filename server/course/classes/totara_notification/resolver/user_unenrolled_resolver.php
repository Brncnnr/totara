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
 * @author Simon Player <simon.player@totaralearning.com>
 * @package core_course
 */

namespace core_course\totara_notification\resolver;

use context_course;
use core_course\totara_notification\placeholder\course as placeholder_course;
use core_completion\totara_notification\placeholder\course_completion as placeholder_completion;
use core_user\totara_notification\placeholder\user;
use core_user\totara_notification\placeholder\users;
use lang_string;
use totara_core\extended_context;
use totara_job\job_assignment;
use totara_notification\resolver\abstraction\audit_resolver;
use totara_notification\resolver\notifiable_event_resolver;
use totara_notification\resolver\abstraction\permission_resolver;
use totara_notification\placeholder\placeholder_option;
use totara_notification\recipient\subject;
use totara_notification\recipient\manager;

class user_unenrolled_resolver extends notifiable_event_resolver implements permission_resolver, audit_resolver {

    /**
     * @inheritDocs
     * @throws \coding_exception
     */
    public static function get_plugin_name(): ?string {
        return get_string('course');
    }

    /**
     * Returns the title for this notifiable event, which should be used
     * within the tree table of available notifiable events.
     *
     * @return string
     */
    public static function get_notification_title(): string {
        return get_string('notification_course_unenrolment_title', 'moodle');
    }

    /**
     * Returns an array of available recipients (metadata) for this event (concrete class).
     *
     * @return array
     */
    public static function get_notification_available_recipients(): array {
        return [
            subject::class,
            manager::class
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
        return [
            'email',
            'popup'
        ];
    }

    /**
     * Returns the list of available placeholder options.
     *
     * @return placeholder_option[]
     */
    public static function get_notification_available_placeholder_options(): array {
        return [
            placeholder_option::create(
                'recipient',
                user::class,
                new lang_string('placeholder_group_recipient', 'totara_notification'),
                function (array $unused_event_data, int $target_user_id): user {
                    return user::from_id($target_user_id);
                }
            ),
            placeholder_option::create(
                'subject',
                user::class,
                new lang_string('placeholder_group_subject', 'totara_notification'),
                function (array $event_data): user {
                    return user::from_id($event_data['user_id']);
                }
            ),
            placeholder_option::create(
                'managers',
                users::class,
                new lang_string('placeholder_group_manager', 'totara_notification'),
                function (array $event_data): users {
                    return users::from_ids(job_assignment::get_all_manager_userids($event_data['user_id']));
                }
            ),
            placeholder_option::create(
                'course',
                placeholder_course::class,
                new lang_string('placeholder_group_course'),
                function (array $event_data): placeholder_course {
                    return placeholder_course::from_id($event_data['course_id']);
                }
            ),
            placeholder_option::create(
                'completion',
                placeholder_completion::class,
                new lang_string('placeholder_group_course_completion', 'core_completion'),
                function (array $event_data): placeholder_completion {
                    return placeholder_completion::from_course_id_and_user_id($event_data['course_id'], $event_data['user_id']);
                }
            ),
        ];
    }

    /**
     * Returns the extended context of where this event occurred. Note that this should almost certainly be
     * either the same as the natural context (but wrapped in the extended context container class) or an
     * extended context where the natural context is the immediate parent.
     *
     * @return extended_context
     */
    public function get_extended_context(): extended_context {
        return extended_context::make_with_context(
            context_course::instance($this->event_data['course_id']),
        );
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
            return in_array($context->contextlevel, [CONTEXT_SYSTEM, CONTEXT_TENANT, CONTEXT_COURSECAT, CONTEXT_COURSE]);
        }

        return false;
    }

    /**
     * @param extended_context $context
     * @param int              $user_id
     * @return bool
     */
    public static function can_user_manage_notification_preferences(extended_context $context, int $user_id): bool {
        $natural_context = $context->get_context();
        $capability = 'moodle/course:managecoursenotifications';
        return has_capability($capability, $natural_context, $user_id);
    }

    /**
     * @inheritDoc
     */
    public static function can_user_audit_notifications(extended_context $context, int $user_id): bool {
        $natural_context = $context->get_context();
        $capability = 'moodle/course:auditcoursenotifications';
        return has_capability($capability, $natural_context, $user_id);
    }

    /**
     * @inheritDoc
     */
    public function get_notification_log_display_string_key_and_params(): array {
        // The resolver title is translated at view time
        $params = ['resolver_title' => ''];

        $user = user::from_id($this->get_event_data()['user_id']);
        $params['user'] = $user->do_get('full_name');

        $course = placeholder_course::from_id($this->get_event_data()['course_id']);
        $params['course'] = $course->do_get('full_name');

        return [
            'key' => 'notification_log_course_unenrolment',
            'component' => 'core',
            'params' => $params,
        ];
    }

}
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
 * @author David Curry <david.curry@totaralearning.com>
 * @package core_course
 */

namespace core_course\totara_notification\resolver;

use container_course\module\course_module;
use context_module;
use core\entity\course;
use core\orm\query\builder;
use core_course\totara_notification\placeholder\course as placeholder_course;
use core_course\totara_notification\placeholder\activity as placeholder_activity;
use core_completion\totara_notification\placeholder\activity_completion as placeholder_completion;
use core_user\totara_notification\placeholder\user;
use core_user\totara_notification\placeholder\users;
use lang_string;
use moodle_recordset;
use totara_core\extended_context;
use totara_job\job_assignment;
use totara_notification\resolver\abstraction\audit_resolver;
use totara_notification\resolver\notifiable_event_resolver;
use totara_notification\resolver\abstraction\scheduled_event_resolver;
use totara_notification\resolver\abstraction\permission_resolver;
use totara_notification\placeholder\placeholder_option;
use totara_notification\recipient\subject;
use totara_notification\recipient\manager;
use totara_notification\schedule\schedule_after_event;
use totara_notification\schedule\schedule_on_event;

class activity_completed_resolver extends notifiable_event_resolver implements scheduled_event_resolver, permission_resolver, audit_resolver {
    /**
     * @inheritDocs
     * @throws \coding_exception
     */
    public static function get_plugin_name(): ?string {
        return get_string('notification_group_course_activity');
    }

    /**
     * Returns the title for this notifiable event, which should be used
     * within the tree table of available notifiable events.
     *
     * @return string
     * @throws \coding_exception
     */
    public static function get_notification_title(): string {
        return get_string('notification_activity_completed_title');
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
     * Returns an array of available schedules for this event.
     *
     * @return array
     */
    public static function get_notification_available_schedules(): array {
        return [
            schedule_on_event::class,
            schedule_after_event::class,
        ];
    }

    /**
     * Return all events scheduled to happen between min/max times
     *
     * @param int $min_time
     * @param int $max_time
     * @return moodle_recordset
     * @throws \coding_exception
     */
    public static function get_scheduled_events(int $min_time, int $max_time): moodle_recordset {

        $subquery = builder::table('course_modules_completion')
            ->select(['id', 'coursemoduleid as course_module_id', 'userid as user_id'])
            ->add_select_raw("
               CASE WHEN timecompleted IS NULL AND completionstate > 0
               THEN timemodified
               ELSE timecompleted
               END AS time_completed
            ");


        return builder::table($subquery)
            ->as('cmc')
            ->select(['cmc.time_completed', 'cmc.course_module_id', 'cmc.user_id', 'cm.course as course_id'])
            ->join(['course_modules', 'cm'], 'cmc.course_module_id', 'cm.id')
            ->where('cmc.time_completed', '>=', $min_time)
            ->where('cmc.time_completed', '<', $max_time)
            ->order_by('cmc.time_completed')
            ->get_lazy();
    }

    /**
     * @return int
     */
    public function get_fixed_event_time(): int {
        return $this->event_data['time_completed'];
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
     * @throws \coding_exception
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
                'activity',
                placeholder_activity::class,
                new lang_string('placeholder_group_course_module'),
                function (array $event_data): placeholder_activity {
                    return placeholder_activity::from_id($event_data['course_module_id']);
                }
            ),
            placeholder_option::create(
                'activity_completion',
                placeholder_completion::class,
                new lang_string('placeholder_group_activity_completion', 'core_completion'),
                function (array $event_data): placeholder_completion {
                    return placeholder_completion::from_activity_id_and_user_id(
                        $event_data['course_module_id'],
                        $event_data['user_id']
                    );
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
            context_module::instance($this->event_data['course_module_id']),
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
        global $DB;

        $context = $extended_context->get_context();
        if ($extended_context->is_natural_context()) {
            if (in_array($context->contextlevel, [CONTEXT_SYSTEM, CONTEXT_TENANT, CONTEXT_COURSECAT])) {
                return true;
            }

            // Make sure this course is acutually a course.
            if ($context->contextlevel == CONTEXT_COURSE) {
                $container = $DB->get_field('course', 'containertype', ['id' => $context->instanceid]);
                return $container == 'container_course';
            }

            // Make sure this module is a course module.
            if ($context->contextlevel == CONTEXT_MODULE) {
                $cm = course_module::from_id($context->instanceid);
                return $cm->is_course_module();
            }
        }

        return false;
    }

    /**
     * @param extended_context $context
     * @param int $user_id
     * @return bool
     * @throws \coding_exception
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
     * This notification depends on activity completion being enabled for the course and activity contexts.
     *
     * @param extended_context $extended_context
     * @return array
     */
    public static function get_warnings(extended_context $extended_context): array {
        $warnings = parent::get_warnings($extended_context);

        // Course context warning.
        if ($extended_context->is_natural_context() && $extended_context->get_context_level() == CONTEXT_COURSE) {
            $course_completion_is_enabled = course::repository()
                ->where('id', '=', $extended_context->get_context()->instanceid)
                ->where('enablecompletion', '=', '1')
                ->exists();
            if (!$course_completion_is_enabled) {
                $warnings[] = get_string(
                    'notification_activity_completed_disabled_course_warning',
                    'moodle'
                );
            }
        }

        // Activity context warning.
        if ($extended_context->is_natural_context() && $extended_context->get_context_level() == CONTEXT_MODULE) {
            $course_completion_is_enabled = course::repository()
                ->where('id', '=', $extended_context->get_context()->get_parent_context()->instanceid)
                ->where('enablecompletion', '=', '1')
                ->exists();
            if (!$course_completion_is_enabled) {
                $warnings[] = get_string(
                    'notification_activity_completed_disabled_course_warning',
                    'moodle'
                );
            }

            $activity = builder::table('course_modules')
                ->where('id', '=', $extended_context->get_context()->instanceid)
                ->one();
            if ($activity->completion == COMPLETION_TRACKING_NONE) {
                $coursemodule = get_coursemodule_from_id(false, $extended_context->get_context()->instanceid);
                $activity_type_name = get_string('pluginadministration', $coursemodule->modname);
                $warnings[] = get_string(
                    'notification_activity_completed_disabled_activity_warning',
                    'moodle',
                    $activity_type_name
                );
            }
        }

        return $warnings;
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

        $cm = placeholder_activity::from_id($this->get_event_data()['course_module_id']);
        $params['activity'] = $cm->do_get('name');

        return [
            'key' => 'notification_log_activity_completed',
            'component' => 'core',
            'params' => $params,
        ];
    }

}

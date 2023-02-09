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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package core_course
 * @category totara_notification
 */

namespace core_course\totara_notification\resolver;

use completion_info;
use context_course;
use core\entity\course;
use core\entity\course_completion as course_completion_entity;
use core\orm\query\builder;
use lang_string;
use moodle_recordset;
use totara_core\extended_context;
use totara_job\job_assignment;
use totara_notification\placeholder\placeholder_option;
use totara_notification\resolver\abstraction\audit_resolver;
use totara_notification\resolver\abstraction\permission_resolver;
use totara_notification\resolver\abstraction\scheduled_event_resolver;
use totara_notification\resolver\notifiable_event_resolver;
use totara_notification\schedule\schedule_after_event;
use totara_notification\schedule\schedule_before_event;
use totara_notification\schedule\schedule_on_event;
use totara_notification\recipient\manager;
use totara_notification\recipient\subject;
use core_course\totara_notification\placeholder\course as course_placeholder;
use core_completion\totara_notification\placeholder\course_completion as course_completion_placeholder;
use core_user\totara_notification\placeholder\user as user_placeholder;
use core_user\totara_notification\placeholder\users as users_placeholder;

class course_due_date_resolver extends notifiable_event_resolver implements scheduled_event_resolver, permission_resolver, audit_resolver {

    /**
     * @return string
     */
    public static function get_notification_title(): string {
        return get_string('notification_course_due_date_resolver_title', 'moodle');
    }

    /**
     * @return string[]
     */
    public static function get_notification_available_recipients(): array {
        return [
            subject::class,
            manager::class,
        ];
    }

    /**
     * @return string[]
     */
    public static function get_notification_available_schedules(): array {
        return [
            schedule_before_event::class,
            schedule_on_event::class,
            schedule_after_event::class,
        ];
    }

    /**
     * @param int $min_time
     * @param int $max_time
     * @return moodle_recordset
     */
    public static function get_scheduled_events(int $min_time, int $max_time): moodle_recordset {
        global $CFG;
        require_once($CFG->libdir . '/completionlib.php');

        if (!completion_info::is_enabled_for_site()) {
            return new \array_recordset([]);
        }

        return builder::table('course_completions')
            ->as('cc')
            ->select(['cc.course as course_id', 'cc.userid as user_id', 'cc.duedate'])
            ->join(['course', 'c'], 'course', 'c.id')
            ->where('c.enablecompletion', COMPLETION_ENABLED)
            ->where_not_in('cc.status', [COMPLETION_STATUS_COMPLETE, COMPLETION_STATUS_COMPLETEVIARPL])
            ->where('cc.duedate', '>=', $min_time)
            ->where('cc.duedate', '<', $max_time)
            ->get_lazy();
    }

    /**
     * @inheritDoc
     */
    public function get_fixed_event_time(): int {
        return $this->event_data['duedate'];
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
                'subject',
                user_placeholder::class,
                new lang_string('placeholder_group_subject', 'totara_notification'),
                function (array $event_data): user_placeholder {
                    return user_placeholder::from_id($event_data['user_id']);
                }
            ),
            placeholder_option::create(
                'managers',
                users_placeholder::class,
                new lang_string('placeholder_group_manager', 'totara_notification'),
                function (array $event_data): users_placeholder {
                    return users_placeholder::from_ids(job_assignment::get_all_manager_userids($event_data['user_id']));
                }
            ),
            placeholder_option::create(
                'course',
                course_placeholder::class,
                new lang_string('notification_course_placeholder_group', 'moodle'),
                function (array $event_data): course_placeholder {
                    return course_placeholder::from_id($event_data['course_id']);
                }
            ),
            placeholder_option::create(
                'course_completion',
                course_completion_placeholder::class,
                new lang_string('notification_course_completion_placeholder_group', 'moodle'),
                function (array $event_data): course_completion_placeholder {
                    return course_completion_placeholder::from_course_id_and_user_id(
                        $event_data['course_id'],
                        $event_data['user_id']
                    );
                }
            ),
        ];
    }

    /**
     * @inheritDoc
     */
    public function get_extended_context(): extended_context {
        return extended_context::make_with_context(
            context_course::instance($this->event_data['course_id']),
        );
    }

    /**
     * @inheritDoc
     */
    public static function uses_on_event_queue(): bool {
        return false;
    }
    /**
     * @inheritDoc
     */
    public static function supports_context(extended_context $extended_context): bool {
        $context = $extended_context->get_context();

        if ($extended_context->is_natural_context()) {
            return in_array($context->contextlevel, [CONTEXT_SYSTEM, CONTEXT_TENANT, CONTEXT_COURSECAT, CONTEXT_COURSE]);
        }

        return false;
    }

    /**
     * @inheritDoc
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
    public static function get_plugin_name(): ?string {
        return get_string('course');
    }

    /**
     * This notification depends on course completion and due date being enabled for the course.
     *
     * @param extended_context $extended_context
     * @return array
     */
    public static function get_warnings(extended_context $extended_context): array {
        $warnings = parent::get_warnings($extended_context);

        // Only display the warning in the course context.
        if ($extended_context->is_natural_context() && $extended_context->get_context_level() == CONTEXT_COURSE) {
            $course = course::repository()
                ->where('id', '=', $extended_context->get_context()->instanceid)
                ->one();

            if (!$course->enablecompletion) {
                $warnings[] = get_string('notification_course_due_disabled_completion_warning', 'moodle');
            }

            if (empty($course->duedate) && empty($course->duedateoffsetamount)) {
                $warnings[] = get_string('notification_course_due_disabled_due_date_warning', 'moodle');
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

        $user = user_placeholder::from_id($this->get_event_data()['user_id']);
        $params['user'] = $user->do_get('full_name');

        $course = course_placeholder::from_id($this->get_event_data()['course_id']);
        $params['course'] = $course->do_get('full_name');

        return [
            'key' => 'notification_log_course_due_date',
            'component' => 'core',
            'params' => $params,
        ];
    }

}

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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package totara_program
 */
namespace totara_program\totara_notification\resolver;

use context_program;
use core\orm\query\builder;
use core_user\totara_notification\placeholder\user;
use core_user\totara_notification\placeholder\users;
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
use totara_notification\schedule\schedule_on_event;
use totara_program\totara_notification\placeholder\assignment;
use totara_program\totara_notification\placeholder\program;
use totara_notification\recipient\manager;
use totara_notification\recipient\subject;

class completed extends notifiable_event_resolver implements scheduled_event_resolver, permission_resolver, audit_resolver {

    public static function get_notification_title(): string {
        return get_string('notification_completed_resolver_title', 'totara_program');
    }

    public static function get_notification_available_recipients(): array {
        return [
            subject::class,
            manager::class,
        ];
    }

    public static function get_notification_available_schedules(): array {
        return [
            schedule_on_event::class,
            schedule_after_event::class,
        ];
    }

    public static function get_notification_default_delivery_channels(): array {
        return ['email', 'popup'];
    }

    public static function get_notification_available_placeholder_options(): array {
        return [
            placeholder_option::create(
                'program',
                program::class,
                new lang_string('notification_program_placeholder_group', 'totara_program'),
                function (array $event_data): program {
                    return program::from_id($event_data['program_id']);
                }
            ),
            placeholder_option::create(
                'assignment',
                assignment::class,
                new lang_string('notification_assignment_placeholder_group', 'totara_program'),
                function (array $event_data): assignment {
                    return assignment::from_program_id_and_user_id($event_data['program_id'], $event_data['user_id']);
                }
            ),
            placeholder_option::create(
                'subject',
                user::class,
                new lang_string('notification_subject_placeholder_group', 'totara_program'),
                function (array $event_data): user {
                    return user::from_id($event_data['user_id']);
                }
            ),
            placeholder_option::create(
                'managers',
                users::class,
                new lang_string('notification_manager_placeholder_group', 'totara_program'),
                function (array $event_data): users {
                    return users::from_ids(job_assignment::get_all_manager_userids($event_data['user_id']));
                }
            ),
            placeholder_option::create(
                'recipient',
                user::class,
                new lang_string('notification_recipient_placeholder_group', 'totara_program'),
                function (array $unused_event_data, int $target_user_id): user {
                    return user::from_id($target_user_id);
                }
            ),
        ];
    }

    public static function get_scheduled_events(int $min_time, int $max_time): moodle_recordset {
        global $CFG;
        require_once($CFG->dirroot . '/totara/program/program.class.php');

        return builder::table('prog_completion')
            ->select(['programid as program_id', 'userid as user_id', 'timecompleted as time_completed'])
            ->join(['prog', 'p'], 'programid', 'p.id')
            ->where(function (builder $builder) {
                $builder->where('p.certifid', 0)
                    ->or_where_null('p.certifid');
            })
            ->where('status', STATUS_PROGRAM_COMPLETE)
            ->where('coursesetid', 0)
            ->where('timecompleted', '>=', $min_time)
            ->where('timecompleted', '<', $max_time)
            ->get_lazy();
    }

    public static function can_user_manage_notification_preferences(extended_context $context, int $user_id): bool {
        $natural_context = $context->get_context();
        return has_capability('totara/program:configuremessages', $natural_context, $user_id);
    }

    /**
     * @inheritDoc
     */
    public static function can_user_audit_notifications(extended_context $context, int $user_id): bool {
        $natural_context = $context->get_context();
        return has_capability('totara/program:auditmessages', $natural_context, $user_id);
    }

    public function get_extended_context(): extended_context {
        return extended_context::make_with_context(
            context_program::instance($this->event_data['program_id']),
            'totara_program',
            'program',
            $this->event_data['program_id']
        );
    }

    /**
     * @return int
     */
    public function get_fixed_event_time(): int {
        return $this->event_data['time_completed'];
    }

    public static function uses_on_event_queue(): bool {
        // On-event notifications are processed through the event queue by the program observer.
        return true;
    }

    public static function supports_context(extended_context $extended_context): bool {
        $context = $extended_context->get_context();

        if ($extended_context->is_natural_context()) {
            return in_array($context->contextlevel, [CONTEXT_SYSTEM, CONTEXT_COURSECAT, CONTEXT_PROGRAM]);
        }

        return $context->contextlevel === CONTEXT_PROGRAM && $extended_context->get_component() === 'totara_program';
    }

    /**
     * @inheritDoc
     */
    public function get_notification_log_display_string_key_and_params(): array {
        // The resolver title is translated at view time
        $params = ['resolver_title' => ''];

        $user = user::from_id($this->get_event_data()['user_id']);
        $params['user'] = $user->do_get('full_name');

        $program = program::from_id($this->get_event_data()['program_id']);
        $params['program'] = $program->do_get('full_name');

        return [
            'key' => 'notification_log_completed_resolver',
            'component' => 'totara_program',
            'params' => $params,
        ];
    }

}
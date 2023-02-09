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
 * @author David Curry <david.curry@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\totara_notification\resolver;

use coding_exception;
use core\orm\query\builder;
use core\orm\query\subquery;
use core\orm\query\table;
use core_course\totara_notification\placeholder\activity as placeholder_activity;
use core_course\totara_notification\placeholder\course as placeholder_course;
use core_user\totara_notification\placeholder\user;
use core_user\totara_notification\placeholder\users;
use lang_string;
use mod_facetoface\signup\state\{requested, requestedadmin, requestedrole, waitlisted, booked, user_cancelled};
use mod_facetoface\totara_notification\placeholder\event as placeholder_event;
use mod_facetoface\totara_notification\placeholder\signup as placeholder_signup;
use mod_facetoface\totara_notification\recipient\third_party;
use moodle_recordset;
use totara_core\extended_context;
use totara_job\job_assignment;
use totara_notification\placeholder\placeholder_option;
use totara_notification\recipient\subject;
use totara_notification\recipient\manager;
use totara_notification\resolver\abstraction\scheduled_event_resolver;
use totara_notification\resolver\abstraction\additional_criteria_resolver;
use totara_notification\schedule\schedule_after_event;
use totara_notification\schedule\schedule_before_event;
use totara_notification\schedule\schedule_on_event;


class booking_event_start_date extends seminar_resolver_base implements scheduled_event_resolver, additional_criteria_resolver {

    /**
     * Returns the title for this notifiable event, which should be used
     * within the tree table of available notifiable events.
     * @return string
     * @throws coding_exception
     */
    public static function get_notification_title(): string {
        return get_string('notification_booking_event_start_date_title', 'mod_facetoface');
    }

    /**
     * Returns an array of available recipients (metadata) for this event (concrete class).
     *
     * @return array
     */
    public static function get_notification_available_recipients(): array {
        return [
            subject::class,
            manager::class,
            third_party::class,
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
     * @throws coding_exception
     */
    public static function get_scheduled_events(int $min_time, int $max_time): moodle_recordset {
        $site_allows_legacy_notifications = (int) facetoface_site_allows_legacy_notifications();

        $builder = builder::table('facetoface_signups')->as('fss');
        $builder->select([
            'fss.id',
            'fss.sessionid as seminar_event_id',
            'fss.userid as user_id',
            'st.statuscode as status_code'
        ]);

        $builder->where('fss.archived', '=', '0'); // Ignore archived signups.

        // Join and perform some checks on the current status.
        $builder->join(['facetoface_signups_status', 'st'], 'st.signupid', 'fss.id');
        $builder->where('st.superceded', '=', '0');
        // Status now checked as part of additional criteria.

        // Find and join on the module info.
        $builder->join(['facetoface_sessions', 'event'], 'fss.sessionid', 'event.id');
        $builder->join(['facetoface', 'f2f'], 'event.facetoface', 'f2f.id');

        $mod_info = new subquery(
            builder::table('course_modules')->as('cm')
            ->join(['modules', 'm'], 'cm.module', 'm.id')
            ->select([
                'cm.id as cm_id',
                'cm.instance as instance_id'
            ])
            ->where('m.name', '=', 'facetoface')
        );
        $mod_info->as('mod_info');
        $mod_table = new table($mod_info);

        $builder->join($mod_table, function (builder $joining) {
            $joining->where_raw('mod_info.instance_id = f2f.id');
        });

        $builder->add_select('mod_info.cm_id as module_id');
        $builder->add_select('f2f.course as course_id');
        $builder->add_select('f2f.id as seminar_id');

        // Find and join on the earliest date of each event.
        $event_dates = new subquery(
            builder::table('facetoface_sessions_dates')
                ->select([
                    'sessionid as sid',
                    'MIN(timestart) as time_start'
                ])
                ->group_by([
                    'sessionid',
                ])
        );
        $event_dates->as('dates');
        $dates_table = new table($event_dates);

        $builder->join($dates_table, function (builder $joining) {
            $joining->where_raw('dates.sid = fss.sessionid');
        });

        $builder->add_select('dates.time_start');

        // Righto make sure we're limiting the dates.
        $builder->where('dates.time_start', '>=', $min_time);
        $builder->where('dates.time_start', '<', $max_time);
        // Include all seminars if site doesn't allow legacy notifs, otherwise only include seminars set to use CN.
        $builder->where_raw("({$site_allows_legacy_notifications} = 0 OR f2f.legacy_notifications = 0)");

        return $builder->get_lazy();
    }

    /**
     * @inheritDoc
     */
    public function get_fixed_event_time(): int {
        return $this->event_data['time_start'];
    }

    /**
     * Returns the list of available placeholder options.
     * @return placeholder_option[]
     * @throws coding_exception
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
                'event',
                placeholder_event::class,
                new lang_string('notification_placeholder_group_event', 'mod_facetoface'),
                function (array $event_data): placeholder_event {
                    return placeholder_event::from_event_id($event_data['seminar_event_id']);
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
                    return placeholder_activity::from_id($event_data['module_id']);
                }
            ),
            placeholder_option::create(
                'signup',
                placeholder_signup::class,
                new lang_string('notification_placeholder_group_signup', 'mod_facetoface'),
                function (array $event_data): placeholder_signup {
                    return placeholder_signup::from_event_id_and_user_id(
                        $event_data['seminar_event_id'],
                        $event_data['user_id']
                    );
                }
            ),
        ];
    }

    /**
     * This is to check whether the resolver is processed through event queue or not and also it could be override if
     * dev want to skip queueing up.
     *
     * @return bool
     */
    public static function uses_on_event_queue(): bool {
        return false;
    }

    /**
     * Define the additional vue componenent necessary for the extra settings.
     */
    public static function get_additional_criteria_component(): string {
        return 'mod_facetoface/components/notification/BookingStatus';
    }

    /**
     * Verify the returned data is a valid booking state.
     */
    public static function is_valid_additional_criteria(array $additional_criteria, extended_context $extended_context): bool {
        if (!isset($additional_criteria['recipients']) || !is_array($additional_criteria['recipients'])) {
            return false;
        }

        // Define expected booking state codes.
        $expected = [
            "status_booked",
            "status_pending_requests",
            "status_user_cancelled",
            "status_waitlisted"
        ];

        foreach ($additional_criteria['recipients'] as $state_code) {
            if (!in_array($state_code, $expected)) {
                // We've returned something outside expected state codes.
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

        $expected_status_codes = [];
        foreach ($additional_criteria['recipients'] as $state) {
            switch ($state) {
                case "status_booked":
                    $expected_status_codes[] = booked::get_code();
                    break;
                case "status_pending_requests":
                    $expected_status_codes[] = requested::get_code();
                    $expected_status_codes[] = requestedadmin::get_code();
                    $expected_status_codes[] = requestedrole::get_code();
                    break;
                case "status_user_cancelled":
                    $expected_status_codes[] = user_cancelled::get_code();
                    break;
                case "status_waitlisted":
                    $expected_status_codes[] = waitlisted::get_code();
                    break;
            }
        }

        return (in_array($event_data['status_code'], $expected_status_codes));
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

        $event = placeholder_event::from_event_id($this->get_event_data()['seminar_event_id']);
        $params['date'] = $event->do_get('start_timestamp');

        $activity = placeholder_activity::from_id($this->get_event_data()['module_id']);
        $params['activity'] = $activity->do_get('name');

        return [
            'key' => 'notification_log_booking_event_start_date',
            'component' => 'mod_facetoface',
            'params' => $params,
        ];
    }

}

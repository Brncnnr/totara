<?php
/*
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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\totara_notification\resolver;

use core\orm\query\builder;
use core\orm\query\table;
use core_course\totara_notification\placeholder\activity as placeholder_activity;
use core_course\totara_notification\placeholder\course as placeholder_course;
use core_user\totara_notification\placeholder\user;
use lang_string;
use mod_facetoface\signup\state\booked;
use mod_facetoface\signup\state\waitlisted;
use mod_facetoface\totara_notification\placeholder\event as placeholder_event;
use mod_facetoface\totara_notification\recipient\notifiable_roles;
use mod_facetoface\totara_notification\recipient\third_party;
use moodle_recordset;
use totara_notification\placeholder\placeholder_option;
use totara_notification\resolver\abstraction\scheduled_event_resolver;
use totara_notification\schedule\schedule_before_event;
use totara_notification\schedule\schedule_on_event;

global $CFG;
require_once $CFG->dirroot . '/mod/facetoface/notification/lib.php';

class event_under_minimum_bookings extends seminar_resolver_base implements scheduled_event_resolver {

    /**
     * @inheritDoc
     */
    public static function get_notification_title(): string {
        return get_string('notification_event_under_minimum_bookings_title', 'mod_facetoface');
    }

    /**
     * @inheritDoc
     */
    public static function get_notification_available_recipients(): array {
        return [
            notifiable_roles::class,
            third_party::class,
        ];
    }

    /**
     * @inheritDoc
     */
    public static function get_notification_available_schedules(): array {
        return [
            schedule_before_event::class,
            schedule_on_event::class,
        ];
    }

    /**
     * @inheritDoc
     */
    public static function get_scheduled_events(int $min_time, int $max_time): moodle_recordset {
        $statuscodes = [waitlisted::get_code(), booked::get_code()];
        $site_allows_legacy_notifications = (int) facetoface_site_allows_legacy_notifications();

        $sub_query_minstart = builder::table('facetoface_sessions')
            ->as('ss')
            ->select('id')
            ->add_select_raw('MIN(sd.timestart) AS minstart')
            ->join(['facetoface_sessions_dates', 'sd'], 'ss.id', 'sd.sessionid')
            ->group_by('ss.id');

        $sub_query_signups = builder::table('facetoface_signups')
            ->as('su')
            ->select('sessionid')
            ->add_select_raw('COUNT(su.id) AS num_signup')
            ->join(['facetoface_signups_status', 'st'], 'su.id', 'st.signupid')
            ->where('su.archived', 0)
            ->where('st.superceded', 0)
            ->where('st.statuscode', $statuscodes)
            ->group_by('su.sessionid');

        $sub_query_module = builder::table('course_modules')
            ->as('cm')
            ->join(['modules', 'm'], 'cm.module', 'm.id')
            ->select([
                'cm.id AS cm_id',
                'cm.instance AS instance_id',
            ])
            ->where('m.name', '=', 'facetoface');

        return builder::table('facetoface_sessions')
            ->as('event')
            ->select([
                'event.id AS seminar_event_id',
                'cm.cm_id AS module_id',
                'f2f.course AS course_id',
                'f2f.id AS seminar_id',
                'dates.minstart AS time_start',
            ])
            ->join(['facetoface', 'f2f'], 'event.facetoface', 'f2f.id')
            ->join((new table($sub_query_minstart))->as('dates'), 'event.id', 'dates.id')
            ->left_join((new table($sub_query_signups))->as('signups'), 'event.id', 'signups.sessionid')
            ->join((new table($sub_query_module))->as('cm'), 'f2f.id', 'cm.instance_id')
            ->where('event.mincapacity', '>', 0)
            ->where('event.cancelledstatus', '=', 0)
            ->where('dates.minstart', '>=', $min_time)
            ->where('dates.minstart', '<', $max_time)
            ->where_raw('(signups.num_signup IS NULL OR event.mincapacity > signups.num_signup)')
            // Include all seminars if site doesn't allow legacy notifs, otherwise only include seminars set to use CN.
            ->where_raw("({$site_allows_legacy_notifications} = 0 OR f2f.legacy_notifications = 0)")
            ->get_lazy();
    }

    /**
     * @return int
     */
    public function get_fixed_event_time(): int {
        return $this->event_data['time_start'];
    }

    /**
     * @inheritDoc
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
                'event',
                placeholder_event::class,
                new lang_string('notification_placeholder_group_event', 'mod_facetoface'),
                function (array $event_data): placeholder_event {
                    return placeholder_event::from_event_id($event_data['seminar_event_id']);
                }
            ),
            placeholder_option::create(
                'activity',
                placeholder_activity::class,
                new lang_string('placeholder_group_course_module'),
                function (array $event_data): placeholder_activity {
                    return placeholder_activity::from_id($event_data['module_id'], null);
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
        ];
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
    public function get_notification_log_display_string_key_and_params(): array {
        // The resolver title is translated at view time
        $params = ['resolver_title' => ''];

        $course = placeholder_course::from_id($this->get_event_data()['course_id']);
        $params['course'] = $course->do_get('full_name');

        $event = placeholder_event::from_event_id($this->get_event_data()['seminar_event_id']);
        $params['date'] = $event->do_get('start_timestamp');

        $activity = placeholder_activity::from_id($this->get_event_data()['module_id']);
        $params['activity'] = $activity->do_get('name');

        return [
            'key' => 'notification_log_event_under_minimum_bookings',
            'component' => 'mod_facetoface',
            'params' => $params,
        ];
    }

}
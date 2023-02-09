<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\query\event;

defined('MOODLE_INTERNAL') || die();

use stdClass;
use coding_exception;
use core\orm\query\builder;
use core\orm\query\sql\query;
use core\orm\query\table;
use mod_facetoface\seminar;
use mod_facetoface\signup\state\attendance_state;
use mod_facetoface\signup\state\booked;
use mod_facetoface\signup\state\declined;
use mod_facetoface\signup\state\event_cancelled;
use mod_facetoface\signup\state\user_cancelled;

/**
 * Helper class to assist query builder.
 */
final class filter_factory {
    /**
     * Return the query of joint sessions and dates.
     *
     * @param stdClass|seminar|int $seminar
     * @return builder
     */
    public static function query_sessions_and_dates($seminar): builder {
        if ($seminar instanceof seminar) {
            $f2fid = $seminar->get_id();
        } else if ($seminar instanceof stdClass) {
            $f2fid = $seminar->id;
        } else {
            $f2fid = (int)$seminar;
        }
        $subquery = builder::table('facetoface_sessions_dates', 'fsd')
            ->select([
                'fsd.sessionid',
                'COUNT(fsd.id) AS cntdates',
                'MIN(fsd.timestart) AS mintimestart',
                'MAX(fsd.timestart) AS maxtimestart',
                'MIN(fsd.timefinish) AS mintimefinish',
                'MAX(fsd.timefinish) AS maxtimefinish'
            ])
            ->group_by('fsd.sessionid');
        return builder::table('facetoface_sessions', 's')
            ->left_join([$subquery, 'm'], 'id', 'sessionid')
            ->where('facetoface', '=', $f2fid);
    }

    /**
     * Return the query of joint sessions and dates.
     *
     * @param int|null $sessionid
     * @param \facetoface_notification $facetoface_notification
     * @return builder
     */
    public static function query_notifications_sessions_and_dates(
        ?int $sessionid,
        \facetoface_notification $facetoface_notification
    ): builder {
        $subquery1 = builder::table('facetoface_sessions_dates', 'fsd')
            ->select([
                'fsd.sessionid',
                'COUNT(fsd.id) AS cntdates',
                'MIN(fsd.timestart) AS mintimestart',
                'MAX(fsd.timestart) AS maxtimestart',
                'MIN(fsd.timefinish) AS mintimefinish',
                'MAX(fsd.timefinish) AS maxtimefinish'
            ])
            ->group_by('fsd.sessionid');

        $subquery2 = builder::table('facetoface_notification_sent', 'ns')
            ->join((new table('user'))->as('usr'), 'usr.id', '=', 'ns.userid')
            ->select('ns.userid')
            ->where('ns.notificationid', '=', $facetoface_notification->id)
            ->where_field('ns.userid','u.id')
            ->where_field('ns.sessionid', 's.id');

        $builder = builder::table('user', 'u')
            ->join((new table('facetoface_signups'))->as('si'), 'si.userid', '=', 'u.id')
            ->join((new table('facetoface_signups_status'))->as('sis'), 'sis.signupid', '=', 'si.id')
            ->join((new table('facetoface_sessions'))->as('s'), 's.id', '=', 'si.sessionid')
            ->join((new table('facetoface'))->as('f'), 'f.id', '=', 's.facetoface')
            ->left_join([$subquery1, 'm'], 's.id', 'sessionid')
            ->select(['*', 's.id AS sessionid'])
            ->where('f.id', '=', $facetoface_notification->facetofaceid)
            ->where('sis.superceded', '=', 0)
            ->where_not_exists($subquery2);

        if ((int)$sessionid > 0) {
            $builder->where('s.id', '=', $sessionid);
        }
        return $builder;
    }

    /**
     * Include future events.
     *
     * @param builder $builder
     * @param integer $time
     * @return builder
     */
    public static function event_future(builder $builder, int $time): builder {
        return self::event_not_cancelled($builder)->where('m.mintimestart', '>', $time);
    }

    /**
     * Include ongoing events.
     *
     * @param builder $builder
     * @param integer $time
     * @return builder
     */
    public static function event_inprogress(builder $builder, int $time): builder {
        return self::event_not_cancelled($builder)->where('m.mintimestart', '<=', $time)->where('m.maxtimefinish', '>', $time);
    }

    /**
     * Include past events.
     *
     * @param builder $builder
     * @param integer $time
     * @return builder
     */
    public static function event_past(builder $builder, int $time): builder {
        return self::event_not_cancelled($builder)->where('m.maxtimefinish', '<=', $time);
    }

    /**
     * Include future events and wait-listed events.
     *
     * @param builder $builder
     * @param integer $time
     * @return builder
     */
    public static function event_upcoming(builder $builder, int $time): builder {
        return self::event_not_cancelled($builder)
            ->where(function (builder $inner) use ($time) {
                return $inner->where('m.mintimestart', '>', $time)->or_where_null('m.cntdates');
            });
    }

    /**
     * Include future events, wait-listed events and ongoing events.
     *
     * @param builder $builder
     * @param integer $time
     * @return builder
     */
    public static function event_not_past_or_cancelled(builder $builder, int $time): builder {
        return self::event_not_cancelled($builder)
            ->where(function (builder $inner) use ($time) {
                return $inner->where('m.maxtimefinish', '>', $time)->or_where_null('m.cntdates');
            });
    }

    /**
     * Include past events and cancelled events.
     *
     * @param builder $builder
     * @param integer $time
     * @return builder
     */
    public static function event_over(builder $builder, int $time): builder {
        return self::event_cancelled($builder)
            ->or_where('m.maxtimefinish', '<=', $time);
    }

    /**
     * Include wait-listed events.
     *
     * @param builder $builder
     * @return builder
     */
    public static function event_waitlisted(builder $builder): builder {
        return self::event_not_cancelled($builder)->where_null('m.cntdates');
    }

    /**
     * Include cancelled events.
     *
     * @param builder $builder
     * @return builder
     */
    public static function event_cancelled(builder $builder): builder {
        return $builder->where('s.cancelledstatus', '!=', 0);
    }

    /**
     * Include NOT cancelled events.
     *
     * @param builder $builder
     * @return builder
     */
    public static function event_not_cancelled(builder $builder): builder {
        return $builder->where('s.cancelledstatus', '=', 0);
    }

    /**
     * Include open sign-up period.
     *
     * @param builder $builder
     * @param integer $time
     * @return builder
     */
    public static function registration_open(builder $builder, int $time): builder {
        return $builder->where(function (builder $inner) use ($time) {
            $inner->where('s.registrationtimestart', '=', 0)->or_where('s.registrationtimestart', '<=', $time);
        })
        ->where(function (builder $inner) use ($time) {
            $inner->where('s.registrationtimefinish', '=', 0)->or_where('s.registrationtimefinish', '>=', $time);
        });
    }

    /**
     * Include signup not archived.
     *
     * @param builder $builder
     * @return builder
     */
    public static function signup_not_archived(builder $builder): builder {
        return $builder->where('su.archived', '=', 0);
    }

    /**
     * Include signup not superseded.
     *
     * @param builder $builder
     * @return builder
     */
    public static function signup_not_superseded(builder $builder): builder {
        return $builder->where('sus.superceded', '=', 0);
    }

    /**
     * Return the subquery of signup table.
     *
     * @return builder
     */
    private static function signup_session_subquery(): builder {
        return builder::table('facetoface_signups', 'su')
            ->where_field('sessionid', 's.id');
    }

    /**
     * Return the subquery of joint signup and signup status.
     *
     * @param string $type Join type
     * @return builder
     */
    private static function signup_status_subquery(string $type = 'inner'): builder {
        return self::signup_session_subquery()
            ->join(['facetoface_signups_status', 'sus'], 'id', '=', 'signupid', $type);
    }

    /**
     * Include signups that are not superseded.
     *
     * @param string $type Join type
     * @return builder
     */
    private static function signup_current_subquery(string $type = 'inner'): builder {
        $builder = self::signup_status_subquery($type);
        self::signup_not_superseded($builder);
        return $builder;
    }

    /**
     * Include events where sign-up matches/does not match any of code.
     *
     * @param boolean $match true to match, false to not match
     * @param integer[] $code array of status code
     * @return builder
     */
    public static function signup_with_status_subquery(bool $match, array $code): builder {
        $operator = $match ? '=' : '!=';
        return self::signup_current_subquery()->where('sus.statuscode', $operator, $code);
    }

    /**
     * Include events where sign-up is available.
     *
     * @return builder
     */
    public static function signup_available_subquery(): builder {
        $declinedorcancelled = [declined::get_code(), user_cancelled::get_code(), event_cancelled::get_code()];
        return self::signup_with_status_subquery(false, $declinedorcancelled);
    }

    /**
     * Include any events that someone has booked.
     *
     * @return builder
     */
    public static function signup_booked_subquery(): builder {
        $bookedstates = attendance_state::get_all_attendance_code_with([booked::class]);
        return self::signup_with_status_subquery(true, $bookedstates);
    }

    /**
     * Include booked events.
     *
     * @param builder $builder
     * @return builder
     */
    public static function event_booked(builder $builder): builder {
        $subquery = self::signup_booked_subquery()
            ->left_join(['user', 'u'], 'id', 'su.userid')
            ->where(function (builder $inner) {
                $inner->where_null('u.deleted')->or_where('u.deleted', 0);
            });
        return $builder->where_exists($subquery);
    }

    /**
     * Validate an operator string.
     *
     * @param string $operator expected one of ['>', '<', '>=', '<=', '=', '!=', '<>']
     * @return boolean
     */
    private static function validate_operator(string $operator): bool {
        return in_array($operator, ['>', '<', '>=', '<=', '=', '!=', '<>']);
    }

    /**
     * Join signups and signups_status.
     *
     * @param builder $builder
     * @param string $alias
     * @param string $type Join type
     * @param callable|null $callback Optional callback that takes a subquery
     * @return builder
     */
    private static function join_active_signup_and_status(builder $builder, string $alias, string $type = 'inner', callable $callback = null): builder {
        $subquery = builder::table('facetoface_signups', 'su')
            ->join(['facetoface_signups_status', 'sus'], 'id', '=', 'signupid', 'inner')
            ->select('sessionid');
        self::signup_not_superseded($subquery);
        if ($callback !== null) {
            $callback($subquery);
        }
        return $builder->join([$subquery, $alias], 'id', '=', 'sessionid', $type);
    }

    /**
     * Include minimum or maximum booking capacity.
     *
     * @param builder $builder
     * @param string $operator [signupcount] operator [capacity]
     * @param string $type 'min' or 'max'
     * @return builder
     * @throws coding_exception
     */
    public static function booking_capacity(builder $builder, string $operator, string $type): builder {
        if (!self::validate_operator($operator)) {
            throw new coding_exception('Invalid operator passed: '.$operator);
        }
        if ($type !== 'min' && $type !== 'max') {
            throw new coding_exception('Invalid type passed: '.$type);
        }
        $alias = 'bc'.$type;
        $capacity = $type === 'min' ? 'mincapacity' : 'capacity';
        return self::join_active_signup_and_status($builder, $alias, 'left', function (builder $subquery) {
            $bookedstates = attendance_state::get_all_attendance_code_with([booked::class]);
            $subquery
                ->where_in('sus.statuscode', $bookedstates)
                ->add_select('COUNT(sessionid) AS cntsignup')
                ->group_by('sessionid');
        })->where_raw("COALESCE({$alias}.cntsignup, 0) {$operator} s.{$capacity}");
    }

    /**
     * Include events booked by a user.
     *
     * @param builder $builder
     * @param int|null $userid 0 or null as current user
     * @return builder
     */
    public static function event_user_signup_available(builder $builder, ?int $userid): builder {
        global $USER;
        if (empty($userid)) {
            $userid = $USER->id;
        }
        $subquery = self::signup_available_subquery()
            ->select('su.sessionid')
            ->where('su.userid', $userid);
        return $builder->where_not_exists($subquery);
    }

    /**
     * Include events booked by a user.
     *
     * @param builder $builder
     * @param int|null $userid 0 or null as current user
     * @return builder
     */
    public static function event_user_booked(builder $builder, ?int $userid): builder {
        global $USER;
        if (empty($userid)) {
            $userid = $USER->id;
        }
        $subquery = self::signup_booked_subquery()
            ->select('su.sessionid')
            ->where('su.userid', $userid);
        return $builder->where_exists($subquery);
    }

    /**
     * Include events with specific signup status.
     *
     * @param builder $builder
     * @param integer|null $userid 0 or null as current user
     * @param array $code array of status code
     * @return builder
     */
    public static function event_user_signup_with(builder $builder, ?int $userid, array $code): builder {
        global $USER;
        if (empty($userid)) {
            $userid = $USER->id;
        }
        $subquery = self::signup_with_status_subquery(true, $code)
            ->select('su.sessionid')
            ->where('su.userid', $userid);
        return $builder->where_exists($subquery);
    }

    /**
     * Include event attendance status.
     *
     * @param builder $builder
     * @param integer $time Current time stamp
     * @param boolean $allsaved Set true for 'all saved'
     * @return void
     */
    public static function event_attendance(builder $builder, int $time, bool $allsaved): void {
        // Subquery comprising "is an event attendance gate open?"
        $subquerytime = builder::table('facetoface', 'f2f')
            ->select('id')
            ->where_field('f2f.id', 's.facetoface')
            ->where(function (builder $mediator) use ($time) {
                $mediator->where(function (builder $inner) {
                    $inner->where('f2f.attendancetime', seminar::EVENT_ATTENDANCE_UNRESTRICTED)
                        ->where_exists(builder::table('facetoface_sessions_dates', 'sd')->where_field('sd.sessionid', 's.id'));
                })
                ->or_where(function (builder $inner) use ($time) {
                    $inner->where('f2f.attendancetime', seminar::EVENT_ATTENDANCE_FIRST_SESSION_START)
                        ->where('m.mintimestart', '<=', $time);
                })
                ->or_where(function (builder $inner) use ($time) {
                    $inner->where('f2f.attendancetime', seminar::EVENT_ATTENDANCE_LAST_SESSION_END)
                        ->where('m.maxtimefinish', '<=', $time);
                })
                ->or_where(function (builder $inner) use ($time) {
                    $inner->where('f2f.attendancetime', seminar::EVENT_ATTENDANCE_LAST_SESSION_START)
                        ->where('m.maxtimestart', '<=', $time);
                });
            });
        $builder->where_exists($subquerytime);

        // Subquery comprising "has event attendance not been taken?"
        $subqueryattendance = self::signup_current_subquery('left')
            ->where(function (builder $inner) {
                $inner->where_null('sus.statuscode')
                    ->or_where('sus.statuscode', 0)
                    ->or_where('sus.statuscode', booked::get_code());
            });

        if ($allsaved) {
            $builder->where_not_exists($subqueryattendance);
        } else {
            $builder->where_exists($subqueryattendance);
        }
    }

    /**
     * Include session attendance status.
     *
     * @param builder $builder
     * @param integer $time Current time stamp
     * @param boolean $allsaved Set true for 'all saved'
     * @return void
     */
    public static function session_attendance(builder $builder, int $time, bool $allsaved): void {
        // Subquery for "Is a session attendance gate open?"
        $subquerytime = builder::table('facetoface', 'f2f')
            ->select('id')
            ->where_field('f2f.id', 's.facetoface')
            ->where('f2f.sessionattendance', '!=', seminar::SESSION_ATTENDANCE_DISABLED)
            ->where(function (builder $mediator) use ($time) {
                $mediator->where(function (builder $inner) {
                    $inner->where('f2f.sessionattendance', seminar::SESSION_ATTENDANCE_UNRESTRICTED)
                        ->where_exists(builder::table('facetoface_sessions_dates', 'sd')->where_field('sd.sessionid', 's.id'));
                })
                ->or_where(function (builder $inner) use ($time) {
                    $inner->where('f2f.sessionattendance', seminar::SESSION_ATTENDANCE_END)
                        ->where('m.mintimefinish', '<=', $time);
                })
                ->or_where(function (builder $inner) use ($time) {
                    $inner->where('f2f.sessionattendance', seminar::SESSION_ATTENDANCE_START)
                        ->where('m.mintimestart', '<=', $time);
                });
            });
        $builder->where_exists($subquerytime);

        // Subquery for "Is there any session attendance that has not been taken?"
        $subqueryattendance = self::signup_current_subquery()
            ->left_join(['facetoface_signups_dates_status', 'suds'], 'id', 'signupid')
            ->where(function (builder $inner) {
                $inner->where_null('suds.attendancecode')
                    ->or_where('suds.attendancecode', 0)
                    ->or_where('suds.attendancecode', booked::get_code());
            });

        if ($allsaved) {
            $builder->where_not_exists($subqueryattendance);
        } else {
            $builder->where_exists($subqueryattendance);
        }
    }

    /**
     * Include events facilitated by the user.
     *
     * @param builder $builder
     * @param integer|null $userid the facilitator user id or 0 to use the current user
     * @return void
     */
    public static function event_facilitate(builder $builder, ?int $userid): void {
        global $USER;
        if (empty($userid)) {
            $userid = $USER->id;
        }
        $subquery = self::signup_current_subquery()
            ->join(['facetoface_sessions_dates', 'sd'], 's.id', 'sessionid')
            ->join(['facetoface_facilitator_dates', 'fad'], 'sd.id', 'fad.sessionsdateid')
            ->join(['facetoface_facilitator', 'fa'], 'fa.id', 'fad.facilitatorid')
            ->where('fa.userid', $userid);
        $builder->where_exists($subquery);
    }
}

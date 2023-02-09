<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface;

use mod_facetoface\signup\state\{attendance_state, booked, waitlisted};

defined('MOODLE_INTERNAL') || die();


/**
 * Class seminar_session_list represents Seminar event sessions date list
 */
final class seminar_session_list implements \Iterator, \Countable {

    use traits\seminar_iterator {
        delete as traitDelete;
    }

    /**
     * Define sorting orders.
     */
    public const SORT_ASC = '0';
    public const SORT_DESC = '1';

    /**
     * Reloading the trackers of those session(s) that either are over and upcoming.
     * This is for test purpose and also ability to re-calculate the sessions
     * @return seminar_session_list
     */
    public function reload(): seminar_session_list {
        debugging('The method ' . __METHOD__ . '() has been deprecated and should not be called anymore.', DEBUG_DEVELOPER);
        return $this;
    }

    /**
     * Add seminar session to item list
     * @param seminar_session $item
     * @return void
     */
    public function add(seminar_session $item): void {
        $id = $item->get_id();
        $this->items[$id] = $item;
    }

    /**
     * Create list of seminar sessions from seminar event
     * @param seminar_event $seminarevent
     * @return seminar_session_list
     */
    public static function from_seminar_event(seminar_event $seminarevent): seminar_session_list {
        global $DB;
        $sessionrecords = $DB->get_records('facetoface_sessions_dates', ['sessionid' => $seminarevent->get_id()], 'timestart ASC');
        return self::from_records($sessionrecords);
    }

    /**
     * Create list of seminar sessions from an array of objects
     *
     * @param \stdClass[] $sessionrecords
     * @param boolean $strict Set false to ignore bogus properties
     * @return seminar_session_list
     */
    public static function from_records(array $sessionrecords, bool $strict = true): seminar_session_list {
        $list = new seminar_session_list(); // Create an empty list.
        foreach ($sessionrecords as $sessionrecord) {
            $session = new seminar_session();
            $list->add($session->from_record($sessionrecord, $strict));
        }
        return $list;
    }

    /**
     * Count sessions that will happen in the future.
     *
     * @param int $time
     * @return int
     */
    public function count_upcoming(int $time =0): int {
        return count($this->get_upcoming($time));
    }

    /**
     * Count sessions that are over, based on given time.
     *
     * @param int $time
     * @return int
     */
    public function count_over(int $time = 0): int {
        return count($this->get_over($time));
    }

    /**
     * Get sessions that are over.
     *
     * @param int $time
     * @return seminar_session[]
     */
    public function get_over(int $time = 0): array {
        if (0 >= $time) {
            $time = time();
        }

        $overs = [];

        /** @var seminar_session $session */
        foreach ($this->items as $session) {
            if ($session->is_over($time)) {
                $overs[] = $session;
            }
        }

        return $overs;
    }

    /**
     * Get sessions that will happen in the future.
     *
     * @param int $time
     * @return seminar_session[]
     */
    public function get_upcoming(int $time = 0): array {
        if (0 >= $time) {
            $time = time();
        }

        $upcoming = [];

        /** @var seminar_session $session */
        foreach ($this->items as $session) {
            if ($session->is_upcoming($time)) {
                $upcoming[] = $session;
            }
        }

        return $upcoming;
    }

    /**
     * Return true if ALL the sessions are over.
     *
     * If there are no sessions, then it is most likely to be a waitlist event.
     *
     * @param int $time
     * @return bool
     */
    public function is_everything_over(int $time = 0): bool {
        if ($this->is_empty()) {
            // Wait-listed event.
            return false;
        }

        return $this->count_over($time) === $this->count();
    }

    /**
     * Return true if ANY session is over.
     *
     * If there are no sessions, then it is most likely to be a waitlist event.
     *
     * @param int $time
     * @return bool
     */
    public function is_anything_over(int $time = 0): bool {
        if ($this->is_empty()) {
            // Wait-listed event.
            return false;
        }

        return $this->count_over($time) > 0;
    }

    /**
     * Get the session status summary string.
     *
     * @param bool|null $reload Deprecated. Do NOT set this parameter anymore.
     * @return string
     */
    public function get_summary(bool $reload = null): string {
        if ($reload === true || $reload === false) {
            debugging('Setting $reload has been deprecated and should not be done anymore.', DEBUG_DEVELOPER);
        }

        $time = time();
        $a = new \stdClass();

        $a->total = count($this->items);
        $a->upcoming = $this->count_upcoming($time);
        $a->over = $this->count_over($time);

        return get_string('eventsummary', 'mod_facetoface', $a);
    }

    /**
     * Return the last session of an event.
     *
     * @return seminar_session|null
     */
    public function get_last(): ?seminar_session {
        /** @var seminar_session|null */
        $found = null;
        /** @var seminar_session $item */
        foreach ($this->items as $id => $item) {
            if (!$found || $item->get_timefinish() > $found->get_timefinish()) {
                $found = $item;
            }
        }
        return $found;
    }

    /**
     * Return the first session of an event.
     *
     * @return seminar_session|null
     */
    public function get_first(): ?seminar_session {
        /** @var seminar_session|null */
        $found = null;
        /** @var seminar_session $item */
        foreach ($this->items as $id => $item) {
            if (!$found || $item->get_timefinish() < $found->get_timefinish()) {
                $found = $item;
            }
        }
        return $found;
    }

    /**
     * Returning an array of dummy class holding data of seminar_session. If $preservekeys is passed with true then
     * the session's id associated as array key.
     *
     * @param bool $preservekeys
     * @return \stdClass[]
     */
    public function to_records(bool $preservekeys = true): array {
        $data = [];

        /** @var seminar_session $item */
        foreach ($this->items as $item) {
            $data[$item->get_id()] = $item->to_record();
        }

        // If you don't want the item ID as the key.
        if (!$preservekeys) {
            return array_values($data);
        }

        return $data;
    }

    /**
     * Delete sessions and their associated assets.
     * @return void
     */
    public function delete(): void {
        $this->traitDelete();
    }

    /**
     * A function to check if the dates in a session have been changed.
     *
     * @param array $olddates   The dates the session used to be set to
     * @param array $newdates   The dates the session is now set to
     *
     * @return boolean
     */
    public static function dates_check(array $olddates, array $newdates): bool {
        // Dates have changed if the amount of dates has changed.
        if (count($olddates) != count($newdates)) {
            return true;
        }

        // Anonymous function used to compare dates to be sorted in an identical way.
        $cmpfunction = function ($date1, $date2) {
            // Order by session start time.
            if (($order = strcmp($date1->timestart, $date2->timestart)) === 0) {
                // If start time is the same, ordering by finishtime.
                if (($order = strcmp($date1->timefinish, $date2->timefinish)) === 0) {
                    // Just to be on a safe side, if the start and finish dates are the same let's also order by timezone.
                    $order = strcmp($date1->sessiontimezone, $date2->sessiontimezone);
                }
            }

            return $order;
        };

        // Sort the old and new dates in a similar way.
        usort($olddates, $cmpfunction);
        usort($newdates, $cmpfunction);

        $dateschanged = false;

        $old_date_count = count($olddates);
        for ($i = 0; $i < $old_date_count; $i++) {
            if (self::has_date_changed($olddates[$i], $newdates[$i])) {
                $dateschanged = true;
                break;
            }
        }

        return $dateschanged;
    }

    /**
     * Check for one old date/new date object pair if anything relevant has changed.
     *
     * @param \stdClass $old_date
     * @param \stdClass $new_date
     * @return bool
     */
    public static function has_date_changed(\stdClass $old_date, \stdClass $new_date): bool {
        return (
            $old_date->timestart != $new_date->timestart
                || $old_date->timefinish != $new_date->timefinish
                || $old_date->sessiontimezone != $new_date->sessiontimezone
                // NOTE: roomids and facilitatorids must be sorted in the same order.
                || (isset($old_date->roomids, $new_date->roomids) && $old_date->roomids != $new_date->roomids)
                || (isset($old_date->facilitatorids, $new_date->facilitatorids) && $old_date->facilitatorids != $new_date->facilitatorids)
        );
    }

    /**
     * Sort the list of items based on field and order provided.
     *
     * @param string $field
     * @param string $order
     * @return seminar_session_list
     */
    public function sort(string $field, string $order = seminar_session_list::SORT_ASC): seminar_session_list {
        $function = 'get_' . $field;
        if (!method_exists(\mod_facetoface\seminar_session::class, $function)) {
            throw new \coding_exception("Function $function does not exist in seminar_session");
        }

        // Used uasort, because we need to maintain the index of $items (sessions id).
        uasort(
            $this->items,
            function (seminar_session $a, seminar_session $b) use ($function, $order) {
                if ($order == seminar_session_list::SORT_ASC) {
                    $result = $a->{$function}() > $b->{$function}();
                } else {
                    $result = $a->{$function}() < $b->{$function}();
                }
                return $result ? 1 : -1;
            }
        );

        return $this;
    }

    /**
     * Given an array of date object (where it is must include $timestart and $timefinish), then it this factory method will be able to retrieve the list of session dates that
     * the passed in $userid is having and conflicting with the $dates. Each date object in $dates must have the keys specified as bellow
     * + timestart: int => time start of a new session, or old session
     * + timefinish: int => time finish of new session, or old session
     *
     * @param \stdClass[]        $dates
     * @param int                $userid
     * @param seminar_event|null $excludeseminarevent
     * @param string[]           $additionalstatuses
     *
     * @return seminar_session_list
     */
    public static function from_user_conflicts_with_dates(int $userid, array $dates, seminar_event $excludeseminarevent = null,
                                                         array $additionalstatuses = []): seminar_session_list {
        global $DB;
        $list = new static();

        if (empty($dates)) {
            // No times were given, so we can't supply sessions within any times. Just return empty list.
            return $list;
        }

        $sql = "
            SELECT d.*
            FROM {facetoface_sessions_dates} d
            INNER JOIN {facetoface_sessions} s
            ON s.id = d.sessionid

            LEFT JOIN {facetoface_signups} su
            ON su.sessionid = s.id AND su.userid = :userid1

            LEFT JOIN {facetoface_signups_status} ss
            ON ss.signupid = su.id AND ss.superceded <> 1

            LEFT JOIN {facetoface_session_roles} sr
            ON sr.sessionid = s.id AND sr.userid = :userid2

            WHERE s.cancelledstatus = 0
        ";

        $params = [
            'userid1' => $userid,
            'userid2' => $userid
        ];

        // Building status sql for the signup/users.
        $additional = array_merge([booked::class, waitlisted::class], $additionalstatuses);
        $statuses = attendance_state::get_all_attendance_code_with($additional);

        [$asql, $aparams] = $DB->get_in_or_equal($statuses, SQL_PARAMS_NAMED);

        $sql .= " AND ((ss.id IS NOT NULL AND ss.statuscode {$asql}) OR sr.id IS NOT NULL)";
        $params = array_merge($params, $aparams);

        // Building times conflicting sql.
        $timesqls = [];
        $i = 0;

        foreach ($dates as $date) {
            $timesqls[] = "(d.timefinish > :timestart{$i} AND d.timestart < :timefinish{$i})";
            $params["timestart{$i}"] = $date->timestart;
            $params["timefinish{$i}"] = $date->timefinish;
            $i++;
        }

        $sql .= " AND (" . implode(" OR ", $timesqls) . ")";

        // Finally, building the excluded seminar_event sql pharse here.
        if (null !== $excludeseminarevent) {
            $sql .= " AND s.id <> :sessionid";
            $params['sessionid'] = $excludeseminarevent->get_id();
        }

        $records = $DB->get_records_sql($sql, $params);
        if (!$records) {
            return $list;
        }

        foreach ($records as $record) {
            $session = new seminar_session();
            $session->from_record($record);
            $list->add($session);
        }

        return $list;
    }

    /**
     * Given the list of sessions, this factory will try to pre-build the data for $dates, then invoke
     * static::from_user_conflicts with_date function.
     *
     * @param int                  $userid
     * @param seminar_session_list $sessions
     * @param seminar_event|null   $excludeseminarevent
     * @param array                $additionalstatuses
     *
     * @return seminar_session_list
     */
    public static function from_user_conflicts_with_sessions(int $userid, seminar_session_list $sessions,
                                                             seminar_event $excludeseminarevent = null,
                                                             array $additionalstatuses = []): seminar_session_list {
        $dates = [];

        /** @var seminar_session $session */
        foreach ($sessions as $session) {
            $date = new \stdClass();
            $date->timestart = $session->get_timestart();
            $date->timefinish = $session->get_timefinish();

            $dates[] = $date;
        }

        return self::from_user_conflicts_with_dates($userid, $dates, $excludeseminarevent, $additionalstatuses);
    }
}
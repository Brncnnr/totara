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

namespace mod_facetoface\dashboard\filters;

use context;
use mod_facetoface\seminar;
use mod_facetoface\event_time;
use mod_facetoface\query\event\query;
use mod_facetoface\query\event\filter\event_time_filter as query_event_time_filter;

defined('MOODLE_INTERNAL') || die();

/**
 * Provide the event timeline filter.
 */
final class event_time_filter implements filter {
    const PARAM_NAME = 'eventtime';
    const DEFAULT_VALUE = 0;

    /** @var integer */
    private $value;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->value = static::DEFAULT_VALUE;
    }

    /**
     * @inheritDoc
     */
    public function load_param(callable $param_loader): void {
        $this->value = $param_loader(static::PARAM_NAME, static::DEFAULT_VALUE, PARAM_INT);
    }

    /**
     * @inheritDoc
     */
    public function modify_query(query $query): void {
        $query->with_filter(new query_event_time_filter($this->value));
    }

    /**
     * @inheritDoc
     */
    public static function get_class(): string {
        return 'eventtime';
    }

    /**
     * @inheritDoc
     */
    public static function get_label(): string {
        return get_string('filterbyeventtime', 'mod_facetoface');
    }

    /**
     * @inheritDoc
     */
    public static function get_options(seminar $seminar): array {
        return [
            event_time::ALL => get_string('filter_event:all', 'mod_facetoface'),
            event_time::FUTURE => get_string('filter_event:future', 'mod_facetoface'),
            event_time::INPROGRESS => get_string('filter_event:inprogress', 'mod_facetoface'),
            event_time::PAST => get_string('filter_event:past', 'mod_facetoface'),
            event_time::WAITLISTED => get_string('filter_event:waitlisted', 'mod_facetoface'),
            event_time::CANCELLED => get_string('filter_event:cancelled', 'mod_facetoface'),
        ];
    }

    /**
     * @inheritDoc
     */
    public static function get_param_name(): string {
        return static::PARAM_NAME;
    }

    /**
     * @inheritDoc
     */
    public function get_param_value() {
        return $this->value;
    }

    /**
     * @inheritDoc
     */
    public function set_param_value($value): void {
        $this->value = (int)$value;
    }

    /**
     * @inheritDoc
     */
    public static function get_default_value() {
        return static::DEFAULT_VALUE;
    }

    /**
     * @inheritDoc
     */
    public static function is_visible(seminar $seminar, context $context, ?int $userid): bool {
        global $DB;
        /** @var \moodle_database $DB */

        $time = time();

        $sql =
        'SELECT COUNT(
            DISTINCT CASE
                WHEN (:t1 < m.mintimestart AND s.cancelledstatus = 0) THEN :et1
                WHEN ((m.mintimestart <= :t2 AND :t3 < m.maxtimefinish) AND s.cancelledstatus = 0) THEN :et2
                WHEN (m.maxtimefinish  <= :t4 AND s.cancelledstatus = 0) THEN :et3
                WHEN (m.cntdates IS NULL AND s.cancelledstatus = 0) THEN :et4
                WHEN (s.cancelledstatus != 0) THEN :et5
            END)
         FROM {facetoface_sessions} s
         LEFT JOIN (
            SELECT fsd.sessionid,
            COUNT(fsd.id) AS cntdates,
            MIN(fsd.timestart) AS mintimestart,
            MAX(fsd.timefinish) AS maxtimefinish
            FROM {facetoface_sessions_dates} fsd
            GROUP BY fsd.sessionid
         ) m ON m.sessionid = s.id
         WHERE s.facetoface = :f2f';

        $params = [
            'et1' => event_time::FUTURE,
            'et2' => event_time::INPROGRESS,
            'et3' => event_time::PAST,
            'et4' => event_time::WAITLISTED,
            'et5' => event_time::CANCELLED,
            't1' => $time,
            't2' => $time,
            't3' => $time,
            't4' => $time,
            'f2f' => $seminar->get_id(),
        ];

        $count = $DB->get_field_sql($sql, $params) ?? 0;

        // Hide if all events have the same event_time status.
        if ($count <= 1) {
            return false;
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public static function get_filterbar_option(int $option) {
        if ($option === filter::OPTION_ORDER) {
            // Sorting key.
            return 10;
        }
        return null;
    }
}

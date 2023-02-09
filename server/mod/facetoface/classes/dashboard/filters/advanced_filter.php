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
use mod_facetoface\query\event\query;
use mod_facetoface\query\event\filter\advanced_filter as query_advanced_filter;

defined('MOODLE_INTERNAL') || die();

/**
 * Provide the "advanced" filter.
 */
final class advanced_filter implements filter {
    const PARAM_NAME = 'advanced';
    const DEFAULT_VALUE = query_advanced_filter::ALL;

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
        $query->with_filter(new query_advanced_filter($this->value));
    }

    /**
     * @inheritDoc
     */
    public static function get_class(): string {
        return 'advanced';
    }

    /**
     * @inheritDoc
     */
    public static function get_label(): string {
        return get_string('filterbyadvanced', 'mod_facetoface');
    }

    /**
     * @inheritDoc
     */
    public static function get_options(seminar $seminar): array {
        $select = [
            query_advanced_filter::ALL => get_string('filter_advanced:all', 'mod_facetoface'),
            query_advanced_filter::ATTENDANCE_OPEN => get_string('filter_advanced:attendanceopen', 'mod_facetoface'),
            query_advanced_filter::ATTENDANCE_SAVED => get_string('filter_advanced:attendancesaved', 'mod_facetoface'),
            query_advanced_filter::OVERBOOKED => get_string('filter_advanced:overbooked', 'mod_facetoface'),
            query_advanced_filter::UNDERBOOKED => get_string('filter_advanced:underbooked', 'mod_facetoface'),
        ];
        return $select;
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
        // Hide from non-editor users.
        return has_any_capability([
            'mod/facetoface:viewattendees', 'mod/facetoface:editevents',
            'mod/facetoface:addattendees', 'mod/facetoface:addattendees',
            'mod/facetoface:takeattendance'
        ], $context, $userid);
    }

    /**
     * @inheritDoc
     */
    public static function get_filterbar_option(int $option) {
        if ($option === filter::OPTION_ORDER) {
            // Sorting key.
            return 40;
        }
        return null;
    }
}

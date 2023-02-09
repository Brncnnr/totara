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
use mod_facetoface\query\event\filter\booking_filter as query_booking_filter;

defined('MOODLE_INTERNAL') || die();

/**
 * Provide the booking status filter.
 */
final class booking_filter implements filter {
    const PARAM_NAME = 'booking';
    const DEFAULT_VALUE = query_booking_filter::ALL;

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
        $query->with_filter(new query_booking_filter($this->value));
    }

    /**
     * @inheritDoc
     */
    public static function get_class(): string {
        return 'book';
    }

    /**
     * @inheritDoc
     */
    public static function get_label(): string {
        return get_string('filterbybooking', 'mod_facetoface');
    }

    /**
     * @inheritDoc
     */
    public static function get_options(seminar $seminar): array {
        $select = [
            query_booking_filter::ALL => get_string('filter_booking:all', 'mod_facetoface'),
            query_booking_filter::OPEN => get_string('filter_booking:open', 'mod_facetoface'),
            query_booking_filter::BOOKED => get_string('filter_booking:booked', 'mod_facetoface'),
            query_booking_filter::WAITLISTED => get_string('filter_booking:waitlisted', 'mod_facetoface'),
            query_booking_filter::REQUESTED => get_string('filter_booking:requested', 'mod_facetoface'),
            query_booking_filter::CANCELLED => get_string('filter_booking:cancelled', 'mod_facetoface'),
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
        return true;
    }

    /**
     * @inheritDoc
     */
    public static function get_filterbar_option(int $option) {
        if ($option === filter::OPTION_ORDER) {
            // Sorting key.
            return 20;
        }
        return null;
    }
}

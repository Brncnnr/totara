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
use mod_facetoface\query\event\filter\room_filter as query_room_filter;

defined('MOODLE_INTERNAL') || die();

/**
 * Provide the room name filter.
 */
final class room_filter implements filter {
    const PARAM_NAME = 'roomid';
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
        $query->with_filter(new query_room_filter($this->value));
    }

    /**
     * @inheritDoc
     */
    public static function get_class(): string {
        return 'room';
    }

    /**
     * @inheritDoc
     */
    public static function get_label(): string {
        return get_string('filterbyroom', 'mod_facetoface');
    }

    /**
     * @inheritDoc
     */
    public static function get_options(seminar $seminar): array {
        $roomselect = [
            static::DEFAULT_VALUE => get_string('allrooms', 'mod_facetoface')
        ];
        $rooms = \mod_facetoface\room_list::get_seminar_rooms($seminar->get_id());
        // Here used to be some fancy code that deal with missing room names,
        // that magic cannot be done easily any more, allow selection of named rooms only here.
        foreach ($rooms as $room) {
            $roomname = format_string((string) $room);
            if ($roomname === '') {
                continue;
            }
            $roomselect[$room->get_id()] = $roomname;
        }

        if (count($roomselect) > 2) {
            return $roomselect;
        }

        return [];
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
            return 30;
        }
        if ($option === filter::OPTION_TOOLTIPS) {
            // The room filter needs tooltips to see a truncated, very long room name.
            return true;
        }
        return null;
    }
}

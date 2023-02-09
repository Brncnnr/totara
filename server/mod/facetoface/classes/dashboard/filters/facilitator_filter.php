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
use mod_facetoface\query\event\filter\facilitator_filter as query_facilitator_filter;

defined('MOODLE_INTERNAL') || die();

/**
 * Provide the facilitator name filter.
 */
final class facilitator_filter implements filter {
    const PARAM_NAME = 'facilitatorid';
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
        $query->with_filter(new query_facilitator_filter($this->value));
    }

    /**
     * @inheritDoc
     */
    public static function get_class(): string {
        return 'facilitator';
    }

    /**
     * @inheritDoc
     */
    public static function get_label(): string {
        return get_string('filterbyfacilitator', 'mod_facetoface');
    }

    /**
     * @inheritDoc
     */
    public static function get_options(seminar $seminar): array {
        $facilitatorselect = [
            static::DEFAULT_VALUE => get_string('allfacilitators', 'mod_facetoface')
        ];

        $facilitators = \mod_facetoface\facilitator_list::get_distinct_users_from_seminar($seminar->get_id());
        // Here used to be some fancy code that deal with missing facilitator names,
        // that magic cannot be done easily any more, allow selection of named facilitators only here.
        foreach ($facilitators as $facilitator) {
            /** @var \mod_facetoface\facilitator_user $facilitator */
            $facilitatorname = $facilitator->get_display_name();
            if ($facilitatorname === '') {
                continue;
            }
            $facilitatorselect[$facilitator->get_id()] = $facilitatorname;
        }

        if (count($facilitatorselect) > 2) {
            return $facilitatorselect;
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
            return 35;
        }
        if ($option === filter::OPTION_TOOLTIPS) {
            // The facilitator filter needs tooltips to see a truncated, very long facilitator name.
            return true;
        }
        return null;
    }
}

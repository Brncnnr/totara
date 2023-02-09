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
use mod_facetoface\query\event\filter\past_event_filter as query_past_event_filter;

defined('MOODLE_INTERNAL') || die();

/**
 * Provide the ability to cut off past events by time period.
 * NOTE: this filter is not visible on the filter bar.
 */
final class past_event_filter implements filter {
    const PARAM_NAME = 'allpreviousevents';
    const DEFAULT_VALUE = false;

    /** @var boolean */
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
        $this->value = (bool)$param_loader(static::PARAM_NAME, static::DEFAULT_VALUE, PARAM_BOOL);
    }

    /**
     * @inheritDoc
     */
    public function modify_query(query $query): void {
        if ($this->value) {
            $timeperiod = 0;
        } else {
            $timeperiod = (int)get_config(null, 'facetoface_previouseventstimeperiod');
        }
        $query->with_filter(new query_past_event_filter($timeperiod));
    }

    /**
     * @inheritDoc
     */
    public static function get_class(): string {
        return '';
    }

    /**
     * @inheritDoc
     */
    public static function get_label(): string {
        return '';
    }

    /**
     * @inheritDoc
     */
    public static function get_options(seminar $seminar): array {
        // We still need an array of possible values even though this is not displayed in the filter bar
        return [
            (int)false => '',
            (int)true => ''
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
        return (int)$this->value;
    }

    /**
     * @inheritDoc
     */
    public function set_param_value($value): void {
        $this->value = (bool)$value;
    }

    /**
     * @inheritDoc
     */
    public static function get_default_value() {
        return (int)static::DEFAULT_VALUE;
    }

    /**
     * @inheritDoc
     */
    public static function is_visible(seminar $seminar, context $context, ?int $userid): bool {
        return false;
    }

    /**
     * @inheritDoc
     */
    public static function get_filterbar_option(int $option) {
        return null;
    }
}

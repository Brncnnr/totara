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

defined('MOODLE_INTERNAL') || die();

/**
 * A filter interface for seminar event dashboard.
 */
interface filter {
    /**
     * Constants for get_filterbar_option().
     */
    const OPTION_ORDER = 0;
    const OPTION_TOOLTIPS = 1;
    const OPTION_PARAMTYPE = 2;

    /**
     * Load the filter value.
     *
     * @param callable $param_loader    An external parameter loader function that takes the same parameter as optional_param()
     * @return void
     */
    public function load_param(callable $param_loader): void;

    /**
     * Update $query object.
     *
     * @param query $query
     * @return void
     */
    public function modify_query(query $query): void;

    /**
     * Get part of a CSS class name used by seminarevent_filterbar.
     *
     * @return string
     */
    public static function get_class(): string;

    /**
     * Get the label text that will be attached to the filter drop-down list.
     *
     * @return string
     */
    public static function get_label(): string;

    /**
     * The the list of possible filter values and its label text.
     *
     * @param seminar $seminar
     * @return array containing [ value => label ]
     */
    public static function get_options(seminar $seminar): array;

    /**
     * Get the name attribute.
     *
     * @return string
     */
    public static function get_param_name(): string;

    /**
     * Get the filter value.
     *
     * @return string|integer
     */
    public function get_param_value();

    /**
     * Set the filter value.
     *
     * @param string|integer $value
     * @return void
     */
    public function set_param_value($value): void;

    /**
     * Get the default value of this filter.
     *
     * @return mixed
     */
    public static function get_default_value();

    /**
     * See if the filter can be visible to the current context.
     *
     * @param seminar $seminar
     * @param context $context
     * @param integer|null $userid  A user ID or null to use the current user
     * @return boolean
     */
    public static function is_visible(seminar $seminar, context $context, ?int $userid): bool;

    /**
     * Get an optional value for a filter bar.
     *
     * @param integer $option One of OPTION_xxx constants
     * @return mixed Depends on $option
    *       OPTION_ORDER: An integer value used as a sorting key of the filter
     *      OPTION_TOOLTIPS: A boolean value indicating whether the tooltips is displayed on the filter
     */
    public static function get_filterbar_option(int $option);
}

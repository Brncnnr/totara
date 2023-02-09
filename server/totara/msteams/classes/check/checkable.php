<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @package totara_msteams
 */

namespace totara_msteams\check;

defined('MOODLE_INTERNAL') || die;

use moodle_url;

/**
 * checkable interface
 */
interface checkable {
    /**
     * Return the human readable name of this check.
     *
     * @return string
     */
    public function get_name(): string;

    /**
     * Return the config name of this check.
     *
     * @return string|null
     */
    public function get_config_name(): ?string;

    /**
     * Return the help link to the documentation about this check.
     *
     * @return moodle_url|null
     */
    public function get_helplink(): ?moodle_url;

    /**
     * Check.
     * @return int One of the following constants in the status class.
     *              - PASS: Check passed
     *              - FAILED: Check failed
     *              - SKIPPED: Not applicable in current configuration
     */
    public function check(): int;

    /**
     * Return the reason why this check fails.
     * This function returns valid information only after the check() function does not return PASS.
     *
     * @return string
     */
    public function get_report(): string;
}

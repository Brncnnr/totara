<?php
/**
 * This file is part of Totara Core
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_contentmarketplace
 */
namespace totara_contentmarketplace\testing\mock;

use totara_contentmarketplace\sync\sync_action;

/**
 * Mock sync action class.
 */
class mock_sync_action extends sync_action {
    /**
     * @var bool
     */
    private static $invoked = false;

    /**
     * This variable will be default to false.
     * @var bool
     */
    private static $skipped = false;

    /**
     * @param bool $value
     * @return void
     */
    public static function set_is_skipped(bool $value): void {
        self::$skipped = $value;
    }

    /**
     * @return bool
     */
    public static function get_invoked(): bool {
        return self::$invoked;
    }

    /**
     * @return void
     */
    public static function clear(): void {
        self::$invoked = false;
        self::$skipped = false;
    }

    /**
     * @return void
     */
    public function invoke(): void {
        self::$invoked = true;
    }

    /**
     * @return bool
     */
    public function is_skipped(): bool {
        return self::$skipped;
    }
}
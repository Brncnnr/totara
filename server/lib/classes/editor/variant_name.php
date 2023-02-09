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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package core
 */
namespace core\editor;

use coding_exception;

/**
 * A metadata class that is used to store the editor's variant name.
 * These variant names are defined at core, but to each different editor plugin,
 * it can have a different meaning.
 *
 * Note: if you want to add a new variant name, please add a new constant which is a part of this class,
 * and also update the function {@see \core\editor\variant_name::is_valid} to sync with the newly created
 * constant function.
 */
class variant_name {
    public const FULL = 'full';
    public const STANDARD = 'standard';
    public const BASIC = 'basic';
    public const SIMPLE = 'simple';

    // Legacy variants:
    public const DESCRIPTION = 'description';

    /**
     * Prevent construction.
     */
    private function __construct() {
    }

    /**
     * Checking whether it is a valid variant name.
     *
     * @param string $variant_name
     * @return bool
     */
    public static function is_valid(string $variant_name): bool {
        return in_array(
            $variant_name,
            [
                static::FULL,
                static::STANDARD,
                static::BASIC,
                static::SIMPLE,
                static::DESCRIPTION,
            ]
        );
    }

    /**
     * @param string $variant_name
     * @return void
     */
    public static function validate(string $variant_name): void {
        if (!static::is_valid($variant_name)) {
            throw new coding_exception("Invalid variant name '{$variant_name}'");
        }
    }
}
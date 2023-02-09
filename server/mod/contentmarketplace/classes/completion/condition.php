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
 * @package mod_contentmarketplace
 */
namespace mod_contentmarketplace\completion;

use coding_exception;
use core_component;
use totara_contentmarketplace\completion_constants;
use totara_contentmarketplace\plugininfo\contentmarketplace;

/**
 * A class to define constants and any helper functionalities that are related
 * to the constants.
 */
class condition {
    /**
     * A constant to say the completion to mark the activity completed
     * when the content is marked as completed from content provider's side.
     *
     * @var int
     */
    public const CONTENT_MARKETPLACE = completion_constants::COMPLETION_CONDITION_CONTENT_MARKETPLACE;

    /**
     * condition constructor.
     */
    private function __construct() {
        // Preventing this class from instantiation.
    }

    /**
     * Returns the string that associated with the constant {@see condition::CONTENT_MARKETPLACE}.
     * Which is about "Show activity as complete when 'Content provider' conditions have been met".
     *
     * @param string $marketplace_component The full plugin name of a content marketplace sub plugin.
     *                                      For example: contentmarketplace_goone
     * @return string
     */
    public static function get_content_marketplace_conditions_string(string $marketplace_component): string {
        [$unused_plugin_type, $plugin_name] = core_component::normalize_component($marketplace_component);
        if ($unused_plugin_type !== 'contentmarketplace') {
            throw new coding_exception("{$marketplace_component} is not found");
        }

        $plugin_info = contentmarketplace::plugin($plugin_name);

        return get_string(
            'completion_content_provider_description',
            'mod_contentmarketplace',
            $plugin_info->displayname
        );
    }

    /**
     * Checks whether the given $condition_constant is a valid value or  not.
     *
     * @param int $condition_constant
     * @return bool
     */
    public static function is_valid(int $condition_constant): bool {
        return in_array($condition_constant, [self::CONTENT_MARKETPLACE]);
    }

    /**
     * Throw exception when the $condition_constant is not a valid value.
     *
     * @param int $condition_constant
     * @return void
     */
    public static function validate(int $condition_constant): void {
        if (!self::is_valid($condition_constant)) {
            throw new coding_exception("The completion condition is invalid");
        }
    }
}

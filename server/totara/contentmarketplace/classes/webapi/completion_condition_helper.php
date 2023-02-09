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
namespace totara_contentmarketplace\webapi;

use coding_exception;
use totara_contentmarketplace\completion_constants;

/**
 * A helper class that can help to convert a magic number which the server system can understand,
 * into an enum string that GraphQL layer can understand.
 */
class completion_condition_helper {
    /**
     * @var string
     */
    private const CONTENT_MARKETPLACE = "CONTENT_MARKETPLACE";

    /**
     * Preventing this class from instantiation.
     */
    private function __construct() {
    }

    /**
     * @param int $value
     * @return string|null
     */
    public static function get_enum(int $value): ?string {
        if (completion_constants::COMPLETION_CONDITION_CONTENT_MARKETPLACE === $value) {
            return self::CONTENT_MARKETPLACE;
        }

        return null;
    }
}

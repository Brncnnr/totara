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
namespace totara_contentmarketplace;

/**
 * Just a constant class that contains the constant for completion condition.
 */
class completion_constants {
    /**
     * Prevent the instantiation of this class.
     */
    private function __construct() {
    }

    /**
     * A constant to say the completion to mark the activity completed
     * when the content is marked as completed from content provider's side.
     *
     * @var int
     */
    public const COMPLETION_CONDITION_CONTENT_MARKETPLACE = 2;
}
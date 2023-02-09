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
 * @package totara_engage
 */
namespace totara_engage\question;

use totara_engage\local\helper;

/**
 * A resolver for resolving the capabilities of actions on the answers. Extend this class at the component level,
 * where the component is using engage question.
 */
abstract class question_resolver {
    /**
     * question_resolver constructor.
     * Preventing the children to have a complicate construction.
     */
    final public function __construct() {
    }

    /**
     * @return string
     */
    public function get_component(): string {
        $cls = static::class;
        return helper::get_component_name($cls);
    }

    /**
     * Checking the current $userid is able to create the question or not.
     *
     * @param int $userid
     * @return bool
     */
    public abstract function can_create(int $userid): bool;

    /**
     * Checking the current $userid is able to delete a question or not.
     * @param int $userid
     * @param int $questionid
     *
     * @return bool
     */
    public abstract function can_delete(int $userid, int $questionid): bool;
}
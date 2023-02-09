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

/**
 * Factory to store the cache of a question.
 */
final class question_factory {
    /**
     * @var question[]|null
     */
    private static $questions;

    /**
     * Preventing any construction on this class
     * question_factory constructor.
     */
    private function __construct() {
    }

    /**
     * @param int   $question_id
     * @param bool  $reload
     * @return question
     */
    public static function get_question_by_id(int $question_id, bool $reload = false): question {
        if (!isset(static::$questions)) {
            static::$questions = [];
        }

        if (!isset(static::$questions[$question_id]) || $reload) {
            static::$questions[$question_id] = question::from_id($question_id);
        }

        return static::$questions[$question_id];
    }
}
<?php
/**
 * This file is part of Totara Learn
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_engage
 */
namespace totara_engage\exception;

/**
 * An exception for the answer not found.
 */
final class no_answer_found extends \coding_exception{
    /**
     * no_answer_found constructor.
     *
     * @param int      $questionid
     * @param int|null $userid
     * @param null     $debuginfo
     */
    public function __construct(int $questionid, int $userid = null, $debuginfo = null) {
        global $USER;

        if (null == $userid) {
            $userid = $USER->id;
        }

        parent::__construct(
            "No answer was of question with id '{$questionid}' was found for user '{$userid}'",
            $debuginfo
        );
    }
}
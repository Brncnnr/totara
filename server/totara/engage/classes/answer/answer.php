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
namespace totara_engage\answer;

use core\orm\entity\repository;
use totara_engage\question\question;

abstract class answer {
    /**
     * @var question
     */
    protected $question;

    /**
     * @var int
     */
    protected $userid;

    /**
     * answer constructor.
     * @param question $question
     * @param int      $userid
     */
    final protected function __construct(question $question, int $userid) {
        $this->question = $question;
        $this->userid = $userid;
    }

    /**
     * @param question $question
     * @param int      $userid
     *
     * @return answer
     */
    public static function from_user(question $question, int $userid): answer {
        return new static($question, $userid);
    }

    /**
     * @return int
     */
    public function get_userid(): int {
        return $this->userid;
    }

    /**
     * @return question
     */
    public function get_question(): question {
        return $this->question;
    }

    /**
     * @param int $userid   The actor's id to be checked against.
     * @return bool
     */
    public function can_delete(int $userid): bool {
        if ($userid == $this->userid) {
            // Same owner can delete an answer of self.
            return true;
        } else if ($this->question->can_delete($userid)) {
            // Any one who can delete the question can also be able to delete the answer related to that question.
            // Question creator can delete an answer of some-one created.
            return true;
        }

        // Other than that, no one can delete answer of someone-else.
        return false;
    }

    /**
     * @param question $question
     * @param int      $userid
     * @param array    $data
     *
     * @return answer
     */
    abstract public static function create(question $question, int $userid, array $data): answer;

    /**
     * Loading all the answer_record from the database.
     *
     * @param bool $reload
     * @return void
     */
    abstract public function load(bool $reload = false): void;

    /**
     * @return repository
     */
    abstract public static function repository(): repository;

    /**
     * @param int|null $userid   The actor's id who is performing action on the answer.
     * @return void
     */
    abstract public function delete(?int $userid = null): void;
}
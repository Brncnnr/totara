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

use totara_engage\exception\no_answer_found;
use totara_engage\repository\answer_choice_repository;
use totara_engage\question\question;

final class answer_factory {
    /**
     * answer_factory constructor.
     */
    private function __construct() {
        // Preventing the class to be constructed
    }

    /**
     * @param question $question
     * @return answer[]
     */
    public static function get_answers(question $question): array {
        if (!$question->exists()) {
            return [];
        }

        $answertype = $question->get_answer_type();
        $questionid = $question->get_id();

        switch ($answertype) {
            case answer_type::MULTI_CHOICE:
                /** @var answer_choice_repository $repo */
                $repo = multi_choice::repository();
                $entities = $repo->get_answers($questionid);

                if (empty($entities)) {
                    return [];
                }

                $records = [];
                foreach ($entities as $entity) {
                    $userid = $entity->userid;

                    if (!isset($records[$userid])) {
                        $records[$userid] = [];
                    }

                    $records[$userid][] = $entity;
                }

                $answers = [];
                foreach ($records as $userid => $items) {
                    $answers[] = multi_choice::from_chocies($items, $question);
                }

                return $answers;

            case answer_type::SINGLE_CHOICE:
                /** @var answer_choice_repository $repo */
                $repo = single_choice::repository();
                $entities = $repo->get_answers($questionid);

                if (empty($entities)) {
                    return [];
                }

                $answers = [];
                foreach ($entities as $entity) {
                    $userid = $entity->userid;

                    if (isset($answers[$userid])) {
                        debugging(
                            "For single choice answer type, each answer should only have one choice ticked",
                            DEBUG_DEVELOPER
                        );

                        continue;
                    }

                    $answers[$userid] = single_choice::from_choice($entity, $question);

                }

                return $answers;

            default:
                throw new \coding_exception("Invalid answer type from the question");
        }
    }

    /**
     * This function will try to invoke {@see answer::repository()}
     * @param question $question
     * @return bool
     */
    public static function has_answers(question $question): bool {
        if (!$question->exists()) {
            debugging("The question is not existing in the system", DEBUG_DEVELOPER);
            return false;
        }

        $answertype = $question->get_answer_type();
        $questionid = $question->get_id();

        switch ($answertype) {
            case answer_type::MULTI_CHOICE:
                /** @var answer_choice_repository $repo */
                $repo = multi_choice::repository();
                return $repo->has_answers($questionid);

            case answer_type::SINGLE_CHOICE:
                /** @var answer_choice_repository $repo */
                $repo = single_choice::repository();
                return $repo->has_answers($questionid);

            default:
                throw new \coding_exception("Invalid answer type from the question with id '{$questionid}'");
        }
    }

    /**
     * This function will invoke {@see answer::from_user()}
     *
     * @param question $question
     * @param int      $userid
     *
     * @return answer|null
     */
    public static function get_answer_of_user(question $question, int $userid): ?answer {
        if (!$question->exists()) {
            throw new \coding_exception("Cannot get the answer of a question that is not existing in the system");
        }

        $answertype = $question->get_answer_type();

        try {
            switch ($answertype) {
                case answer_type::MULTI_CHOICE:
                    return multi_choice::from_user($question, $userid);

                case answer_type::SINGLE_CHOICE:
                    return single_choice::from_user($question, $userid);
            }
        } catch (no_answer_found $e) {
            // No answer for the user.
            return null;
        }

        throw new \coding_exception("Invalid answer type from question id '{$question->get_id()}'");
    }

    /**
     * @param question $question
     * @param int      $userid
     * @param int[]    $optionids
     *
     * @return answer
     */
    public static function create_answer_for_user(question $question, int $userid, array $optionids): answer {
        if ($question->has_answer_of_user($userid)) {
            throw new \coding_exception(
                "User with id '{$userid}' already had an answer for the question with id '{$question->get_id()}'"
            );
        }

        $type = $question->get_answer_type();

        switch ($type) {
            case answer_type::MULTI_CHOICE:
                return multi_choice::create($question, $userid, $optionids);

            case answer_type::SINGLE_CHOICE:
                return single_choice::create($question, $userid, $optionids);

            default:
                throw new \coding_exception("Invalid answer type '{$type}'");
        }
    }
}
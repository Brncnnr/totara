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
use totara_engage\entity\answer_choice as entity;
use totara_engage\event\single_choice_answer_deleted;
use totara_engage\exception\no_answer_found;
use totara_engage\question\question;
use totara_engage\repository\answer_choice_repository;

final class single_choice extends answer {
    /**
     * Single choice should only have once choice.
     * @var entity
     */
    private $choice;

    /**
     * @param question $question
     * @param int      $userid
     *
     * @return answer
     */
    public static function from_user(question $question, int $userid): answer {
        /** @var answer_choice_repository $repo */
        $repo = static::repository();
        $questionid = $question->get_id();

        $choices = $repo->get_choices($questionid, $userid);

        if (1 !== count($choices)) {
            throw new no_answer_found($questionid, $userid);
        }

        /** @var single_choice $answer */
        $answer = parent::from_user($question, $userid);
        $answer->choice = reset($choices);

        return $answer;
    }

    /**
     * Create an answer instance from the user's choice
     *
     * @param entity        $choice
     * @param question|null $question
     *
     * @return single_choice
     */
    public static function from_choice(entity $choice, question $question = null): single_choice {
        if (!$choice->exists()) {
            throw new \coding_exception(
                "Unable to create a single_choice instance, where the choice that is not existing in the system"
            );
        }

        if (null === $question) {
            $question = question::from_id($choice->questionid);
        } else if ($question->get_id() != $choice->questionid) {
            // This is to prevent the creation of invalid answer from choice and the question.
            throw new \coding_exception("The choice's question id is not equal to the question's id");
        }

        $userid = $choice->userid;
        $answer = new static($question, $userid);
        $answer->choice = $choice;

        return $answer;
    }

    /**
     * @param question $question
     * @param int      $userid
     * @param int[]    $options     The array of option id, however it should have only option id for single_choice.
     *
     * @return answer
     */
    public static function create(question $question, int $userid, array $options): answer {
        if (!$question->exists()) {
            throw new \coding_exception("Cannot create an answer for a question that is not existing in the system");
        } else if (!empty($options) && 1 != count($options)) {
            throw new \coding_exception("The single choice answer should pick one option only");
        }

        if ($question->has_answer_of_user($userid)) {
            debugging(
                "The answer of user with id '{$userid}' had already been made " .
                "for question with id '{$question->get_id()}'",
                DEBUG_DEVELOPER
            );

            return $question->get_answer_for_user($userid);
        }

        if (!$question->verify_submitted_options_exist($options)) {
            throw new \coding_exception("Cannot create answers for options that are not in the system");
        }

        $optionid = reset($options);
        $option = $question->get_answer_option($optionid);

        $entity = new entity();

        $entity->userid = $userid;
        $entity->questionid = $question->get_id();
        $entity->optionid = $option->id;

        $entity->save();

        $answer = new static($question, $userid);
        $answer->choice = $entity;

        return $answer;
    }

    /**
     * @param bool $reload
     * @return void
     */
    public function load(bool $reload = false): void {
        if ($reload) {
            $this->choice = null;
        }

        if (null == $this->choice) {
            /** @var answer_choice_repository $repo */
            $repo = entity::repository();

            $questionid = $this->question->get_id();
            $choices = $repo->get_choices($questionid, $this->userid);

            if (empty($choices)) {
                debugging(
                    "There was no choices found for user with id '{$this->userid}' in question with id '{$questionid}",
                    DEBUG_DEVELOPER
                );
            } else if (1 !== count($choices)) {
                debugging("There are multiple choices selection for a single choice answer", DEBUG_DEVELOPER);
            }

            $this->choice = reset($choices);
        }
    }

    /**
     * @return entity
     */
    public function get_choice(): entity {
        return $this->choice;
    }

    /**
     * @return repository
     */
    public static function repository(): repository {
        return entity::repository();
    }

    /**
     * @param int|null $userid
     * @return void
     */
    public function delete(?int $userid = null): void {
        global $USER;

        if (null == $userid) {
            $userid = $USER->id;
        }

        if (!$this->can_delete($userid)) {
            throw new \coding_exception(
                "User with id '{$userid}' cannot delete an answer for question '{$this->question->get_id()}'"
            );
        }

        $this->do_delete($userid);
    }

    /**
     * This function is where the deleting happening.
     *
     * @param int $userid
     * @return void
     */
    protected function do_delete(int $userid): void {
        global $DB;

        // Begin the transaction.
        $transaction = $DB->start_delegated_transaction();

        // Pre-load the choice.
        $this->load(true);

        // Currently using the context_user as there is no context for the answer/question yet.
        $context = \context_user::instance($this->userid);

        $event = single_choice_answer_deleted::from_answer($this, $context, $userid);
        $event->trigger();

        $this->choice->delete();
        $transaction->allow_commit();
    }
}
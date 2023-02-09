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
use totara_engage\event\multi_choice_answer_deleted;
use totara_engage\exception\no_answer_found;
use totara_engage\repository\answer_choice_repository;
use totara_engage\question\question;

class multi_choice extends answer {
    /**
     * @var entity[]
     */
    protected $choices;

    /**
     * @param question $question
     * @param int      $userid
     * @param int[]    $options     The array of option id
     *
     * @return answer
     */
    public static function create(question $question, int $userid, array $options): answer {
        if (!$question->exists()) {
            throw new \coding_exception("Cannot create an answer for a question that is not existing");
        } else if (empty($options)) {
            throw new \coding_exception("Cannot create a choice answer without the picked option");
        }

        if ($question->has_answer_of_user($userid)) {
            debugging(
                "The answer of user with id '{$userid}' had already been made for " .
                "question with id '{$question->get_id()}",
                DEBUG_DEVELOPER
            );

            return $question->get_answer_for_user($userid);
        }

        if (!$question->verify_submitted_options_exist($options)) {
            throw new \coding_exception("Cannot create answers for options that are not in the system");
        }

        $choices = [];

        foreach ($options as $optionid) {
            $option = $question->get_answer_option($optionid);

            $entity = new entity();
            $entity->questionid = $question->get_id();
            $entity->optionid = $option->id;
            $entity->userid = $userid;
            $entity->save();

            $choices[$entity->id] = $entity;
        }

        return static::from_chocies($choices, $question);
    }

    /**
     * @param question $question
     * @param int      $userid
     *
     * @return answer
     */
    public static function from_user(question $question, int $userid): answer {
        if (!$question->exists()) {
            throw new \coding_exception("Unable to create an answer of a question that is not existing in the system");
        }

        $questionid = $question->get_id();

        /** @var answer_choice_repository $repo */
        $repo = static::repository();
        $choices = $repo->get_choices($questionid, $userid);

        if (empty($choices)) {
            throw new no_answer_found($questionid, $userid);
        }

        /** @var  multi_choice $answer */
        $answer = parent::from_user($question, $userid);
        $answer->choices = [];

        $answer->load();
        return $answer;
    }

    /**
     * @param question|null $question
     * @param entity[]  $entities
     *
     * @return multi_choice
     */
    public static function from_chocies(array $entities, question $question = null): multi_choice {
        if (empty($entities)) {
            throw new \coding_exception("Cannot create a multi-choices answer when the empty choices");
        }

        $userid = null;
        $questionid = null;

        foreach ($entities as $entity) {
            if (!$entity->exists()) {
                throw new \coding_exception(
                    "Cannot create a multi-choices answer when the choices are not existing in the system"
                );
            }

            if (null === $userid) {
                $userid = $entity->userid;
            }

            if (null === $questionid) {
                $questionid = $entity->questionid;
            }

            if ($userid !== $entity->userid || $questionid !== $entity->questionid) {
                throw new \coding_exception("Cannot create a multi-choice answer from a list of inconsistency choices");
            }
        }

        if (null == $question) {
            $question = question::from_id($questionid);
        } else if ($question->get_id() != $questionid) {
            throw new \coding_exception("The question's id is not consistent with the choice's question id");
        }

        $answer = new static($question, $userid);
        $answer->choices = [];

        foreach ($entities as $entity) {
            $answer->choices[$entity->id] = $entity;
        }

        return $answer;
    }

    /**
     * Pre loading the user's choices.
     *
     * @param bool $reload
     * @return void
     */
    public function load(bool $reload = false): void {
        if ($reload) {
            $this->choices = [];
        }

        if (empty($this->choices)) {
            /** @var answer_choice_repository $repo */
            $repo = entity::repository();

            $questionid = $this->question->get_id();
            $choices = $repo->get_choices($questionid, $this->userid);

            if (empty($choices)) {
                debugging(
                    "There are no answers for a question with id '{$questionid}' of a user '{$this->userid}'",
                    DEBUG_DEVELOPER
                );

                return;
            }

            foreach ($choices as $choice) {
                $this->choices[$choice->id] = $choice;
            }
        }
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
            $questionid = $this->question->get_id();
            throw new \coding_exception(
                "User with id '{$userid}' cannot delete an answer of question '{$questionid}'"
            );
        }

        $this->do_delete($userid);
    }

    /**
     * A proper function to do the deletion.
     *
     * @param int $actorid
     * @return void
     */
    protected function do_delete(int $actorid): void {
        global $DB;

        // Begin the transaction.
        $transaction = $DB->start_delegated_transaction();
        $this->load(true);

        // For now, we use the user context.
        $context = \context_user::instance($this->userid);

        $event = multi_choice_answer_deleted::from_answer($this, $context, $actorid);
        $event->trigger();

        // Start deleting the choices.
        foreach ($this->choices as $choice) {
            $choice->delete();
        }

        // End the transaction.
        $transaction->allow_commit();
    }

    /**
     * @return entity[]
     */
    public function get_choices(): array {
        $this->load();
        return array_values($this->choices);
    }

    /**
     * @return repository
     */
    public static function repository(): repository {
        return entity::repository();
    }
}
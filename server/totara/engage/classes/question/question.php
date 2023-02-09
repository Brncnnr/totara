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
namespace totara_engage\question;

use totara_engage\access\access_manager;
use totara_engage\answer\answer;
use totara_engage\answer\answer_factory;
use totara_engage\entity\answer_option;
use totara_engage\entity\question as question_entity;
use totara_engage\exception\question_exception;
use totara_engage\repository\answer_option_repository;

/**
 * A model of a question.
 */
final class question {
    /**
     * @var question_entity
     */
    private $question;

    /**
     * @var answer[]
     */
    private $answers;

    /**
     * @var answer_option[]
     */
    private $options;

    /**
     * question constructor.
     * @param question_entity $entity
     */
    private function __construct(question_entity $entity) {
        $this->question = $entity;
        $this->answers = [];
        $this->options = [];
    }

    /**
     * @param int $questionid
     * @return question
     */
    public static function from_id(int $questionid): question {
        $entity = new question_entity($questionid);

        $question = new static($entity);
        $question->load_answer_options();

        return $question;
    }

    /**
     * @param question_entity $entity
     * @return question
     */
    public static function from_entity(question_entity $entity): question {
        if (!$entity->exists()) {
            throw new \coding_exception("Cannot instantiate a question from entity that is not existing");
        }

        return new static($entity);
    }

    /**
     * @param string   $value
     * @param int      $answertype
     * @param string   $component
     * @param int|null $userid
     *
     * @return question
     */
    public static function create(string $value, int $answertype, string $component, int $userid = null): question {
        global $USER;

        if (null == $userid) {
            $userid = $USER->id;
        }

        if (!static::can_create($userid, $component)) {
            throw question_exception::on_create();
        }

        $entity = new question_entity();
        $entity->value = $value;
        $entity->answertype = $answertype;
        $entity->component = $component;
        $entity->userid = $userid;

        $entity->save();
        return new static($entity);
    }

    /**
     * We need a component to find out the resolver for running permissions check.
     *
     * @param int       $userid
     * @param string    $component
     *
     * @return bool
     */
    public static function can_create(int $userid, string $component): bool {
        $resolver = question_resolver_factory::get_resolver($component);
        return $resolver->can_create($userid);
    }

    /**
     * @param array $values
     * @return bool
     */
    public function add_answer_options(array $values): bool {
        if (empty($values)) {
            debugging(
                "Parameter \$values is empty - no point to add the answer " .
                "option for question with id '{$this->question->id}'",
                DEBUG_DEVELOPER
            );

            return false;
        }

        foreach ($values as $value) {
            $this->add_answer_option($value);
        }

        return true;
    }

    /**
     * @param string $value
     * @return answer_option
     */
    public function add_answer_option(string $value): answer_option {
        $option = new answer_option();
        $option->value = $value;
        $option->questionid = $this->question->id;

        $option->save();
        $this->options[$option->id] = $option;

        return $option;
    }

    /**
     * @param string[] $values
     * @param int|null $userid  Who is the actor of this very action.
     *
     * @return bool
     */
    public function update_answer_options(array $values, int $userid = null): bool {
        global $USER, $DB;

        if (empty($values)) {
            debugging("Cannot delete the options of a question", DEBUG_DEVELOPER);
            return false;
        }

        if (null == $userid) {
            $userid = $USER->id;
        }

        if (!$this->can_update($userid)) {
            throw question_exception::on_update();
        }

        // Begin the transaction.
        $transaction = $DB->start_delegated_transaction();
        $this->load_answer_options(true);

        // $current is the list of the answer option values. And var $remain is the list of object answer_option
        // remaining after deleted a few other option.
        $current = [];
        $remaining = [];

        foreach ($this->options as $option) {
            $currentvalue = $option->value;

            if (!in_array($currentvalue, $values)) {
                $option->delete();
            } else {
                $current[] = $currentvalue;
                $remaining[] = $option;
            }
        }

        // Applying the remaining.
        $this->options = $remaining;
        $newvalues = array_diff($values, $current);

        if (empty($newvalues)) {
            // No new values, just commit the changes on deleting and skip the rest of the code.
            $transaction->allow_commit();
            return true;
        }

        $result = $this->add_answer_options($newvalues);

        if ($result) {
            $transaction->allow_commit();
        } else {
            // Rollback on fail adding new option.
            $transaction->rollback();
        }

        return $result;
    }

    /**
     * @param int $optionid
     * @return void
     */
    public function delete_option(int $optionid): void {
        $this->load_answer_options();

        if (!isset($this->options[$optionid])) {
            debugging(
                "No option with id '{$optionid}' found for question with id '{$this->question->id}",
                DEBUG_DEVELOPER
            );

            return;
        }

        $option = $this->options[$optionid];
        $option->delete();

        unset($this->options[$optionid]);
    }

    /**
     * @param int $userid
     * @return bool
     */
    public function can_delete(int $userid): bool {
        $creatorid = $this->question->userid;
        $creator_context = \context_user::instance($creatorid);

        // Engage manager can do anything.
        if (access_manager::can_manage_engage($creator_context, $userid)) {
            return true;
        }

        if ($creatorid != $userid) {
            return false;
        }

        $component = $this->question->component;
        $resolver = question_resolver_factory::get_resolver($component);

        return $resolver->can_delete($userid, $this->question->id);
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
            throw question_exception::on_delete();
        }

        // We need to delete the answer first, because option is a parent of the answer. Therefore, deleting
        // option first will cause the answer being deleted sequencely - cascade.
        $this->load_answers(true);
        foreach ($this->answers as $answer) {
            $answer->delete($userid);
        }

        $this->load_answer_options(true);
        foreach ($this->options as $option) {
            $option->delete();
        }

        $this->question->delete();
    }

    /**
     * @return bool
     */
    public function has_answers(): bool {
        return answer_factory::has_answers($this);
    }

    /**
     * @param int $userid
     * @return bool
     */
    public function has_answer_of_user(int $userid): bool {
        if (isset($this->answers[$userid])) {
            return true;
        } else {
            $answer = answer_factory::get_answer_of_user($this, $userid);
        }

        if (null == $answer) {
            return false;
        }

        // Cache it, so that it can be reused.
        $this->answers[$userid] = $answer;
        return true;
    }

    /**
     * @param int $userid
     * @return answer
     */
    public function get_answer_for_user(int $userid): answer {
        if (!$this->has_answer_of_user($userid)) {
            throw new \coding_exception("The user does not have an answer yet");
        }

        return $this->answers[$userid];
    }

    /**
     * @return array
     */
    public function get_answers(): array {
        $this->load_answers();
        return $this->answers;
    }

    /**
     * @param bool $reload
     * @return void
     */
    public function load_answer_options(bool $reload = false): void {
        if ($reload) {
            $this->options = [];
        }

        if (empty($this->options)) {
            /** @var answer_option_repository $repo */
            $repo = answer_option::repository();
            $options = $repo->get_options($this->question->id);

            if (empty($options)) {
                return;
            }

            foreach ($options as $option) {
                $this->options[$option->id] = $option;
            }
        }
    }

    /**
     * @param int $optionid
     * @return answer_option
     */
    public function get_answer_option(int $optionid): answer_option {
        if (isset($this->options[$optionid])) {
            return $this->options[$optionid];
        }

        /** @var answer_option_repository $repo */
        $repo = answer_option::repository();
        $option = $repo->get_option($this->question->id, $optionid);

        if (null == $option) {
            throw new \coding_exception(
                "No option with id '{$optionid}' found for question with id '{$this->question->id}'",
                DEBUG_DEVELOPER
            );
        }

        // Cache it.
        $this->options[$option->id] = $option;
        return $option;
    }

    /**
     * @return answer_option[]
     */
    public function get_answer_options(): array {
        $this->load_answer_options();
        return array_values($this->options);
    }

    /**
     * @param bool $reload
     * @return void
     */
    public function load_answers(bool $reload = false): void {
        if ($reload) {
            $this->answers = [];
        }

        if (empty($this->answers)) {
            $answers = answer_factory::get_answers($this);
            if (empty($answers)) {
                return;
            }

            foreach ($answers as $answer) {
                $userid = $answer->get_userid();

                if (isset($this->answers[$userid])) {
                    debugging("User with id '{$userid}' already had the answer", DEBUG_DEVELOPER);
                    continue;
                }

                $this->answers[$userid] = $answer;
            }
        }
    }

    /**
     * Only be able to update the record, when there is no answer yet.
     *
     * @param int|null $userid
     * @return void
     */
    public function update(int $userid = null): void {
        global $USER;

        if (null == $userid) {
            $userid = $USER->id;
        }

        if (!$this->can_update($userid)) {
            throw question_exception::on_update();
        }

        $this->question->update();
    }

    /**
     * @return bool
     */
    public function can_be_updated(): bool {
        if ($this->has_answers()) {
            // There are answers for this question, hence, it cannot bet updated
            return false;
        }

        return true;
    }

    /**
     * A function that checking the permission of $userid with its instance.
     *
     * @param int $userid
     * @return bool
     */
    public function can_update(int $userid): bool {
        if (!$this->can_be_updated()) {
            return false;
        }

        if ($this->question->userid != $userid) {
            return false;
        }

        // todo: capability check?
        return true;
    }

    /**
     * @param string $value
     * @return void
     */
    public function set_value(string $value): void {
        $this->question->value = $value;
    }

    /**
     * @param int $value
     * @return void
     */
    public function set_answertype(int $value): void {
        $this->question->answertype = $value;
    }

    /**
     * @return int
     */
    public function get_id(): int {
        return $this->question->id;
    }

    /**
     * Returning the text of the question.
     * @return string
     */
    public function get_value(): string {
        return $this->question->value;
    }

    /**
     * @return int
     */
    public function get_answer_type(): int {
        return $this->question->answertype;
    }

    /**
     * @return int
     */
    public function get_timecreated(): int {
        return $this->question->timecreated;
    }

    /**
     * @return int
     */
    public function get_timemodified(): int {
        return $this->question->timemodified;
    }

    /**
     * @return int
     */
    public function get_userid(): int {
        return $this->question->userid;
    }

    /**
     * @return bool
     */
    public function exists(): bool {
        return 0 != $this->question->id && $this->question->exists();
    }

    /**
     * Ensure that answers submitted by user exist.
     *
     * @param array $user_options
     * @return bool
     */
    public function verify_submitted_options_exist(array $user_options) : bool {
        // Get valid option ids.
        $valid_options = [];
        foreach ($this->options as $question_option) {
            $valid_options[] = $question_option->id;
        }

        // Check that user options exist.
        foreach ($user_options as $user_option) {
            if (!in_array($user_option, $valid_options)) {
                return false;
            }
        }

        return true;
    }
}
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
defined('MOODLE_INTERNAL') || die();

use totara_engage\question\question;
use totara_engage\answer\answer_type;
use totara_engage\answer\answer_factory;
use totara_engage\answer\single_choice;
use totara_engage\question\question_resolver_factory;
use totara_engage\exception\question_exception;

class totara_engage_update_answer_options_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_update_answer_option(): void {
        global $DB, $CFG;
        require_once("{$CFG->dirroot}/totara/engage/tests/fixtures/question_resolver.php");

        $resolver = new question_resolver();
        $resolver->set_component('totara_engage');

        question_resolver_factory::phpunit_set_default_resolver($resolver);

        $initial = ['hello', 'world'];
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $question = question::create('hello world', answer_type::SINGLE_CHOICE, 'totara_engage');
        $question->add_answer_options($initial);

        $options = $question->get_answer_options();
        foreach ($options as $option) {
            $value = $option->value;
            $this->assertTrue(in_array($value, $initial));
        }

        $this->assertTrue($DB->record_exists('engage_question', ['id' => $question->get_id()]));
        $this->assertTrue($DB->record_exists('engage_answer_option', ['questionid' => $question->get_id()]));

        $question->update_answer_options(['x', 'o']);
        $options = $question->get_answer_options();

        foreach ($options as $option) {
            $value = $option->value;
            $this->assertFalse(in_array($value, $initial));
        }
    }

    /**
     * @return void
     */
    public function test_unable_to_update_answer_options(): void {
        global $DB, $CFG;
        require_once("{$CFG->dirroot}/totara/engage/tests/fixtures/question_resolver.php");

        $resolver = new question_resolver();
        $resolver->set_component('totara_engage');

        question_resolver_factory::phpunit_set_default_resolver($resolver);

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $question = question::create('Hello world', answer_type::SINGLE_CHOICE, 'totara_engage');
        $question->add_answer_options(['hello', 'world']);

        $options = $question->get_answer_options();
        $first = reset($options);

        /** @var single_choice $answer */
        $answer = answer_factory::create_answer_for_user($question, $user->id, [$first->id]);
        $this->assertInstanceOf(single_choice::class, $answer);

        $choice = $answer->get_choice();
        $this->assertTrue($DB->record_exists('engage_answer_choice', ['id' => $choice->id]));

        // Cannot update the question, due to there is already an answer.
        $this->expectException(question_exception::class);
        $question->update_answer_options(['xo', 'ox']);
    }
}
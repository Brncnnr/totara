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

use totara_engage\question\question_resolver_factory;
use totara_engage\question\question;
use totara_engage\answer\answer_type;
use totara_engage\answer\answer_factory;
use totara_engage\answer\single_choice;
use totara_engage\answer\multi_choice;

class totara_engage_delete_answer_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_delete_single_choice_answer(): void {
        global $CFG, $DB;
        require_once("{$CFG->dirroot}/totara/engage/tests/fixtures/question_resolver.php");

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $resolver = new question_resolver();
        $resolver->set_component('totara_engage');

        question_resolver_factory::phpunit_set_default_resolver($resolver);

        $question = question::create('Hello world ?', answer_type::SINGLE_CHOICE, 'totara_engage');
        $question->add_answer_options(['hello', 'world']);

        $options = $question->get_answer_options();
        $option = reset($options);

        /** @var single_choice $answer */
        $answer = answer_factory::create_answer_for_user($question, $user->id, [$option->id]);
        $choice = $answer->get_choice();

        $this->assertInstanceOf(single_choice::class, $answer);
        $this->assertTrue($DB->record_exists('engage_answer_choice', ['id' => $choice->id]));

        $answer->delete();
        $this->assertFalse($DB->record_exists('engage_answer_choice', ['id' => $choice->id]));
    }

    /**
     * @return void
     */
    public function test_delete_multi_choice_answer(): void {
        global $CFG, $DB;
        require_once("{$CFG->dirroot}/totara/engage/tests/fixtures/question_resolver.php");

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $resolver = new question_resolver();
        $resolver->set_component('totara_engage');

        question_resolver_factory::phpunit_set_default_resolver($resolver);

        $question = question::create('Helxcslo world ?', answer_type::MULTI_CHOICE, 'totara_engage');
        $question->add_answer_options(['o', 'a']);

        $options = $question->get_answer_options();
        $optionids = [];
        foreach ($options as $option) {
            $optionids[] = $option->id;
        }

        /** @var multi_choice $answer */
        $answer = answer_factory::create_answer_for_user($question, $user->id, $optionids);
        $this->assertInstanceOf(multi_choice::class, $answer);

        $choices = $answer->get_choices();
        foreach ($choices as $choice) {
            $this->assertTrue($DB->record_exists('engage_answer_choice', ['id' => $choice->id]));
        }

        $answer->delete();

        foreach ($choices as $choice) {
            $this->assertFalse($DB->record_exists('engage_answer_choice', ['id' => $choice->id]));
        }
    }
}
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

class totara_engage_create_answer_testcase extends advanced_testcase {
    /**
     * @param int $answertype
     * @return question
     */
    private function create_question(int $answertype): question {
        global $CFG;
        require_once("{$CFG->dirroot}/totara/engage/tests/fixtures/question_resolver.php");

        $resolver = new question_resolver();
        $resolver->set_component('totara_engage');

        question_resolver_factory::phpunit_set_default_resolver($resolver);
        return question::create('Hello world ?', $answertype, 'totara_engage');
    }

    /**
     * @return void
     */
    public function test_create_single_choice_answer_for_question(): void {
        global $DB;
        $gen = $this->getDataGenerator();

        $user = $gen->create_user();
        $this->setUser($user);

        $user2 = $gen->create_user();

        $question = $this->create_question(answer_type::SINGLE_CHOICE);
        $question->add_answer_options(['y', 'n']);

        $options = $question->get_answer_options();
        $firstoption = reset($options);

        answer_factory::create_answer_for_user($question, $user2->id, [$firstoption->id]);
        $sql = '
            SELECT 1 FROM "ttr_engage_answer_option" eao
            INNER JOIN "ttr_engage_answer_choice" eac ON eao.id = eac.optionid
            WHERE eac.userid = ?
        ';

        $this->assertTrue($DB->record_exists_sql($sql, [$user2->id]));
    }

    /**
     * @return void
     */
    public function test_create_invalid_single_choice_answer_for_question(): void {
        $gen = $this->getDataGenerator();

        $user = $gen->create_user();
        $this->setUser($user);

        $question = $this->create_question(answer_type::SINGLE_CHOICE);
        $question->add_answer_options(['y', 'n']);

        $options = $question->get_answer_options();
        $ids = [];

        foreach ($options as $option) {
            $ids[] = $option->id;
        }

        $this->expectException(\coding_exception::class);

        $user2 = $gen->create_user();
        answer_factory::create_answer_for_user($question, $user2->id, $ids);
    }

    /**
     * @return void
     */
    public function test_create_multi_choice_answer_for_question(): void {
        global $DB;
        $gen = $this->getDataGenerator();

        $user = $gen->create_user();
        $this->setUser($user);

        $question = $this->create_question(answer_type::MULTI_CHOICE);
        $question->add_answer_options(['x', 'b', 'c', 'd']);

        $options = $question->get_answer_options();
        $ids = [];

        foreach ($options as $option) {
            $ids[] = $option->id;
        }

        $user2 = $gen->create_user();
        answer_factory::create_answer_for_user($question, $user2->id, $ids);

        $sql = '
            SELECT 1 FROM "ttr_engage_answer_option" eao
            INNER JOIN "ttr_engage_answer_choice" eac ON eac.optionid = eao.id
            WHERE eac.userid = ?
        ';
        $this->assertTrue($DB->record_exists_sql($sql, [$user2->id]));
    }
}
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
 * @author Vernon Denny <vernon.denny@totaralearning.com>
 * @package engage_survey
 */

defined('MOODLE_INTERNAL') || die();

use totara_engage\answer\answer_type;
use totara_engage\access\access;
use totara_webapi\phpunit\webapi_phpunit_helper;


class engage_survey_webapi_create_answer_question_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * Ensure answering user may access survey.
     *
     * @return void
     */
    public function test_answer_a_question_access(): void {

        $gen = $this->getDataGenerator();

        $user = $gen->create_user();
        $user_evil = $gen->create_user();
        $this->setUser($user);

        /** @var \engage_survey\testing\generator $surveygen */
        $surveygen = $gen->get_plugin_generator('engage_survey');
        $survey = $surveygen->create_survey('Why is yellow?', [], answer_type::MULTI_CHOICE, ['access' => access::PRIVATE]);

        $questions = $survey->get_questions();
        $question = reset($questions);

        $options = $question->get_answer_options();
        $optionids = [];

        foreach ($options as $option) {
            $optionids[] = $option->id;
        }

        $this->setUser($user_evil);

        $this->expectException(totara_engage\exception\resource_exception::class);
        $this->expectExceptionMessage("Cannot answer the survey");

        $this->resolve_graphql_mutation(
            'engage_survey_create_answer',
            [
                'questionid' => $question->get_id(),
                'resourceid' => $survey->get_id(),
                'options' => $optionids
            ]
        );
    }

    /**
     * Ensure that supplied answer options actually exist for the question being answered.
     *
     * @return void
     */
    public function test_answer_a_question_options_negative_test(): void {

        $gen = $this->getDataGenerator();

        $user = $gen->create_user();
        $this->setUser($user);

        /** @var \engage_survey\testing\generator $surveygen */
        $surveygen = $gen->get_plugin_generator('engage_survey');
        $survey = $surveygen->create_survey('Why is yellow?', [], answer_type::MULTI_CHOICE, ['access' => access::PUBLIC]);

        $questions = $survey->get_questions();
        $question = reset($questions);

        // Retrieve answer option ids.
        $options = $question->get_answer_options();
        $optionids = [];
        foreach ($options as $option) {
            $optionids[] = $option->id;
        }

        // An answering user.
        $user_evil = $gen->create_user();
        $this->setUser($user_evil);

        // Add in an invalid submitted option - should fail.
        $optionids[] = 999999999;

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Cannot create answers for options that are not in the system");

        $this->resolve_graphql_mutation(
            'engage_survey_create_answer',
            [
                'questionid' => $question->get_id(),
                'resourceid' => $survey->get_id(),
                'options' => $optionids
            ]
        );
    }

    /**
     * Ensure that supplied answer options are stored.
     *
     * @return void
     */
    public function test_answer_a_question_options_positive_test(): void {
        global $DB;

        $gen = $this->getDataGenerator();

        $user = $gen->create_user();
        $this->setUser($user);

        /** @var \engage_survey\testing\generator $surveygen */
        $surveygen = $gen->get_plugin_generator('engage_survey');
        $survey = $surveygen->create_survey('Why is yellow?', [], answer_type::MULTI_CHOICE, ['access' => access::PUBLIC]);

        $questions = $survey->get_questions();
        $question = reset($questions);

        // Retrieve answer option ids.
        $options = $question->get_answer_options();
        $optionids = [];
        foreach ($options as $option) {
            $optionids[] = $option->id;
        }

        // An answering user.
        $user2 = $gen->create_user();
        $this->setUser($user2);

        // Set up sql query.
        $sql_count = "SELECT COUNT('x') FROM {engage_answer_choice} WHERE userid = :userid AND questionid = :questionid";
        $params = [
            'userid' => $user2->id,
            'questionid' => $question->get_id()
        ];

        // No answers should exist yet.
        $this->assertEquals(
            0,
            $DB->count_records_sql($sql_count, $params)
        );

        // Submit the answer options.
        $result = $this->resolve_graphql_mutation(
            'engage_survey_create_answer',
            [
                'questionid' => $question->get_id(),
                'resourceid' => $survey->get_id(),
                'options' => $optionids
            ]
        );

        // Successful mutation.
        $this->assertIsBool($result);
        $this->assertTrue($result);

        // Check that answers now exist.
        $this->assertEquals(
            count($optionids),
            $DB->count_records_sql($sql_count, $params)
        );
    }
}
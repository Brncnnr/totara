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
 * @package engage_survey
 */
defined('MOODLE_INTERNAL') || die();

use core\webapi\execution_context;
use engage_survey\totara_engage\resource\survey;
use totara_engage\access\access;
use totara_engage\answer\answer_type;
use totara_webapi\graphql;

class engage_survey_update_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_update_survey(): void {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        /** @var survey $survey */
        $survey = survey::create(
            [
                'questions' => [
                    [
                        'value' => "Hello world ?",
                        'answertype' => answer_type::SINGLE_CHOICE,
                        'options' => ['Yes', 'No']
                    ]
                ]
            ]
        );

        $questions = $survey->get_questions();
        $this->assertCount(1, $questions);

        $question = reset($questions);
        $this->assertEquals('Hello world ?', $question->get_value());

        // Confirm that the resource name is the same as the question.
        $resource = totara_engage\resource\resource_factory::create_instance_from_id($survey->get_id());
        $this->assertEquals('Hello world ?', $resource->get_name());

        $survey->update([
            'questions' => [
                [
                    'id' => $question->get_id(),
                    'answertype' => answer_type::SINGLE_CHOICE,
                    'value' => 'Is this not a hello world',
                    'options' => ['Yes', 'No']
                ]
            ],
            'access' => access::PUBLIC
        ]);

        $questions = $survey->get_questions();
        $question = reset($questions);

        $this->assertEquals('Is this not a hello world', $question->get_value());
        $this->assertEquals( access::PUBLIC, $survey->get_access());

        // Also confirm that the resource name changed.
        $resource = totara_engage\resource\resource_factory::create_instance_from_id($survey->get_id());
        $this->assertEquals('Is this not a hello world', $resource->get_name());
    }

    /**
     * @return void
     */
    public function test_update_survey_without_access(): void {
        $gen = $this->getDataGenerator();
        $user = $gen->create_user();
        $this->setUser($user);

        /** @var \engage_survey\testing\generator $articlegen */
        $surveygen = $gen->get_plugin_generator('engage_survey');

        /** @var survey $survey */
        $survey = $surveygen->create_survey();
        $questions = $survey->get_questions();
        $question = reset($questions);

        $survey->update([
            'questions' => [
                [
                    'id' => $question->get_id(),
                    'answertype' => answer_type::SINGLE_CHOICE,
                    'value' => 'No access?',
                    'options' => ['Yes', 'No']
                ]
            ],
        ]);

        $questions = $survey->get_questions();
        $question = reset($questions);
        $this->assertCount(1, $questions);
        $this->assertEquals('No access?', $question->get_value());
    }

    /**
     * @return void
     */
    public function test_update_survey_via_graphql(): void {
        $this->setAdminUser();
        /** @var \totara_topic\testing\generator $topicgen */
        $topicgen = $this->getDataGenerator()->get_plugin_generator('totara_topic');
        $topics[] = $topicgen->create_topic('topic1')->get_id();
        $topics[] = $topicgen->create_topic('topic2')->get_id();

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        /** @var survey $survey */
        $survey = survey::create(
            [
                'questions' => [
                    [
                        'value' => "Hello world ?",
                        'answertype' => answer_type::SINGLE_CHOICE,
                        'options' => ['Yes', 'No']
                    ]
                ]
            ]
        );

        $questions = $survey->get_questions();
        $question = reset($questions);

        $args = [
            'resourceid' => $survey->get_id(),
            'questions' => [
                [
                    'id' => $question->get_id(),
                    'answertype' => answer_type::SINGLE_CHOICE,
                    'value' => 'Is this not a hello world',
                    'options' => ['Yes', 'No']
                ]
            ],
            'access' => 'PUBLIC',
            'topics' => $topics
        ];

        $ec = execution_context::create('ajax', 'engage_survey_update_survey');
        $result = graphql::execute_operation($ec, $args);

        $this->assertEmpty($result->errors);
        $this->assertNotEmpty($result->data);
        $this->assertArrayHasKey('survey', $result->data);

        $questions = $survey->get_questions();
        $this->assertCount(1, $questions);

        $survey = $result->data['survey'];

        $question = reset($questions);
        $this->assertEquals('Is this not a hello world', $question->get_value());
        $this->assertEquals('PUBLIC', $survey['resource']['access']);
    }

    /**
     * @return void
     */
    public function test_survey_question_validation(): void {
        $gen = $this->getDataGenerator();
        $user = $gen->create_user();
        $this->setUser($user);
        /** @var \engage_survey\testing\generator $articlegen */
        $surveygen = $gen->get_plugin_generator('engage_survey');

        /** @var survey $survey */
        $survey = $surveygen->create_survey();
        $questions = $survey->get_questions();
        $this->assertCount(1, $questions);
        $question = reset($questions);

        $this->assertEquals(76, core_text::strlen("TfIKQ8IXoycfkcbGaav6B1XVVibwtIYTlyGIOiJukJ4xVOVd4dlbDBnVioSmM5LwdJ7lEv7MCNax"));
        $this->expectException('coding_exception');
        $this->expectExceptionMessage("Validation run for property 'questions' has been failed");

        $survey->update([
            'questions' => [
                [
                    'id' => $question->get_id(),
                    'answertype' => answer_type::SINGLE_CHOICE,
                    'value' => "TfIKQ8IXoycfkcbGaav6B1XVVibwtIYTlyGIOiJukJ4xVOVd4dlbDBnVioSmM5LwdJ7lEv7MCNax",
                    'options' => ['Yes', 'No']
                ]
            ],
        ]);
    }

    /**
     * @return void
     */
    public function test_survey_answer_validation(): void {
        $gen = $this->getDataGenerator();
        $user = $gen->create_user();
        $this->setUser($user);
        /** @var \engage_survey\testing\generator $articlegen */
        $surveygen = $gen->get_plugin_generator('engage_survey');

        /** @var survey $survey */
        $survey = $surveygen->create_survey();
        $questions = $survey->get_questions();
        $this->assertCount(1, $questions);
        $question = reset($questions);

        $this->expectException('coding_exception');
        $this->expectExceptionMessage("Validation run for property 'questions' has been failed");
        $survey->update([
            'questions' => [
                [
                    'id' => $question->get_id(),
                    'answertype' => answer_type::SINGLE_CHOICE,
                    'options' => ['OoCtvoljRosLba2P8FxNULYk41c6KSdeSGIX3IAj15ayYsbIvS3bSoZubTTwxugQOACkrPMbvHeNmC8E5', 'No']
                ]
            ],
        ]);
    }
}
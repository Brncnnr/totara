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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package engage_survey
 */

defined('MOODLE_INTERNAL') || die();

use totara_webapi\phpunit\webapi_phpunit_helper;
use engage_survey\totara_engage\resource\survey;

class engage_survey_webapi_vote_result_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_access_private_vote_result_not_yours(): void {
        $gen = $this->getDataGenerator();
        $user = $gen->create_user();
        $user1 = $gen->create_user();

        $this->setUser($user);
        $survey = $this->create_mock_survey_by_type();

        // User can not access other's private vote result
        $this->expectException('coding_exception');
        $this->expectExceptionMessage("User with id '{$user1->id}' does not have access to this survey result");
        $this->setUser($user1);
        $this->resolve_graphql_query(
            'engage_survey_vote_result',
            [
                'resourceid' => $survey->get_id()
            ]
        );
    }

    /**
     * @return void
     */
    public function test_access_public_vote_result_not_yours(): void {
        $gen = $this->getDataGenerator();
        $user = $gen->create_user();
        $user1 = $gen->create_user();

        $this->setUser($user);
        $survey = $this->create_mock_survey_by_type('PUBLIC');

        $this->setUser($user1);
        $result = $this->resolve_graphql_query(
            'engage_survey_vote_result',
            [
                'resourceid' => $survey->get_id()
            ]
        );
        $this->assertIsArray($result);
        $vote = reset($result);
        $questions = $survey->get_questions();
        $question = reset($questions);
        $this->assertEquals($question->get_id(), $vote->get_id());
    }

    /**
     * @return void
     */
    public function test_access_restricted_vote_result_not_yours(): void {
        $gen = $this->getDataGenerator();
        $user = $gen->create_user();
        $user1 = $gen->create_user();

        $this->setUser($user);
        $survey = $this->create_mock_survey_by_type('RESTRICTED');

        // User can not access other's RESTRICTED vote result
        $this->expectException('coding_exception');
        $this->expectExceptionMessage("User with id '{$user1->id}' does not have access to this survey result");
        $this->setUser($user1);
        $this->resolve_graphql_query(
            'engage_survey_vote_result',
            [
                'resourceid' => $survey->get_id()
            ]
        );
    }

    /**
     * @return void
     */
    public function test_vote_result_via_graphql(): void {
        $gen = $this->getDataGenerator();
        $user = $gen->create_user();

        $this->setUser($user);
        $survey = $this->create_mock_survey_by_type();

        $result = $this->resolve_graphql_query(
            'engage_survey_vote_result',
            [
                'resourceid' => $survey->get_id()
            ]
        );

        $this->assertIsArray($result);
        $vote = reset($result);
        $questions = $survey->get_questions();
        $question = reset($questions);
        $this->assertEquals($question->get_id(), $vote->get_id());
    }

    /**
     * @param string|null $type
     * @return survey
     */
    private function create_mock_survey_by_type(?string $type = 'PRIVATE'): survey {
        $gen = $this->getDataGenerator();
        /** @var \engage_survey\testing\generator $surveygen */
        $surveygen = $gen->get_plugin_generator('engage_survey');

        switch ($type) {
            case 'PUBLIC':
                $survey = $surveygen->create_public_survey();
                break;

            case 'RESTRICTED':
                $survey = $surveygen->create_restricted_survey();
                break;

            case 'PRIVATE':
                $survey = $surveygen->create_survey();
                break;
        }
        $questions = $survey->get_questions();
        $question = reset($questions);

        $options = $question->get_answer_options();
        $option_ids = [];

        foreach ($options as $option) {
            $option_ids[] = $option->id;
        }

        $survey->add_answer($question->get_id(), $option_ids);
        return $survey;
    }
}
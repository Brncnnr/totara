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

use engage_survey\totara_engage\resource\survey;
use totara_engage\answer\answer_type;
use core\webapi\execution_context;
use totara_webapi\graphql;

class engage_survey_create_instance_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_create_instance(): void {
        $this->setAdminUser();
        $data = [
            'questions' => [
                [
                    'value' => 'Hello world ?',
                    'answertype' => answer_type::SINGLE_CHOICE,
                    'options' => ['yes', 'no']
                ]
            ]
        ];

        $resource = survey::create($data);
        $this->assertTrue($resource->is_exists(true));
    }

    /**
     * @return void
     */
    public function test_create_via_graphql(): void {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $data = [
            'questions' => [
                [
                    'value' => 'Is this hello world?',
                    'answertype' => answer_type::SINGLE_CHOICE,
                    'options' => ['Yes', 'No']
                ]
            ]
        ];

        $ec = execution_context::create('ajax', 'engage_survey_create_survey');
        $result = graphql::execute_operation($ec, $data);

        $this->assertEmpty($result->errors);
        $this->assertNotEmpty($result->data);
        $this->assertArrayHasKey('survey', $result->data);

        $survey = $result->data['survey'];
        $id = $survey['id'];

        $sql = '
            SELECT 1 FROM "ttr_engage_survey" es
            INNER JOIN "ttr_engage_resource" er ON es.id = er.instanceid AND er.resourcetype = :component
            WHERE es.id = :surveyid
        ';

        $params = [
            'component' => 'engage_survey',
            'surveyid' => $id
        ];

        $this->assertTrue($DB->record_exists_sql($sql, $params));
    }

    /**
     * @return void
     */
    public function test_survey_question_validation(): void {
        $generator = $this->getDataGenerator();
        $user = $generator->create_user();

        $this->setUser($user);
        $data = [
            'questions' => [
                [
                    'value' => "TfIKQ8IXoycfkcbGaav6B1XVVibwtIYTlyGIOiJukJ4xVOVd4dlbDBnVioSmM5LwdJ7lEv7MCNax",
                    'answertype' => answer_type::SINGLE_CHOICE,
                    'options' => ['yes', 'no']
                ]
            ]
        ];
        $this->assertEquals(
            76,
            core_text::strlen("TfIKQ8IXoycfkcbGaav6B1XVVibwtIYTlyGIOiJukJ4xVOVd4dlbDBnVioSmM5LwdJ7lEv7MCNax")
        );
        $this->expectException('coding_exception');
        $this->expectExceptionMessage("Validation run for property 'questions' has been failed");
        survey::create($data);
    }

    /**
     * @return void
     */
    public function test_survey_answer_validation(): void {
        $generator = $this->getDataGenerator();
        $user = $generator->create_user();

        $this->setUser($user);
        $data = [
            'questions' => [
                [
                    'value' => "Test answer",
                    'answertype' => answer_type::SINGLE_CHOICE,
                    'options' => [
                        'yes',
                        'OoCtvoljRosLba2P8FxNULYk41c6KSdeSGIX3IAj15ayYsbIvS3bSoZubTTwxugQOACkrPMbvHeNmC8E5'
                    ]
                ]
            ]
        ];
        $this->assertEquals(
            81,
            core_text::strlen("OoCtvoljRosLba2P8FxNULYk41c6KSdeSGIX3IAj15ayYsbIvS3bSoZubTTwxugQOACkrPMbvHeNmC8E5")
        );

        $this->expectException('coding_exception');
        $this->expectExceptionMessage("Validation run for property 'questions' has been failed");
        survey::create($data);
    }

}
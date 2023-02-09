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

use core_phpunit\testcase;
use core_user\totara_engage\share\recipient\user;
use totara_core\advanced_feature;
use totara_core\feature_not_available_exception;
use totara_engage\access\access;
use totara_engage\answer\answer_type;
use engage_survey\totara_engage\resource\survey;
use totara_engage\entity\share as share_entity;
use totara_engage\exception\share_exception;
use totara_engage\share\recipient\helper;
use totara_topic\provider\topic_provider;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * Class engage_survey_webapi_update_survey_testcase
 *
 * Note that access-related tests for the same resolver are done in engage_survey_webapi_update_access_testcase.
 */
class engage_survey_webapi_update_survey_testcase extends testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_survey_answer_validation_option_too_long(): void {
        $generator = self::getDataGenerator();
        /** @var \engage_survey\testing\generator $survey_generator */
        $survey_generator = $generator->get_plugin_generator('engage_survey');
        $survey_owner = $generator->create_user();
        self::setUser($survey_owner);

        $survey = $survey_generator->create_survey(null, [], answer_type::MULTI_CHOICE, [
            'access' => access::PUBLIC
        ]);

        $too_long_string = "OoCtvoljRosLba2P8FxNULYk41c6KSdeSGIX3IAj15ayYsbIvS3bSoZubTTwxugQOACkrPMbvHeNmC8E5";
        self::assertEquals(81, core_text::strlen($too_long_string));

        $questions = $survey->get_questions();
        $question = reset($questions);
        $args = [
            'resourceid' => $survey->get_id(),
            'questions' => [
                [
                    'id' => $question->get_id(),
                    'value' => 'Is this not a hello world',
                    'answertype' => answer_type::SINGLE_CHOICE,
                    'options' => [
                        'Yes',
                        $too_long_string
                    ]
                ]
            ],
        ];

        $this->expectException('coding_exception');
        $this->expectExceptionMessage(
            "Coding error detected, it must be fixed by a programmer: Validation run for property 'questions' has been failed"
        );
        $this->resolve_graphql_mutation('engage_survey_update', $args);
    }

    /**
     * @return void
     */
    public function test_survey_question_validation_question_value_too_long(): void {
        $generator = self::getDataGenerator();
        /** @var \engage_survey\testing\generator $survey_generator */
        $survey_generator = $generator->get_plugin_generator('engage_survey');
        $survey_owner = $generator->create_user();
        self::setUser($survey_owner);

        $survey = $survey_generator->create_survey(null, [], answer_type::MULTI_CHOICE, [
            'access' => access::PUBLIC
        ]);

        $too_long_string = "TfIKQ8IXoycfkcbGaav6B1XVVibwtIYTlyGIOiJukJ4xVOVd4dlbDBnVioSmM5LwdJ7lEv7MCNax";
        self::assertEquals(76, core_text::strlen("TfIKQ8IXoycfkcbGaav6B1XVVibwtIYTlyGIOiJukJ4xVOVd4dlbDBnVioSmM5LwdJ7lEv7MCNax"));

        $questions = $survey->get_questions();
        $question = reset($questions);
        $args = [
            'resourceid' => $survey->get_id(),
            'questions' => [
                [
                    'id' => $question->get_id(),
                    'value' => $too_long_string,
                    'answertype' => answer_type::SINGLE_CHOICE,
                    'options' => [
                        'Yes',
                        'No'
                    ]
                ]
            ],
        ];

        $this->expectException('coding_exception');
        $this->expectExceptionMessage(
            "Coding error detected, it must be fixed by a programmer: Validation run for property 'questions' has been failed"
        );
        $this->resolve_graphql_mutation('engage_survey_update', $args);
    }

    public function test_update_survey_with_shares(): void {
        $generator = self::getDataGenerator();
        /** @var \engage_survey\testing\generator $survey_generator */
        $survey_generator = $generator->get_plugin_generator('engage_survey');

        $survey_owner = $generator->create_user();
        $user1 = $generator->create_user();
        $user2 = $generator->create_user();

        self::setUser($survey_owner);
        $survey = $survey_generator->create_survey(null, [], answer_type::MULTI_CHOICE, [
            'access' => access::PUBLIC
        ]);

        $args = [
            'resourceid' => $survey->get_id(),
            'shares' => [
                [
                    'instanceid' => $user1->id,
                    'component' => helper::get_component(user::class),
                    'area' => user::AREA
                ],
                [
                    'instanceid' => $user2->id,
                    'component' => helper::get_component(user::class),
                    'area' => user::AREA
                ],
            ]
        ];

        // Make sure survey has no share recipients before update.
        $repo = share_entity::repository();
        $recipients = $repo->get_recipients($survey->get_id(), 'engage_survey');
        self::assertCount(0, $recipients);

        /** @var survey $survey */
        $survey = $this->resolve_graphql_mutation('engage_survey_update', $args);
        self::assertInstanceOf(survey::class, $survey);

        // Two share recipients expected after update.
        $recipients = $repo->get_recipients($survey->get_id(), 'engage_survey');
        self::assertCount(2, $recipients);
        $share_recipient_user_ids = [];
        foreach ($recipients as $recipient) {
            self::assertEquals($survey_owner->id, $recipient['sharerid']);
            self::assertEquals('core_user', $recipient['component']);
            $share_recipient_user_ids[] = $recipient['instanceid'];
        }
        self::assertEqualsCanonicalizing([$user1->id, $user2->id], $share_recipient_user_ids);
    }

    public function test_update_survey_with_topics(): void {
        self::setAdminUser();
        $generator = self::getDataGenerator();
        /** @var \totara_topic\testing\generator $topic_generator */
        $topic_generator = $generator->get_plugin_generator('totara_topic');
        $topic1 = $topic_generator->create_topic('topic1');
        $topic2 = $topic_generator->create_topic('topic2');

        /** @var \engage_survey\testing\generator $survey_generator */
        $survey_generator = $generator->get_plugin_generator('engage_survey');

        $survey_owner = $generator->create_user();
        self::setUser($survey_owner);
        $survey = $survey_generator->create_survey(null, [], answer_type::MULTI_CHOICE, [
            'access' => access::PUBLIC
        ]);

        $args = [
            'resourceid' => $survey->get_id(),
            'topics' => [$topic1->get_id(), $topic2->get_id()]
        ];

        $topics = topic_provider::get_for_item($survey->get_id(), 'engage_survey', 'survey');
        self::assertCount(0, $topics);

        /** @var survey $survey */
        $survey = $this->resolve_graphql_mutation('engage_survey_update', $args);
        self::assertInstanceOf(survey::class, $survey);

        $topics = topic_provider::get_for_item($survey->get_id(), 'engage_survey', 'engage_resource');
        self::assertCount(2, $topics);
        self::assertEqualsCanonicalizing(
            [$topic1->get_id(), $topic2->get_id()],
            [$topics[0]->get_id(), $topics[1]->get_id()]
        );
    }

    public function test_update_survey_with_questions(): void {
        $generator = self::getDataGenerator();
        /** @var \engage_survey\testing\generator $survey_generator */
        $survey_generator = $generator->get_plugin_generator('engage_survey');

        $survey_owner = $generator->create_user();
        self::setUser($survey_owner);
        $survey = $survey_generator->create_survey(null, [], answer_type::MULTI_CHOICE, [
            'access' => access::PUBLIC
        ]);

        // Update the question that was created by the generator.
        $questions = $survey->get_questions();
        $question = reset($questions);
        $args = [
            'resourceid' => $survey->get_id(),
            'questions' => [
                [
                    'id' => $question->get_id(),
                    'value' => 'New question text',
                    'answertype' => answer_type::SINGLE_CHOICE,
                    'options' => [
                        'New option 1',
                        'New option 2',
                    ]
                ]
            ],
        ];

        /** @var survey $survey */
        $survey = $this->resolve_graphql_mutation('engage_survey_update', $args);
        self::assertInstanceOf(survey::class, $survey);

        // Refresh survey to make sure we get values from DB.
        $survey = survey::from_resource_id($survey->get_id());

        $questions = $survey->get_questions();
        $question = reset($questions);

        self::assertEquals('New question text', $question->get_value());
        $options = $question->get_answer_options();
        self::assertEqualsCanonicalizing(
            ['New option 1', 'New option 2'],
            [$options[0]->value, $options[1]->value]
        );
    }

    public function test_update_survey_uses_middleware_require_login(): void {
        $generator = self::getDataGenerator();
        /** @var \engage_survey\testing\generator $survey_generator */
        $survey_generator = $generator->get_plugin_generator('engage_survey');
        $survey_owner = $generator->create_user();
        self::setUser($survey_owner);
        $survey = $survey_generator->create_survey(null, [], answer_type::MULTI_CHOICE, [
            'access' => access::PUBLIC
        ]);

        self::setUser();
        $this->expectException(require_login_exception::class);
        $this->expectExceptionMessage('Course or activity not accessible. (You are not logged in)');
        $this->resolve_graphql_mutation('engage_survey_update', [ 'resourceid' => $survey->get_id() ]);
    }

    public function test_update_survey_uses_middleware_require_advanced_feature(): void {
        $generator = self::getDataGenerator();
        /** @var \engage_survey\testing\generator $survey_generator */
        $survey_generator = $generator->get_plugin_generator('engage_survey');
        $survey_owner = $generator->create_user();
        self::setUser($survey_owner);
        $survey = $survey_generator->create_survey(null, [], answer_type::MULTI_CHOICE, [
            'access' => access::PUBLIC
        ]);

        advanced_feature::disable('engage_resources');
        $this->expectException(feature_not_available_exception::class);
        $this->expectExceptionMessage('Feature engage_resources is not available.');
        $this->resolve_graphql_mutation('engage_survey_update', [ 'resourceid' => $survey->get_id() ]);
    }

    public function test_update_survey_uses_middleware_require_valid_recipients(): void {
        $generator = self::getDataGenerator();
        /** @var \engage_survey\testing\generator $survey_generator */
        $survey_generator = $generator->get_plugin_generator('engage_survey');
        $survey_owner = $generator->create_user();
        
        // Make recipient invalid by setting confirmed to false.
        $user1 = $generator->create_user(['confirmed' => 0]);

        self::setUser($survey_owner);
        $survey = $survey_generator->create_survey(null, [], answer_type::MULTI_CHOICE, [
            'access' => access::PUBLIC
        ]);

        $args = [
            'resourceid' => $survey->get_id(),
            'shares' => [
                [
                    'instanceid' => $user1->id,
                    'component' => helper::get_component(user::class),
                    'area' => user::AREA
                ],
            ]
        ];

        $this->expectException(share_exception::class);
        $this->expectExceptionMessage('Invalid recipient');
        $this->resolve_graphql_mutation('engage_survey_update', $args);
    }
}
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
 * @package engage_survey
 */
defined('MOODLE_INTERNAL') || die();

use totara_webapi\phpunit\webapi_phpunit_helper;
use totara_engage\access\access;
use engage_survey\totara_engage\resource\survey;

class engage_survey_webapi_update_access_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_update_survey_access_without_user_in_session(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        /** @var \engage_survey\testing\generator $survey_generator */
        $survey_generator = $generator->get_plugin_generator('engage_survey');

        $this->setUser($user_one);
        $survey = $survey_generator->create_survey();

        // Unset user so that we can check the graphql.
        $this->setUser(null);

        $result = $this->execute_graphql_operation(
            'engage_survey_update_access',
            [
                'resourceid' => $survey->get_id(),
                'access' => access::get_code(access::PUBLIC),
                'topics' => []
            ]
        );

        $this->assertEmpty($result->data);
        $this->assertNotEmpty($result->errors);

        $this->assertIsArray($result->errors);
        $this->assertCount(1, $result->errors);

        $error = reset($result->errors);

        $this->assertIsObject($error);
        $this->assertObjectHasAttribute('message', $error);

        $this->assertSame(
            "Course or activity not accessible. (You are not logged in)",
            $error->getMessage()
        );
    }

    /**
     * @return void
     */
    public function test_update_survey_access_with_invalid_survey_id(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);
        $result = $this->execute_graphql_operation(
            'engage_survey_update_access',
            [
                'resourceid' => 42,
                'access' => access::get_code(access::PUBLIC),
                'topics' => []
            ]
        );

        $this->assertEmpty($result->data);
        $this->assertNotEmpty($result->errors);

        $this->assertIsArray($result->errors);
        $this->assertCount(1, $result->errors);

        $error = reset($result->errors);

        $this->assertIsObject($error);
        $this->assertObjectHasAttribute('message', $error);

        $this->assertStringContainsString(
            "Can not find data record in database.",
            $error->getMessage()
        );
    }

    /**
     * @return void
     */
    public function test_update_survey_access_by_different_user(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        // Log in as user one and start creating a survey.
        $this->setUser($user_one);

        /** @var \engage_survey\testing\generator $survey_generator */
        $survey_generator = $generator->get_plugin_generator('engage_survey');
        $survey = $survey_generator->create_survey();

        $this->assertTrue($survey->is_private());
        $this->assertFalse($survey->is_public());
        $this->assertFalse($survey->is_restricted());

        // Log in as user two and run upgrade access.
        $user_two = $generator->create_user();
        $this->setUser($user_two);

        $result = $this->execute_graphql_operation(
            'engage_survey_update_access',
            [
                'resourceid' => $survey->get_id(),
                'access' => access::get_code(access::PUBLIC),
                'topics' => []
            ]
        );

        $this->assertEmpty($result->data);
        $this->assertNotEmpty($result->errors);
        $this->assertIsArray($result->errors);
        $this->assertCount(1, $result->errors);

        $error = reset($result->errors);
        $this->assertIsObject($error);
        $this->assertObjectHasAttribute('message', $error);
        $this->assertEquals('Cannot update the survey', $error->getMessage());

        $updated_survey = survey::from_resource_id($survey->get_id());

        $this->assertTrue($updated_survey->is_private());
        $this->assertFalse($updated_survey->is_public());
        $this->assertFalse($updated_survey->is_restricted());
    }

    /**
     * @return void
     */
    public function test_update_survey_access_as_same_user(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        // Log in as first user and create a private survey
        $this->setUser($user_one);

        /** @var \engage_survey\testing\generator $survey_generator */
        $survey_generator = $generator->get_plugin_generator('engage_survey');
        $survey = $survey_generator->create_survey();

        $this->assertFalse($survey->is_public());
        $this->assertTrue($survey->is_private());
        $this->assertFalse($survey->is_restricted());

        /** @var \totara_topic\testing\generator  $topic_generator */
        $this->setAdminUser();
        $topic_generator = $generator->get_plugin_generator('totara_topic');
        $topic_one = $topic_generator->create_topic();

        // Log back in as first user an run update.
        $this->setUser($user_one);
        $result = $this->execute_graphql_operation(
            'engage_survey_update_access',
            [
                'resourceid' => $survey->get_id(),
                'access' => access::get_code(access::PUBLIC),
                'topics' => [$topic_one->get_id()]
            ]
        );

        $this->assertNotEmpty($result->data);
        $this->assertEmpty($result->errors);

        $updated_survey = survey::from_resource_id($survey->get_id());

        $this->assertTrue($updated_survey->is_public());
        $this->assertFalse($updated_survey->is_private());
        $this->assertFalse($updated_survey->is_restricted());
    }

    /**
     * @return void
     */
    public function test_update_survey_access_as_site_admin(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        // Log in as first user and create a survey.
        $this->setUser($user_one);

        /** @var \engage_survey\testing\generator $survey_generator */
        $survey_generator = $generator->get_plugin_generator('engage_survey');
        $survey = $survey_generator->create_survey();

        $this->assertFalse($survey->is_public());
        $this->assertTrue($survey->is_private());
        $this->assertFalse($survey->is_restricted());

        // Log in as admin and start updating the survey.
        $this->setAdminUser();

        /** @var \totara_topic\testing\generator $topic_generator */
        $topic_generator = $generator->get_plugin_generator('totara_topic');
        $topic_one = $topic_generator->create_topic();

        $result = $this->execute_graphql_operation(
            'engage_survey_update_access',
            [
                'resourceid' => $survey->get_id(),
                'access' => access::get_code(access::PUBLIC),
                'topics' => [$topic_one->get_id()]
            ]
        );

        $this->assertNotEmpty($result->data);
        $this->assertEmpty($result->errors);

        $updated_survey = survey::from_resource_id($survey->get_id());

        $this->assertTrue($updated_survey->is_public());
        $this->assertFalse($updated_survey->is_private());
        $this->assertFalse($updated_survey->is_restricted());
    }

    /**
     * @return void
     */
    public function test_update_survey_access_from_public_to_private(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \engage_survey\testing\generator $survey_generator */
        $survey_generator = $generator->get_plugin_generator('engage_survey');
        $survey = $survey_generator->create_public_survey();

        $this->assertTrue($survey->is_public());
        $this->assertFalse($survey->is_private());
        $this->assertFalse($survey->is_restricted());

        // Update from public to private.
        $result = $this->execute_graphql_operation(
            'engage_survey_update_access',
            [
                'resourceid' => $survey->get_id(),
                'access' => access::get_code(access::PRIVATE),
                'topics' => []
            ]
        );

        $survey->refresh();
        $this->assertTrue($survey->is_public());
        $this->assertFalse($survey->is_private());
        $this->assertFalse($survey->is_restricted());

        $this->assertEmpty($result->data);
        $this->assertNotEmpty($result->errors);

        $this->assertIsArray($result->errors);
        $this->assertCount(1, $result->errors);

        $error = reset($result->errors);
        $this->assertIsObject($error);
        $this->assertSame("Cannot update access of a resource 'engage_survey'", $error->getMessage());
    }
}
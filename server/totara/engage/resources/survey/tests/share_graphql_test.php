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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package engage_survey
 */

defined('MOODLE_INTERNAL') || die();

use totara_engage\access\access;
use core_user\totara_engage\share\recipient\user as user_recipient;
use engage_survey\totara_engage\resource\survey;
use core\webapi\execution_context;
use totara_webapi\graphql;
use totara_engage\answer\answer_type;
use totara_engage\share\recipient\helper as recipient_helper;

class engage_survey_share_graphql_testcase extends advanced_testcase {

    /**
     * Validate the following:
     *   1. We can share a survey using the graphql query.
     */
    public function test_share_item() {
        $gen = $this->getDataGenerator();
        /** @var \totara_playlist\testing\generator $playlistgen */
        $surveygen = $gen->get_plugin_generator('engage_survey');

        // Create users.
        $users = $surveygen->create_users(3);

        // Create survey.
        $this->setUser($users[0]);
        $survey = $surveygen->create_survey(null, [], answer_type::MULTI_CHOICE, [
            'access' => access::PUBLIC
        ]);

        // Set capabilities for all users.
        foreach ($users as $user) {
            $surveygen->set_capabilities(CAP_ALLOW, $user->id, $survey->get_context());
        }

        // Set user to someone other than the owner of the survey.
        $this->setUser($users[1]);

        // Create share via graphql.
        $ec = execution_context::create('ajax', 'totara_engage_share');
        $parameters = [
            'itemid' => $survey->get_id(),
            'component' => survey::get_resource_type(),
            'recipients' => [
                [
                    'instanceid' => $users[2]->id,
                    'component' => recipient_helper::get_component(user_recipient::class),
                    'area' => user_recipient::AREA
                ]
            ]
        ];

        $result = graphql::execute_operation($ec, $parameters);
        $this->assertEmpty($result->errors, !empty($result->errors) ? $result->errors[0]->message: '');
        $this->assertNotEmpty($result->data);
        $this->assertArrayHasKey('shares', $result->data);

        $shares = $result->data['shares'];
        $this->assertArrayHasKey('sharedbycount', $shares);
        $this->assertEquals(1, $shares['sharedbycount']);
    }

    /**
     * Validate the following:
     *   1. We can share a survey during creation.
     */
    public function test_survey_create() {
        $gen = $this->getDataGenerator();
        /** @var \totara_playlist\testing\generator $playlistgen */
        $surveygen = $gen->get_plugin_generator('engage_survey');

        // Create users.
        $users = $surveygen->create_users(2);

        // Set capabilities for all users.
        foreach ($users as $user) {
            $surveygen->set_capabilities(CAP_ALLOW, $user->id, context_system::instance());
        }

        // Set owner of survey.
        $this->setUser($users[0]);

        // Create share via graphql.
        $ec = execution_context::create('ajax', 'engage_survey_create_survey');
        $parameters = [
            'questions' => [
                [
                    'value' => 'This or that?',
                    'answertype' => answer_type::SINGLE_CHOICE,
                    'options' => ['this', 'that'],
                ]
            ],
            'access' => 'RESTRICTED',
            'shares' => [
                [
                    'instanceid' => $users[1]->id,
                    'component' => recipient_helper::get_component(user_recipient::class),
                    'area' => user_recipient::AREA
                ]
            ]
        ];

        $result = graphql::execute_operation($ec, $parameters);
        $this->assertEmpty($result->errors, !empty($result->errors) ? $result->errors[0]->message: '');
        $this->assertNotEmpty($result->data);
        $this->assertArrayHasKey('survey', $result->data);

        $survey = $result->data['survey'];
        $this->assertArrayHasKey('sharedbycount', $survey);
        $this->assertEquals(0, $survey['sharedbycount']);
    }

    /**
     * Validate the following:
     *   1. We can share a survey during update.
     */
    public function test_survey_update() {
        $gen = $this->getDataGenerator();
        /** @var \totara_playlist\testing\generator $playlistgen */
        $surveygen = $gen->get_plugin_generator('engage_survey');

        // Create users.
        $users = $surveygen->create_users(2);

        // Create survey.
        $this->setUser($users[0]);
        $survey = $surveygen->create_survey(null, [], answer_type::MULTI_CHOICE, [
            'access' => access::PUBLIC
        ]);

        // Set capabilities for all users.
        foreach ($users as $user) {
            $surveygen->set_capabilities(CAP_ALLOW, $user->id, $survey->get_context());
        }

        // Create share via graphql.
        $ec = execution_context::create('ajax', 'engage_survey_update_survey');
        $parameters = [
            'resourceid' => $survey->get_id(),
            'shares' => [
                [
                    'instanceid' => $users[1]->id,
                    'component' => recipient_helper::get_component(user_recipient::class),
                    'area' => user_recipient::AREA
                ]
            ]
        ];

        $result = graphql::execute_operation($ec, $parameters);
        $this->assertEmpty($result->errors, !empty($result->errors) ? $result->errors[0]->message: '');
        $this->assertNotEmpty($result->data);
        $this->assertArrayHasKey('survey', $result->data);

        $survey = $result->data['survey'];
        $this->assertArrayHasKey('sharedbycount', $survey);
        $this->assertEquals(0, $survey['sharedbycount']);
    }

    /**
     * Validate the following:
     *   1. We can query share totals via graphql.
     */
    public function test_share_totals() {
        $gen = $this->getDataGenerator();
        /** @var \totara_playlist\testing\generator $playlistgen */
        $surveygen = $gen->get_plugin_generator('engage_survey');

        // Create users.
        $users = $surveygen->create_users(2);
        $this->setUser($users[1]);

        // Create survey.
        $survey = $surveygen->create_survey(null, [], answer_type::MULTI_CHOICE, [
            'access' => access::RESTRICTED
        ]);

        // Set capabilities for all users.
        foreach ($users as $user) {
            $surveygen->set_capabilities(CAP_ALLOW, $user->id, $survey->get_context());
        }

        // Share survey.
        $recipients = $surveygen->create_user_recipients([$users[0]]);
        $surveygen->share_survey($survey, $recipients);

        // Get share totals.
        $ec = execution_context::create('ajax', 'totara_engage_share_totals');
        $parameters = [
            'itemid' => $survey->get_id(),
            'component' => survey::get_resource_type()
        ];

        $result = graphql::execute_operation($ec, $parameters);
        $this->assertEmpty($result->errors, !empty($result->errors) ? $result->errors[0]->message: '');
        $this->assertNotEmpty($result->data);
        $this->assertArrayHasKey('shares', $result->data);

        $shares = $result->data['shares'];
        $this->assertEquals(1, $shares['totalrecipients']);

        $this->assertArrayHasKey('recipients', $shares);
        $recipients = $shares['recipients'];
        $this->assertEquals(1, sizeof($recipients));

        $recipient = reset($recipients);
        $this->assertEquals(user_recipient::AREA, $recipient['area']);
        $this->assertEquals(1, $recipient['total']);
    }

    /**
     * Validate the following:
     *   1. We can query recipients of a specific shared item.
     */
    public function test_recipients() {
        $gen = $this->getDataGenerator();
        /** @var \totara_playlist\testing\generator $playlistgen */
        $surveygen = $gen->get_plugin_generator('engage_survey');

        // Create users.
        $users = $surveygen->create_users(2);
        $this->setUser($users[1]);

        // Create survey.
        $survey = $surveygen->create_survey(access::PUBLIC);

        // Set capabilities for all users.
        foreach ($users as $user) {
            $surveygen->set_capabilities(CAP_ALLOW, $user->id, $survey->get_context());
        }

        // Share survey.
        $recipients = $surveygen->create_user_recipients([$users[0]]);
        $surveygen->share_survey($survey, $recipients);

        // Switch to admin user to not be blocked by privacy checks.
        $this->setUser(2);

        // Get recipients.
        $ec = execution_context::create('ajax', 'totara_engage_share_recipients');
        $parameters = [
            'itemid' => $survey->get_id(),
            'component' => survey::get_resource_type(),
            'theme' => 'ventura',
        ];

        $result = graphql::execute_operation($ec, $parameters);
        $this->assertEmpty($result->errors, !empty($result->errors) ? $result->errors[0]->message: '');
        $this->assertNotEmpty($result->data);
        $this->assertArrayHasKey('recipients', $result->data);

        $recipients = $result->data['recipients'];
        $this->assertNotEmpty($recipients);
        $this->assertEquals(1, sizeof($recipients));
        $recipient = reset($recipients);
        $this->assertArrayHasKey('user', $recipient);
        $user = $recipient['user'];
        $this->assertArrayHasKey('fullname', $user);
        $this->assertEquals('Some1 Any1', $user['fullname']);
    }
}
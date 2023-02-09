<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_approval
 */

use core\date_format;
use core\entity\user;
use core_phpunit\testcase;
use mod_approval\model\application\application;
use mod_approval\testing\approval_workflow_test_setup;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\webapi\resolver\type\application_submission
 */
class mod_approval_webapi_type_application_submission_testcase extends testcase {

    use webapi_phpunit_helper;
    use approval_workflow_test_setup;

    private const TYPE = 'mod_approval_application_submission';

    public function setUp(): void {
        parent::setUp();
    }

    public function tearDown(): void {
        parent::tearDown();
    }

    /**
     * Gets the approval workflow generator instance
     *
     * @return \mod_approval\testing\generator
     */
    protected function generator(): \mod_approval\testing\generator {
        return \mod_approval\testing\generator::instance();
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve(): void {
        list($workflow, $framework, $assignment) = $this->create_workflow_and_assignment();
        $user1 = $this->getDataGenerator()->create_user(['firstname' => 'Tommy', 'lastname' => 'Tom', 'middlename' => '']);
        $application = $this->create_submitted_application($workflow, $assignment, new user($user1));
        $context = $application->get_context();
        $this->setUser($user1);
        $this->insert_application_submissions($application, '{"agency_code" :    "what?"}');
        $submission = $application->last_submission;
        $value = $this->resolve_graphql_type(self::TYPE, 'id', $submission, [], $context);
        $this->assertEquals($submission->id, $value);
        $value = $this->resolve_graphql_type(self::TYPE, 'submitted', $submission, ['format' => date_format::FORMAT_ISO8601], $context);
        $this->assertEquals('2021-04-05T13:06:07+0800', $value);
        $value = $this->resolve_graphql_type(self::TYPE, 'form_data', $submission, [], $context);
        $this->assertEquals('{"agency_code" :    "what?"}', $value);
        $value = $this->resolve_graphql_type(self::TYPE, 'user', $submission, [], $context);
        $this->assertEquals('Tommy Tom', $value->fullname);
    }

    /**
     * @param application $application
     * @param string $form_data
     */
    private function insert_application_submissions(application $application, string $form_data): void {
        global $DB;
        /** @var moodle_database $DB */
        $DB->insert_records('approval_application_submission', [
            [
                'application_id' => $application->id,
                'user_id' => user::logged_in()->id,
                'workflow_stage_id' => $application->current_state->get_stage_id(),
                'superseded' => true,
                'form_data' => $form_data,
                'created' => strtotime('2021-01-01T01:02:03Z'),
                'submitted' => strtotime('2021-01-02T02:03:04Z'),
                'updated' => strtotime('2021-01-03T03:04:05Z'),
            ],
            [
                'application_id' => $application->id,
                'user_id' => user::logged_in()->id,
                'workflow_stage_id' => $application->current_state->get_stage_id(),
                'superseded' => false,
                'form_data' => $form_data,
                'created' => strtotime('2021-04-04T04:05:06Z'),
                'submitted' => strtotime('2021-04-05T05:06:07Z'),
                'updated' => strtotime('2021-04-06T06:07:08Z'),
            ]
        ]);
        $application->refresh(true);
    }

}

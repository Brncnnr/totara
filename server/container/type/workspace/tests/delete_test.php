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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package container_workspace
 */

use container_workspace\local\workspace_helper;
use container_workspace\task\delete_workspace_task;
use container_workspace\tracker\tracker;
use core\orm\query\builder;
use core\webapi\execution_context;
use totara_core\advanced_feature;
use totara_webapi\graphql;
use totara_webapi\phpunit\webapi_phpunit_helper;
use container_workspace\event\workspace_deleted;
use container_workspace\totara_engage\share\recipient\library;

defined('MOODLE_INTERNAL') || die();

class container_workspace_delete_testcase extends advanced_testcase {
    private const MUTATION = 'container_workspace_delete_workspace';

    use webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_delete_workspace(): void {
        global $DB, $USER;
        $this->setAdminUser();

        $workspace = workspace_helper::create_workspace('Workspace 1010', $USER->id);

        $sql = '
            SELECT 1 FROM "ttr_course" c
            INNER JOIN "ttr_workspace" wo ON wo.course_id = c.id
            WHERE c.id = :course_id
        ';

        $this->assertTrue($DB->record_exists_sql($sql, ['course_id' => $workspace->id]));

        $workspace->mark_to_be_deleted(true);
        workspace_helper::delete_workspace($workspace, $USER->id);
        $this->assertFalse($DB->record_exists_sql($sql, ['course_id' => $workspace->id]));
    }

    /**
     * @return void
     */
    public function test_delete_workspace_via_graphql(): void {
        global $DB;
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $workspace = workspace_helper::create_workspace('Workspace 1010', $user->id);

        $tracker = new tracker($user->id);
        $tracker->visit_workspace($workspace);
        $this->assertEquals($workspace->id, $tracker->get_last_visit_workspace(), 'wrong visited workspace');
        $this->assertEquals(0, $this->get_queued_delete_task_count());

        $ec = execution_context::create('ajax', self::MUTATION);

        // We will call the endpoint multiple times, but the task will only ever be queued once.
        for ($i = 0; $i <= 2; $i++) {
            $result = graphql::execute_operation(
                $ec,
                ['workspace_id' => $workspace->get_id()]
            );

            $this->assertEmpty($result->errors);
            $this->assertNotEmpty($result->data);
            $this->assertEquals(1, $this->get_queued_delete_task_count());

            $sql = '
                SELECT 1 FROM "ttr_course" c
                INNER JOIN "ttr_workspace" wo ON wo.course_id = c.id
                WHERE c.id = :course_id
                AND wo.to_be_deleted = 0
            ';

            $this->assertFalse($DB->record_exists_sql($sql, ['course_id' => $workspace->id]));
            $this->assertNull($tracker->get_last_visit_workspace(), 'wrong visited workspace');
        }
    }

    /**
     * @return void
     */
    public function test_failed_graphql_call(): void {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $workspace = workspace_helper::create_workspace('Workspace 1010', $user->id);
        $args = ['workspace_id' => $workspace->get_id()];

        $feature = 'container_workspace';
        advanced_feature::disable($feature);
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed($result, "Feature $feature is not available.");
        advanced_feature::enable($feature);

        $result = $this->parsed_graphql_operation(self::MUTATION, []);
        $this->assert_webapi_operation_failed($result, 'Variable "$workspace_id" of required type "param_integer!" was not provided.');

        $args['workspace_id'] = 1293;
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed($result, 'Can not find data record in database');

        $user1 = $this->getDataGenerator()->create_user();
        self::setUser($user1);
        $tracker = new tracker($user1->id);
        $tracker->visit_workspace($workspace);
        $this->assertEquals($workspace->id, $tracker->get_last_visit_workspace(), 'wrong visited workspace');

        $args = ['workspace_id' => $workspace->get_id()];
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed($result, get_string('error:delete_workspace', 'container_workspace'));
        $this->assertEquals($workspace->id, $tracker->get_last_visit_workspace(), 'wrong visited workspace');
    }

    /**
     * @return void
     */
    public function test_delete_workspace_that_trigger_event(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();
        $workspace->mark_to_be_deleted(true);

        $event_sink = $this->redirectEvents();
        self::assertEmpty($event_sink->get_events());

        $workspace_id = $workspace->get_id();
        self::assertTrue($DB->record_exists('course', ['id' => $workspace_id]));

        workspace_helper::delete_workspace($workspace);
        self::assertFalse($DB->record_exists('course', ['id' => $workspace_id]));

        $events = $event_sink->get_events();
        self::assertNotEmpty($events);

        // 3 events in total - because the first 2 events are for deleting the enrol instance.
        self::assertCount(3, $events);

        // The last event is about workspace deleted event.
        $deleted_event = end($events);

        self::assertInstanceOf(workspace_deleted::class, $deleted_event);
        self::assertEquals($user_one->id, $deleted_event->userid);
    }

    /**
     * @return void
     */
    public function test_delete_workspace_should_also_remove_the_recipient_records(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        /** @var \engage_article\testing\generator $article_generator */
        $article_generator = $generator->get_plugin_generator('engage_article');
        $article = $article_generator->create_public_article();

        $library = new library($workspace->get_id());
        $shares = $article_generator->share_article($article, [$library]);

        self::assertNotEmpty($shares);
        self::assertCount(1, $shares);

        $share = reset($shares);

        self::assertTrue($DB->record_exists('engage_share_recipient', ['id' => $share->get_recipient_id()]));
        self::assertTrue($DB->record_exists('course', ['id' => $workspace->get_id()]));

        // Deleting the workspace should delete the recipient.
        $workspace->mark_to_be_deleted(true);
        workspace_helper::delete_workspace($workspace);

        self::assertFalse($DB->record_exists('engage_share_recipient', ['id' => $share->get_recipient_id()]));
        self::assertFalse($DB->record_exists('course', ['id' => $workspace->get_id()]));
    }

    private function get_queued_delete_task_count(): int {
        return builder::table('task_adhoc')->where('classname', '\\' . delete_workspace_task::class)->count();
    }

}
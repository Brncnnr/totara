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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package container_workspace
 * @category test
 */

use container_workspace\event\audience_added;
use core\collection;
use core\entity\cohort;
use core\event\base;
use core\task\manager;
use core\entity\adhoc_task as adhoc_task_entity;
use core\entity\cohort_member as cohort_member_entity;
use container_workspace\loader\member\loader;
use container_workspace\exception\enrol_exception;
use container_workspace\member\member;
use container_workspace\query\member\query;
use container_workspace\task\bulk_add_workspace_members_adhoc_task as task;

/**
 * @group container_workspace
 */
class container_workspace_bulk_add_workspace_members_adhoc_task_testcase extends advanced_testcase {
    /**
     * @dataProvider enqueue_invalid_data
     */
    public function test_enqueue_invalid_data(int $workspace_id, array $cohort_ids, string $error): void {
        try {
            task::enqueue($workspace_id, $cohort_ids);
        } catch (coding_exception $exception) {
            $this->assertInstanceOf(coding_exception::class, $exception);
            $this->assertStringContainsString($error, $exception->getMessage());
        }
        $this->assertDebuggingCalledCount(1);
    }

    /**
     * Data provider for test_enqueue_invalid_data
     */
    public function enqueue_invalid_data(): array {
        $workspace_error = 'invalid workspace id';
        $cohort_error = 'invalid cohort ids';

        return [
            'invalid_workspace' => [-100, [1, 2, 3], $workspace_error],
            'invalid_workspace_and_cohorts' => [0, ['a', 'b'], $workspace_error],
            'invalid_cohorts' => [20, ['a', 'b'], $cohort_error],
            'empty_cohorts' => [20, [], $cohort_error]
        ];
    }

    /**
     * @return void
     */
    public function test_enqueue_with_no_other_task_enqueued(): void {
        $test_data = $this->create_test_data();
        $workspace_id = $test_data->workspace->id;
        $cohort_ids = $test_data->cohort_ids;

        // Since there are no other tasks, the enqueuing should always create a
        // new task entry with valid data.
        $this->assertEquals(0, $this->all_tasks()->count());
        $task_id = task::enqueue($workspace_id, $cohort_ids->all());
        $tasks = $this->all_tasks();
        $this->assertEquals(1, $tasks->count());

        $task = $tasks->first();
        $this->assertEquals($task->get_id(), $task_id);

        $expected_data = (object)$this->task_data($workspace_id, $cohort_ids);
        $this->assertEquals($expected_data, $task->get_custom_data());
        $this->assertDebuggingCalledCount(1);
    }

    /**
     * @return void
     */
    public function test_enqueue_with_duplicate_cohorts(): void {
        $test_data = $this->create_test_data(3);
        $workspace_id = $test_data->workspace->id;
        $cohort_ids = $test_data->cohort_ids;

        $cohorts_1 = $cohort_ids->all();
        $extra_cohort = array_pop($cohorts_1); // $cohorts_1 now has one less cohort.

        // Adding a list of cohorts when there are no existing tasks should
        // create a new task entry. Duplicate cohorts should be filtered out
        // (this is verified later on).
        $cohorts_1_duplicated = array_merge($cohorts_1, $cohorts_1);
        $task_id_1 = task::enqueue($workspace_id, $cohorts_1_duplicated);
        $this->assertEquals(1, $this->all_tasks()->count());

        // Adding a list of cohorts that have already been registered should not
        // create a new task entry.
        $result = task::enqueue($workspace_id, $cohorts_1);
        $this->assertEquals(task::ENQUEUE_NO_COHORTS_TO_ADD, $result);
        $this->assertEquals(1, $this->all_tasks()->count());

        // However if there are "new" cohorts to be added, then there should be
        // a new task created but only registering those new cohorts. Even if
        // new cohorts are repeated, these should be filtered out.
        $cohorts_2 = $cohort_ids->all();
        $cohorts_2_duplicated = array_merge($cohorts_2, $cohorts_2);
        $task_id_2 = task::enqueue($workspace_id, $cohorts_2_duplicated);

        $expected_tasks = [
            $task_id_1 => $cohorts_1,
            $task_id_2 => [$extra_cohort]
        ];

        $tasks = $this->all_tasks();
        $this->assertEquals(count($expected_tasks), $tasks->count());

        foreach ($tasks as $task) {
            $task_id = $task->get_id();

            $expected_cohort_ids = $expected_tasks[$task_id] ?? [];
            $expected_data = (object)$this->task_data(
                $workspace_id,
                collection::new($expected_cohort_ids)
            );
            $this->assertEquals($expected_data, $task->get_custom_data());
        }
        $this->assertDebuggingCalledCount(3);
    }

    /**
     * @dataProvider execute_invalid_task_data
     */
    public function test_execute_invalid_task_data(array $task_data, int $expected): void {
        $result = $this->get_enqueued_task($task_data)->execute();
        $this->assertEquals($expected, $result);
        $this->assertDebuggingCalledCount(1);
    }

    /**
     * Data provider for test_execute_invalid_task_data
     */
    public function execute_invalid_task_data(): array {
        $cohort_ids = collection::new([1, 2, 3]);

        return [
            'no_data' => [
                [],
                task::EXECUTE_INVALID_WORKSPACE_IN_TASK_DATA
            ],

            'invalid_workspace' => [
                $this->task_data(-123, $cohort_ids),
                task::EXECUTE_INVALID_WORKSPACE_IN_TASK_DATA
            ],

            'invalid_workspace_type' => [
                [task::WORKSPACE_ID => 'a', task::COHORT_IDS => $cohort_ids],
                task::EXECUTE_INVALID_WORKSPACE_IN_TASK_DATA
            ],

            'missing_workspace_key' => [
                [task::COHORT_IDS => $cohort_ids],
                task::EXECUTE_INVALID_WORKSPACE_IN_TASK_DATA
            ],

            'invalid_cohort_value' => [
                [task::WORKSPACE_ID => 123, task::COHORT_IDS => collection::new(['a'])],
                task::EXECUTE_INVALID_COHORTS_IN_TASK_DATA
            ],

            'invalid_cohort_type' => [
                [task::WORKSPACE_ID => 123, task::COHORT_IDS => "abs"],
                task::EXECUTE_INVALID_COHORTS_IN_TASK_DATA
            ],

            'missing_cohorts_key' => [
                [task::WORKSPACE_ID => 123],
                task::EXECUTE_INVALID_COHORTS_IN_TASK_DATA
            ],

            'missing_workspace' => [
                $this->task_data(123, $cohort_ids),
                task::EXECUTE_WORKSPACE_DOES_NOT_EXIST
            ]
        ];
    }

    /**
     * @return void
     */
    public function test_execute_with_cohorts_removed_after_enqueuing(): void {
        $test_data = $this->create_test_data();

        $workspace_id = $test_data->workspace->id;
        $original_members = [$test_data->owner_id];
        $this->assert_workspace_members($workspace_id, $original_members);

        $task_data = $this->task_data($workspace_id, collection::new([1, 2, 3]));
        $added_member_count = $this->get_enqueued_task($task_data)->execute();
        $this->assertEquals(0, $added_member_count);
        $this->assert_workspace_members($workspace_id, $original_members);
        $this->assertDebuggingCalledCount(8);
    }

    /**
     * @return void
     */
    public function test_execute_add_to_empty_workspace_with_unique_cohort_members(): void {
        $user_count = 2;
        $test_data = $this->create_test_data($user_count, $user_count);

        // Each cohort has only one but unique member.
        $cohort_ids = $test_data->cohort_ids;
        $user_ids = $test_data->user_ids;
        foreach ($cohort_ids as $i => $cohort_id) {
            $user_id = $user_ids->item($i);
            cohort_add_member($cohort_id, $user_id);

            $this->assert_cohort_members($cohort_id, [$user_id]);
        }

        // The workspace originally has only 1 member - the owner.
        $workspace_id = $test_data->workspace->id;
        $original_members = [$test_data->owner_id];
        $this->assert_workspace_members($workspace_id, $original_members);

        // Since all the users in the cohorts are unique, all of them should be
        // added to the workspace.
        $task_data = $this->task_data($workspace_id, $cohort_ids);
        $added_member_count = $this->get_enqueued_task($task_data)->execute();
        $this->assertEquals($user_count, $added_member_count);

        $updated_members = array_merge($original_members, $user_ids->all());
        $this->assert_workspace_members($workspace_id, $updated_members);
        $this->assertDebuggingCalledCount(11);
    }

    /**
     * @return void
     */
    public function test_execute_add_to_empty_workspace_with_duplicate_cohort_members(): void {
        $cohort_count = 2;
        $test_data = $this->create_test_data($cohort_count, $cohort_count + 5);

        $user_ids = $test_data->user_ids;
        $common_users = $user_ids->all();

        // Each cohort has 1 unique member; the rest are common.
        $unique_cohort_members = [];
        $cohort_ids = $test_data->cohort_ids;
        foreach ($cohort_ids as $cohort_id) {
            // Note common_users will have one less user after each round.
            $unique_cohort_members[$cohort_id] = array_pop($common_users);
        }

        foreach ($cohort_ids as $cohort_id) {
            $cohort_unique_member = $unique_cohort_members[$cohort_id];
            $cohort_members = array_merge($common_users, [$cohort_unique_member]);

            foreach ($cohort_members as $user_id) {
                cohort_add_member($cohort_id, $user_id);
            }

            $this->assert_cohort_members($cohort_id, $cohort_members);
        }

        // The workspace originally has only 1 member - the owner.
        $workspace_id = $test_data->workspace->id;
        $original_members = [$test_data->owner_id];
        $this->assert_workspace_members($workspace_id, $original_members);

        // The workspace should only gain the members of one cohort + the unique
        // members of the other cohorts.
        $task_data = $this->task_data($workspace_id, $cohort_ids);
        $added_member_count = $this->get_enqueued_task($task_data)->execute();
        $this->assertEquals($user_ids->count(), $added_member_count);

        $updated_members = array_merge($original_members, $user_ids->all());
        $this->assert_workspace_members($workspace_id, $updated_members);
        $this->assertDebuggingCalledCount(11);
    }

    /**
     * @return void
     */
    public function test_execute_add_already_existing_members_to_workspace(): void {
        $test_data = $this->create_test_data(1);
        $workspace = $test_data->workspace;
        $cohort_id = $test_data->cohort_ids->first();
        $user_ids = $test_data->user_ids->all();

        // The cohort has the same common members as the workspace.
        foreach ($user_ids as $user_id) {
            cohort_add_member($cohort_id, $user_id);
            member::added_to_workspace($workspace, $user_id);
        }
        $this->assert_cohort_members($cohort_id, $user_ids);

        // The workspace originally has only the owner and the common members.
        $workspace_id = $workspace->id;
        $original_members = array_merge($user_ids, [$test_data->owner_id]);
        $this->assert_workspace_members($workspace_id, $original_members);

        // So no new member should be added.
        $task_data = $this->task_data($workspace_id, $test_data->cohort_ids);
        $added_member_count = $this->get_enqueued_task($task_data)->execute();
        $this->assertEquals(0, $added_member_count);
        $this->assert_workspace_members($workspace_id, $original_members);
        $this->assertDebuggingCalledCount(8);
    }

    /**
     * @return void
     */
    public function test_execute_add_to_populated_workspace_with_duplicate_cohort_members(): void {
        $cohort_count = 2;
        $additional_workspace_members = 3;
        $test_data = $this->create_test_data($cohort_count, $cohort_count + $additional_workspace_members + 5);

        // The workspace originally has $additional_workspace_members + 1 owner,
        // all unique.
        $common_users = $test_data->user_ids->all();
        $original_members = [$test_data->owner_id];
        $workspace = $test_data->workspace;
        for ($i = 0; $i < $additional_workspace_members; $i++) {
            // Note common_users will have one less user after each round.
            $user_id = array_pop($common_users);
            member::added_to_workspace($workspace, $user_id);

            $original_members[] = $user_id;
        }

        $workspace_id = $test_data->workspace->id;
        $this->assert_workspace_members($workspace_id, $original_members);

        // The cohorts have 1 unique member each; all others are common.
        $unique_cohort_members = [];
        $cohort_ids = $test_data->cohort_ids;
        foreach ($cohort_ids as $cohort_id) {
            // Note common_users will have one less user after each round.
            $unique_cohort_members[$cohort_id] = array_pop($common_users);
        }

        foreach ($cohort_ids as $cohort_id) {
            $cohort_unique_member = $unique_cohort_members[$cohort_id];
            $cohort_members = array_merge($common_users, [$cohort_unique_member]);

            foreach ($cohort_members as $user_id) {
                cohort_add_member($cohort_id, $user_id);
            }

            $this->assert_cohort_members($cohort_id, $cohort_members);
        }

        // The workspace should only gain the members of one cohort + the unique
        // members of the other cohorts.
        $task_data = $this->task_data($workspace_id, $cohort_ids);
        $added_member_count = $this->get_enqueued_task($task_data)->execute();
        $this->assertEquals(
            $test_data->user_ids->count() - $additional_workspace_members,
            $added_member_count
        );

        $updated_members = array_merge(
            $original_members, $unique_cohort_members, $common_users
        );
        $this->assert_workspace_members($workspace_id, $updated_members);
        $this->assertDebuggingCalledCount(11);
    }

    /**
     * @return void
     */
    public function test_execute_task_as_workspace_owner(): void {
        $test_data = $this->create_test_data(1, 1);

        $cohort_ids = $test_data->cohort_ids;
        $user_ids = $test_data->user_ids->all();

        foreach ($cohort_ids as $cohort_id) {
            foreach ($user_ids as $user_id) {
                cohort_add_member($cohort_id, $user_id);
            }

            $this->assert_cohort_members($cohort_id, $user_ids);
        }

        // The workspace originally has only 1 member - the owner.
        $workspace_id = $test_data->workspace->id;
        $original_members = [$test_data->owner_id];
        $this->assert_workspace_members($workspace_id, $original_members);

        // Since we are running as the owner,
        $this->setUser($test_data->owner_id);

        $message_sink = $this->redirectMessages();
        $events_sink = $this->redirectEvents();

        $task_data = $this->task_data($workspace_id, $cohort_ids);
        $added_member_count = $this->get_enqueued_task($task_data)->execute();
        $this->assertEquals(count($user_ids), $added_member_count);

        $updated_members = array_merge($original_members, $user_ids);
        $this->assert_workspace_members($workspace_id, $updated_members);

        $messages = $message_sink->get_messages();
        $this->assertCount(1, $messages);

        $message = array_shift($messages);

        $cohort_names = cohort::repository()
            ->where('id', $cohort_ids->all())
            ->get()
            ->pluck('name');

        $this->assertEquals('container_workspace', $message->component);
        $this->assertEquals('bulk_members_via_audience_added', $message->eventtype);
        $this->assertEquals(get_string('bulk_add_audiences_notification_subject', 'container_workspace'), $message->subject);
        $this->assertEquals($test_data->owner_id, $message->useridto);
        $this->assertStringContainsString(
            $added_member_count . ' people from the following audiences were added to <a href="https://www.example.com/moodle/container/type/workspace/workspace.php?id='.$workspace_id.'">'.$test_data->workspace->get_name().'</a>',
            $message->fullmessagehtml
        );
        foreach ($cohort_names as $cohort_name) {
            $this->assertStringContainsString($cohort_name, $message->fullmessagehtml);
        }

        $events = $events_sink->get_events();
        // Two events for enrolling the user, one for the actual task and one for notification
        // is viewed by the user who is in charge
        $this->assertCount(4, $events);
        $events = array_filter($events, function (base $event) {
            return $event instanceof audience_added;
        });
        $this->assertCount(1, $events);
        $event = array_shift($events);

        $this->assertInstanceOf(audience_added::class, $event);
        $this->assertEquals($test_data->workspace->id, $event->objectid);
        $this->assertEquals($test_data->workspace->id, $event->courseid);
        $this->assertEquals($test_data->owner_id, $event->userid);
        $this->assertEquals($test_data->owner_id, $event->relateduserid);
        $this->assertEquals($cohort_ids->all(), $event->other['cohort_ids']);
        $this->assertEquals(1, $event->other['number_of_members_added']);
        $this->assertDebuggingCalledCount(10);
    }

    /**
     * @return void
     */
    public function test_execute_task_as_admin(): void {
        $test_data = $this->create_test_data(1, 1);

        $cohort_ids = $test_data->cohort_ids;
        $user_ids = $test_data->user_ids->all();

        foreach ($cohort_ids as $cohort_id) {
            foreach ($user_ids as $user_id) {
                cohort_add_member($cohort_id, $user_id);
            }

            $this->assert_cohort_members($cohort_id, $user_ids);
        }

        // The workspace originally has only 1 member - the owner.
        $workspace_id = $test_data->workspace->id;
        $original_members = [$test_data->owner_id];
        $this->assert_workspace_members($workspace_id, $original_members);

        // Since we are running as the owner,
        $this->setAdminUser();

        $message_sink = $this->redirectMessages();
        $events_sink = $this->redirectEvents();

        $task_data = $this->task_data($workspace_id, $cohort_ids);
        $added_member_count = $this->get_enqueued_task($task_data)->execute();
        $this->assertEquals(count($user_ids), $added_member_count);

        $updated_members = array_merge($original_members, $user_ids);
        $this->assert_workspace_members($workspace_id, $updated_members);

        $messages = $message_sink->get_messages();
        $this->assertCount(2, $messages);

        // Both, the admin and the owner should have been notified
        $user_id_tos = array_column($messages, 'useridto');
        $this->assertEqualsCanonicalizing([get_admin()->id, $test_data->owner_id], $user_id_tos);

        $cohort_names = cohort::repository()
            ->where('id', $cohort_ids->all())
            ->get()
            ->pluck('name');

        foreach ($messages as $message) {
            $this->assertEquals('container_workspace', $message->component);
            $this->assertEquals('bulk_members_via_audience_added', $message->eventtype);
            $this->assertEquals(get_string('bulk_add_audiences_notification_subject', 'container_workspace'), $message->subject);
            $this->assertStringContainsString(
                $added_member_count . ' people from the following audiences were added to <a href="https://www.example.com/moodle/container/type/workspace/workspace.php?id='.$workspace_id.'">'.$test_data->workspace->get_name().'</a>',
                $message->fullmessagehtml
            );
            foreach ($cohort_names as $cohort_name) {
                $this->assertStringContainsString($cohort_name, $message->fullmessagehtml);
            }
        }

        $events = $events_sink->get_events();
        // Two events for enrolling the user, one for the actual task
        // and two events for the notifications that are viewed by the workspace owner.
        $this->assertCount(5, $events);
        $events = array_filter($events, function (base $event) {
            return $event instanceof audience_added;
        });
        $this->assertCount(1, $events);
        $event = array_shift($events);

        $this->assertInstanceOf(audience_added::class, $event);
        $this->assertEquals($test_data->workspace->id, $event->objectid);
        $this->assertEquals($test_data->workspace->id, $event->courseid);
        $this->assertEquals(get_admin()->id, $event->userid);
        $this->assertEquals($test_data->owner_id, $event->relateduserid);
        $this->assertEquals($cohort_ids->all(), $event->other['cohort_ids']);
        $this->assertEquals(1, $event->other['number_of_members_added']);
        $this->assertDebuggingCalledCount(13);
    }

    /**
     * @return void
     */
    public function test_execute_task_as_non_owner(): void {
        $test_data = $this->create_test_data(1, 1);

        $cohort_ids = $test_data->cohort_ids;
        $user_ids = $test_data->user_ids;

        foreach ($cohort_ids as $cohort_id) {
            foreach ($user_ids as $user_id) {
                cohort_add_member($cohort_id, $user_id);
            }

            $this->assert_cohort_members($cohort_id, $user_ids->all());
        }

        $this->setUser($user_ids->first());

        try {
            $task_data = $this->task_data($test_data->workspace->id, $cohort_ids);
            $this->get_enqueued_task($task_data)->execute();
        } catch (enrol_exception $exception) {
            $this->assertInstanceOf(enrol_exception::class, $exception);
            $this->assertEquals('User does not have permission to enrol cohort members into workspace', $exception->getMessage());
        }
        $this->assertDebuggingCalledCount(2);
    }

    /**
     * @return void
     */
    public function test_execute_task_on_deleted_workspace(): void {
        $test_data = $this->create_test_data(1, 1);

        $cohort_ids = $test_data->cohort_ids;
        $user_ids = $test_data->user_ids->all();

        foreach ($cohort_ids as $cohort_id) {
            foreach ($user_ids as $user_id) {
                cohort_add_member($cohort_id, $user_id);
            }

            $this->assert_cohort_members($cohort_id, $user_ids);
        }

        // The workspace originally has only 1 member - the owner.
        $workspace_id = $test_data->workspace->id;
        $original_members = [$test_data->owner_id];
        $this->assert_workspace_members($workspace_id, $original_members);

        // Since we are running as the owner,
        $this->setAdminUser();

        $test_data->workspace->mark_to_be_deleted(true);

        $message_sink = $this->redirectMessages();
        $events_sink = $this->redirectEvents();

        $task_data = $this->task_data($workspace_id, $cohort_ids);
        $added_member_count = $this->get_enqueued_task($task_data)->execute();
        $this->assertNull($added_member_count);

        $this->assertEmpty($message_sink->get_messages());
        $this->assertEmpty($events_sink->get_events());
        $this->assertDebuggingCalledCount(1);
    }

    /**
     * Generates test data.
     *
     * @param int $no_of_cohorts no of cohorts to generate.
     * @param int $no_of_users no of users to generate.
     *
     * @return stdClass an object with the following fields:
     *         - workspace workspace
     *         - collection cohort_ids
     *         - collection user_ids
     *         - int owner_id
     * @throws coding_exception
     * @throws dml_exception
     */
    private function create_test_data(
        int $no_of_cohorts = 2,
        int $no_of_users = 10
    ): stdClass {
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $owner = $generator->create_user();

        // The owner has the right to add audience members.
        $sys_context = context_system::instance();
        $roleid = $generator->create_role();
        assign_capability('moodle/cohort:view', CAP_ALLOW, $roleid, $sys_context);
        role_assign($roleid, $owner->id, $sys_context);

        $user_ids = collection::new([]);
        for ($i = 0; $i < $no_of_users; $i++) {
            $user_ids->append($generator->create_user()->id);
        }

        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator
            ->create_workspace_from_params([
                'name' => "test_workspace",
                'owner' => $owner->username
            ]);

        $cohort_ids = collection::new([]);
        for ($i = 0; $i < $no_of_cohorts; $i++) {
            $cohort_ids->append($generator->create_cohort()->id);
        }

        return (object) [
            'workspace' => $workspace,
            'cohort_ids' => $cohort_ids,
            'user_ids' => $user_ids,
            'owner_id' => $owner->id
        ];
    }

    /**
     * Returns all the task objects in the repository.
     *
     * @return collection|task[] the list of
     *         adhoc tasks
     */
    private function all_tasks(): collection {
        return adhoc_task_entity::repository()
            ->filter_by_component(task::COMPONENT)
            ->filter_by_class(task::class)
            ->get()
            ->map_to(
                function (adhoc_task_entity $task): task {
                    $record = $task->to_array();
                    return manager::adhoc_task_from_record((object)$record);
                }
            );
    }

    /**
     * Creates the data that is stored in a task's custom data field.
     *
     * @param int $workspace_id workspace id
     * @param collection|int[] $cohort_ids cohorts to process.
     *
     * @return array the task data.
     */
    private function task_data(int $workspace_id, collection $cohort_ids): array {
        return [
            task::WORKSPACE_ID => $workspace_id,
            task::COHORT_IDS => $cohort_ids->all()
        ];
    }

    /**
     * Convenience function to simulate the retrieval of an adhoc task entry from
     * the database.
     *
     * @param array $task_data task data.
     *
     * @return task the task.
     */
    private function get_enqueued_task(array $task_data): task {
        global $USER;

        $task = new task();

        $task->set_component(task::COMPONENT);
        $task->set_next_run_time(time());
        $task->set_blocking(false);
        $task->set_fail_delay(0);
        $task->set_userid($USER->id);
        $task->set_custom_data($task_data);

        return $task;
    }

    /**
     * Verifies the actual members of the workspace.
     *
     * @param int $workspace_id the workspace to check.
     * @param int[] $user_ids the expected member ids.
     */
    private function assert_workspace_members(int $workspace_id, array $user_ids): void {
        $workspace_query = new query($workspace_id);
        $existing_members = loader::get_members($workspace_query)
            ->get_items()
            ->map_to(
                function (member $member): int {
                    return $member->get_user_id();
                }
            )
            ->all();

        $this->assertCount(count($user_ids), $existing_members);
        $this->assertEqualsCanonicalizing($user_ids, $existing_members);
    }

    /**
     * Verifies the actual members of the cohort.
     *
     * @param int $cohort_id the cohort to check.
     * @param int[] $user_ids the expected member ids.
     */
    private function assert_cohort_members(int $cohort_id, array $user_ids): void {
        $existing_members = cohort_member_entity::repository()
            ->where('cohortid', $cohort_id)
            ->get()
            ->pluck('userid');

        $this->assertCount(count($user_ids), $existing_members);
        $this->assertEqualsCanonicalizing($user_ids, $existing_members);
    }
}

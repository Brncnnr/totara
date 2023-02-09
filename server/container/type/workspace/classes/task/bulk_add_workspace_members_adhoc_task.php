<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 * @author  Murali Nair <murali.nair@totaralearning.com>
 * @package container_workspace
 */
namespace container_workspace\task;

defined('MOODLE_INTERNAL') || die();

use coding_exception;
use container_workspace\event\audience_added;
use container_workspace\member\member_handler;
use container_workspace\workspace;
use core\collection;
use core\entity\adhoc_task as adhoc_task_entity;
use core\entity\cohort;
use core\entity\user;
use core\message\message;
use core\task\adhoc_task;
use core\task\manager;
use core\task\manager as task_manager;
use core_user;
use dml_missing_record_exception;
use stdClass;

/**
 * Adds workspace members in bulk.
 * @deprecated since Totara 16.0 Bulk add audience members has been deprecated.
 */
final class bulk_add_workspace_members_adhoc_task extends adhoc_task {
    // Task's data keys.
    const COHORT_IDS = 'cohort_ids';
    const COMPONENT = 'container_workspace';
    const WORKSPACE_ID = 'workspace_id';

    // Return codes.
    const ENQUEUE_NO_COHORTS_TO_ADD = -100;
    const EXECUTE_INVALID_WORKSPACE_IN_TASK_DATA = -200;
    const EXECUTE_INVALID_COHORTS_IN_TASK_DATA = -201;
    const EXECUTE_WORKSPACE_DOES_NOT_EXIST = -202;

    /**
     * Creates an instance of the adhoc task to update the given workspace with
     * members from the selected cohorts.
     *
     * @param int $raw_workspace_id indicates the workspace to which to add
     *        members.
     * @param int[] $raw_cohort_ids cohorts whose members are to be added to the
     *        workspace.
     *
     * @return int the id of the created adhoc task if the task was enqueued
     *         successfully. This could be -1 if there are no cohorts to add ie
     *         the incoming cohorts are due to be added in another adhoc task.
     * @deprecated since Totara 16.0 Bulk add audience members has been deprecated.
     */
    public static function enqueue(int $raw_workspace_id, array $raw_cohort_ids): int {
        debugging("bulk_add_workspace_members_adhoc_task::enqueue has been deprecated", DEBUG_DEVELOPER);
        [$workspace_id, $cohort_ids] = self::clean($raw_workspace_id, $raw_cohort_ids);
        if (!$workspace_id) {
            throw new coding_exception('invalid workspace id');
        }

        if (!$cohort_ids) {
            throw new coding_exception('invalid cohort ids');
        }

        $cohorts_to_add = self::filter_cohorts($workspace_id, $cohort_ids);
        if (!$cohorts_to_add) {
            return self::ENQUEUE_NO_COHORTS_TO_ADD;
        }

        $task = new bulk_add_workspace_members_adhoc_task();
        $task->set_component(self::COMPONENT);
        $task->set_userid(user::logged_in()->id);
        $task->set_custom_data([
            self::WORKSPACE_ID => $workspace_id,
            self::COHORT_IDS => $cohorts_to_add
        ]);

        return manager::queue_adhoc_task($task);
    }

    /**
     * Sanitizes the incoming data.
     *
     * @param mixed $raw_workspace_id the workspace id to validate
     * @param mixed $raw_cohort_ids the cohorts ids to validate.
     *
     * @return array a sanitized (workspace id, cohort ids) tuple.
     */
    private static function clean($raw_workspace_id, $raw_cohort_ids): array {
        $workspace_id = is_numeric($raw_workspace_id) ? (int)$raw_workspace_id : 0;
        $workspace_id = $workspace_id < 1 ? 0 : $workspace_id;

        $cohort_ids = array_filter(
            is_array($raw_cohort_ids) ? $raw_cohort_ids : [],
            function ($id): bool {
                return is_numeric($id);
            }
        );
        $cohort_ids = array_unique($cohort_ids);

        return [$workspace_id, $cohort_ids];
    }

    /**
     * Filters the incoming cohort id list for cohorts that are already due to
     * be processed by another queued bulk_add_workspace_members_adhoc_task.
     *
     * @param int $workspace_id workspace to which to add members from a user
     *        grouping.
     * @param int[] $cohort_ids cohorts to be filtered.
     *
     * @return collection the filtered list of cohort ids.
     */
    private static function filter_cohorts(int $workspace_id, array $cohort_ids): array {
        // This has to check against the database because \core\task\manager and
        // \core\task\adhoc_task do not have APIs do so. A terrible hack, but no
        // choice.
        $existing_task_data = adhoc_task_entity::repository()
            ->filter_by_component(self::COMPONENT)
            ->filter_by_class(self::class)
            ->get()
            ->map_to(
                function ($task): array {
                    return json_decode($task->customdata, true);
                }
            )
            ->filter(
                function (array $custom_data) use ($workspace_id): bool {
                    $existing_workspace_id = $custom_data[self::WORKSPACE_ID] ?? 0;
                    return (int)$existing_workspace_id === $workspace_id;
                }
            )
            ->all();

        if (!$existing_task_data) {
            return $cohort_ids;
        }

        $cohort_ids_to_add = [];
        foreach ($existing_task_data as $data) {
            $existing_cohort_ids = $data[self::COHORT_IDS] ?? [];
            $new_cohort_ids = array_diff($cohort_ids, $existing_cohort_ids);

            $cohort_ids_to_add = array_merge($cohort_ids_to_add, $new_cohort_ids);
        }

        return array_unique($cohort_ids_to_add);
    }

    /**
     * {@inheritdoc}
     * @deprecated since Totara 16.0 Bulk add audience members has been deprecated.
     */
    public function execute() {
        debugging("bulk_add_workspace_members_adhoc_task::execute has been deprecated.", DEBUG_DEVELOPER);
        // Note the error handling in this method; as far as possible, cannot
        // throw/rethrow exceptions since the task should not be rerun in the
        // future.
        [
            'error' => $error,
            'workspace' => $workspace,
            'cohort_ids' => $cohort_ids
        ] = $this->parse_task_data();

        if ($error) {
            return $error;
        }

        $workspace_name = $workspace->get_name();
        $this->log("adding audience members to '$workspace_name'...");

        if (empty($cohort_ids)) {
            $this->log('no audiences to queue, skipping.');
            return;
        }

        if ($workspace->is_to_be_deleted()) {
            $this->log('workspace is queued for deletion, skipping.');
            return;
        }

        $handler = new member_handler($this->get_userid());
        $new_members = $handler->add_workspace_members_from_cohorts($workspace, $cohort_ids, false);

        $new_member_ids = [];
        foreach ($new_members as $new_member) {
            $new_member_ids[] = $new_member->get_id();
        }

        $new_member_count = count($new_member_ids);

        $result = sprintf(
            "added %d member%s to '%s'",
            $new_member_count,
            $new_member_count === 1 ? '' : 's',
            $workspace_name
        );

        $this->send_notification_task_finished($workspace, $cohort_ids, $new_member_count);
        // We don't want to trigger a lot of individual adhoc tasks so let's send the via bulk
        $this->queue_bulk_notification($workspace, $new_member_ids);

        $this->log($result);

        audience_added::from_workspace($workspace, $cohort_ids->all(), $new_member_count, $this->get_userid())->trigger();

        return $new_member_count;
    }

    /**
     * Parse the task's data to get the workspace and cohort ids to use.
     *
     * @return array the parse result with the following keys:
     *         - error: 0 if successful or the error code otherwise.
     *         - workspace: the workspace to use or null if there were errors.
     *         - cohort_ids: collection of cohort ids to use or an empty list if
     *           there were errors.
     */
    private function parse_task_data(): array {
        $result = [
            'error' => 0,
            'workspace' => null,
            'cohort_ids' => collection::new([])
        ];

        $task_data = (array)$this->get_custom_data();
        [$workspace_id, $cohort_ids] = self::clean(
            $task_data[self::WORKSPACE_ID] ?? 0,
            $task_data[self::COHORT_IDS] ?? []
        );

        if (!$workspace_id) {
            $this->log("invalid workspace id in task data");
            $result['error'] = self::EXECUTE_INVALID_WORKSPACE_IN_TASK_DATA;

            return $result;
        }

        if (!$cohort_ids) {
            $this->log("invalid audiences in task data");
            $result['error'] = self::EXECUTE_INVALID_COHORTS_IN_TASK_DATA;

            return $result;
        }

        try {
            $workspace = workspace::from_id($workspace_id);
        } catch (dml_missing_record_exception $e) {
            $this->log("missing workspace; id = '$workspace_id'");
            $result['error'] = self::EXECUTE_WORKSPACE_DOES_NOT_EXIST;

            return $result;
        }

        $result['workspace'] = $workspace;
        $result['cohort_ids'] = collection::new($cohort_ids);

        return $result;
    }

    /**
     * Convenience function to print log a message on the console.
     *
     * @param string $message log message.
     */
    private function log(string $message): void {
        $is_phpunit_env = defined('PHPUNIT_TEST') && PHPUNIT_TEST;
        if (!$is_phpunit_env) {
            $final_message = sprintf('[%s] %s', self::class, $message);
            mtrace($final_message);
        }
    }

    /**
     * Queue bulk member notifications
     *
     * @param workspace $workspace
     * @param int[] $member_ids
     * @return void
     */
    private function queue_bulk_notification(workspace $workspace, array $member_ids): void {
        $member_id_chunks = array_chunk($member_ids, 500);

        foreach ($member_id_chunks as $member_id_chunk) {
            $task = notify_added_to_workspace_bulk_task::from_members($workspace, $member_id_chunk);
            task_manager::queue_adhoc_task($task);
        }
    }

    /**
     * Send notification to the user that the task is now finished
     *
     * @param workspace $workspace
     * @param collection $cohort_ids
     * @param int $number_of_members_added
     * @return void
     */
    private function send_notification_task_finished(
        workspace $workspace,
        collection $cohort_ids,
        int $number_of_members_added
    ): void {
        $user = core_user::get_user($this->get_userid());
        $message = $this->create_message($user, $workspace, $cohort_ids, $number_of_members_added);
        message_send($message);

        if ($this->get_userid() != $workspace->get_user_id()) {
            $owner = core_user::get_user($workspace->get_user_id());
            if ($owner) {
                // Make sure we send the notification in the language of the user
                cron_setup_user($owner);

                $message = $this->create_message($owner, $workspace, $cohort_ids, $number_of_members_added);
                message_send($message);

                cron_setup_user($user);
            }
        }
    }

    /**
     * Create message
     *
     * @param stdClass $receiver
     * @param workspace $workspace
     * @param collection $cohort_ids
     * @param int $number_of_members_added
     * @return message
     */
    private function create_message(
        stdClass $receiver,
        workspace $workspace,
        collection $cohort_ids,
        int $number_of_members_added
    ): message {
        $workspace_name = format_string($workspace->get_name());
        $url = new \moodle_url("/container/type/workspace/workspace.php", ['id' => $workspace->id]);

        $message_text = get_string(
            'bulk_add_audiences_notification_message',
            'container_workspace',
            (object) [
                'number' => $number_of_members_added,
                'name' => $workspace_name,
                'link' => $url->out(),
            ]
        );

        $audience_names = $this->get_audience_names($cohort_ids);
        $list_of_audiences_added = "<li>".implode("</li><li>", $audience_names)."</li>";

        $message_text .= "<br><br><ul>{$list_of_audiences_added}</ul>";

        $message = new message();
        $message->userfrom = \core_user::get_noreply_user();
        $message->userto = $receiver;
        $message->subject = get_string('bulk_add_audiences_notification_subject', 'container_workspace');
        $message->fullmessage = $message_text;
        $message->fullmessageformat = FORMAT_HTML;
        $message->fullmessagehtml = $message_text;
        $message->component = $workspace::get_type();
        $message->name = 'bulk_members_via_audience_added';
        $message->courseid = $workspace->get_id();
        $message->contexturl = $url;
        $message->contexturlname = $workspace_name;

        return $message;
    }

    /**
     * Resolve given ids to audience names
     *
     * @param collection $cohort_ids
     * @return string[]
     */
    private function get_audience_names(collection $cohort_ids): array {
        if ($cohort_ids->count() == 0) {
            return [];
        }

        return cohort::repository()
            ->where('id', $cohort_ids->all())
            ->get()
            ->map(function (cohort $cohort) {
                return format_string($cohort->name);
            })
            ->all();
    }
}

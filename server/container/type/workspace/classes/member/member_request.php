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
 * @package container_workspace
 */
namespace container_workspace\member;

use container_workspace\entity\workspace_member_request;
use container_workspace\entity\workspace_member_request as entity;
use container_workspace\task\notify_decline_join_request_task;
use container_workspace\task\notify_join_request_task;
use container_workspace\task\send_accept_request_task;
use container_workspace\workspace;
use core_container\factory;
use container_workspace\loader\member\loader as member_loader;
use container_workspace\interactor\workspace\interactor;
use core\task\manager;

/**
 * Model class for member request
 */
final class member_request {
    /**
     * @var entity
     */
    private $entity;

    /**
     * @var \stdClass|null
     */
    private $user_record;

    /**
     * member_request constructor.
     * @param entity $entity
     */
    private function __construct(entity $entity) {
        $this->entity = $entity;
        $this->user_record = null;
    }

    /**
     * @param int $id
     * @return member_request
     */
    public static function from_id(int $id): member_request {
        $entity = new entity($id);
        return new static($entity);
    }

    /**
     * @param entity            $entity
     * @param \stdClass|null    $user_record
     *
     * @return member_request
     */
    public static function from_entity(entity $entity, ?\stdClass $user_record = null): member_request {
        if (!$entity->exists()) {
            throw new \coding_exception("Entity does not exist within the system");
        }

        $member_request = new static($entity);

        if (null !== $user_record) {
            $member_request->set_user($user_record);
        }

        return $member_request;
    }

    /**
     * If $user_id is not provided, user in session will be used. Ideally that this $user_id will
     * be used as the requester.
     *
     * @param int       $workspace_id
     * @param int|null  $user_id
     * @param string    $request_content
     *
     * @return member_request
     */
    public static function create(int $workspace_id, ?int $user_id = null, string $request_content = ''): member_request {
        global $USER;

        /** @var workspace $workspace */
        $workspace = factory::from_id($workspace_id);
        if (!$workspace->is_typeof(workspace::get_type())) {
            throw new \coding_exception("Cannot use the member request for different container type");
        }

        if ($workspace->is_public()) {
            throw new \coding_exception("Workspace is a public workspace - cannot create a request");
        }

        if (null === $user_id || 0 === $user_id) {
            $user_id = $USER->id;
        }

        $member = member_loader::get_for_user($user_id, $workspace_id);
        if (null !== $member && $member->is_active()) {
            throw new \coding_exception(
                "Member is already existing for user '{$user_id}', cannot create another request"
            );
        }

        // Check if user is able to see the workspace or not. A user should also not be able to join a private, hidden
        // workspace without administrate permissions.
        $interactor = new interactor($workspace, $user_id);
        if (($workspace->is_hidden() && !$interactor->can_administrate()) || !$interactor->can_view_workspace()) {
            throw new \coding_exception("User is not able to see the workspace");
        }

        $repository = workspace_member_request::repository();
        $old_entity = $repository->get_current_pending_request($workspace_id, $user_id);

        if (null !== $old_entity) {
            // Re-using the current one. Note that when we are reusing the old request entity, we assume
            // that the adhoc tasks had already been queued - meaning that we would not want to queue the
            // adhoc taks again.
            return static::from_entity($old_entity);
        }

        $entity = new entity();
        $entity->user_id = $user_id;
        $entity->course_id = $workspace_id;
        $entity->request_content = $request_content;

        $entity->save();

        // Id attribute should be populated for us.
        $task = notify_join_request_task::from_member_request($entity->id);
        manager::queue_adhoc_task($task);

        return static::from_entity($entity);
    }

    /**
     * @return \stdClass
     */
    public function get_user(): \stdClass {
        if (null === $this->user_record) {
            $user_id = $this->entity->user_id;
            $this->user_record = \core_user::get_user($user_id, '*', MUST_EXIST);
        }

        return $this->user_record;
    }

    /**
     * @param \stdClass $user_record
     * @return void
     */
    public function set_user(\stdClass $user_record): void {
        if (!property_exists($user_record, 'id')) {
            throw new \coding_exception("Missing key 'id'");
        }

        // Check against the entity.
        $entity_user_id = $this->entity->user_id;
        if ($entity_user_id != $user_record->id) {
            throw new \coding_exception("The user's id(s) are not the same");
        }

        $this->user_record = $user_record;
    }

    /**
     * @param int|null $time
     * @param int|null $actor_id
     * @return void
     */
    public function accept(?int $actor_id = null, ?int $time = null): void {
        global $USER, $CFG, $DB;

        if ($this->is_accepted()) {
            return;
        }

        if ($this->is_declined() || $this->is_cancelled()) {
            throw new \coding_exception(
                "The request had already been declined or cancelled - cannot accept the request"
            );
        }

        if (null === $actor_id || 0 === $actor_id) {
            $actor_id = $USER->id;
        }

        $workspace = $this->get_workspace();
        $workspace_interactor = new interactor($workspace, $actor_id);

        if (!$workspace_interactor->can_accept_member_request()) {
            throw new \coding_exception("Actor cannot accept the member request");
        }

        if ($CFG->tenantsenabled) {
            // Multi-tenancy is on - we just have to make sure that if the original requester is still
            // able to see this very workspace or not.
            $requester_id = $this->entity->user_id;
            $workspace_requester_interactor = new interactor($workspace, $requester_id);

            if (!$workspace_requester_interactor->can_view_workspace_with_tenant_check()) {
                throw new \coding_exception("The requester is not able to see the workspace anymore");
            }
        }

        if (null === $time || 0 === $time) {
            $time = time();
        }

        $transaction = $DB->start_delegated_transaction();
        $this->entity->time_accepted = $time;
        $this->entity->save();

        // Add user to the workspace. Note that we do not trigger any tasks here when adding users to the workspace.
        // This is because we do not want to send notification to users twice about one thing.
        member::added_to_workspace($workspace, $this->entity->user_id, false, $actor_id);

        $task = new send_accept_request_task();
        $task->set_component(workspace::get_type());
        $task->set_member_request_id($this->entity->id);
        $task->set_userid($actor_id);

        manager::queue_adhoc_task($task);
        $transaction->allow_commit();
    }

    /**
     *
     * @param int|null $actor_id
     * @param int|null $time
     * @param string $decline_content
     */
    public function decline(?int $actor_id = null, ?int $time = null, string $decline_content = ''): void {
        global $USER;

        if ($this->is_declined()) {
            return;
        }

        if ($this->is_cancelled() || $this->is_accepted()) {
            throw new \coding_exception(
                "The request had already been accepted or cancelled - cannot decline the request"
            );
        }

        if (null === $actor_id || 0 === $actor_id) {
            $actor_id = $USER->id;
        }

        $workspace = $this->get_workspace();
        $workspace_interactor = new interactor($workspace, $actor_id);

        if (!$workspace_interactor->can_decline_member_request()) {
            throw new \coding_exception("Actor cannot decline the member request");
        }

        if (null === $time || 0 === $time) {
            $time = time();
        }

        $this->entity->time_declined = $time;
        $this->entity->request_content = '';
        $this->entity->decline_content = $decline_content;
        $entity = $this->entity->save();

        notify_decline_join_request_task::enqueue($entity->id);
    }

    /**
     * @param int|null $time
     * @param int|null $actor_id
     * @return void
     */
    public function cancel(?int $actor_id = null, ?int $time = null): void {
        global $USER;

        if ($this->is_cancelled()) {
            return;
        }

        if ($this->is_accepted() || $this->is_declined()) {
            throw new \coding_exception(
                "The request had already been declined or accepted - cannot cancel the request"
            );
        }

        if (null === $actor_id || 0 === $actor_id) {
            $actor_id = $USER->id;
        }

        $requester_id = $this->entity->user_id;
        if (!is_siteadmin($actor_id) && $actor_id != $requester_id) {
            throw new \coding_exception("Actor cannot cancel the request");
        }

        if (null === $time || 0 === $time) {
            $time = time();
        }

        $this->entity->time_cancelled = $time;
        $this->entity->request_content = '';
        $this->entity->save();
    }

    /**
     * @return bool
     */
    public function is_cancelled(): bool {
        return null !== $this->entity->time_cancelled;
    }

    /**
     * @return bool
     */
    public function is_declined(): bool {
        return null !== $this->entity->time_declined;
    }

    /**
     * @return bool
     */
    public function is_accepted(): bool {
        return null !== $this->entity->time_accepted;
    }

    /**
     * @return int
     */
    public function get_workspace_id(): int {
        return $this->entity->course_id;
    }

    /**
     * @return workspace
     */
    public function get_workspace(): workspace {
        $course_id = $this->entity->course_id;

        /** @var workspace $workspace */
        $workspace = factory::from_id($course_id);
        return $workspace;
    }

    /**
     * @return int
     */
    public function get_id(): int {
        return $this->entity->id;
    }

    /**
     * @return int
     */
    public function get_time_created(): int {
        return $this->entity->time_created;
    }

    /**
     * @return int
     */
    public function get_user_id(): int {
        return $this->entity->user_id;
    }

    /**
     * @return string
     */
    public function get_request_content(): string {
        return clean_param($this->entity->request_content,PARAM_TEXT);
    }

    /**
     * @return string
     */
    public function get_decline_content(): string {
        return clean_param($this->entity->decline_content,PARAM_TEXT);
    }
}
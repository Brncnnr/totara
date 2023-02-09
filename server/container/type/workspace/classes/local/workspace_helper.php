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
namespace container_workspace\local;

use container_workspace\discussion\discussion_helper;
use container_workspace\event\workspace_deleted;
use container_workspace\exception\workspace_exception;
use container_workspace\interactor\workspace\category_interactor;
use container_workspace\interactor\workspace\interactor;
use container_workspace\loader\member\loader;
use container_workspace\member\member;
use container_workspace\member\member_handler;
use container_workspace\task\notify_new_workspace_owner_task;
use container_workspace\totara_engage\share\recipient\library;
use container_workspace\tracker\tracker;
use container_workspace\workspace;
use core\orm\query\builder;
use core\task\manager;
use totara_core\content\processor\hashtag_processor;
use totara_core\content\content;
use totara_engage\entity\share_recipient;
use totara_engage\repository\share_recipient_repository;

/**
 * Class workspace_helper
 * @package container_workspace\local
 */
final class workspace_helper {
    /**
     * Preventing this class form being constructed.
     * workspace_helper constructor.
     */
    private function __construct() {
    }

    /**
     * @param string        $name
     * @param int           $actor_id
     * @param int|null      $category_id
     * @param string|null   $summary
     * @param int|null      $summary_format
     * @param int|null      $draft_id           This is for default image of a workspace
     * @param bool          $is_private         To tell whether the workspace is private or not.
     * @param bool          $is_hidden          Only works if the workspace is private - if not and hidden is set to
     *                                          true, then exception will be thrown.
     *
     * @return workspace
     */
    public static function create_workspace(string $name, int $actor_id,
                                            ?int $category_id = null, ?string $summary = null,
                                            ?int $summary_format = null,
                                            ?int $draft_id = null, bool $is_private = false,
                                            bool $is_hidden = false): workspace {
        global $CFG;
        if (empty($name)) {
            throw new \coding_exception("Cannot create a workspace with empty name");
        }

        if (\core_text::strlen(trim($name)) > 75) {
            throw workspace_exception::on_create();
        }

        if (null === $category_id) {
            $category_id = workspace::get_default_category_id();
        }

        $category_interactor = category_interactor::from_category_id($category_id, $actor_id);
        // The three types of workspaces (private, public & hidden) have individual capabilities
        // to work with. If you're trying to create a specific type of workspace, the matching
        // capability must be assigned.

        // Create hidden check
        if ($is_hidden && !$category_interactor->can_create_hidden()) {
            throw workspace_exception::on_create();
        }

        // Create private check
        if ($is_private && !$category_interactor->can_create_private()) {
            throw workspace_exception::on_create();
        }

        // Finally public check. Public is assumed if we're not private (since hidden is a child of private)
        if (!$is_private && !$category_interactor->can_create_public()) {
            throw workspace_exception::on_create();
        }

        // Final sanity check - you can't make a hidden public workspace.
        if (!$is_private && $is_hidden) {
            throw new \coding_exception("Cannot create a hidden public workspace");
        }

        $record = new \stdClass();
        $record->fullname = $name;

        // Empty string by default, even though the table is allowing this field to be null.
        // We are moving away from null for summary pretty much.
        $record->summary = '';
        $record->summaryformat = FORMAT_JSON_EDITOR;
        $record->user_id = $actor_id;
        $record->category = $category_id;
        $record->visible = 1;
        $record->workspace_private = $is_private;

        if ($is_private && $is_hidden) {
            $record->visible = 0;

            if (!defined('COHORT_VISIBLE_ENROLLED')) {
                require_once("{$CFG->dirroot}/totara/core/totara.php");
            }

            $record->audiencevisible = COHORT_VISIBLE_ENROLLED;
        }

        if (null !== $summary) {
            $record->summary = $summary;
        }

        if (null !== $summary_format) {
            $record->summaryformat = $summary_format;
        }

        /** @var workspace $workspace */
        $workspace = workspace::create($record);
        $manager = $workspace->get_enrolment_manager();

        // After create a workspace, we might need to enable the self enrol plugin
        // here for this specific public workspace.
        $manager->enable_self_enrolment();
        $manager->enable_manual_enrolment();

        // Then enrol this very user as an owner for the workspace.
        member::join_workspace($workspace, $actor_id);

        if (null !== $draft_id && 0 !== $draft_id) {
            $workspace->save_image($draft_id, $actor_id);
        }

        // If hidden, make sure we grant the hidden capability
        // This will be removed when 'workspacemember' becomes an archetype
        if ($workspace->is_hidden()) {
            $manager->toggle_viewhidden_capability(true);
        }

        // Process hashtags.
        self::workspace_summary_hashtags($workspace);

        return $workspace;
    }

    /**
     * @param workspace $workspace
     * @param int|null $actor_id
     *
     * @return void
     */
    public static function delete_workspace(workspace $workspace, ?int $actor_id = null): void {
        global $USER;

        if (null === $actor_id || 0 === $actor_id) {
            $actor_id = $USER->id;
        }

        $interactor = new interactor($workspace, $actor_id);
        // The workspace delete mutation resolver will have already checked that a user had the required capability prior
        // to queuing the workspace delete task, and set workspace.is_to_be_deleted to true.
        if (!$workspace->is_to_be_deleted()) {
            throw workspace_exception::on_delete();
        }

        $transaction = builder::get_db()->start_delegated_transaction();
        $deleted_event = workspace_deleted::from_workspace($workspace, $actor_id);

        $member_handler = new member_handler($actor_id);
        $member_handler->delete_members_of_workspace($workspace);

        discussion_helper::delete_discussions_of_workspace($workspace, $actor_id);

        // Clear the tracker for navigating the right page.
        tracker::clear_all_for_workspace($workspace->get_id());

        // After deleting the member, we now need to delete all the instances.
        $manager = $workspace->get_enrolment_manager();
        $manager->delete_enrol_instances($actor_id);

        // Start deleting the workspace as a recipients of all the shares.

        /** @var share_recipient_repository $repository */
        $repository = share_recipient::repository();
        $repository->delete_recipients_by_identifier(
            workspace::get_type(),
            library::AREA,
            $workspace->get_id()
        );

        // Then finally, deleted the workspace itself.
        $workspace->delete();

        // Make all the changes permanent.
        $transaction->allow_commit();

        // Transaction is over - we trigger event.
        $deleted_event->trigger();
    }

    /**
     * To check whether the given workspace is able to be updated to hidden status or not.
     *
     * @param workspace $workspace
     * @return bool
     */
    public static function can_workspace_update_to_hidden(workspace $workspace): bool {
        if (!static::can_workspace_update_to_private($workspace)) {
            return false;
        }

        if ($workspace->is_private()) {
            return false;
        }

        // Probably this is the last thing - which is when the workspace is hidden - meaning that user is able to
        // update to hidden.
        return true;
    }

    /**
     * To check whether the given workspace is able to be updated to private status or not.
     *
     * @param workspace $workspace
     * @return bool
     */
    public static function can_workspace_update_to_private(workspace $workspace): bool {
        // As long as workspace is a public workspace, then from either private or hidden can go up to
        // this status of access.
        return !$workspace->is_public();
    }

    /**
     * Function to update the workspace time stamp and also update the user's tracker.
     * We have to update user's tracker because:
     * + user visit workspace can happen before he/she add any content to the workspace. This means that when
     *   the workspace was updated with timestamp then timestamp will be higher than the user last access time.
     *   Meaning that it can cause the error calculation.
     *
     * @param workspace $workspace
     * @param int|null $actor_id
     * @param int|null $time
     *
     * @return void
     */
    public static function update_workspace_timestamp(workspace $workspace, ?int $actor_id = null,
                                                       ?int $time = null): void {
        global $USER;
        if (empty($actor_id)) {
            $actor_id = $USER->id;
        }

        if ($workspace->is_to_be_deleted()) {
            // No point to go to the rest of the code.
            return;
        }

        if (empty($time)) {
            $time = time();
        }

        $interactor = new interactor($workspace, $actor_id);
        if (!$interactor->is_joined() && !$interactor->can_update()) {
            // This is to prevent the tracker being updated if user is not either any.
            debugging("User is not a member nor someone who has capability to update the workspace", DEBUG_DEVELOPER);
            return;
        }

        $workspace->touch($time);

        $tracker = new tracker($actor_id);
        $tracker->visit_workspace($workspace, $time);
    }

    /**
     * @param workspace $workspace
     * @param int $new_user_id
     * @param int|null $actor_id
     *
     * @return void
     */
    public static function update_workspace_primary_owner(workspace $workspace, int $new_user_id,
                                                          ?int $actor_id = null): void {
        global $USER, $CFG;
        if (empty($actor_id)) {
            $actor_id = $USER->id;
        }

        $actor_workspace_interactor = new interactor($workspace, $actor_id);
        if (!$actor_workspace_interactor->can_manage()) {
            throw new \coding_exception("Actor does not have ability to update workspace owner");
        }

        // Checking if the new user is a valid one.
        $new_owner = \core_user::get_user($new_user_id, '*', MUST_EXIST);
        if ($new_owner->deleted || $new_owner->suspended) {
            throw new \coding_exception(
                "Cannot update the workspace primary owner to user that had been suspended or deleted"
            );
        }

        if ($CFG->tenantsenabled) {
            // Check if the new owner is able to see this workspace or not.
            $new_owner_interactor = new interactor($workspace, $new_user_id);
            if (!$new_owner_interactor->can_view_workspace_with_tenant_check()) {
                throw new \coding_exception("New owner does not have ability to access the workspace");
            }
        }

        $workspace_id = $workspace->get_id();
        $current_owner_id = $workspace->get_user_id();

        if (null !== $current_owner_id && $new_user_id == $current_owner_id) {
            // Same actor what so ever.
            return;
        }

        // We have to promote/add new user to the workspace first, as this is because when the actor
        // is an actual owner, the actor will not have any power to promote/added any other user to the workspace
        // Check if user is already a member of a workspace or not.
        $new_user_member = loader::get_for_user($new_user_id, $workspace_id);

        if (null === $new_user_member || !$new_user_member->is_active()) {
            // Note that we do not trigger any notification for adding user to the workspace.
            $new_user_member = member::added_to_workspace($workspace, $new_user_id, false, $actor_id);
        }

        $new_user_member->promote_to_owner($actor_id);

        if (null !== $current_owner_id) {
            // There is current owner id, we will demote them.
            $workspace->remove_user();
            // Demote user as member.
            $current_owner_member = member::from_user($current_owner_id, $workspace_id);
            $current_owner_member->demote_from_owner($actor_id);
        }

        // Then update the workspace's record.
        $workspace->update_user($new_user_id);

        $task = notify_new_workspace_owner_task::from_workspace($workspace_id, $actor_id);
        manager::queue_adhoc_task($task);
    }

    /**
     * Process any hashtags that have been included in workspace summary.
     *
     * @param workspace $workspace
     */
    public static function workspace_summary_hashtags(workspace $workspace): void {
        // Leave if nothing to process.
        if ($workspace->summary === null) {
            return;
        }

        // Create a content object to enable hashtag processing.
        $content = new content(
            $workspace->get_name(),
            $workspace->summary,
            $workspace->summaryformat,
            $workspace->get_id(),
            $workspace->containertype,
            ' '
        );

        $processor = new hashtag_processor();
        switch ($workspace->summaryformat) {
            case FORMAT_PLAIN:
                $processor->process_format_text($content);
                break;

            case FORMAT_HTML:
                $processor->process_format_html($content);
                break;

            case FORMAT_JSON_EDITOR:
                $processor->process_format_json_editor($content);
                break;

            case FORMAT_MOODLE:
            default:
                $processor->process_format_moodle($content);
                break;
        }
    }
}
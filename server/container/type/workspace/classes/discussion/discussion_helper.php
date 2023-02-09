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
namespace container_workspace\discussion;

use container_workspace\event\discussion_created;
use container_workspace\event\discussion_deleted;
use container_workspace\event\discussion_soft_deleted;
use container_workspace\event\discussion_updated;
use container_workspace\exception\discussion_exception;
use container_workspace\interactor\discussion\interactor as discussion_interactor;
use container_workspace\interactor\workspace\interactor as workspace_interactor;
use container_workspace\loader\discussion\loader as discussion_loader;
use container_workspace\local\workspace_helper;
use container_workspace\query\discussion\query as discussion_query;
use container_workspace\query\discussion\sort;
use container_workspace\workspace;
use core\orm\query\builder;
use core\pagination\offset_cursor;
use totara_comment\comment_helper;
use totara_reaction\reaction_helper;

/**
 * Class discussion_helper
 * @package container_workspace\discussion
 */
final class discussion_helper {
    /**
     * discussion_helper constructor.
     * Preventing this class from construction
     */
    private function __construct() {
    }

    /**
     * @param workspace $workspace
     * @param string $content
     * @param int|null $draft_id
     * @param int|null $content_format
     * @param int|null $actor_id
     *
     * @return discussion
     */
    public static function create_discussion(workspace $workspace, string $content, ?int $draft_id = null,
                                             ?int $content_format = null, ?int $actor_id = null): discussion {
        global $USER;

        if (null === $actor_id || 0 === $actor_id) {
            $actor_id = $USER->id;
        }

        $interactor = new workspace_interactor($workspace, $actor_id);
        if (!$interactor->can_create_discussions()) {
            throw discussion_exception::on_create();
        }

        // Start creating the discussion, after that we will trigger an event for this.
        $discussion = discussion::create(
            $content,
            $workspace->get_id(),
            $draft_id,
            $content_format,
            $actor_id
        );

        // Update the timestamp of the workspace.
        workspace_helper::update_workspace_timestamp($workspace, $actor_id);

        $event = discussion_created::from_discussion($discussion, $actor_id);
        $event->trigger();

        return $discussion;
    }

    /**
     * @param discussion $discussion
     * @param int|null $actor_id
     * @return void
     */
    public static function delete_discussion(discussion $discussion, ?int $actor_id = null): void {
        global $USER;

        if (null === $actor_id || 0 === $actor_id) {
            $actor_id = $USER->id;
        }

        $interactor = new discussion_interactor($discussion, $actor_id);
        if (!$interactor->can_delete()) {
            throw discussion_exception::on_delete();
        }

        // Trigger event before deleting first.
        $event = discussion_deleted::from_discussion($discussion, $actor_id);
        $event->trigger();

        static::do_delete_discussion($discussion);
    }

    /**
     * Note that this function will not check for any capabilities. The capabilities
     * check should be done before calling to this function.
     *
     * @param discussion $discussion
     * @return void
     */
    public static function do_delete_discussion(discussion $discussion): void {
        self::remove_related_content($discussion);
        $discussion->delete();
    }

    /**
     * Soft-delete the provided discussion.
     *
     * @param discussion $discussion
     * @param int|null $actor_id
     * @param int|null $delete_reason
     * @return void
     */
    public static function soft_delete_discussion(discussion $discussion, ?int $actor_id = null,
                                                  ?int $delete_reason = null): void {
        global $USER;

        if (null === $actor_id || 0 === $actor_id) {
            $actor_id = $USER->id;
        }

        $interactor = new discussion_interactor($discussion, $actor_id);
        if (!$interactor->can_delete()) {
            throw discussion_exception::on_delete();
        }

        if ($delete_reason === discussion::REASON_DELETED_REPORTED) {
            // This is an administrative delete, therefore we need to remove any files & likes/reactions & comments
            self::remove_related_content($discussion);
        }

        // Trigger event before deleting first.
        $event = discussion_soft_deleted::from_discussion($discussion, $actor_id);
        $event->trigger();

        $discussion->soft_delete($delete_reason);
    }

    /**
     * @param int       $discussion_id
     * @param string    $content
     * @param int|null  $draft_id
     * @param int|null  $actor_id
     * @param int|null  $content_format
     *
     * @return discussion
     */
    public static function update_discussion_content(int $discussion_id, string $content, ?int $draft_id = null,
                                                     ?int $content_format = null, ?int $actor_id = null): discussion {
        global $USER;

        if (null === $actor_id || 0 === $actor_id) {
            $actor_id = $USER->id;
        }

        $discussion = discussion::from_id($discussion_id);
        $interactor = new discussion_interactor($discussion, $actor_id);

        if (!$interactor->can_update()) {
            throw discussion_exception::on_update();
        }

        $discussion->update_content($content, $draft_id, $content_format, $actor_id);

        // Update the timestamp of the workspace.
        $workspace = $discussion->get_workspace();
        workspace_helper::update_workspace_timestamp($workspace, $actor_id);

        // Triggering an event.
        $event = discussion_updated::from_discussion($discussion, $actor_id);
        $event->trigger();

        return $discussion;
    }

    /**
     * Delete any related items, including files and reactions.
     *
     * @param discussion $discussion
     */
    private static function remove_related_content(discussion $discussion): void {
        global $CFG;

        $discussion_id = $discussion->get_id();
        $workspace_component = workspace::get_type();
        $area = discussion::AREA;
        $context_id = $discussion->get_workspace()->get_context()->id;

        // Purge all the reactions of the discussion.
        reaction_helper::purge_area_reactions(
            $workspace_component,
            $area,
            $discussion_id
        );

        // Purge all the files that are uploaded to the discussion.
        require_once("{$CFG->dirroot}/lib/filelib.php");
        $fs = get_file_storage();
        $fs->delete_area_files(
            $context_id,
            $workspace_component,
            $area,
            $discussion_id
        );

        // Comments as well
        $workspace_component = workspace::get_type();
        $discussion_id = $discussion->get_id();

        // Start deleting the comments first.
        comment_helper::purge_area_comments(
            $workspace_component,
            discussion::AREA,
            $discussion_id
        );
    }

    /**
     * Deleting all the discussions of the workspace.
     * This function should be used when the workspace is deleted, not when
     * the workspace is not deleted. However, it is not responsible to check whether
     * the workspace has been deleted or not. Its itnention is to delete the discussions only.
     *
     * @param workspace $workspace
     * @param int|null  $actor_id
     * @param int       $bach_limit     How many records do we want to delete per one batch. As the deletion is
     *                                  being done with batch deleting.
     *
     * @return void
     */
    public static function delete_discussions_of_workspace(workspace $workspace, ?int $actor_id = null,
                                                           int $bach_limit = 100): void {
        global $USER;

        if (empty($actor_id)) {
            $actor_id = $USER->id;
        }

        $transaction = builder::get_db()->start_delegated_transaction();

        // We are going to use this cursor thru out the whole process.
        // It is because the loader is moving forward, hence for every single batch deleted,
        // we will miss one page as the next cursor would be invalid after the deletion happened.
        $cursor = new offset_cursor([
            'limit' => $bach_limit,
            'page' => 1
        ]);

        $query = new discussion_query($workspace->get_id());
        $query->set_cursor($cursor);
        $query->set_sort(sort::DATE_POSTED);

        $cursor_paginator = discussion_loader::get_discussions($query);
        $discussions = $cursor_paginator->get_items()->all();

        while (!empty($discussions)) {
            foreach ($discussions as $discussion) {
                self::delete_discussion($discussion, $actor_id);
            }

            $cursor_paginator = discussion_loader::get_discussions($query);
            $discussions = $cursor_paginator->get_items()->all();
        }

        $transaction->allow_commit();
    }
}
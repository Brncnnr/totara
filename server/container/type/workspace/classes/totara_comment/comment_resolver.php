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
namespace container_workspace\totara_comment;

use coding_exception;
use container_workspace\discussion\discussion;
use container_workspace\workspace;
use context_course;
use core_container\factory;
use totara_comment\comment;
use totara_comment\resolver;
use container_workspace\interactor\workspace\interactor as workspace_interactor;
use container_workspace\interactor\discussion\interactor as discussion_interactor;

/**
 * Discussion's comment resolver
 */
final class comment_resolver extends resolver {
    /**
     * @param int $instance_id
     * @param string $area
     * @param int $actor_id
     * @return bool
     */
    public function is_allow_to_create(int $instance_id, string $area, int $actor_id): bool {
        if (discussion::AREA == $area) {
            $discussion = discussion::from_id($instance_id);
            $interactor = new discussion_interactor($discussion, $actor_id);

            return $interactor->can_comment();
        }

        debugging("Area '{$area}' is not supported in container_workspace for comment", DEBUG_DEVELOPER);
        return false;
    }

    /**
     * @param comment $comment
     * @param int $actorid
     * @return bool
     */
    public function is_allow_to_delete(comment $comment, int $actorid): bool {
        global $DB;

        if (is_siteadmin($actorid)) {
            return true;
        }

        $area = $comment->get_area();

        if (discussion::AREA == $area) {
            $discussion_id = $comment->get_instanceid();
            $workspace_id = $DB->get_field(
                'workspace_discussion',
                'course_id',
                ['id' => $discussion_id]
            );

            /** @var workspace $workspace */
            $workspace = factory::from_id($workspace_id);
            $owner_id = $workspace->get_user_id();

            return $owner_id == $actorid;
        }

        debugging("Area '{$area}' is not supported in container_workspace for comment", DEBUG_DEVELOPER);
        return false;
    }

    /**
     * @param comment $comment
     * @param int $actorid
     * @return bool
     */
    public function is_allow_to_update(comment $comment, int $actorid): bool {
        global $DB;

        if (is_siteadmin($actorid)) {
            // Save us another cycle of fetching.
            return true;
        }

        $area = $comment->get_area();

        if (discussion::AREA == $area) {
            $discussion_id = $comment->get_instanceid();
            $workspace_id = $DB->get_field(
                'workspace_discussion',
                'course_id',
                ['id' => $discussion_id]
            );

            /** @var workspace $workspace */
            $workspace = factory::from_id($workspace_id);
            $owner_id = $workspace->get_user_id();

            return $owner_id == $actorid;
        }

        debugging("Area '{$area}' is not supported in container_workspace for comment", DEBUG_DEVELOPER);
        return false;
    }

    /**
     * @param int       $instance_id
     * @param string    $area
     * @return int
     */
    public function get_context_id(int $instance_id, string $area): int {
        global $DB;

        if (discussion::AREA == $area) {
            $workspace_id = $DB->get_field('workspace_discussion', 'course_id', ['id' => $instance_id]);

            $context = context_course::instance($workspace_id);
            return $context->id;
        }

        throw new coding_exception(
            "Cannot find the context base on instance id '{$instance_id}' and area '{$area}'"
        );
    }

    /**
     * Returning the result of the ability to create reaction on either comment/reply. But this is
     * depending on the area that is using comment within the workspace.
     *
     * @param comment $comment
     * @param int $actor_id
     * @return bool
     */
    public function can_create_reaction_on_comment(comment $comment, int $actor_id): bool {
        global $DB;

        $result = parent::can_create_reaction_on_comment($comment, $actor_id);
        if (!$result) {
            // Seems like the actor is the owner.
            return false;
        }

        // Now we will have to check whether the user has joined the workspace or not.
        $area = $comment->get_area();
        if (discussion::AREA === $area) {
            $discussion_id = $comment->get_instanceid();
            $sql = '
                SELECT c.id FROM "ttr_course" c
                INNER JOIN "ttr_workspace_discussion" wd on c.id = wd.course_id
                WHERE c.containertype = :workspace_type
                AND wd.id = :discussion_id
            ';

            $params = [
                'workspace_type' => workspace::get_type(),
                'discussion_id' => $discussion_id
            ];

            $workspace_id = $DB->get_field_sql($sql, $params, MUST_EXIST);
            $workspace_interactor = workspace_interactor::from_workspace_id($workspace_id, $actor_id);

            // Only joined member is allow to create reaction on the comment.
            return $workspace_interactor->is_joined();
        }

        throw new coding_exception("Invalid area that is not supported yet");
    }

    /**
     * @param int $instance_id
     * @param string $area
     * @param int $actor_id
     *
     * @return bool
     */
    public function can_see_comments(int $instance_id, string $area, int $actor_id): bool {
        if (discussion::AREA === $area) {
            $discussion = discussion::from_id($instance_id);
            $workspace = $discussion->get_workspace();

            // As long as the actor can view the discussions, meaning that user is able to view comments.
            $workspace_interactor = new workspace_interactor($workspace, $actor_id);
            return $workspace_interactor->can_view_discussions();
        }

        throw new coding_exception("Invalid area that is not supported yet");
    }

    /**
     * If the user actor is able to see the discussions/workspace then user should be
     * able to see the reactions related to the comments.
     *
     * @param comment   $comment
     * @param int       $actor_id
     *
     * @return bool
     */
    public function can_view_reactions_of_comment(comment $comment, int $actor_id): bool {
        global $DB;
        $area = $comment->get_area();

        if (discussion::AREA === $area) {
            $discussion_id = $comment->get_instanceid();
            $sql = '
                SELECT c.id FROM "ttr_course" c
                INNER JOIN "ttr_workspace_discussion" wd ON c.id = wd.course_id
                WHERE c.containertype = :workspace_type
                AND wd.id = :discussion_id
            ';

            $params = [
                'workspace_type' => workspace::get_type(),
                'discussion_id' => $discussion_id
            ];

            $workspace_id = $DB->get_field_sql($sql, $params, MUST_EXIST);
            $workspace_interactor = workspace_interactor::from_workspace_id($workspace_id, $actor_id);

            return $workspace_interactor->can_view_discussions();
        }

        throw new coding_exception("Invalid area that is not supported yet");
    }

    /**
     * Returns the owner's id of the instance given $area and $instance_id.
     * Note that if the instance does not have owner, return NULL instead.
     *
     * @param string $area
     * @param int    $instance_id
     *
     * @return int|null
     */
    public function get_owner_id_from_instance(string $area, int $instance_id): ?int {
        if (discussion::AREA !== $area) {
            throw new coding_exception("Not supported area by component '{$this->component}'");
        }

        $discussion = discussion::from_id($instance_id);
        return $discussion->get_user_id();
    }
}
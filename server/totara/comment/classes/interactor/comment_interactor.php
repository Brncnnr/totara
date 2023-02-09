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
 * @package totara_comment
 */
namespace totara_comment\interactor;

use totara_comment\access\author_access_handler;
use totara_comment\comment;
use totara_comment\resolver_factory;
use totara_reaction\loader\reaction_loader;
use totara_reaction\resolver\resolver_factory as reaction_resolver_factory;

/**
 * Class comment_interactor
 * @package totara_comment\interactor
 */
final class comment_interactor {
    /**
     * @var comment
     */
    private $comment;

    /**
     * @var int
     */
    private $actor_id;

    /**
     * comment_interactor constructor.
     * @param comment $comment
     * @param int|null $actor_id
     */
    public function __construct(comment $comment, ?int $actor_id = null) {
        global $USER;

        if ($comment->is_reply()) {
            throw new \coding_exception("Cannot instantiate a new interactor object for a comment that is a reply");
        }

        if (null === $actor_id || 0 === $actor_id) {
            $actor_id = $USER->id;
        }

        $this->comment = $comment;
        $this->actor_id = $actor_id;
    }

    /**
     * Since this totara_comment component is quite a universal component. Therefore, these interactor functions
     * will have to invoke to the resolver object/API to tell whether the actor is able to perform anything.
     * In this case, we are running check if the actor is able to reply or not.
     *
     * @return bool
     */
    public function can_reply(): bool {
        // Block guests from replying.
        if (isguestuser($this->actor_id)) {
            return false;
        }

        if ($this->comment->is_soft_deleted()) {
            // Start checking the reason of deleted
            $reason = $this->comment->get_reason_deleted();
            if (null !== $reason && comment::REASON_DELETED_REPORTED == $reason) {
                // Comment has been reported, therefore you are not allow to reply to this comment.
                return false;
            }
        }

        $component = $this->comment->get_component();
        $resolver = resolver_factory::create_resolver($component);

        $area = $this->comment->get_area();
        $instance_id = $this->comment->get_instanceid();

        return $resolver->is_allow_to_create($instance_id, $area, $this->actor_id);
    }

    /**
     * Since this totara_comment component is quite a universal component. Therefore, these interactor functions
     * will have to invoke to the resolver object/API to tell whether the actor is able to perform anything.
     * In this case, we are running check if the actor is able to react on the comment or not.
     *
     * Note that we are checking whether the reaction is being created against the totara_comment
     * component or not.
     *
     * @return bool
     */
    public function can_react(): bool {
        // Block guests from reacting.
        if (isguestuser($this->actor_id)) {
            return false;
        }

        if ($this->comment->is_soft_deleted()) {
            // Start checking the reason of deleted.
            $reason = $this->comment->get_reason_deleted();
            if (null !== $reason && comment::REASON_DELETED_REPORTED == $reason) {
                // Comment has been reported, therefore you are not allow to react to this comment.
                return false;
            }
        }

        $resolver = reaction_resolver_factory::create_resolver('totara_comment');

        // Instance's  id in this case is a comment's id. We are checking whether the user is able to create
        // the reaction against the comment.
        $comment_id = $this->comment->get_id();
        return $resolver->can_create_reaction($comment_id, $this->actor_id, comment::COMMENT_AREA);
    }

    /**
     * @return bool
     */
    public function can_delete(): bool {
        // Block guests from deleting.
        if (isguestuser($this->actor_id)) {
            return false;
        }

        if ($this->comment->is_soft_deleted()) {
            return false;
        }

        if ($this->actor_id == $this->comment->get_userid()) {
            return true;
        }

        $component = $this->comment->get_component();
        $resolver = resolver_factory::create_resolver($component);

        return $resolver->is_allow_to_delete($this->comment, $this->actor_id);
    }

    /**
     * @return bool
     */
    public function can_update(): bool {
        // Block guests from updating.
        if (isguestuser($this->actor_id)) {
            return false;
        }

        if ($this->comment->is_soft_deleted()) {
            return false;
        }

        if ($this->actor_id == $this->comment->get_userid()) {
            return true;
        }

        $component = $this->comment->get_component();
        $resolver = resolver_factory::create_resolver($component);

        return $resolver->is_allow_to_update($this->comment, $this->actor_id);
    }

    /**
     * @return bool
     */
    public function can_report(): bool {
        if ($this->comment->is_soft_deleted()) {
            return false;
        }

        $component = $this->comment->get_component();
        $resolver = resolver_factory::create_resolver($component);

        return $resolver->can_report_comment($this->comment, $this->actor_id);
    }

    /**
     * @return int
     */
    public function get_comment_id(): int {
        return $this->comment->get_id();
    }

    /**
     * Returning the actor's id
     * @return int
     */
    public function get_user_id(): int {
        return $this->actor_id;
    }

    /**
     * @return bool
     */
    public function reacted(): bool {
        $owner_id = $this->comment->get_userid();
        if ($owner_id == $this->actor_id) {
            // Save us from fetching sql.
            return false;
        }

        $comment_id = $this->comment->get_id();
        return reaction_loader::exist(
            $comment_id,
            'totara_comment',
            comment::COMMENT_AREA,
            $this->actor_id
        );
    }

    /**
     * @return bool
     */
    public function can_view_author(): bool {
        $handler = new author_access_handler($this->actor_id);
        $owner_id = $this->comment->get_userid();

        return $handler->can_see_user($owner_id);
    }
}

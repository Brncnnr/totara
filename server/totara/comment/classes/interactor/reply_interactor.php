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
 * Interactor for a reply
 */
final class reply_interactor {
    /**
     * @var comment
     */
    private $reply;

    /**
     * @var int
     */
    private $actor_id;

    /**
     * reply_interactor constructor.
     * @param comment $reply
     * @param int|null $actor_id
     */
    public function __construct(comment $reply, ?int $actor_id = null) {
        global $USER;

        if (!$reply->is_reply()) {
            throw new \coding_exception("Cannot instantiate an interactor from a non-reply comment");
        }

        if (null === $actor_id || 0 === $actor_id) {
            $actor_id = $USER->id;
        }

        $this->actor_id = $actor_id;
        $this->reply = $reply;
    }

    /**
     * @return bool
     */
    public function can_delete(): bool {
        // Block guests from deleting.
        if (isguestuser($this->actor_id)) {
            return false;
        }

        if ($this->reply->is_soft_deleted()) {
            return false;
        }

        if ($this->actor_id == $this->reply->get_userid()) {
            return true;
        }

        $component = $this->reply->get_component();
        $resolver = resolver_factory::create_resolver($component);

        return $resolver->is_allow_to_delete($this->reply, $this->actor_id);
    }

    /**
     * @return bool
     */
    public function can_update(): bool {
        // Block guests from updating.
        if (isguestuser($this->actor_id)) {
            return false;
        }

        if ($this->reply->is_soft_deleted()) {
            return false;
        }

        if ($this->actor_id == $this->reply->get_userid()) {
            return true;
        }

        $component = $this->reply->get_component();
        $resolver = resolver_factory::create_resolver($component);

        return $resolver->is_allow_to_delete($this->reply, $this->actor_id);
    }

    /**
     * As long as the actor is not an owner of this reply, then he/she
     * can pretty much be able to report it.
     *
     * @return bool
     */
    public function can_report(): bool {
        if ($this->reply->is_soft_deleted()) {
            return false;
        }

        $component = $this->reply->get_component();
        $resolver = resolver_factory::create_resolver($component);

        return $resolver->can_report_reply($this->reply, $this->actor_id);
    }

    /**
     * Note that we are checking whether the interactor is able to react agains the comment component or not.
     *
     * @return bool
     */
    public function can_react(): bool {
        // Block guests from reacting.
        if (isguestuser($this->actor_id)) {
            return false;
        }

        if ($this->reply->is_soft_deleted()) {
            $reason = $this->reply->get_reason_deleted();
            if (null !== $reason && comment::REASON_DELETED_REPORTED == $reason) {
                // This has been reported. Therefore you are not allow to react with it.
                return false;
            }
        }

        // Instance's id in this case is a reply's id. We are checking whether the reaction is able
        // to be created against the reply.
        $reply_id = $this->reply->get_id();

        $resolver = reaction_resolver_factory::create_resolver('totara_comment');
        return $resolver->can_create_reaction($reply_id, $this->actor_id, comment::REPLY_AREA);
    }

    /**
     * Clearly that you are not able to create a reply of a reply, but this is to check that if you can create another
     * reply within the same comment parent but a follow up of this reply.
     *
     * Since this totara_comment component is quite a universal component. Therefore, these interactor functions
     * will have to invoke to the resolver object/API to tell whether the actor is able to perform anything.
     * In this case, we are running check if the actor is able to reply or not.
     *
     * @return bool
     */
    public function can_follow_reply(): bool {
        // Block guests from replying.
        if (isguestuser($this->actor_id)) {
            return false;
        }

        if ($this->reply->is_soft_deleted()) {
            $reason = $this->reply->get_reason_deleted();

            if (null !== $reason && comment::REASON_DELETED_REPORTED == $reason) {
                // This has been reported. Therefore you are not allow to react with it.
                return false;
            }
        }

        $reply_component = $this->reply->get_component();
        $resolver = resolver_factory::create_resolver($reply_component);

        $instance_id = $this->reply->get_instanceid();
        $reply_area = $this->reply->get_area();

        return $resolver->is_allow_to_create($instance_id, $reply_area, $this->actor_id);
    }

    /**
     * @return int
     */
    public function get_user_id(): int {
        return $this->actor_id;
    }

    /**
     * @return int
     */
    public function get_reply_id(): int {
        return $this->reply->get_id();
    }

    /**
     * @return bool
     */
    public function reacted(): bool {
        $owner_id = $this->reply->get_userid();

        if ($owner_id == $this->actor_id) {
            // Save us from db cycle.
            return false;
        }

        $reply_id = $this->reply->get_id();
        return reaction_loader::exist(
            $reply_id,
            'totara_comment',
            comment::REPLY_AREA,
            $this->actor_id
        );
    }

    /**
     * @return bool
     */
    public function can_view_author(): bool {
        $handler = new author_access_handler($this->actor_id);
        $owner_id = $this->reply->get_userid();

        return $handler->can_see_user($owner_id);
    }
}

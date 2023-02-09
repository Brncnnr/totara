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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_comment
 */
namespace totara_comment;

use coding_exception;
use totara_comment\pagination\cursor;

/**
 * Base resolver class, the children should be implementing this class to override any logical implementation.
 */
abstract class resolver {
    /**
     * @var string
     */
    protected $component;

    /**
     * Preventing the children to have a complicate constructor
     * resolver constructor.
     */
    final public function __construct() {
        $classname = get_called_class();
        $parts = explode("\\", $classname);

        $this->component = reset($parts);

        if (clean_param($this->component, PARAM_COMPONENT) != $this->component) {
            // No point to construct a class that is so invalid.
            throw new \coding_exception("Invalid component name '{$this->component}'");
        }
    }

    /**
     * @return string
     */
    final public function get_component(): string {
        return $this->component;
    }

    /**
     * This function will be responsible for checking whether user is able to create comment/reply for
     * the instance that is being represented by $instanceid
     *
     * @param int       $instanceid
     * @param string    $area
     * @param int       $actorid
     *
     * @return bool
     */
    abstract public function is_allow_to_create(int $instanceid, string $area, int $actorid): bool;

    /**
     * @param comment $comment
     * @param int $actorid
     *
     * @return bool
     */
    abstract public function is_allow_to_delete(comment $comment, int $actorid): bool;

    /**
     * @param comment $comment
     * @param int $actorid
     *
     * @return bool
     */
    abstract public function is_allow_to_update(comment $comment, int $actorid): bool;

    /**
     * Returning the default cursor usage within the component. This will be helpful to detect the cursor
     * type that has been encoded with json and base-64
     *
     * Children should be extending this function to provide the default cursor if needed.
     * It can be different cursor depending on the $area.
     *
     * @param string $area
     * @return cursor
     */
    public function get_default_cursor(string $area): cursor {
        $cursor = new cursor();
        $cursor->set_limit(comment::ITEMS_PER_PAGE);

        return $cursor;
    }

    /**
     * Resolving the context's id needed by instances to store the comments/replies.
     *
     * @param int $instance_id
     * @param string $area
     *
     * @return int
     */
    public abstract function get_context_id(int $instance_id, string $area): int;

    /**
     * This function is allowing us to ask the component that using this totara_comment component to check whether
     * the $actor_id is having the ability to create its reaction on the comment record or not.
     *
     * Note that whatever the result return from this function (whether it is being overridden from the parent),
     * it will be applied for both comment and reply.
     *
     * Several general rules:
     * + If the owner of the comment cannot like his/her own comment is the actor then comment cannot be reacted
     *
     * However, note that this rule is in here to allow the children to override this rule.
     *
     * @param comment $comment
     * @param int     $actor_id
     * @return bool
     */
    public function can_create_reaction_on_comment(comment $comment, int $actor_id): bool {
        $owner = $comment->get_userid();
        if ($owner == $actor_id) {
            return false;
        }

        return true;
    }

    /**
     * This function is allowing us to ask the component that using this totara_comment component to check whether
     * the $actor_id is having the ability to view the reaction records on the comment record or not.
     *
     * Note that whatever the result return from this function (whether it is being overridden from the parent),
     * it will be applied for both comment and reply.
     *
     * @param comment   $comment
     * @param int       $actor_id
     *
     * @return bool
     */
    public function can_view_reactions_of_comment(comment $comment, int $actor_id): bool {
        return true;
    }

    /**
     * Check if acting user is allowed to report the comment.
     *
     * @param comment $comment
     * @param integer $actor_id
     * @return boolean
     */
    public function can_report_comment(comment $comment, int $actor_id): bool {
        $owner_id = $comment->get_userid();
        return $owner_id != $actor_id;
    }

    /**
     * Check if acting user is allowed to report the reply.
     *
     * @param comment $comment
     * @param integer $actor_id
     * @return boolean
     */
    public function can_report_reply(comment $comment, int $actor_id): bool {
        $owner_id = $comment->get_userid();
        return $owner_id != $actor_id;
    }

    /**
     * Check if acting user is allowed to view the instance's comments
     *
     * @param int       $instance_id
     * @param string    $area
     * @param int       $actor_id
     *
     * @return bool
     */
    public function can_see_comments(int $instance_id, string $area, int $actor_id): bool {
        return true;
    }

    /**
     * Check if the acting user is allowed to view the instance's replies.
     *
     * @param int       $instance_id
     * @param string    $area
     * @param int       $actor_id
     *
     * @return bool
     */
    public function can_see_replies(int $instance_id, string $area, int $actor_id): bool {
        return $this->can_see_comments($instance_id, $area, $actor_id);
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
        throw new coding_exception(
            "Please extend the function '\\totara_comment\\resolver\\get_owner_id_from_instance' " .
            "to help resolve the owner's id of instance"
        );
    }
}

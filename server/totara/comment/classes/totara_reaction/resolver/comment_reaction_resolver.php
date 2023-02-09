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
namespace totara_comment\totara_reaction\resolver;

use totara_comment\comment;
use totara_comment\resolver_factory as comment_resolver_factory;
use totara_reaction\resolver\base_resolver;

/**
 * Resolver for creating reaction on comment instance.
 */
final class comment_reaction_resolver extends base_resolver {
    /**
     * Totara comment will just act like a proxy here, anything that happen for the reaction should be done in the
     * instance's context. As totara_comment does not use any context.
     *
     * @param int $comment_id
     * @param string $area
     * @return \context
     */
    public function get_context(int $comment_id, string $area): \context {
        $comment = comment::from_id($comment_id);

        // Note that the $area and $comment_area are different.
        $comment_component = $comment->get_component();
        $comment_instance_area = $comment->get_area();

        $resolver = comment_resolver_factory::create_resolver($comment_component);
        $comment_instance_id = $comment->get_instanceid();

        $context_id = $resolver->get_context_id($comment_instance_id, $comment_instance_area);
        return \context::instance_by_id($context_id);
    }

    /**
     * @param string $area
     * @param comment $comment
     *
     * @return void
     */
    protected function validate_instance_with_area(string $area, comment $comment): void {
        if (comment::REPLY_AREA === $area && !$comment->is_reply()) {
            throw new \coding_exception(
                "Expecting the comment record to be a reply according to the value of parameter area"
            );
        }

        if (comment::COMMENT_AREA === $area && $comment->is_reply()) {
            throw new \coding_exception(
                "Expecting the comment record to be a comment according to the value of parameter area"
            );
        }
    }

    /**
     * Since the component totara_comment is quite a universal component, therefore what we are going to do
     * is to ask the comment resolver - which is drilled down to the component that is using this totara_comment
     * component to check whether we are allowing the actor ($user_id) to be able to create the reaction on the
     * comment item or not.
     *
     * @param int $comment_id
     * @param int $user_id
     * @param string $area
     * @return bool
     */
    public function can_create_reaction(int $comment_id, int $user_id, string $area): bool {
        if (!in_array($area, [comment::REPLY_AREA, comment::COMMENT_AREA])) {
            throw new \coding_exception(
                "The area for checking the ability to create reaction on comment is invalid: '{$area}'"
            );
        }

        $comment = comment::from_id($comment_id);

        // Start running check on the $area against the model just in case the $area is being used wrongly.
        $this->validate_instance_with_area($area, $comment);

        // Now we really need the comment_resolver of a specific component.
        $comment_component = $comment->get_component();

        $resolver = comment_resolver_factory::create_resolver($comment_component);
        return $resolver->can_create_reaction_on_comment($comment, $user_id);
    }

    /**
     * Since the component totara_comment is an universal component, hence we are going to ask
     * the comment resolver whether the actor ($user_id) is able to view the reactions or not.
     *
     * @param int       $instance_id
     * @param int       $user_id
     * @param string    $area
     *
     * @return bool
     */
    public function can_view_reactions(int $instance_id, int $user_id, string $area): bool {
        if (!in_array($area, [comment::REPLY_AREA, comment::COMMENT_AREA])) {
            throw new \coding_exception("Invalid area passed to comment resolver: {$area}");
        }

        $comment = comment::from_id($instance_id);

        // Start running check on the $area against the model just in case the $area is being used wrongly.
        $this->validate_instance_with_area($area, $comment);

        // Now we really need the comment_resolver of a specific component.
        $comment_component = $comment->get_component();
        $resolver = comment_resolver_factory::create_resolver($comment_component);

        return $resolver->can_view_reactions_of_comment($comment, $user_id);
    }
}
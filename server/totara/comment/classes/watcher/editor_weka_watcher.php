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
namespace totara_comment\watcher;

use editor_weka\hook\find_context;
use totara_comment\comment;
use totara_comment\resolver_factory;

/**
 * Watcher for editor_weka hooks.
 */
final class editor_weka_watcher {
    /**
     * @param find_context $hook
     * @return void
     */
    public static function load_context(find_context $hook): void {
        $component = $hook->get_component();
        if ('totara_comment' !== $component) {
            return;
        }

        $area = $hook->get_area();

        if (!in_array($area, [comment::COMMENT_AREA, comment::REPLY_AREA])) {
            debugging("Invalid area for totara_comment: '{$area}'", DEBUG_DEVELOPER);
            return;
        }

        $comment_or_reply_id = $hook->get_instance_id();
        if (null !== $comment_or_reply_id) {
            $comment = comment::from_id($comment_or_reply_id);

            // For totara_comment component, it will be acting like a proxy, as it does not use any context.
            // Hence it will try to fetch the context from the component that is trying to using it.
            $resolver = resolver_factory::create_resolver($comment->get_component());
            $context_id = $resolver->get_context_id(
                $comment->get_instanceid(),
                $comment->get_area()
            );

            $context = \context::instance_by_id($context_id);
            $hook->set_context($context);
        }
    }
}
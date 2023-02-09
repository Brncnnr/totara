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
namespace totara_comment\webapi\resolver\query;

use core\webapi\execution_context;
use core\webapi\middleware\require_login;
use core\webapi\query_resolver;
use totara_comment\exception\comment_exception;
use totara_comment\loader\comment_loader;
use totara_comment\pagination\cursor_paginator;
use totara_comment\resolver_factory;

/**
 * Resolver to fetch the comment cursor, which has the metadata about pagination.
 */
class comment_cursor extends query_resolver {
    /**
     * @param array $args
     * @param execution_context $ec
     *
     * @return cursor_paginator
     */
    public static function resolve(array $args, execution_context $ec): cursor_paginator {
        global $USER;

        $instance_id = $args['instance_id'];
        $component = $args['component'];
        $area = $args['area'];

        $resolver = resolver_factory::create_resolver($component);
        if (!$ec->has_relevant_context()) {
            $context_id = $resolver->get_context_id($instance_id, $area);
            $context = \context::instance_by_id($context_id);

            $ec->set_relevant_context($context);
        } else {
            $context = $ec->get_relevant_context();
        }

        if ($context->is_user_access_prevented($USER->id) ||
            !$resolver->can_see_comments($instance_id, $area, $USER->id)) {
            throw comment_exception::on_access_denied();
        }

        $cursor = $resolver->get_default_cursor($area);
        if (isset($args['cursor'])) {
            $cursor = $cursor::decode($args['cursor']);
        }

        return comment_loader::get_paginator(
            $instance_id,
            $component,
            $area,
            $cursor
        );
    }

    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            new require_login(),
        ];
    }

}
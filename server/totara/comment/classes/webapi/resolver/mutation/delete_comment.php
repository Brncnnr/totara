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
namespace totara_comment\webapi\resolver\mutation;

use core\webapi\execution_context;
use core\webapi\middleware\require_login;
use core\webapi\mutation_resolver;
use totara_comment\comment;
use totara_comment\comment_helper;
use totara_comment\resolver_factory;

class delete_comment extends mutation_resolver {
    /**
     * @param array             $args
     * @param execution_context $ec
     *
     * @return comment
     */
    public static function resolve(array $args, execution_context $ec): comment {
        $comment = comment::from_id($args['id']);

        if (!$ec->has_relevant_context()) {
            $resolver = resolver_factory::create_resolver($comment->get_component());
            $context_id = $resolver->get_context_id(
                $comment->get_instanceid(),
                $comment->get_area()
            );

            $context = \context::instance_by_id($context_id);
            $ec->set_relevant_context($context);
        }

        if ($comment->is_reply()) {
            throw new \coding_exception("Cannot delete a reply via comment api");
        }

        return comment_helper::soft_delete($comment->get_id());
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
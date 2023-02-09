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
namespace totara_comment\webapi\resolver\mutation;

use core\json_editor\helper\document_helper;
use core\webapi\execution_context;
use core\webapi\middleware\clean_content_format;
use core\webapi\middleware\clean_editor_content;
use core\webapi\middleware\require_login;
use core\webapi\mutation_resolver;
use totara_comment\comment;
use totara_comment\comment_helper;
use totara_comment\exception\comment_exception;
use totara_comment\resolver_factory;

/**
 * Update the comment's content
 */
class update_comment extends mutation_resolver {
    /**
     * @param array $args
     * @param execution_context $ec
     *
     * @return comment
     */
    public static function resolve(array $args, execution_context $ec): comment {
        $comment_id = $args['id'];
        $old_comment = comment::from_id($comment_id);

        if (!$ec->has_relevant_context()) {
            $resolver = resolver_factory::create_resolver($old_comment->get_component());
            $context_id = $resolver->get_context_id(
                $old_comment->get_instanceid(),
                $old_comment->get_area()
            );

            $context = \context::instance_by_id($context_id);
            $ec->set_relevant_context($context);
        }


        // Fallback to the current format of the comment.
        $format = $old_comment->get_format();
        if (isset($args['format'])) {
            $format = (int) $args['format'];
        }

        $draft_id = null;
        if (isset($args['draft_id'])) {
            $draft_id = (int) $args['draft_id'];
        }

        $content = $args['content'];
        if ((FORMAT_JSON_EDITOR == $format && document_helper::is_document_empty($content)) || empty($content)) {
            throw comment_exception::on_update('Comment content is empty');
        }

        return comment_helper::update_content(
            $comment_id,
            $content,
            $draft_id,
            $format
        );
    }

    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            new require_login(),
            new clean_editor_content('content', 'format'),
            new clean_content_format('format', null, [FORMAT_JSON_EDITOR])
        ];
    }
}
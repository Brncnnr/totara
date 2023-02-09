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

use core\json_editor\helper\document_helper;
use core\webapi\execution_context;
use core\webapi\middleware\clean_content_format;
use core\webapi\middleware\clean_editor_content;
use core\webapi\middleware\require_login;
use core\webapi\mutation_resolver;
use totara_comment\comment;
use totara_comment\comment_helper;
use totara_comment\exception\comment_exception ;

/**
 * Resolver for mutation create reply.
 */
class create_reply extends mutation_resolver {
    /**
     * @param array             $args
     * @param execution_context $ec
     *
     * @return comment
     */
    public static function resolve(array $args, execution_context $ec): comment {
        global $USER;
        if (!$ec->has_relevant_context()) {
            $ec->set_relevant_context(\context_user::instance($USER->id));
        }

        $commentid = $args['commentid'];

        $content = $args['content'];
        $format = FORMAT_JSON_EDITOR;

        if (isset($args['format'])) {
            $format = $args['format'];
        }

        if (FORMAT_JSON_EDITOR == $format && document_helper::is_document_empty($content) || empty($content)) {
            throw comment_exception::on_create("Reply content is empty");
        }

        $draft_id = null;
        if (isset($args['draft_id'])) {
            $draft_id = (int) $args['draft_id'];
        }

        return comment_helper::create_reply(
            $commentid,
            $content,
            $draft_id,
            $format,
            $USER->id
        );
    }

    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            new require_login(),
            new clean_editor_content('content', 'format'),
            new clean_content_format('format', FORMAT_JSON_EDITOR, [FORMAT_JSON_EDITOR])
        ];
    }
}
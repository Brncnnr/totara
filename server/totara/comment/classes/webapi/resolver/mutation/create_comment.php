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
use core\webapi\middleware\require_login;
use core\webapi\mutation_resolver;
use totara_comment\comment;
use totara_comment\comment_helper;
use core\webapi\middleware\clean_editor_content;
use totara_comment\exception\comment_exception;
use totara_comment\resolver_factory;

class create_comment extends mutation_resolver {
    /**
     * @param array             $args
     * @param execution_context $ec
     *
     * @return comment
     */
    public static function resolve(array $args, execution_context $ec): comment {
        global $USER;

        $component = $args['component'];
        $area = $args['area'];
        $content = $args['content'];
        $instanceid = $args['instanceid'];

        if (!$ec->has_relevant_context()) {
            $resolver = resolver_factory::create_resolver($component);
            $context_id = $resolver->get_context_id($instanceid, $area);

            $context = \context::instance_by_id($context_id);
            $ec->set_relevant_context($context);
        }

        $format = FORMAT_JSON_EDITOR;
        if (isset($args['format'])) {
            $format = $args['format'];
        }

        $draft_id = null;
        if (isset($args['draft_id'])) {
            $draft_id = (int) $args['draft_id'];
        }

        if (FORMAT_JSON_EDITOR == $format && document_helper::is_document_empty($content)) {
            throw comment_exception::on_create("Comment content is empty");
        }

        return comment_helper::create_comment(
            $component,
            $area,
            $instanceid,
            $content,
            $format,
            $draft_id,
            $USER->id
        );
    }

    /**
     * @return array
     */
    public static function get_middleware(): array {
        return [
            new require_login(),
            new clean_editor_content('content', 'format'),

            // For now, we are only supporting FORMAT_JSON_EDITOR via graphql mutation
            new clean_content_format('format', FORMAT_JSON_EDITOR, [FORMAT_JSON_EDITOR])
        ];
    }
}
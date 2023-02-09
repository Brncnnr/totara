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
 * @package container_workspace
 */
namespace container_workspace\webapi\resolver\mutation;

use container_workspace\discussion\discussion_helper;
use container_workspace\exception\discussion_exception;
use core\json_editor\helper\document_helper;
use core\webapi\execution_context;
use core\webapi\middleware\clean_content_format;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_login;
use core\webapi\mutation_resolver;
use container_workspace\discussion\discussion;
use core\webapi\middleware\clean_editor_content;

/**
 * Resolver for updating discussion.
 */
class update_discussion extends mutation_resolver {
    /**
     * @param array $args
     * @param execution_context $ec
     *
     * @return discussion
     */
    public static function resolve(array $args, execution_context $ec): discussion {
        global $USER, $DB;

        $discussion_id = $args['id'];

        if (!$ec->has_relevant_context()) {
            $workspace_id = $DB->get_field(
                'workspace_discussion',
                'course_id',
                ['id' => $discussion_id],
                MUST_EXIST
            );

            $context = \context_course::instance($workspace_id);
            $ec->set_relevant_context($context);
        }

        // Use the current content_format of the existing record.
        $repository = discussion::get_entity_repository();
        $content_format = $repository->get_content_format_of_discussion($discussion_id);

        if (isset($args['content_format'])) {
            $content_format = $args['content_format'];
        }

        $draft_id = null;
        if (isset($args['draft_id'])) {
            $draft_id = (int) $args['draft_id'];
        }

        $content = $args['content'];
        if (FORMAT_JSON_EDITOR == $content_format && document_helper::is_document_empty($content)) {
            throw discussion_exception::on_update("Discussion's content is empty");
        }

        return discussion_helper::update_discussion_content(
            $discussion_id,
            $args['content'],
            $draft_id,
            $content_format,
            $USER->id
        );
    }

    /**
     * @return array
     */
    public static function get_middleware(): array {
        return [
            new require_login(),
            new require_advanced_feature('container_workspace'),
            new clean_editor_content('content', 'content_format'),

            // Using null, here because we would want to fallback to the content format of the current discussion.
            // Note that we are only allow FORMAT_JSON_EDITOR via graphql, as because the front-end side only support
            // weka editor for workspace but not different editor. Lower level API is able to change the format value.
            new clean_content_format('content_format', null, [FORMAT_JSON_EDITOR])
        ];
    }
}